<?php
namespace Home\Controller;
use Think\Controller;
class ServerProviderLoginController extends Controller {

    function index(){
        header('Location:'.__ROOT__.'/index.php/Home/ServerProviderLogin/login');
    }

    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/SHS_Contact_PHP/index.php/Home/ServerProviderLogin/login
*/
    function login(){
        $this->display('Login');
    }

    /**
     * @brief 管理系统登录
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/ServerProviderLogin/loginVerify
     * @param mobile 用户名
     * @param password 密码 6-24位
     */
    function loginVerify(){
        $mobile = $_POST['mobile'];
        $password = $_POST['password'];
        $userInfo = M("biz_server_provider")->field('server_id,username,password,mobile')->where("mobile='%s' and password='%s'",array($mobile,$password))->find();
        if(!empty($userInfo)){
            $_SESSION["user"] = $userInfo;
            header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/index');
        }else{
            $this->assign('error','1');
            $this->display('Login');
        }
    }


}