<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/4/27
 * Time: 12:45 PM
 */

namespace addons\unishop\controller;

use app\common\library\Sms as Smslib;
use addons\unishop\model\User;

/**
 * 短信
 */
class Sms extends Base
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * @ApiTitle    (发送验证码)
     * @ApiSummary  (发送验证码)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="event", type="string", required=true, description="动作:register=注册,resetpwd=重置密码")
     * @ApiReturn   ({"code":1,"msg":"发送成功","data":1})
     *
     */
    public function send()
    {
        $mobile = $this->request->post("mobile");
        $event = $this->request->post("event");
        $event = $event ? $event : 'register';

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 60) {
            $this->error(__('发送频繁'));
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 5) {
            $this->error(__('发送频繁'));
        }
        if ($event) {
            //$userinfo = User::getByMobile($mobile);
            $userinfo = \app\common\model\User::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                //已被注册
                $this->error(__('手机号已被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                $this->error(__('手机号已被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::send($mobile, null, $event);
        if ($ret) {
            $this->success(__('发送成功'), 1);
        } else {
            $this->error(__('发送失败'), 0);
        }
    }

}
