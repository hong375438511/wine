<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/8
 * Time: 10:45 AM
 */


namespace addons\unishop\extend;


use addons\unishop\model\Config;
use GuzzleHttp\Client;
use think\Cache;
use Yansongda\Pay\Pay;

class Ali
{
    public static function initAliPay()
    {
        $config = [
            'app_id' => Config::getByName('ali_app_id')['value'],
            'notify_url' => Config::getByName('ali_notify_url')['value'],
            'return_url' => Config::getByName('ali_return_url')['value'],
            'ali_public_key' => Config::getByName('ali_public_key')['value'],
            // 加密方式： **RSA2**
            'private_key' => Config::getByName('ali_private_key')['value'],
//            'log' => [ // optional
//                'file' => './logs/alipay.log',
//                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
//                'type' => 'single', // optional, 可选 daily.
//                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
//            ],
            'http' => [ // optional
                'timeout' => 5.0,
                'connect_timeout' => 5.0,
                // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
            ],
            //'mode' => 'dev', // optional,设置此参数，将进入沙箱模式
        ];

        if (Config::getByName('ali_sandbox')['value'] == 1) {
            $config['mode'] = 'dev';
        }

        return Pay::alipay($config);
    }

    /**
     * 查看物流详情
     * @param $expressId
     * @return mixed
     */
    public function express($expressId)
    {
        $cacheKey = 'aliexpress_' . $expressId;
        $cache = Cache::get($cacheKey);
        if ($cache) {
            return $cache;
        }
        $host = "https://kdcx.market.alicloudapi.com";
        $path = "/express";
        $appcode = Config::getByName('ali_express_app_code')['value'] ?? '';
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "express_id=$expressId&express_name=";
        $client = new Client([
            'base_uri' => $host,
            'timeout' => 5.0,
        ]);
        $response = $client->post($path . "?" . $querys, [
            'headers' => [
                'Authorization' => "APPCODE $appcode"
            ]
        ]);
        $data = $response->getBody()->getContents();
        $result = json_decode($data, true) ?? [];
        Cache::set($cacheKey, $result, Config::getByName('ali_express_cache')['value'] ?? 0);
        return $result;
    }
}
