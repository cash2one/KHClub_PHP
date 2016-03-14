<?php
namespace Home\Controller;
use Think\Controller;
use Think\Log;

define("TOKEN", "pinweihuanqiu");

class WXReceiverController extends Controller {

    public function index(){

        Log::write($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        Log::write(json_encode($_REQUEST),'INFO');

        $echoStr = $_GET["echostr"];
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            echo $echoStr;
        }else{
            exit;
        }
    }

}

