<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/10/27
 * Time: 5:37 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Hashids;
use addons\unishop\model\Config;
use addons\unishop\model\Product;
use addons\unishop\model\Cart as CartModel;
use think\Exception;

/**
 * 购物车
 */
class Cart extends Base
{
    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['number_change', 'choose_change'];

    /**
     * @ApiTitle    (列表)
     * @ApiSummary  (购物车列表)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="market_price", type="string", description="市场价")
     * @ApiReturnParams  (name="sales_price", type="string", description="销售价")
     * @ApiReturnParams  (name="stock", type="integer", description="库存")
     * @ApiReturnParams  (name="sales", type="integer", description="销量")
     * @ApiReturnParams  (name="image", type="string", description="图片")
     * @ApiReturnParams  (name="title", type="string", description="商品名称")
     * @ApiReturnParams  (name="choose", type="integer", description="是否选中")
     * @ApiReturnParams  (name="isset", type="integer", description="是否生效")
     * @ApiReturnParams  (name="cart_id", type="integer", description="购物车id")
     * @ApiReturnParams  (name="spec", type="string", description="选中的规格")
     * @ApiReturnParams  (name="number", type="integer", description="数量")
     * @ApiReturnParams  (name="oldPrice", type="string", description="旧价格")
     * @ApiReturnParams  (name="nowPrice", type="string", description="现价格")
     * @ApiReturnParams  (name="product_id", type="string", description="商品id")
     *
     */
    public function index()
    {
        $carts = (new CartModel)->where(['user_id' => $this->auth->id])
            ->with([
                'product' => function ($query) {
                    $query->field(['id', 'image', 'title', 'specTableList','sales','market_price','sales_price','stock','use_spec', 'switch']);
                }
            ])
            ->order(['createtime' => 'desc'])
            ->select();
        if (!$carts) {
            $this->success('', []);
        }

        $data = [];
        $productExtend = new \addons\unishop\extend\Product;

        foreach ($carts as $item) {
            $oldProduct = json_decode($item['snapshot'], true);
            $oldData = $productExtend->getBaseData($oldProduct, $item['spec'] ?? '');

            if (empty($item['product'])) {
                $tempData = $oldData;
                $tempData['isset'] = false; // 失效
                $tempData['title'] = $oldProduct['title'];
                $tempData['choose'] = 0;
            } else {
                $productData = $item['product']->getData();
                $tempData = $productExtend->getBaseData($productData, $item['spec'] ?? '');
                $tempData['title'] = $item['product']['title'];
                $tempData['choose'] = $item['choose']; //是否选中

                $tempData['isset'] = true;
                if ($productData['switch'] == Product::SWITCH_OFF) {
                    $tempData['isset'] = false; // 失效
                    $tempData['choose'] = 0;
                }
            }

            $tempData['cart_id'] = $item['id'];
            $tempData['spec'] = $item['spec'];
            $tempData['number'] = $item['number'];

            $tempData['image'] = Config::getImagesFullUrl($oldData['image']);
            $tempData['oldPrice'] = round($oldData['sales_price'], 2);
            $tempData['nowPrice'] = round($tempData['sales_price'], 2);

            $tempData['product_id'] = Hashids::encodeHex($item['product_id']);

            $data[] = $tempData;
        }

        $this->success('', $data);
    }


    /**
     * @ApiTitle    (添加)
     * @ApiSummary  (添加商品到购物车)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="string", description="商品id")
     * @ApiReturn   ({"code":1,"msg":"添加成功","data":1})
     */
    public function add()
    {
        $id = $this->request->post('id', 0);

        $id = \addons\unishop\extend\Hashids::decodeHex($id);

        $product = (new Product)->where(['id' => $id, 'switch' => Product::SWITCH_ON])->find();
        if (!$product) {
            $this->error('产品不存在或已下架');
        }

        $spec = $this->request->post('spec', '');
        $productBase = (new \addons\unishop\extend\Product())->getBaseData($product->getData(), $spec);
        if (!$productBase['stock'] || $productBase['stock'] <= 0) {
            $this->error('库存不足');
        }

        $user_id = $this->auth->id;
        $cartModel = new \addons\unishop\model\Cart();
        $cartModel->where(['user_id' => $user_id, 'product_id' => $id]);
        $spec && $cartModel->where('spec', $spec);
        $oldCart = $cartModel->find();

        if ($oldCart) {
            $this->error('商品已存在购物车');
//            $oldCart->number++;
//            $result = $oldCart->save();
        } else {
            $cartModel->user_id = $user_id;
            $cartModel->product_id = $id;
            $spec && $cartModel->spec = $spec;
            $cartModel->number = 1;
            $cartModel->snapshot = json_encode($product->getData(), true);
            $result = $cartModel->save();
        }

        if ($result) {
            $this->success('添加成功', 1);
        } else {
            $this->error('添加失败');
        }
    }

    /**
     * @ApiTitle    (删除)
     * @ApiSummary  (删除购物车的商品支持多个删除用','号隔开购物车id)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="string", description="购物车id，多个的话用','号隔开")
     * @ApiReturn   ({"code":1,"msg":"删除成功","data":1})
     */
    public function delete()
    {
        $id = $this->request->post('id', '0');
        $userId = $this->auth->id;
        $result = CartModel::destroy(function ($query) use ($id, $userId) {
            $query->whereIn('id', $id)->where(['user_id' => $userId]);
        });
        if ($result) {
            $this->success('删除成功', 1);
        } else {
            $this->error('删除失败', 0);
        }
    }

    /**
     * @ApiTitle    (修改购物车数量)
     * @ApiSummary  (修改购物车数量)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams  (name="id", type=integer, required=true, description="购物车id")
     * @ApiParams  (name="number", type=integer, required=true, description="数量")
     * @ApiReturn   ({"code":1,"msg":"更改成功","data":数量})
     */
    public function number_change()
    {
        $cart_id = $this->request->post('id', 0);
        $number = $this->request->post('number', 1);
        $cart = CartModel::get(['id' => $cart_id, 'user_id' => $this->auth->id]);
        if (empty($cart)) {
            $this->error('此商品不存在购物车');
        }
        $cart->number = $number;
        $result = $cart->save();
        if ($result) {
            $this->success('更改成功', $number);
        } else {
            $this->error('更改失败', $number);
        }
    }

    /**
     * @ApiTitle    (修改购物车选中状态)
     * @ApiSummary  (修改购物车选中状态)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams  (name="trueArr", type=string, required=true, description="选中的购物车id，多个的话用','号隔开")
     * @ApiParams  (name="falseArr", type=string, required=true, description="不选的购物车id，多个的话用','号隔开")
     * @ApiReturn   ({"code":1,"msg":"","data":数量})
     */
    public function choose_change()
    {
        $trueArr = $this->request->post('trueArr', false);
        $falseArr = $this->request->post('falseArr', false);
        $user_id = $this->auth->id;
        try {
            $cart = new CartModel();
            if ($trueArr) {
                $cart->save(['choose' => CartModel::CHOOSE_ON], function ($query) use ($user_id, $trueArr) {
                    $query->where('user_id', $user_id)->where('id', 'IN', $trueArr);
                });
            }
            if ($falseArr) {
                $cart->save(['choose' => CartModel::CHOOSE_OFF], function ($query) use ($user_id, $falseArr) {
                    $query->where('user_id', $user_id)->where('id', 'IN', $falseArr);
                });
            }
        } catch (Exception $e) {
            $this->error('更新失败', 0);
        }
        $this->success('', 1);
    }

}
