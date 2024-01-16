<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/5
 * Time: 10:33 下午
 */


namespace addons\unishop\controller;

use \addons\unishop\model\Address as AddressModel;
use addons\unishop\model\Area;
use think\Cache;
use think\Exception;
use think\Loader;
use think\Validate;

/**
 * 收货地址
 */
class Address extends Base
{
    /**
     * 允许频繁访问的接口
     * @var array
     */
    protected $frequently = ['area'];

    /**
     * @ApiTitle    (全部收货地址)
     * @ApiSummary  (用户收货地址列表)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     * @ApiReturnParams  (name="id", type="integer", description="地址id")
     * @ApiReturnParams  (name="user_id", type="integer", description="用户id")
     * @ApiReturnParams  (name="name", type="string", description="收货人名称")
     * @ApiReturnParams  (name="mobile", type="string", description="收货人电话")
     * @ApiReturnParams  (name="address", type="string", description="收货详细地址")
     * @ApiReturnParams  (name="province_id", type="integer", description="省份id")
     * @ApiReturnParams  (name="city_id", type="integer", description="城市id")
     * @ApiReturnParams  (name="area_id", type="integer", description="地区id")
     * @ApiReturnParams  (name="is_default", type="integer", description="是否默认")
     * @ApiReturnParams  (name="province", type="json", description="{'name':'北京'}")
     * @ApiReturnParams  (name="city", type="json", description="{'name':'北京市'}")
     * @ApiReturnParams  (name="area", type="json", description="{'name':'东城区'}")
     */
    public function all()
    {
        $page = $this->request->post('page', 1);
        $pagesize = $this->request->post('pagesize', 15);

        $data = (new AddressModel())
            ->with([
                'province' => function($query) {$query->field('id,name');},
                'city' => function($query) {$query->field('id,name');},
                'area' => function($query) {$query->field('id,name');}
            ])
            ->where('user_id', $this->auth->id)
            ->order(['is_default' => 'desc', 'id' => 'desc'])
            ->limit(($page - 1) * $pagesize, $pagesize)
            ->select();

        if ($data) {
            $msg = '';
            $data = collection($data)->toArray();
        } else {
            $msg = __('No address');
        }

        $this->success($msg, $data);
    }

    /**
     * @ApiTitle    (添加收货地址)
     * @ApiSummary  (添加收货地址)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="name", type="string", required=true, description="名字")
     * @ApiParams   (name="mobile", type="string", required=true, description="电话号码")
     * @ApiParams   (name="address", type="string", required=true, description="详细地址")
     * @ApiParams   (name="province_id", type="integer", required=true, description="省份id")
     * @ApiParams   (name="city_id", type="integer", required=true, description="城市id")
     * @ApiParams   (name="area_id", type="integer", required=true, description="区域id")
     * @ApiParams   (name="is_default", type="integer", required=true, description="是否默认", sample="1")
     * @ApiReturn   ({"code":1,"msg":"添加成功","data":true})
     */
    public function add()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Address');
            if (!$validate->check($data, [], 'add')) {
                throw new Exception($validate->getError());
            }

            $data['user_id'] = $this->auth->id;

            $addressModel = new AddressModel();
            if ($data['is_default'] == 1) {
                $addressModel->allowField(true)->save(['is_default' => 0], ['user_id' => $data['user_id']]);
            }

            if ($addressModel->where(['user_id' => $this->auth->id])->count() > 49) {
                throw new Exception('不能添加超过50个地址');
            }

            $addressModel = new AddressModel();
            if (!$addressModel->allowField(true)->save($data)) {
                throw new Exception($addressModel->getError());
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
        $this->success('添加成功', true);
    }

