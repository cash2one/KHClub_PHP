<?php
namespace Home\Controller;
use Think\Controller;
use Think\Log;

require THINK_PATH.'Library/Vendor/wxpay/lib/WxPay.Api.php';

//define("TOKEN", "pinweihuanqiu");
define("TOKEN", "test");

class WXReceiverController extends Controller {

    public function index(){

        Log::write($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = \WxPayResults::Init($xml);
        Log::write(json_encode($result),'INFO');

        //通过分享订阅
        if(!empty($result['Event'])){
            $model = M('biz_proxy_share');
            $share = $model->where('share_open_id="'.$result['FromUserName'].'"')->find();
            if(empty($share)){
                $share = array('share_open_id'=>$result['FromUserName'], 'user_id'=>$result['EventKey'], 'add_date'=>time());
                $model->add($share);
            }
        }

//        $echoStr = $_GET["echostr"];
//        $signature = $_GET["signature"];
//        $timestamp = $_GET["timestamp"];
//        $nonce = $_GET["nonce"];

//        $token = TOKEN;
//        $tmpArr = array($token, $timestamp, $nonce);
//        // use SORT_STRING rule
//        sort($tmpArr, SORT_STRING);
//        $tmpStr = implode( $tmpArr );
//        $tmpStr = sha1( $tmpStr );
//
//        if( $tmpStr == $signature ){
//            echo $echoStr;
//        }else{
//            exit;
//        }
    }

}

