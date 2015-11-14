<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/5/4
 * Time: 23:05
 */

namespace Home\Controller;


class TestController {

    public function index(){
//		echo "haha\n";
//        echo C('TestConfig');
//        $this->display();
        echo U("Index/add");
        echo '<br>';
        echo U("Test/get");
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

}