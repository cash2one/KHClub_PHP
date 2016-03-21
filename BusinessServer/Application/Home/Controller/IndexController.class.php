<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class IndexController extends Controller {

    public function index(){

        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();
        $json = '{
            "button": [
                {
                    "name": "会员尊享",
                    "type": "view",
                    "url": "http://114.215.95.23/BusinessServer/index.php/Home/WX/userVerify"
                },
                {
                    "name": "我是商家",
                    "type": "view",
                    "url": "http://114.215.95.23/BusinessServer/index.php/Home/Shop/shopEnter"
                },
                {
                    "name": "成为代理",
                    "type": "view",
                    "url": "http://114.215.95.23/BusinessServer/index.php/Home/WXProxy/proxyEnter"
                }
            ]
        }';

        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$ACC_TOKEN;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        print_r($result);

    }

}

