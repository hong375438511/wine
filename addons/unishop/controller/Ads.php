<?php

namespace addons\unishop\controller;

use app\common\controller\Api;

/**
 * 广告
 */
class Ads extends Api
{

    protected $noNeedLogin = ['index'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * @ApiTitle    (广告列表)
     * @ApiSummary  (首页上方)
     * @ApiMethod   (GET)
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     * @ApiReturnParams  (name="id", type="integer", description="广告id")
     * @ApiReturnParams  (name="image", type="string", description="图片地址")
     * @ApiReturnParams  (name="background", type="string", description="颜色值")
     * @ApiReturnParams  (name="product_id", type="integer", description="跳转商品id")
     * @ApiReturnParams  (name="status", type="integer", description="是否显示")
     */
    public function index()
    {
        $ads = \addons\unishop\model\Ads::where('status', 1)->cache('ads-index', 20)->select();
        $this->success('广告列表', $ads);
    }

}
