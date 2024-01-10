<?php

namespace app\admin\controller\unishop;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 收货地址管理
 *
 * @icon fa fa-circle-o
 */
class Address extends Backend
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
        $this->model = new \app\admin\model\unishop\Address;
    }

    /**
     * 查看
     */
    public function index(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with([
                    'user',
                    'province' => function($query) {
                        $query->field('id, name');
                    },
                    'city' => function($query) {
                        $query->field('id, name');
                    },
                    'area' => function($query) {
                        $query->field('id, name');
                    }
                ])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit)
                ->each(
                    function($item,$key){
                        $item['is_default_name'] = $item['is_default'] ? '是' : '否' ;
                    }
                );

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }


   /* public function selectpage(){
        return parent::selectpage();
    }*/

    /*public function edit($ids = null){
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if ($this->request->isPost()){
            $this->token();

            $params = $this->request->post('row/a');
            if ($row->save($params)){
                $this->success();
            }
            $this->error('');
        }

        return parent::edit($ids);
    }*/
}
