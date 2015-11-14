<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {

    protected $autoCheckFields =false;
    public function index(){

        echo  $_SERVER["REMOTE_ADDR"];
        $user_id = $_REQUEST['user_id'];
        if(!empty($user_id)){
            setcookie("share_id",$user_id);
        }

        echo $_COOKIE;
        $this->display("index");
    }

    public function share(){

        $user_id = $_REQUEST['user_id'];
        if(!empty($user_id)){
            setcookie("share_id",$user_id);
        }
        $this->display("index");
    }

    public function downloadAPK(){
        $user_id = $_REQUEST['user_id'];
        if(!empty($user_id)){
            $shareM = M('kh_share');
            $shareM->add(array('user_id'=>$user_id, 'add_date'=>time()));
        }
//        header("http://192.168.1.102/KHClub_Android.apk");
         header("Location: http://192.168.1.102/KHClub_Android.apk");
    }
}

