<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/9
 * Time: 10:00 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Ali;
use addons\unishop\extend\Hashids;
use addons\unishop\model\Area;
use addons\unishop\model\Config;
use addons\unishop\model\Evaluate;
use addons\unishop\model\Product;
use app\admin\model\unishop\Coupon as CouponModel;
use addons\unishop\model\DeliveryRule as DeliveryRuleModel;
use addons\unishop\model\OrderRefund;
use app\admin\model\unishop\OrderRefundProduct;
use think\Db;
use think\Exception;
use addons\unishop\model\Address as AddressModel;
use think\Hook;
use think\Loader;

/**
 * 订单
 */
class Order extends Base
{

    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['getorders'];

    protected $noNeedLogin = ['count'];

    /**
     * @ApiTitle    (创建订单)
     * @ApiSummary  (创建订单)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="id", type=string, required=true, description="商品id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="order_id", type="string", description="订单编号")
     * @ApiReturnParams  (name="out_trade_no", type="string", description="商户订单号（支付用）")
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
     * @ApiReturnParams  (name="coupon.id", type="integer", description="优惠券id")
     * @ApiReturnParams  (name="coupon.title", type="string", description="优惠券名称")
     * @ApiReturnParams  (name="coupon.least", type="integer", description="至少购买金额")
     * @ApiReturnParams  (name="coupon.value", type="integer", description="满减金额")
     * @ApiReturnParams  (name="coupon.starttime_text", type="integer", description="开始使用时间")
     * @ApiReturnParams  (name="coupon.endtime_text", type="integer", description="到期使用时间")
     *
     * @ApiReturnParams  (name="delivery.id", type="integer", description="货运id")
     * @ApiReturnParams  (name="delivery.name", type="string", description="货运名称")
     * @ApiReturnParams  (name="delivery.type", type="string", description="收费类型")
     * @ApiReturnParams  (name="delivery.min", type="integer", description="至少购买量")
     * @ApiReturnParams  (name="delivery.first", type="integer", description="首重数量")
     * @ApiReturnParams  (name="delivery.first_fee", type="string", description="首重价钱")
     * @ApiReturnParams  (name="delivery.additional", type="integer", description="需重数量")
     * @ApiReturnParams  (name="delivery.additional_fee", type="string", description="需重价钱")     *
     *
     */
    public function create()
    {
        $productId = $this->request->post('id', 0);
        $number = $this->request->post('number', 0);

        try {
            $user_id = $this->auth->id;

            // 单个商品
            if ($productId) {
                $productId = \addons\unishop\extend\Hashids::decodeHex($productId);
                $product = (new Product)->where(['id' => $productId, 'switch' => Product::SWITCH_ON, 'deletetime' => null])->find();
                /** 产品基础数据 **/
                $spec = $this->request->post('spec', '');
                $productData[0] = $product->getDataOnCreateOrder($spec, $number);
            } else {
                // 多个商品
                $cart = $this->request->post('cart');
                $carts = (new \addons\unishop\model\Cart)
                    ->whereIn('id', $cart)
                    ->with(['product'])
                    ->order(['id' => 'desc'])
                    ->select();
                foreach ($carts as $cart) {
                    if ($cart->product instanceof Product) {
                        $productData[] = $cart->product->getDataOnCreateOrder($cart->spec ? $cart->spec : '', $cart->number);
                    }
                }
            }

            if (empty($productData) || !$productData) {
                $this->error(__('Product not exist'));
            }

            /** 默认地址 **/
            $address = (new AddressModel)->where(['user_id' => $user_id, 'is_default' => AddressModel::IS_DEFAULT_YES])->find();
            if ($address) {
                $area = (new Area)->whereIn('id', [$address->province_id, $address->city_id, $address->area_id])->column('name', 'id');
                $address = $address->toArray();
                $address['province']['name'] = $area[$address['province_id']];
                $address['city']['name'] = $area[$address['city_id']];
                $address['area']['name'] = $area[$address['area_id']];
            }


            /** 可用优惠券 **/
            $coupon = CouponModel::all(function ($query) {
                $time = time();
                $query
                    ->where(['switch' => CouponModel::SWITCH_ON])
                    ->where('starttime', '<', $time)
                    ->where('endtime', '>', $time);
            });
            if ($coupon) {
                $coupon = collection($coupon)->toArray();
            }

            /** 运费数据 **/
            $cityId = $address && isset($address['city_id']) ? $address['city_id'] : 0;
            //$delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);
            $delivery = [];

            foreach ($productData as &$product) {
                $product['image'] = Config::getImagesFullUrl($product['image']);
                unset($product['sales_price'],$product['market_price'],$product['sales']);
                /*$product['sales_price'] = round($product['sales_price'], 2);
                $product['market_price'] = round($product['market_price'], 2);*/
            }

            $this->success('', [
                'product' => $productData,
                'address' => $address,
                'coupon' => $coupon,
                'delivery' => isset($delivery['list']) ? $delivery['list'] : []
            ]);

        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
    }

    /**
     * @ApiTitle    (提交订单)
     * @ApiSummary  (提交订单)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="coupon_id", type=string, required=true, description="优惠券id")
     * @ApiParams   (name="product_id", type=string, required=true, description="商品id，多个用法(product_id,product_id,product_id)")
     * @ApiParams   (name="number", type=string, required=true, description="商品数量，多个用法(number,number,number)")
     * @ApiParams   (name="spec", type=string, required=true, description="规格，多个用法(spec|spec2,spec|spec2,spec|spec2)")
     * @ApiParams   (name="is_self_pickup", type=integer, required=true, description="是否自提")
     * @ApiParams   (name="city_id", type=integer, required=true, description="城市id")
     * @ApiParams   (name="address_id", type=string, required=true, description="收货地址id")
     * @ApiParams   (name="delivery_id", type=integer, required=true, description="运费模板id")
     * @ApiParams   (name="remark", type=string, required=true, description="备注")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="order_id", type="string", description="订单编号")
     * @ApiReturnParams  (name="out_trade_no", type="string", description="商户订单号（支付用）")
     *
     */
    public function submit()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Order');
            if (!$validate->check($data, [], 'submit')) {
                throw new Exception($validate->getError());
            }

            Db::startTrans();

            // 判断创建订单的条件
            if (empty(Hook::get('create_order_before'))) {
                Hook::add('create_order_before', 'addons\\unishop\\behavior\\Order');
            }
            // 减少商品库存，增加"已下单未支付数量"
            if (empty(Hook::get('create_order_after'))) {
                Hook::add('create_order_after', 'addons\\unishop\\behavior\\Order');
            }

            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->createOrder($this->auth->id, $data);

            Db::commit();

        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), false);
        }
        $this->success('', $result);
    }

    /**
     * @ApiTitle    (获取运费模板)
     * @ApiSummary  (获取运费模板)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="city_id", type=integer, required=true, description="城市id")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="id", type="integer", description="模板id")
     * @ApiReturnParams  (name="name", type="string", description="模板名称")
     * @ApiReturnParams  (name="type", type="string", description="模板类型")
     * @ApiReturnParams  (name="min", type="integer", description="至少购买量")
     * @ApiReturnParams  (name="first", type="integer", description="首重数量")
     * @ApiReturnParams  (name="first_fee", type="string", description="首重价钱")
     * @ApiReturnParams  (name="additional", type="integer", description="需重数量")
     * @ApiReturnParams  (name="additional_fee", type="string", description="需重价钱")     *
     *
     */
    public function getDelivery()
    {
        $cityId = $this->request->post('city_id', 0);
        $delivery = (new DeliveryRuleModel())->getDelivetyByArea($cityId);
        $this->success('', $delivery['list']);
    }

    /**
     * @ApiTitle    (获取订单列表)
     * @ApiSummary  (获取订单列表信息)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="type", type=integer, required=true, description="类型:0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后")
     * @ApiParams   (name="page", type=integer, required=true, description="第几页")
     * @ApiParams   (name="pagesize", type=integer, required=true, description="每页数量")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="out_trade_no", type="string", description="商户订单号")
     * @ApiReturnParams  (name="order_price", type="string", description="订单原价钱")
     * @ApiReturnParams  (name="discount_price", type="string", description="优惠多少钱")
     * @ApiReturnParams  (name="delivery_price", type="string", description="运费多少钱")
     * @ApiReturnParams  (name="total_price", type="string", description="订单实价")
     * @ApiReturnParams  (name="pay_type", type="integer", description="支付类型")
     * @ApiReturnParams  (name="id", type="string", description="下单ip")
     * @ApiReturnParams  (name="remark", type="string", description="订单备注")
     * @ApiReturnParams  (name="have_paid", type="integer", description="是否支付")
     * @ApiReturnParams  (name="have_delivered", type="integer", description="是否发货")
     * @ApiReturnParams  (name="have_received", type="integer", description="是否收货")
     * @ApiReturnParams  (name="have_commented", type="integer", description="是否评论")
     * @ApiReturnParams  (name="refund_status", type="integer", description="退款状态")
     * @ApiReturnParams  (name="products[].id", type="string", description="商品id")
     * @ApiReturnParams  (name="products[].title", type="string", description="商品名称")
     * @ApiReturnParams  (name="products[].image", type="string", description="商品图片")
     * @ApiReturnParams  (name="products[].number", type="integer", description="商品数量")
     * @ApiReturnParams  (name="products[].price", type="string", description="商品价钱")
     * @ApiReturnParams  (name="products[].spec", type="string", description="选中的规格")
     * @ApiReturnParams  (name="products[].order_product_id", type="integer", description="订单商品id")
     * @ApiReturnParams  (name="products[].evaluate", type="integer", description="是否已评价")
     * @ApiReturnParams  (name="products[].refund", type="integer", description="是否已退款")
     * @ApiReturnParams  (name="extend.express_number", type="integer", description="运单号")
     * @ApiReturnParams  (name="order_id", type="string", description="订单id")
     * @ApiReturnParams  (name="state", type="string", description="状态类型:0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后")
     * @ApiReturnParams  (name="refund_status_text", type="string", description="已退款")
     */
    public function getOrders()
    {
        // 0=全部,1=待付款,2=待发货,3=待收货,4=待评价,5=售后
        $type = $this->request->post('type', 0);
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 10);
        try {

            $orderModel = new \addons\unishop\model\Order();
            $result = $orderModel->getOrdersByType($this->auth->id, $type, $page, $pagesize);

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success('', $result);

    }

    /**
     * @ApiTitle    (取消订单)
     * @ApiSummary  (未支付的订单才叫取消，已支付的叫退货)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string", description="订单id")
     * @ApiReturn   ({"code":1,"msg":"取消成功","data":true})
     *
     */
    public function cancel()
    {
        $order_id = $this->request->post('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        switch ($order['status']) {
            case \addons\unishop\model\Order::STATUS_REFUND:
                $this->error('此订单已退款，无法取消');
                break;
            case \addons\unishop\model\Order::STATUS_CANCEL:
                $this->error('此订单已取消, 无需再取消');
                break;
        }

        if ($order['have_paid'] != \addons\unishop\model\Order::PAID_NO) {
            $this->error('此订单已支付，无法取消');
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_NORMAL && $order['have_paid'] == \addons\unishop\model\Order::PAID_NO) {
            $order->status = \addons\unishop\model\Order::STATUS_CANCEL;
            $order->save();
            $this->success('取消成功', true);
        }
    }

    /**
     * @ApiTitle    (删除订单)
     * @ApiSummary  (只能删除已取消或已退货的订单)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string", description="订单id")
     * @ApiReturn   ({"code":1,"msg":"删除成功","data":true})
     *
     */
    public function delete()
    {
        $order_id = $this->request->post('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_NORMAL) {
            $this->error('只能删除已取消或已退货的订单');
        }

        if ($order['status'] == \addons\unishop\model\Order::STATUS_REFUND && $order['refund_status'] == \addons\unishop\model\Order::REFUND_STATUS_APPLY) {
            $this->error('订单退款中，不可删除订单');
        }

        $order->delete();
        $this->success('删除成功', true);
    }

    /**
     * @ApiTitle    (确认收货)
     * @ApiSummary  (确认收货)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string", description="订单id")
     * @ApiReturn   ({"code":1,"msg":"已确认收货","data":true})
     *
     */
    public function received()
    {
        $order_id = $this->request->post('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        if ($order->have_delivered == 0) {
            $this->error('未发货，不能确认收货');
        }

        $order->have_received = time();
        $order->save();
        $this->success('已确认收货', true);

    }

    /**
     * @ApiTitle    (发表评论/评价)
     * @ApiSummary  (发表评论/评价)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string", description="订单id")
     * @ApiParams   (name="rate", type="integer", description="分数/星星")
     * @ApiParams   (name="anonymous", type="integer", description="是否匿名")
     * @ApiParams   (name="comment", type="string", description="评价内容")
     * @ApiParams   (name="product_id", type="string", description="商品id")
     * @ApiReturn   ({"code":1,"msg":"感谢评价","data":true})
     *
     */
    public function comment()
    {
        $rate = $this->request->post('rate', 5);
        $anonymous = $this->request->post('anonymous', 0);
        $comment = $this->request->post('comment');
        $order_id = $this->request->post('order_id', 0);
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);
        $product_id = $this->request->post('product_id');
        $product_id = \addons\unishop\extend\Hashids::decodeHex($product_id);

        $orderProductModel = new \addons\unishop\model\OrderProduct();
        $orderProduct = $orderProductModel->where(['product_id' => $product_id, 'order_id' => $order_id, 'user_id' => $this->auth->id])->find();

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$orderProduct || !$order) {
            $this->error(__('Order not exist'));
        }
        if ($order->have_received == $orderModel::RECEIVED_NO) {
            $this->error(__('未收货，不可评价'));
        }

        $result = false;
        try {

            $evaluate = new Evaluate();
            $evaluate->user_id = $this->auth->id;
            $evaluate->order_id = $order_id;
            $evaluate->product_id = $product_id;
            $evaluate->rate = $rate;
            $evaluate->anonymous = $anonymous;
            $evaluate->comment = $comment;
            $evaluate->spec = $orderProduct->spec;
            $result = $evaluate->save();

            if ($result) {
                $order->have_commented = time();
                $order->save();
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        if ($result !== false) {
            $this->success(__('Thanks for the evaluation'), 1);
        } else {
            $this->error(__('Evaluation failure'));
        }

    }

    /**
     * @ApiTitle    (获取订单数量)
     * @ApiSummary  (获取订单数量)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="unpaid", type="integer", description="未支付数量")
     * @ApiReturnParams  (name="undelivered", type="integer", description="未发货数量")
     * @ApiReturnParams  (name="unreceived", type="integer", description="未收货数量")
     * @ApiReturnParams  (name="uncomment", type="integer", description="未评价数量")
     * @ApiReturnParams  (name="refund", type="integer", description="正在售后的数量")
     *
     */
    public function count()
    {
        if (!$this->auth->isLogin()) {
            $this->error('');
        }
        $order = new \addons\unishop\model\Order();

        $list = $order
            ->where([
                'user_id' => $this->auth->id,
            ])
            ->where('status', '<>', \addons\unishop\model\Order::STATUS_CANCEL)
            ->where(function ($query) {
                $query
                    ->whereOr([
                        'have_paid' => \addons\unishop\model\Order::PAID_NO,
                        'have_delivered' => \addons\unishop\model\Order::DELIVERED_NO,
                        'have_received' => \addons\unishop\model\Order::RECEIVED_NO,
                        'have_commented' => \addons\unishop\model\Order::COMMENTED_NO
                    ])
                    ->whereOr('refund_status', '>', \addons\unishop\model\Order::REFUND_STATUS_NONE);
            })
            ->field('have_paid,have_delivered,have_received,have_commented,refund_status,had_refund')
            ->select();

        $data = [
            'unpaid' => 0,
            'undelivered' => 0,
            'unreceived' => 0,
            'uncomment' => 0,
            'refund' => 0
        ];
        foreach ($list as $item) {
            switch (true) {
                case $item['have_paid'] > 0 && $item['have_delivered'] > 0 && $item['have_received'] > 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['uncomment']++;
                    break;
                case $item['have_paid'] > 0 && $item['have_delivered'] > 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['unreceived']++;
                    break;
                case $item['have_paid'] > 0 && $item['have_delivered'] == 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['undelivered']++;
                    break;
                case $item['have_paid'] == 0 && $item['have_delivered'] == 0 && $item['have_received'] == 0 && $item['have_commented'] == 0 && $item['refund_status'] == 0:
                    $data['unpaid']++;
                    break;
                case $item['refund_status'] > 0 && $item['had_refund'] == 0 && $item['refund_status'] != 3:
                    $data['refund']++;
                    break;

            }
        }

        $this->success('', $data);
    }

    /**
     * @ApiTitle    (订单详情细节)
     * @ApiSummary  (订单详情细节)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string",required=true, description="订单id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="out_trade_no", type="string", description="商户订单号")
     * @ApiReturnParams  (name="order_price", type="string", description="订单原价钱")
     * @ApiReturnParams  (name="discount_price", type="string", description="优惠多少钱")
     * @ApiReturnParams  (name="delivery_price", type="string", description="运费多少钱")
     * @ApiReturnParams  (name="total_price", type="string", description="订单实价")
     * @ApiReturnParams  (name="pay_type", type="integer", description="支付类型")
     * @ApiReturnParams  (name="id", type="string", description="下单ip")
     * @ApiReturnParams  (name="remark", type="string", description="订单备注")
     * @ApiReturnParams  (name="have_paid", type="integer", description="是否支付")
     * @ApiReturnParams  (name="have_delivered", type="integer", description="是否发货")
     * @ApiReturnParams  (name="have_received", type="integer", description="是否收货")
     * @ApiReturnParams  (name="have_commented", type="integer", description="是否评论")
     * @ApiReturnParams  (name="refund_status", type="integer", description="退款状态")
     * @ApiReturnParams  (name="products[].id", type="string", description="商品id")
     * @ApiReturnParams  (name="products[].title", type="string", description="商品名称")
     * @ApiReturnParams  (name="products[].image", type="string", description="商品图片")
     * @ApiReturnParams  (name="products[].number", type="integer", description="商品数量")
     * @ApiReturnParams  (name="products[].price", type="string", description="商品价钱")
     * @ApiReturnParams  (name="products[].spec", type="string", description="选中的规格")
     * @ApiReturnParams  (name="products[].order_product_id", type="integer", description="订单商品id")
     * @ApiReturnParams  (name="products[].evaluate", type="integer", description="是否已评价")
     * @ApiReturnParams  (name="products[].refund", type="integer", description="是否已退款")
     * @ApiReturnParams  (name="express_number", type="integer", description="运单号")
     * @ApiReturnParams  (name="pay_type_text", type="string", description="支付类型简述")
     * @ApiReturnParams  (name="refund_status_text", type="string", description="退款状态简述")
     * @ApiReturnParams  (name="delivery.username", type="string", description="收货人名称")
     * @ApiReturnParams  (name="delivery.mobile", type="string", description="收货人电话")
     * @ApiReturnParams  (name="delivery.address", type="string", description="收货人地址")
     *
     */
    public function detail()
    {
        echo $order_id = $this->request->post('order_id', 0);
        echo $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        try {
            $orderModel = new \addons\unishop\model\Order();
            $order = $orderModel
                ->with([
                    'products' => function ($query) {
                        $query->field('id,order_id,image,number,score,spec,title,product_id');
                    },
                    'extend' => function ($query) {
                        $query->field('id,order_id,address_id,address_json,express_number,express_company');
                    },
                    'evaluate' => function ($query) {
                        $query->field('id,order_id,product_id');
                    }
                ])
                ->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

            if ($order) {
                $order = $order->append(['state', 'paidtime', 'deliveredtime', 'receivedtime', 'commentedtime', 'pay_type_text', 'refund_status_text'])->toArray();

                // 快递单号
                $order['express_number'] = $order['extend']['express_number'];
                $order['express_company'] = !empty($order['extend']['express_company']) ? $order['extend']['express_company'] : '快递单号';

                if($order['is_self_pickup']){
                    $order['delivery']  = [];
                }
                else{
                    // 送货地址
                    $address = json_decode($order['extend']['address_json'], true);
                    $area = (new \addons\unishop\model\Area())
                        ->whereIn('id', [$address['province_id'], $address['city_id'], $address['area_id']])
                        ->column('name', 'id');
                    $delivery['username'] = $address['name'];
                    $delivery['mobile'] = $address['mobile'];
                    $delivery['address'] = $area[$address['province_id']] . ' ' . $area[$address['city_id']] . ' ' . $area[$address['area_id']] . ' ' . $address['address'];
                    $order['delivery'] = $delivery;
                }


                // 是否已评论
                $evaluate = array_column($order['evaluate'], 'product_id');
                foreach ($order['products'] as &$product) {
                    $product['image'] = Config::getImagesFullUrl($product['image']);
                    if (in_array($product['id'], $evaluate)) {
                        $product['evaluate'] = true;
                    } else {
                        $product['evaluate'] = false;
                    }
                }

                unset($order['evaluate']);
                unset($order['extend']);
            }

        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('', $order);
    }

    /**
     * @ApiTitle    (申请售后信息)
     * @ApiSummary  (申请售后信息)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string",required=true, description="订单id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="status", type="string", description="订单状态:-1=退货,0=取消订单,1=正常啊")
     * @ApiReturnParams  (name="total_price", type="string", description="订单总价钱")
     * @ApiReturnParams  (name="delivery_price", type="string", description="订单运费")
     * @ApiReturnParams  (name="have_paid", type="integer", description="是否支付")
     * @ApiReturnParams  (name="have_delivered", type="integer", description="是否发货")
     * @ApiReturnParams  (name="have_received", type="integer", description="是否收货")
     * @ApiReturnParams  (name="have_commented", type="integer", description="是否评论")
     * @ApiReturnParams  (name="refund_status", type="integer", description="退款状态")
     * @ApiReturnParams  (name="products[].id", type="string", description="商品id")
     * @ApiReturnParams  (name="products[].title", type="string", description="商品名称")
     * @ApiReturnParams  (name="products[].image", type="string", description="商品图片")
     * @ApiReturnParams  (name="products[].number", type="integer", description="商品数量")
     * @ApiReturnParams  (name="products[].price", type="string", description="商品价钱")
     * @ApiReturnParams  (name="products[].spec", type="string", description="选中的规格")
     * @ApiReturnParams  (name="products[].order_product_id", type="integer", description="订单商品id")
     * @ApiReturnParams  (name="products[].choose", type="integer", description="是否选中")
     * @ApiReturnParams  (name="refund_status_text", type="string", description="退款状态简述")
     * @ApiReturnParams  (name="refund.id", type="string", description="退款信息id")
     * @ApiReturnParams  (name="refund.amount", type="float",required=true, description="退款金额")
     * @ApiReturnParams  (name="refund.service_type", type="string",required=true, description="服务类型:0=我要退款无需退货,1=我要退货退款,2=换货")
     * @ApiReturnParams  (name="refund.receiving_status", type="integer",required=true, description="货物状态:0=未收到,1=已收到")
     * @ApiReturnParams  (name="refund.reason_type", type="string",required=true, description="换货原因")
     * @ApiReturnParams  (name="refund.refund_explain", type="string",required=true, description="退款说明")
     * @ApiReturnParams  (name="refund.express_number", type="string",required=true, description="寄货物流单号")
     *
     */
    public function refundInfo()
    {
        $order_id = $this->request->post('order_id');
        $order_id = \addons\unishop\extend\Hashids::decodeHex($order_id);

        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel
            ->with([
                'products' => function ($query) {
                    $query->field('id,order_id,image,number,price,spec,title,product_id,(1) as choose');
                },
                'refund',
                'refundProducts'
            ])
            ->field('id,status,total_price,delivery_price,have_commented,have_delivered,have_paid,have_received,refund_status')
            ->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }

        $order = $order->append(['refund_status_text'])->toArray();

        foreach ($order['products'] as &$product) {
            $product['image'] = Config::getImagesFullUrl($product['image']);
            $product['choose'] = 0;

            // 如果是已提交退货的全选
            if ($order['status'] == \addons\unishop\model\Order::STATUS_REFUND) {
                foreach ($order['refund_products'] as $refundProduct) {
                    if ($product['order_product_id'] == $refundProduct['order_product_id']) {
                        $product['choose'] = 1;
                    }
                }
            }
        }

        unset($order['refund_products']);

        $this->success('', $order);
    }


    /**
     * @ApiTitle    (提交申请售后)
     * @ApiSummary  (提交申请售后)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string",required=true, description="订单id")
     * @ApiParams   (name="amount", type="float",required=true, description="退款金额")
     * @ApiParams   (name="service_type", type="string",required=true, description="服务类型:0=我要退款无需退货,1=我要退货退款,2=换货")
     * @ApiParams   (name="receiving_status", type="integer",required=true, description="货物状态:0=未收到,1=已收到")
     * @ApiParams   (name="reason_type", type="string",required=true, description="换货原因")
     * @ApiParams   (name="refund_explain", type="string",required=true, description="退款说明")
     * @ApiParams   (name="order_product_id", type="integer",required=true, description="订单的商品的id")
     * @ApiReturn   ({"code":1,"msg":"提交","data":1})
     *
     */
    public function refund()
    {
        $order_id = $this->request->post('order_id');
        $order_id = Hashids::decodeHex($order_id);
        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel->where(['id' => $order_id, 'user_id' => $this->auth->id])->find();

        if (!$order) {
            $this->error(__('Order not exist'));
        }
        if ($order['have_paid'] == 0) {
            $this->error(__('订单未支付，可直接取消，无需申请售后'));
        }

        $amount = $this->request->post('amount', 0);
        $serviceType = $this->request->post('service_type');
        $receivingStatus = $this->request->post('receiving_status');
        $reasonType = $this->request->post('reason_type');
        $refundExplain = $this->request->post('refund_explain');
        $orderProductId = $this->request->post('order_product_id');

        if (!$orderProductId) {
            $this->error(__('Please select goods'));
        }
        if (!in_array($receivingStatus, [OrderRefund::UNRECEIVED, OrderRefund::RECEIVED])) {
            $this->error(__('Please select goods status'));
        }
        if (!in_array($serviceType, [OrderRefund::TYPE_REFUND_NORETURN, OrderRefund::TYPE_REFUND_RETURN, OrderRefund::TYPE_EXCHANGE])) {
            $this->error(__('Please select service type'));
        }
        if (in_array($serviceType, [OrderRefund::TYPE_REFUND_NORETURN, OrderRefund::TYPE_REFUND_RETURN]) && $order['total_price'] > 0) {
            if (!$amount) {
                $this->error(__('Please fill in the refund amount'));
            }
        }

        try {
            Db::startTrans();

            $orderRefund = new OrderRefund();
            $orderRefund->user_id = $this->auth->id;
            $orderRefund->order_id = $order_id;
            $orderRefund->receiving_status = $receivingStatus;
            $orderRefund->service_type = $serviceType;
            $orderRefund->reason_type = $reasonType;
            $orderRefund->amount = $amount;
            $orderRefund->refund_explain = $refundExplain;
            $orderRefund->save();

            $productIdArr = explode(',', $orderProductId);
            $refundProduct = [];
            foreach ($productIdArr as $orderProductId) {
                $tmp['order_product_id'] = $orderProductId;
                $tmp['order_id'] = $order_id;
                $tmp['user_id'] = $this->auth->id;
                $tmp['refund_id'] = $orderRefund['id'];
                $tmp['createtime'] = time();
                $refundProduct[] = $tmp;
            }
            (new OrderRefundProduct)->insertAll($refundProduct);

            $order->status = \addons\unishop\model\Order::STATUS_REFUND;
            $order->refund_status = \addons\unishop\model\Order::REFUND_STATUS_APPLY;
            $order->save();

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        $this->success('已申请', 1);
    }

    /**
     * @ApiTitle    (售后发货)
     * @ApiSummary  (售后发货)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="order_id", type="string",required=true, description="订单id")
     * @ApiParams   (name="express_number", type="string",required=true, description="寄货物流单号")
     * @ApiReturn   ({"code":1,"msg":"","data":1})
     *
     */
    public function refundDelivery()
    {
        $orderId = $this->request->post('order_id');
        $expressNumber = $this->request->post('express_number');

        if (!$expressNumber) {
            $this->error(__('Please fill in the express number'));
        }

        $orderId = Hashids::decodeHex($orderId);
        $orderModel = new \addons\unishop\model\Order();
        $order = $orderModel
            ->where(['id' => $orderId, 'user_id' => $this->auth->id])
            ->with(['refund'])->find();

        if (!$order || !$order->refund) {
            $this->error(__('Order not exist'));
        }
        try {
            Db::startTrans();

            $order->refund->express_number = $expressNumber;

            $order->refund_status = \addons\unishop\model\Order::REFUND_STATUS_APPLY;

            if ($order->refund->save() && $order->save()) {
                Db::commit();
            } else {
                throw new Exception(__('Operation failed'));
            }

        } catch (Exception $e) {
            Db::rollback();
            $this->success($e->getMessage());
        }
        $this->success('', 1);
    }


    /**
     * @ApiTitle    (快递查询)
     * @ApiSummary  (快递查询)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiParams   (name="express", type="string",required=true, description="寄货物流公司")
     * @ApiParams   (name="expresssn", type="string",required=true, description="寄货物流单号")
     * @ApiReturn   ({"code":1,"msg":"","data":1})
     *
     */
    public function express() {

        $params = $this->request->post();

        $express = new Ali();
        $data = $express->express($params['expresssn']);
        if ($data['code'] != 0) {
            $this->error($data['reason'] ?? '');
        }

        // 做预处理 数据转换
        $list = $data['result']['list'] ?? [];
        foreach ($list as &$value) {
            $value = [
                'time' => $value['node_time'],
                'step' => $value['node_desc'],
            ];
        }

        $this->success('', [
            'message' => $list,
            'company' => $data['result']['ExpressName'] ?? '快递单号'
        ]);
    }
}
