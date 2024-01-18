<?php

namespace addons\unishop\controller;

use addons\unishop\extend\Hashids;
use addons\unishop\model\Config;
use addons\unishop\model\Evaluate;
use addons\unishop\model\Favorite;
use addons\unishop\model\Product as productModel;
use addons\unishop\model\Coupon;
use think\Exception;

/**
 * 商品
 */
class Product extends Base
{
    protected $noNeedLogin = ['detail', 'lists'];

    /**
     * @ApiTitle    (产品详情)
     * @ApiSummary  (产品详情)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="string", required=true, description="商品id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="category_id", type="integer", description="分类id")
     * @ApiReturnParams  (name="title", type="string", description="商品名称")
     * @ApiReturnParams  (name="image", type="string", description="商品图片")
     * @ApiReturnParams  (name="images_text", type="array", description="商品图片组")
     * @ApiReturnParams  (name="desc", type="string", description="商品详情")
     * @ApiReturnParams  (name="sales", type="integer", description="销量")
     * @ApiReturnParams  (name="score", type="integer", description="积分")
     * @ApiReturnParams  (name="sales_price", type="string", description="销售价钱")
     * @ApiReturnParams  (name="market_price", type="string", description="市场价钱")
     * @ApiReturnParams  (name="product_id", type="string", description="商品id")
     * @ApiReturnParams  (name="stock", type="integer", description="库存")
     * @ApiReturnParams  (name="look", type="integer", description="观看量")
     * @ApiReturnParams  (name="use_spec", type="integer", description="是否使用规格")
     * @ApiReturnParams  (name="server", type="string", description="支持的服务")
     * @ApiReturnParams  (name="favorite", type="integer", description="是否已收藏")
     * @ApiReturnParams  (name="evaluate_data", type="json", description="{count:'评论数据',avg:'好评平均值'}")
     * @ApiReturnParams  (name="coupon", type="array", description="可用优惠券")
     * @ApiReturnParams  (name="cart_num", type="integer", description="购物车数量")
     * @ApiReturnParams  (name="spec_list", type="array", description="规格键值数据")
     * @ApiReturnParams  (name="spec_table_list", type="array", description="规格值数据")
     *
     */
    public function detail()
    {
        $productId = $this->request->post('id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        try {

            $productModel = new productModel();
            $data = $productModel->where(['id' => $productId])->cache(10)->find();
            if (!$data) {
                $this->error(__('Goods not exist'));
            }
            if ($data['switch'] == productModel::SWITCH_OFF) {
                $this->error(__('Goods are off the shelves'));
            }

            // 真实浏览量加一
            $data->real_look++;
            $data->look++;
            $data->save();

            //服务
            $server = explode(',', $data->server);
            $configServer = json_decode(Config::getByName('server')['value'],true);
            $serverValue = [];
            foreach ($server as $k => $v) {
                if (isset($configServer[$v])) {
                    $serverValue[] = $configServer[$v];
                }
            }
            $data->server = count($serverValue) ? implode(' · ', $serverValue) : '';

            // 默认没有收藏
            $data->favorite = false;

            // 评价
            $data['evaluate_data'] = (new Evaluate)->where(['product_id' => $productId])
                ->field('COUNT(*) as count, IFNULL(CEIL(AVG(rate)/5*100),0) as avg')
                ->cache(10)->find();

            //优惠券
            $data->coupon = (new Coupon)->where('endtime', '>', time())
                ->where(['switch' => Coupon::SWITCH_ON])->cache(10)->order('weigh DESC')->select();

            // 是否已收藏
            if ($this->auth->id) {
                $data->favorite = (new Favorite)->where(['user_id' => $this->auth->id, 'product_id' => $productId])->count();
            }

            // 购物车数量
            $data->cart_num = (new \addons\unishop\model\Cart)->where(['user_id' => $this->auth->id])->count();

            // 评价信息
            $evaluate = (new Evaluate)->alias('e')
                ->join('user u', 'e.user_id = u.id')
                ->where(['e.product_id' => $productId, 'toptime' => ['>', Evaluate::TOP_OFF]])
                ->field('u.username,u.avatar,e.*')
                ->order(['toptime' => 'desc', 'createtime' => 'desc'])->select();
            if ($evaluate) {
                $data->evaluate_list = collection($evaluate)->append(['createtime_text'])->toArray();
            }
            $data = $data->append(['images_text', 'spec_list', 'spec_table_list'])->toArray();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('', $data);
    }

    /**
     * @ApiTitle    (产品列表)
     * @ApiSummary  (产品列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="sid", type="integer", required=false, description="二级分类id")
     * @ApiParams   (name="fid", type="integer", required=true, description="一级分类id")
     * @ApiParams   (name="page", type="integer", required=true, description="页面")
     * @ApiParams   (name="pagesize", type="integer", required=true, description="每页数量")
     * @ApiParams   (name="by", type="string", required=true, description="排序字段")
     * @ApiParams   (name="desc", type="string", required=true, description="排序desc,asc")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     * @ApiReturnParams  (name="title", type="string", description="商品名称")
     * @ApiReturnParams  (name="image", type="string", description="商品图片")
     * @ApiReturnParams  (name="sales", type="integer", description="销量")
     * @ApiReturnParams  (name="score", type="integer", description="积分")
     * @ApiReturnParams  (name="sales_price", type="string", description="销售价钱")
     * @ApiReturnParams  (name="product_id", type="string", description="商品id")
     */
    public function lists()
    {
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 20);
        $by = $this->request->post('by', 'weigh');
        $desc = $this->request->post('desc', 'desc');

        $sid = $this->request->post('sid'); // 二级分类Id
        $fid = $this->request->post('fid'); // 一级分类Id

        $productModel = new productModel();

        if ($fid && !$sid) {
            $categoryModel = new \addons\unishop\model\Category();
            $sArr = $categoryModel->where('pid', $fid)->field('id')->select();
            $sArr = array_column($sArr, 'id');
            array_push($sArr, $fid);
            $productModel->where('category_id', 'in', $sArr);
        } else {
            $sid && $productModel->where(['category_id' => $sid]);
        }

        $result = $productModel
            ->where(['switch' => productModel::SWITCH_ON])
            ->page($page, $pagesize)
            ->order($by, $desc)
            //->field('id,title,image,score,sales_price,sales,real_sales')
            ->field('id,title,image,score,stock')
            ->select();

        if ($result) {
            $result = collection($result)->toArray();
        } else {
            $this->success('没有更多数据', []);
        }
        $this->success('', $result);
    }

