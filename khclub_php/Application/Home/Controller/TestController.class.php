<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/5/4
 * Time: 23:05
 */
namespace Home\Controller;

Vendor('alisdk.TopSdk');

class TestController {

    public function index(){
//		echo "haha\n";
//        echo C('TestConfig');
//        $this->display();
        echo U("Index/add");
        echo '<br>';
        echo U("Test/get");
        echo 'test';
        echo 'test2';
    }

    public function get(){
        echo $_REQUEST['username'].testEcho();
        $get = M('testtable');
        $data = $get->find();
        if($data){
            echo json_encode($data);

        }else{
            echo '没有数据';
        }

    }

    //验证码发送
    public function testSMS(){

        $c = new \TopClient();
        $c->appkey = '23298649';
        $c->secretKey = '2baa68b8ae1790f1512c585d576d19b6';
        $req = new \AlibabaAliqinFcSmsNumSendRequest();
        $req->setSmsType("normal");
        $req->setSmsFreeSignName("阿里大鱼");
        $req->setSmsParam("");
        $req->setRecNum("13000000000");
        $req->setSmsTemplateCode("SMS_585014");
        $resp = $c->execute($req);
        print_r($resp);

    }

}