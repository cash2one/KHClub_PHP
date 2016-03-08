<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller {

    function index(){

    }

    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/login/login
     */
    function login(){
        $this->display('Login');
    }

    /**
     * @brief 管理系统登录
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/login/loginVerify
     * @param username 用户名
     * @param password 密码 6-24位
     */
    function loginVerify(){
        $username = $_POST['username'];
        $password = $_POST['password'];
        if(empty($username) || empty($password)){
            $this->assign('error','1');
            $this->display('Login');
            exit;
        }

        //用户名密码先写死
        if($username == 'admin' && $password == 'khclub1234'){
            $_SESSION['manager'] = 1;
            header('Location:'.__ROOT__.'/index.php/Home/WXManager/withdrawRequest');
        }else{
            $this->assign('error','1');
            $this->display('Login');
        }
    }


}