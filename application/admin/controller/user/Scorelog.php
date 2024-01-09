<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;

/**
 * 会员积分管理
 *
 * @icon fa fa-user
 */
class Scorelog extends Backend
{

    protected $relationSearch = true;
    protected $searchFields = 'id,username,nickname';

    /**
     * @var \app\admin\model\User
     */
    protected $model = null;
    protected $admin = null;
    protected $schoolSelect = null;
    protected $levelSelect = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\common\model\ScoreLog();
        $this->admin = session('admin');
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                ->with([ 'user'])
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
}
