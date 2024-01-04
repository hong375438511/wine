<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/2/9
 * Time: 6:18 PM
 */


namespace addons\unishop\controller;


use addons\unishop\extend\Hashids;
use addons\unishop\extend\Redis;
use addons\unishop\model\Address as AddressModel;
use addons\unishop\model\Area;
use addons\unishop\model\Config;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\Evaluate;
use addons\unishop\model\FlashProduct;
use addons\unishop\model\FlashSale;
use addons\unishop\model\Product;
use think\Db;
use think\Exception;
use think\Hook;
use think\Loader;

/**
 * 秒杀
 */
class Flash extends Base
{
    protected $noNeedLogin = ['index', 'navbar', 'product', 'productDetail'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * @ApiTitle    (首页秒杀信息)
     * @ApiSummary  (首页秒杀信息)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="title", type="string", description="当前秒杀活动标题")
     * @ApiReturnParams  (name="introdution", type="string", description="当前秒杀活动简介")
     * @ApiReturnParams  (name="product.name", type="string", description="商品图片")
     * @ApiReturnParams  (name="product.title", type="string", description="商品标题")
     * @ApiReturnParams  (name="product.sales_price", type="string", description="商品价钱")
     * @ApiReturnParams  (name="product.flash_product_id", type="string", description="商品id")
     * @ApiReturnParams  (name="flash_id", type="string", description="秒杀id")
     * @ApiReturnParams  (name="starttime_hour", type="string", description="开始时间的所在小时")
     * @ApiReturnParams  (name="current", type="bool", description="当前展示的秒杀活动是否正在进行中")
     * @ApiReturnParams  (name="countdown", type="json", description="下一场秒杀活动的时间等信息")
     *
     */
    public function index()
    {
        $flashSaleModel = new FlashSale();
        $hour = strtotime(date('Y-m-d H:00:00'));
        $flash = $flashSaleModel
            ->where('endtime', '>=', $hour)
            ->where([
                'switch' => FlashSale::SWITCH_YES,
                'status' => FlashSale::STATUS_NO,
            ])
            ->with([
                'product' => function ($query) {
                    //$query->with('product')->where(['switch' => FlashProduct::SWITCH_ON]);
                    $query->alias('fp')->join('unishop_product p', 'fp.product_id = p.id')
                        ->field('fp.id,fp.flash_id,fp.product_id,p.image,p.title,p.sales_price')
                        ->where([
                            'fp.switch' => FlashProduct::SWITCH_ON,
                            'p.deletetime' => NULL
                        ]);
                }
            ])
            ->order('starttime ASC')
            ->find();


        if ($flash) {
            $flash = $flash->toArray();
            foreach ($flash['product'] as &$product) {
                $product['image'] = Config::getImagesFullUrl($product['image']);
            }

            // 寻找下一场的倒计时
            $nextFlash = $flashSaleModel
                ->where('starttime', '>', $hour)
                ->where([
                    'switch' => FlashSale::SWITCH_YES,
                    'status' => FlashSale::STATUS_NO,
                ])
                ->order("starttime ASC")
                ->cache(10)
                ->find();
            if ($nextFlash) {
                $flash['starttime'] = $nextFlash['starttime'];
                $flash['countdown'] = FlashSale::countdown($flash['starttime']);
            }
        }

        $this->success('', $flash);
    }

    /**
     * @ApiTitle    (获取秒杀时间段)
     * @ApiSummary  (获取秒杀时间段)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="title", type="string", description="当前秒杀活动标题")
     * @ApiReturnParams  (name="introdution", type="string", description="当前秒杀活动简介")、
     * @ApiReturnParams  (name="flash_id", type="string", description="秒杀id")
     * @ApiReturnParams  (name="starttime_hour", type="string", description="开始时间的所在小时")
     * @ApiReturnParams  (name="current", type="bool", description="是否进行中")
     *
     */
    public function navbar()
    {
        $flashSaleModel = new FlashSale();
        $flash = $flashSaleModel
            ->where('endtime', '>', time())
            ->where([
                'switch' => FlashSale::SWITCH_YES,
                'status' => FlashSale::STATUS_NO
            ])
            ->field('id,starttime,title,introdution,endtime')
            ->order('starttime ASC')
            ->cache(2)
            ->select();

        $this->success('', $flash);
    }