    /**
     * @ApiTitle    (修改收货地址)
     * @ApiSummary  (修改收货地址)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="integer", required=true, description="地址id")
     * @ApiParams   (name="name", type="string", required=true, description="名字")
     * @ApiParams   (name="mobile", type="string", required=true, description="电话号码")
     * @ApiParams   (name="address", type="string", required=true, description="详细地址")
     * @ApiParams   (name="province_id", type="integer", required=true, description="省份id")
     * @ApiParams   (name="city_id", type="integer", required=true, description="城市id")
     * @ApiParams   (name="area_id", type="integer", required=true, description="区域id")
     * @ApiParams   (name="is_default", type="integer", required=true, description="是否默认", sample="1")
     * @ApiReturn   ({"code":1,"msg":"修改成功","data":true})
     */
    public function edit()
    {
        $data = $this->request->post();
        try {
            $validate = Loader::validate('\\addons\\unishop\\validate\\Address');
            if (!$validate->check($data, [], 'edit')) {
                throw new Exception($validate->getError());
            }

            $addressModel = new AddressModel();
            $data['user_id'] = $this->auth->id;
            if ($data['is_default'] == 1) {
                $addressModel->allowField(true)->save(['is_default' => 0], ['user_id' => $data['user_id']]);
            }
            $data['updatetime'] = time();
            if (!$addressModel->allowField(true)->save($data,['id' => $data['id'], 'user_id' => $data['user_id']])) {
                throw new Exception($addressModel->getError());
            }
        } catch (Exception $e) {
            $this->error($e->getMessage(), false);
        }
        $this->success('修改成功', true);
    }

    /**
     * @ApiTitle    (删除收货地址)
     * @ApiSummary  (删除收货地址)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="integer", required=true, description="地址id")
     * @ApiReturn   ({"code":1,"msg":"删除成功","data":1})
     */
    public function delete()
    {
        $address_id = $this->request->post('id', 0);

        $data = (new AddressModel())
            ->where([
                'id' => $address_id,
                'user_id' => $this->auth->id
            ])
            ->delete();

        if ($data) {
            $this->success('删除成功', 1);
        } else {
            $this->success('没有数据', 0);
        }
    }

    /**
     * @ApiTitle    (获取地区信息)
     * @ApiSummary  (获取地区信息)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="pid", type="integer", required=true, description="省市区的id")
     * @ApiReturn   ({"code":1,"msg":"","data":[]})
     * @ApiReturnParams  (name="id", type="integer", description="省市区的id")
     * @ApiReturnParams  (name="pid", type="integer", description="上级id")
     * @ApiReturnParams  (name="label", type="integer", description="省市区简称")
     */
    public function area()
    {
        $pid = $this->request->post('pid', 0);
        Cache::clear('area_pid_'.$pid);
        if (Cache::has('area_pid_'.$pid)) {
            $area = Cache::get('area_pid_'.$pid);
        } else {
            $areaModel = new Area();
            $area = $areaModel
                ->field('name as label,pid,id,code as value')
                ->where(['pid' => $pid])
                ->order(['pid' => 'asc', 'id' => 'asc'])
                ->select();

            if ($area) {
                $area = collection($area)->toArray();
                Cache::set('area_pid_'.$pid, $area, 60);
            }
        }
        $this->success('', $area);
    }

    /**
     * @ApiTitle    (获取单个收货地址)
     * @ApiSummary  (获取单个收货地址)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=token, type=string, required=true, description="用户登录的Token", sample="a2e3cc70-d2d1-41e6-9c14-f1d774ee5e1e")
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="id", type="integer", required=true, description="省市区的id")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     * @ApiReturnParams  (name="id", type="integer", description="地址id")
     * @ApiReturnParams  (name="user_id", type="integer", description="用户id")
     * @ApiReturnParams  (name="name", type="string", description="收货人名称")
     * @ApiReturnParams  (name="mobile", type="string", description="收货人电话")
     * @ApiReturnParams  (name="address", type="string", description="收货详细地址")
     * @ApiReturnParams  (name="province_id", type="integer", description="省份id")
     * @ApiReturnParams  (name="city_id", type="integer", description="城市id")
     * @ApiReturnParams  (name="area_id", type="integer", description="地区id")
     * @ApiReturnParams  (name="is_default", type="integer", description="是否默认")
     */
    public function info()
    {
        $id = $this->request->post('id');
        $address = (new AddressModel())->where(['id' => $id, 'user_id' => $this->auth->id])->find()->toArray();
        $this->success('', $address);
    }

}
