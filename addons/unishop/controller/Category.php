<?php

namespace addons\unishop\controller;

use app\common\controller\Api;

/**
 * 分类
 */
class Category extends Api
{

    protected $noNeedLogin = ['all', 'menu', 'inlist', 'tabs'];
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \addons\unishop\model\Category();
    }


    /**
     * @ApiTitle    (全部分类数据)
     * @ApiSummary  (全部分类数据)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     *
     * @ApiReturnParams  (name="id", type="integer", description="分类id")
     * @ApiReturnParams  (name="name", type="string", description="分类名称")
     * @ApiReturnParams  (name="pid", type="integer", description="上级id")
     * @ApiReturnParams  (name="image", type="string", description="图片")
     * @ApiReturnParams  (name="type", type="string", description="类型")
     * @ApiReturnParams  (name="flag", type="integer", description="标签/位置")
     * @ApiReturnParams  (name="weigh", type="integer", description="排序")
     */
    public function all()
    {
        $all = $this->model
            ->where('type', 'product')
            ->where('status', 'normal')
            ->field('id,name,pid,image,type,flag,weigh')
            ->order('weigh ASC')
            ->cache(20)
            ->select();
        if ($all) {
            $all = collection($all)->toArray();
        }
        $this->success('', $all);
    }


    /**
     * @ApiTitle    (首页广告下面的分类)
     * @ApiSummary  (首页广告下面的分类)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="id", type="integer", description="分类id")
     * @ApiReturnParams  (name="name", type="string", description="分类名称")
     * @ApiReturnParams  (name="pid", type="integer", description="上级id")
     * @ApiReturnParams  (name="image", type="string", description="图片")
     * @ApiReturnParams  (name="type", type="string", description="类型")
     * @ApiReturnParams  (name="flag", type="integer", description="标签/位置")
     * @ApiReturnParams  (name="weigh", type="integer", description="排序")
     */
    public function menu()
    {
        $list = $this->model
            ->where('flag', 'index')
            ->where('status', 'normal')
            ->cache(20)
            ->select();
        if ($list) {
            $list = collection($list)->toArray();
        }
        $this->success('菜单', $list);
    }


    /**
     * @ApiTitle    (首页的tabs分类)
     * @ApiSummary  (首页的tabs分类)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="id", type="integer", description="分类id")
     * @ApiReturnParams  (name="name", type="string", description="分类名称")
     */
    public function tabs()
    {
        $list = $this->model
//            ->with('children')
            ->where('pid', 0)
            ->where('status', 'normal')
            ->cache(20)
            ->select();
        if ($list) {
            $list = collection($list)->toArray();
        }
        $tabs = [];
        foreach ($list as $item) {
            $tabs[] = [
                'id' => $item['id'],
                'name' => $item['name'],
            ];
           /* foreach ($item['children'] as $child) {
                $tabs[] = [
                    'id' => $child['id'],
                    'name' => $item['name'] . '-' . $child['name'],
                ];
            }*/
        }
        $this->success('菜单', $tabs);
    }
}