    /**
     * @ApiTitle (收藏/取消)
     * @ApiSummary (收藏/取消)
     * @ApiMethod (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiParams   (name="id", type="string", required=true, description="商品id")
     * @ApiReturn   ({"code":1,"msg":"","data":true})
     */
    public function favorite()
    {
        $id = $this->request->post('id', 0);
        $id = \addons\unishop\extend\Hashids::decodeHex($id);

        $user_id = $this->auth->id;
        $favoriteModel = Favorite::get(function ($query) use ($id, $user_id) {
            $query->where(['user_id' => $user_id, 'product_id' => $id]);
        });
        if ($favoriteModel) {
            Favorite::destroy($favoriteModel->id);
        } else {
            $product = productModel::withTrashed()->where(['id' => $id, 'switch' => productModel::SWITCH_ON])->find();
            if (!$product) {
                $this->error('参数错误');
            }
            $favoriteModel = new Favorite();
            $favoriteModel->user_id = $user_id;
            $favoriteModel->product_id = $id;
            $product = $product->getData();
            $data['image'] = $product['image'];
            $data['market_price'] = $product['market_price'];
            $data['product_id'] = Hashids::encodeHex($product['id']);
            $data['sales_price'] = $product['sales_price'];
            $data['title'] = $product['title'];
            $favoriteModel->snapshot = json_encode($data);
            $favoriteModel->save();
        }

        $this->success('', true);
    }


    /**
     * @ApiTitle    (收藏列表)
     * @ApiSummary  (收藏列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="page", type="integer", required=true, description="页面")
     * @ApiParams   (name="pagesize", type="integer", required=true, description="每页数量")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="id", type="integer", description="收藏id")
     * @ApiReturnParams  (name="user_id", type="integer", description="用户id")
     * @ApiReturnParams  (name="product.image", type="string", description="商品图片")
     * @ApiReturnParams  (name="product.title", type="string", description="商品名称")
     * @ApiReturnParams  (name="product.sales_price", type="string", description="商品销售价钱")
     * @ApiReturnParams  (name="product.market_price", type="string", description="商品市场价钱")
     * @ApiReturnParams  (name="product.product_id", type="string", description="商品id")
     * @ApiReturnParams  (name="status", type="integer", description="是否还有效")
     *
     */
    public function favoriteList()
    {
        $page = $this->request->post('page', 1);
        $pageSize = $this->request->post('pagesize', 20);

        $list = (new Favorite)->where(['user_id' => $this->auth->id])->with(['product'])->page($page, $pageSize)->select();

        $list = collection($list)->toArray();
        foreach ($list as &$item) {
            if (!empty($item['product'])) {
                $item['status'] = 1;
            } else {
                $item['status'] = 0;
                $item['product'] = json_decode($item['snapshot'],true);
                $image = $item['product']['image'];
                $item['product']['image'] = Config::getImagesFullUrl($image);
            }
            unset($item['snapshot']);
        }

        $this->success('', $list);
    }

    /**
     * @ApiTitle    (商品评论)
     * @ApiSummary  (商品评论)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="product_id", type="string", required=true, description="商品id")
     * @ApiParams   (name="page", type="integer", required=true, description="页面")
     * @ApiParams   (name="pagesize", type="integer", required=true, description="每页数量")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="id", type="integer", description="评论id")
     * @ApiReturnParams  (name="username", type="string", description="评论人名称")
     * @ApiReturnParams  (name="avatar", type="string", description="评论人头像")
     * @ApiReturnParams  (name="product_id", type="string", description="商品id")
     * @ApiReturnParams  (name="comment", type="string", description="评论内容")
     * @ApiReturnParams  (name="rate", type="integer", description="星星")
     * @ApiReturnParams  (name="order_id", type="string", description="订单id")
     * @ApiReturnParams  (name="spec", type="string", description="评论的规格")
     * @ApiReturnParams  (name="anonymous", type="integer", description="是否匿名")
     * @ApiReturnParams  (name="toptime", type="integer", description="是否置顶")
     * @ApiReturnParams  (name="createtime_text", type="string", description="评论时间")
     *
     */
    public function evaluate()
    {
        $page = $this->request->post('page', 1);
        $pageSize = $this->request->post('pagesize', 20);
        $productId = $this->request->post('product_id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        // 评价信息
        $evaluate = (new Evaluate)->alias('e')
            ->join('user u', 'e.user_id = u.id')
            ->where(['e.product_id' => $productId])
            ->field('u.username,u.avatar,e.*')
            ->order(['toptime' => 'desc', 'createtime' => 'desc'])
            ->page($page, $pageSize)
            ->select();
        if ($evaluate) {
            $evaluate = collection($evaluate)->append(['createtime_text'])->toArray();
        }
        $this->success('', $evaluate);
    }
}
