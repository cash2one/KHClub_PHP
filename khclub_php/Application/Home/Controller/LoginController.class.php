<?php
namespace Home\Controller;
use Think\Controller;
class LoginController extends Controller {

    function index(){

    }

    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/login/login
     */
    function login(){
        $this->display('Login');
    }

    /**
     * @brief 管理系统登录
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/login/loginVerify
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
            header('www.pinweihuanqiu.com/khclub_php/index.php/Home/WXManager');
        }else{
            $this->assign('error','1');
            $this->display('Login');
        }
    }

    /**
     * @brief 管理系统登录
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WXManager/withdrawCommit
     * @param target_id 要提现的账户ID
     */
    function withdrawCommit(){
        //未登录
        if(empty($_SESSION['manager'])){
            header('Location: www.pinweihuanqiu.com/khclub_php/index.php/Home/login/login');
            exit;
        }
        $target_id = $_POST['target_id'];
        if(empty($target_id)){
            header('Location: www.pinweihuanqiu.com/khclub_php/index.php/Home/WXManager');
            exit;
        }

        $withdrawModel = M();
        $sql = 'UPDATE kh_withdraw_notice SET withdraw_state=1 WHERE user_id="'.$target_id.'"';
        $withdrawModel->execute($sql);

        //提现
        $sql = 'UPDATE kh_lucky SET state=2,update_date='.time().',withdraw_date='.time().'
                WHERE user_id="'.$target_id.'" AND state=1 AND delete_flag=0';
        $num = $withdrawModel->execute($sql);
        if($num < 1){
            header('Location: www.pinweihuanqiu.com/khclub_php/index.php/Home/WXManager');
            exit;
        }else{
            //提现成功
            header('Location: www.pinweihuanqiu.com/khclub_php/index.php/Home/WXManager');
        }

    }
}