    /**
     * @ApiTitle    (获取秒杀的产品列表)
     * @ApiSummary  (获取秒杀的产品列表)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="flash_id", type=string, required=true, description="秒杀活动id")
     * @ApiParams   (name=page, type=integer, required=true, description="第几页")
     * @ApiParams   (name=pagesize, type=integer, required=true, description="每页展示数量")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="number", type="integer", description="秒杀数量")
     * @ApiReturnParams  (name="introduction", type="string", description="秒杀简介")
     * @ApiReturnParams  (name="sold", type="integer", description="已售数量")
     * @ApiReturnParams  (name="product.title", type="integer", description="商品名称")
     * @ApiReturnParams  (name="product.sales_price", type="integer", description="销售价钱")
     * @ApiReturnParams  (name="product.market_price", type="integer", description="市场价钱")
     * @ApiReturnParams  (name="product.image", type="integer", description="商品图片")
     * @ApiReturnParams  (name="product.product_id", type="integer", description="商品id")
     * @ApiReturnParams  (name="flash_product_id", type="integer", description="秒杀商品id")
     *
     */
    public function product()
    {
        $flash_id = $this->request->post('flash_id', 0);
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 15);

        $flash_id = Hashids::decodeHex($flash_id);
        $productModel = new FlashProduct();
        $products = $productModel
            ->with('product')
            ->where(['flash_id' => $flash_id, 'switch' => FlashProduct::SWITCH_ON])
            ->limit(($page - 1) * $pagesize, $pagesize)
            ->cache(2)
            ->select();

        foreach ($products as &$product) {
            $product['sold'] = $product['sold'] > $product['number'] ? $product['number'] : $product['sold'];
        }

