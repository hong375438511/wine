<?php

namespace app\admin\controller\unishop;

use app\common\controller\Backend;

/**
 * 地址管理
 *
 * @icon fa fa-circle-o
 */
class Area extends Backend
{
    /**
     * 快速搜索时执行查找的字段
     */
    protected $searchFields = 'title';

    /**
     * Multi方法可批量修改的字段
     */
    protected $multiFields = 'switch';

    /**
     * product模型对象
     * @var \app\admin\model\unishop\Product
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\unishop\Area();
    }

    public function getSelect(){
        $pid = $this->request->get('pid',0);
        $data = $this->model->getSelect($pid);
        return json($data);
    }
}
