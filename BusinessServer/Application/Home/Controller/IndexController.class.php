<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class IndexController extends Controller {

    public function index(){

    }

    public function setMenu(){
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();
        $json = '{
                  "button" : [
                    {
                      "name" : "会员专享",
                      "sub_button" : [
                        {
                          "name" : "会员服务",
                          "type" : "view",
                          "url" : "'.HTTP_HOST.'/BusinessServer/index.php/Home/WX/userVerify"
                        },
                        {
                          "name" : "我要结账",
                          "type" : "view",
                          "url" : "'.HTTP_HOST.'/BusinessServer/index.php/Home/WX/getBillShops"
                        },
                        {
                          "name" : "豪车美容",
                          "type" : "view",
                          "url" : "'.HTTP_HOST.'/BusinessServer/index.php/Home/WX/getShops"
                        }
                      ]
                    },
                    {
                      "name" : "品位环球",
                      "sub_button" : [
                        {
                          "url" : "http://c.eqxiu.com/s/27ULUB5f",
                          "name" : "关于我们",
                          "type" : "view"
                        },
                        {
                          "key" : "KEY_INTRO",
                          "name" : "特权介绍",
                          "type" : "click"
                        },
                        {
                          "key" : "KEY_VIP",
                          "name" : "会员条件",
                          "type" : "click"
                        },
                        {
                          "key" : "KEY_MANAGER",
                          "name" : "豪车管家",
                          "type" : "click"
                        }
                      ]
                    },
                    {
                      "name" : "联盟商家",
                      "sub_button" : [
                        {
                          "name" : "商家专区",
                          "type" : "view",
                          "url" : "'.HTTP_HOST.'/BusinessServer/index.php/Home/Shop/shopEnter"
                        },
                        {
                          "name" : "我是代理",
                          "type" : "view",
                          "url" : "'.HTTP_HOST.'/BusinessServer/index.php/Home/WXProxy/proxyEnter"
                        }
                      ]
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

    public function clearMenu(){
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token='.$ACC_TOKEN;
        echo file_get_contents($url);

    }

    public function searchMenu(){
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/get?access_token='.$ACC_TOKEN;
        echo file_get_contents($url);

    }


}

