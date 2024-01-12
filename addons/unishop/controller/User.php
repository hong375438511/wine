<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/10/25
 * Time: 11:09 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Wechat;
use addons\unishop\model\UserExtend;
use app\common\library\Sms;
use think\Cache;
use think\Session;
use think\Validate;

/**
 * 用户
 */
class User extends Base
{
    protected $noNeedLogin = ['login', 'status', 'authSession', 'decryptData', 'register', 'resetpwd', 'loginForWechatMini'];

    /**
     * @ApiTitle    (会员登录)
     * @ApiSummary  (会员登录)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="password", type="string", required=true, description="密码")
     * @ApiReturn   ({"code":1,"msg":"登录成功","data":{}})
     *
     * @ApiReturnParams  (name="user_id", type="integer", description="用户id")
     * @ApiReturnParams  (name="username", type="string", description="用户名称")
     * @ApiReturnParams  (name="mobile", type="string", description="用户电话")
     * @ApiReturnParams  (name="avatar", type="string", description="用户头像")
     * @ApiReturnParams  (name="score", type="string", description="用户积分")
     * @ApiReturnParams  (name="token", type="string", description="登录token")
     */
    public function login()
    {
        $mobile = $this->request->post('mobile');
        $password = $this->request->post('password');
        if (!$mobile || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($mobile, $password);
        if ($ret) {
            $data = $this->auth->getUserinfo();
            $data['avatar'] = \addons\unishop\model\Config::getImagesFullUrl($data['avatar']);
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * @ApiTitle    (重置密码)
     * @ApiSummary  (重置密码)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="password", type="string", required=true, description="新密码")
     * @ApiParams   (name="captcha", type="string", required=true, description="验证码")
     * @ApiReturn   ({"code":1,"msg":"重置成功","data":1})
     *
     */
    public function resetpwd()
    {
        $mobile = $this->request->post("mobile");

        $newpassword = $this->request->post("password");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }

        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if (!$user) {
            $this->error(__('User not found'));
        }
        $ret = Sms::check($mobile, $captcha, 'resetpwd');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        Sms::flush($mobile, 'resetpwd');

        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'), 1);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * @ApiTitle    (注册会员)
     * @ApiSummary  (注册会员)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiParams   (name="username", type="string", required=true, description="用户名称")
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="password", type="string", required=true, description="密码")
     * @ApiParams   (name="captcha", type="string", required=true, description="验证码")
     * @ApiReturn   ({"code":1,"msg":"注册成功","data":1})
     *
     * @ApiReturnParams  (name="userinfo.id", type="integer", description="用户id")
     * @ApiReturnParams  (name="userinfo.username", type="string", description="用户名称")
     * @ApiReturnParams  (name="userinfo.mobile", type="string", description="用户电话")
     * @ApiReturnParams  (name="userinfo.avatar", type="string", description="用户头像")
     * @ApiReturnParams  (name="userinfo.score", type="string", description="用户积分")
     *
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post("captcha");

        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $captcha, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        Sms::flush($mobile, 'register');

        $avatar = \addons\unishop\model\Config::getByName('avatar')['value'] ?? '';
        $ret = $this->auth->register($username, $password, '', $mobile, ['avatar' => $avatar]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * @ApiTitle    (更改用户信息)
     * @ApiSummary  (更改用户信息)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=cookie, type=string, required=false, description="用户会话的cookie")
     * @ApiHeaders  (name=token, type=string, required=true, description="登录token")
     * @ApiParams   (name="username", type="string", required=true, description="用户名称")
     * @ApiParams   (name="mobile", type="string", required=true, description="手机号")
     * @ApiParams   (name="avatar", type="string", required=true, description="头像")
     * @ApiReturn   ({"code":1,"msg":"修改成功","data":1})
     *
     */
    public function edit()
    {
        $userInfo = $this->auth->getUserinfo();
        $username = $this->request->post('username', $userInfo['username']);
        $mobile = $this->request->post('mobile', $userInfo['mobile']);
        $avatar = $this->request->post('avatar', $userInfo['avatar']);

        $user = \app\common\model\User::get($this->auth->id);
        $user->username = $username;
        $user->mobile = $mobile;
        $user->avatar = $avatar;
        if ($user->save()) {
            $this->success(__('Modified'), 1);
        } else {
            $this->error(__('Fail'), 0);
        }
    }

    /**
     * 登录状态
     * @ApiInternal
     */
    public function status()
    {
        $this->success('', $this->auth->isLogin());
    }

    /**
     * @ApiTitle    (微信小程序登录)
     * @ApiSummary  (微信小程序登录)
     * @ApiMethod   (GET)
     * @ApiHeaders  (name=platform, type=string, required=false, description="平台")
     * @ApiParams   (name="code", type="string", required=true, description="小程序调用wx.login返回的code")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="openid", type="integer", description="微信用户openid")
     * @ApiReturnParams  (name="userInfo.id", type="integer", description="用户id")
     * @ApiReturnParams  (name="userInfo.username", type="string", description="用户名称")
     * @ApiReturnParams  (name="userInfo.mobile", type="string", description="用户电话")
     * @ApiReturnParams  (name="userInfo.avatar", type="string", description="用户头像")
     * @ApiReturnParams  (name="userInfo.score", type="string", description="用户积分")
     * @ApiReturnParams  (name="userInfo.token", type="string", description="用户登录token")
     *
     */
    public function authSession()
    {
        $platform = $this->request->header('platform');
        switch ($platform) {
            case 'MP-WEIXIN':
                $code = $this->request->post('code');
                $data = Wechat::authSession($code);
                // 如果有手机号码，自动登录
                if (isset($data['userInfo']['mobile']) && (!empty($data['userInfo']['mobile']) || $data['userInfo']['mobile'] != '')) {
                    $this->auth->direct($data['userInfo']['id']);
                    if ($this->auth->isLogin()) {
                        $data['userInfo']['token'] = $this->auth->getToken();
                        // 支付的时候用
                        Cache::set('openid_' . $data['userInfo']['id'], $data['openid'], 7200);
                    }
                }
                break;
            default:
                $data = [];
        }
        $this->success('', $data);
    }


    /**
     * @ApiTitle    (微信小程序消息解密)
     * @ApiSummary  (微信小程序消息解密，必须先调用authSession获取到session_key)
     * @ApiMethod   (POST)
     * @ApiParams   (name="iv", type="string", required=true, description="")
     * @ApiParams   (name="encryptedData", type="string", required=true, description="")
     * @ApiReturn   ({"code":1,"msg":"","data":{手机号码，用户信息等等，具体看用户授权什么权限}})
     *
     */
    public function decryptData()
    {
        $iv = $this->request->post('iv');

        $encryptedData = $this->request->post('encryptedData');

        $openid = $this->request->post('openid');
        $userExtend = UserExtend::getByOpenid($openid);

        $app = Wechat::initEasyWechat('miniProgram');

        $decryptedData = $app->encryptor->decryptData($userExtend['session_key'], $iv, $encryptedData);

        $this->success('', $decryptedData);
    }

    /**
     * @ApiTitle    (微信小程序通过授权手机号登录)
     * @ApiSummary  (微信小程序通过授权手机号登录)
     * @ApiMethod   (POST)
     * @ApiHeaders  (name=platform, type=string, required=false, description="平台")
     * @ApiParams   (name="iv", type="string", required=true, description="")
     * @ApiParams   (name="encryptedData", type="string", required=true, description="")
     * @ApiReturn   ({"code":1,"msg":"","data":{}})
     *
     * @ApiReturnParams  (name="openid", type="integer", description="微信用户openid")
     * @ApiReturnParams  (name="id", type="integer", description="用户id")
     * @ApiReturnParams  (name="username", type="string", description="用户名称")
     * @ApiReturnParams  (name="mobile", type="string", description="用户电话")
     * @ApiReturnParams  (name="avatar", type="string", description="用户头像")
     * @ApiReturnParams  (name="score", type="string", description="用户积分")
     * @ApiReturnParams  (name="token", type="string", description="用户登录token")
     *
     */
    public function loginForWechatMini()
    {
        $iv = $this->request->post('iv');

        $encryptedData = $this->request->post('encryptedData');

        $app = Wechat::initEasyWechat('miniProgram');

        $openid = $this->request->post('openid');
        $userExtend = UserExtend::getByOpenid($openid);

        $decryptedData = $app->encryptor->decryptData($userExtend['session_key'], $iv, $encryptedData);

        if (isset($decryptedData['phoneNumber'])) {
            // 看看有没有这个mobile的用户
            //$user = \addons\unishop\model\User::getByMobile($decryptedData['phoneNumber']);
            $user = \app\common\model\User::getByMobile($decryptedData['phoneNumber']);
            if ($user) {
                // 把user_extend表的user_id字段换成已存在的用户id
                if ($userExtend['user_id'] != $user->id) {
                    (new \addons\unishop\model\User)->where([
                        'id' => $userExtend->user_id,
                        'mobile' => ''
                    ])->delete();
                    $userExtend->user_id = $user->id;
                    $userExtend->save();
                }
            } else {
                $user = \addons\unishop\model\User::get($userExtend->user_id);
                if ($user) {
                    $user->mobile = $decryptedData['phoneNumber'];
                    $user->save();
                }
            }

            $userInfo['id'] = $user->id;
            $userInfo['openid'] = $openid;
            $userInfo['mobile'] = $user->mobile;
            $userInfo['avatar'] = \addons\unishop\model\Config::getImagesFullUrl($user->avatar);
            $userInfo['username'] = $user->username;

            $this->auth->direct($userInfo['id']);
            if ($this->auth->isLogin()) {
                $userInfo['token'] = $this->auth->getToken();
            }

            $this->success('', $userInfo);

        } else {
            $this->error('登录失败');
        }

    }


}