        $this->success('', $products);
    }


    /**
     * @ApiTitle    (获取秒杀产品)
     * @ApiSummary  (获取秒杀产品数据)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="flash_id", type=string, required=true, description="秒杀活动id")
     * @ApiParams   (name="id", type=string, required=true, description="商品id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="category_id", type="integer", description="分类id")
     * @ApiReturnParams  (name="title", type="string", description="商品名称")
     * @ApiReturnParams  (name="image", type="string", description="商品图片")
     * @ApiReturnParams  (name="images_text", type="array", description="商品图片组")
     * @ApiReturnParams  (name="desc", type="string", description="商品详情")
     * @ApiReturnParams  (name="sales", type="integer", description="销量")
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
     * @ApiReturnParams  (name="flash.starttime", type="string", description="秒杀开始时间")
     * @ApiReturnParams  (name="flash.endtime", type="string", description="秒杀结束时间")
     * @ApiReturnParams  (name="flash.sold", type="string", description="秒杀商品已售数量")
     * @ApiReturnParams  (name="flash.number", type="string", description="提供秒杀商品数量")
     * @ApiReturnParams  (name="flash.text", type="string", description="倒计时时态描述")
     * @ApiReturnParams  (name="flash.countdown", type="string", description="倒计时")
     * @ApiReturnParams  (name="flash.countdown.day", type="string", description="天")
     * @ApiReturnParams  (name="flash.countdown.hour", type="string", description="时")
     * @ApiReturnParams  (name="flash.countdown.minute", type="string", description="分")
     * @ApiReturnParams  (name="flash.countdown.second", type="string", description="秒")
     *
     */
    public function productDetail()
    {
        $productId = $this->request->post('id');
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);
        $flashId = $this->request->post('flash_id');
        $flashId = \addons\unishop\extend\Hashids::decodeHex($flashId);

        try {

            $productModel = new Product();
            $data = $productModel->where(['id' => $productId])->cache(true, 20, 'flashProduct')->find();
            if (!$data) {
                $this->error(__('Product not exist'));
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
                ->cache(true, 20, 'flashEvaluate')->find();

            $redis = new Redis();
            $flash['starttime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            $flash['endtime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');
            $flash['sold'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $flash['number'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $flash['sold'] = $flash['sold'] > $flash['number'] ? $flash['number'] : $flash['sold'];
            $flash['text'] = $flash['starttime'] > time() ? '距开始:' : '距结束:';

            // 秒杀类型不加载优惠券、促销活动、是否已收藏、评价等等，会影响返回速度
            $targetTime = $flash['starttime'] > time() ? $flash['starttime'] : $flash['endtime'];
            $flash['countdown'] = FlashSale::countdown($targetTime);
            $data['coupon'] = [];

            // 秒杀数据
            $data['flash'] = $flash;

            $data->append(['images_text', 'spec_list', 'spec_table_list'])->toArray();

            // 购物车数量
            $data['cart_num'] = (new \addons\unishop\model\Cart)->where(['user_id' => $this->auth->id])->count();

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('', $data);
    }


    /**
     * @ApiTitle    (创建订单)
     * @ApiSummary  (创建订单)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="flash_id", type=string, required=true, description="秒杀活动id")
     * @ApiParams   (name="id", type=string, required=true, description="商品id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="product.title", type="string", description="商品名称")
     * @ApiReturnParams  (name="product.image", type="string", description="商品图片")
     * @ApiReturnParams  (name="product.sales", type="integer", description="销量")
     * @ApiReturnParams  (name="product.sales_price", type="string", description="销售价钱")
     * @ApiReturnParams  (name="product.market_price", type="string", description="市场价钱")
     * @ApiReturnParams  (name="product.id", type="string", description="商品id")
     * @ApiReturnParams  (name="product.stock", type="integer", description="库存")
     * @ApiReturnParams  (name="product.spec", type="integer", description="选中的规格")
     * @ApiReturnParams  (name="product.number", type="integer", description="购买数量")
     *
     * @ApiReturnParams  (name="address.id", type="integer", description="地址id")
     * @ApiReturnParams  (name="address.name", type="string", description="收货人名称")
     * @ApiReturnParams  (name="address.mobile", type="string", description="收货人电话")
     * @ApiReturnParams  (name="address.address", type="string", description="收货人地址")
     * @ApiReturnParams  (name="address.province_id", type="integer", description="省份id")
     * @ApiReturnParams  (name="address.city_id", type="integer", description="城市id")
     * @ApiReturnParams  (name="address.area_id", type="integer", description="地区id")
     * @ApiReturnParams  (name="address.is_default", type="integer", description="是否默认")
     * @ApiReturnParams  (name="address.province.name", type="integer", description="省份")
     * @ApiReturnParams  (name="address.city.name", type="integer", description="城市")
     * @ApiReturnParams  (name="address.area.name", type="integer", description="地区")
     *
     * @ApiReturnParams  (name="delivery.id", type="integer", description="货运id")
     * @ApiReturnParams  (name="delivery.name", type="string", description="货运名称")
     * @ApiReturnParams  (name="delivery.type", type="string", description="收费类型")
     * @ApiReturnParams  (name="delivery.min", type="integer", description="至少购买量")
     * @ApiReturnParams  (name="delivery.first", type="integer", description="首重数量")
     * @ApiReturnParams  (name="delivery.first_fee", type="string", description="首重价钱")
     * @ApiReturnParams  (name="delivery.additional", type="integer", description="需重数量")
     * @ApiReturnParams  (name="delivery.additional_fee", type="string", description="需重价钱")

     * @ApiReturnParams  (name="flash.sold", type="integer", description="已秒数量")
     * @ApiReturnParams  (name="flash.sold", type="integer", description="还剩数量")

     */
    public function createOrder()
    {
        $productId = $this->request->post('id', 0);
        $flashId = $this->request->post('flash_id', 0);
        $flashId = \addons\unishop\extend\Hashids::decodeHex($flashId);
        $productId = \addons\unishop\extend\Hashids::decodeHex($productId);

        try {

            $redis = new Redis();
            $sold = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $number = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $switch = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'switch');
            $starttime = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            $endtime = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');

            //判断是否开始或结束
            if (time() < $starttime) {
                $this->error(__('Activity not started'));
            }
            if ($endtime < time()) {
                $this->error(__('Activity ended'));
            }

            // 截流
            if ($sold >= $number) {
                $this->error(__('Item sold out'));
            }
            if ($switch == FlashSale::SWITCH_NO || $switch == false) {
                $this->error(__('Item is off the shelves'));
            }

            $product = (new Product)->where(['id' => $productId, 'deletetime' => null])->find();
            /** 产品基础数据 **/
            $spec = $this->request->post('spec', '');
            $productData[0] = $product->getDataOnCreateOrder($spec);

            if (!$productData) {
                $this->error(__('Product not exist'));
            }
            $productData[0]['image'] = Config::getImagesFullUrl($productData[0]['image']);
            $productData[0]['sales_price'] = round($productData[0]['sales_price'], 2);
            $productData[0]['market_price'] = round($productData[0]['market_price'], 2);

            /** 默认地址 **/
            $address = AddressModel::get(['user_id' => $this->auth->id, 'is_default' => AddressModel::IS_DEFAULT_YES]);
            if ($address) {
                $area = (new Area)->whereIn('id', [$address->province_id, $address->city_id, $address->area_id])->column('name', 'id');
                $address = $address->toArray();
                $address['province']['name'] = $area[$address['province_id']];
                $address['city']['name'] = $area[$address['city_id']];
                $address['area']['name'] = $area[$address['area_id']];
            }

            /** 运费数据 **/
            $cityId = $address && isset($address['city_id']) ? $address['city_id'] : 0;
            $delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);
            $msg = '';
//            if ($delivery['status'] == 0) {
//                $msg = __('Your receiving address is not within the scope of delivery');
//            }

            $redis = new Redis();
            //$flash['starttime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'starttime');
            //$flash['endtime'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'endtime');
            $flash['sold'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'sold');
            $flash['number'] = $redis->handler->hGet('flash_sale_' . $flashId . '_' . $productId, 'number');
            $flash['sold'] = $flash['sold'] > $flash['number'] ? $flash['number'] : $flash['sold'];
            //$flash['text'] = $flash['starttime'] > time() ? '距开始:' : '距结束:';

            // 秒杀类型不加载优惠券、促销活动、是否已收藏、评价等等，会影响返回速度
            //$targetTime = $flash['starttime'] > time() ? $flash['starttime'] : $flash['endtime'];
            //$flash['countdown'] = FlashSale::countdown($targetTime);


        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
        $this->success($msg, [
            'product' => $productData,
            'address' => $address,
            'delivery' => $delivery['list'],
            'flash' => $flash
        ]);

    }


    /**
     * @ApiTitle    (提交订单)
     * @ApiSummary  (提交订单)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="flash_id", type=string, required=true, description="秒杀活动id")
     * @ApiParams   (name="product_id", type=string, required=true, description="商品id")
     * @ApiParams   (name="number", type=string, required=true, description="商品数量")
     * @ApiParams   (name="city_id", type=integer, required=true, description="城市id")
     * @ApiParams   (name="address_id", type=string, required=true, description="收货地址id")
     * @ApiParams   (name="delivery_id", type=integer, required=true, description="运费模板id")
     * @ApiParams   (name="spec", type=string, required=true, description="规格")
     * @ApiParams   (name="remark", type=string, required=true, description="备注")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="order_id", type="string", description="订单编号")
     * @ApiReturnParams  (name="out_trade_no", type="string", description="商户订单号（支付用）")
     *
     */
    public function submitOrder()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Order');
            if (!$validate->check($data, [], 'submitFlash')) {
                throw new Exception($validate->getError());
            }

            Db::startTrans();

            // 判断创建订单的条件
            if (empty(Hook::get('create_order_before'))) { // 由于自动化测试的时候会注册多个同名行为
                Hook::add('create_order_before', 'addons\\unishop\\behavior\\OrderFlash');
            }
            if (empty(Hook::get('create_order_after'))) {
                Hook::add('create_order_after', 'addons\\unishop\\behavior\\OrderFlash');
            }

            $data['flash_id'] = Hashids::decodeHex($data['flash_id']);
            $data['product_id'] = Hashids::decodeHex($data['product_id']);
            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->createOrder($this->auth->id, $data);

            Db::commit();

            $this->success('', $result);

        } catch (Exception $e) {

            Db::rollback();
            $this->error($e->getMessage(), false);
        }
    }

}
