<?php
/**
 * Created by PhpStorm.
 * User: khclub
 * Date: 2016/3/25
 * Time: 18:10
 */
namespace Home\Controller;
use Think\Controller;
use Think\Exception;

class ServerProviderController extends Controller{
    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/SHS_Contact_PHP/index.php/Home/ServerProvider/index
     */
    function index(){
        if($_SESSION["user"] == null){
            header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/login');
            exit;
        }else{
            header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/main');
        }
    }

    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/SHS_Contact_PHP/index.php/Home/ServerProvider/login
     */
    function login(){
        $this->display('Login');
    }

    /**
     * @brief 管理系统登录
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/ServerProvider/loginVerify
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

    /**
     * @brief 总部首页
     * 接口地址
     * http://192.168.0.104/SHS_Contact_PHP/index.php/Home/ServerProvider/main
     * @param server_id 服务商id
     */
    public function main(){
        try {
            if($_SESSION["user"] == null){
                $this->assign('error','1');
                header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/login');
                exit;
            }
            $today = strtotime(date('Y-m-d'));
            $server_id = $_SESSION['user']['server_id'];
            $shopModel = M('biz_shop');
            $shop = $shopModel->field('id,shop_name')->where('server_id='.$server_id)->select();
            for($i=0;$i<count($shop);$i++){
                $orderModel = M();
                $sql = 'SELECT COUNT(id) carnum, SUM(total_fee) moneynum, use_date FROM biz_order WHERE verify_shop_id='.$shop[$i]['id'].' AND state=2
                        AND delete_flag=0 AND use_date>'.$today;
                $order=$orderModel->query($sql);
                if($order[0]['moneynum'] == ""){
                    $shop[$i]['moneyNum']= '0';
                }else{
                    $shop[$i]['moneyNum']=$order[0]['moneynum'];
                }
                $shop[$i]['carNum']=$order[0]['carnum'];
                if($order[0]['use_date'] == ''){
                    $shop[$i]['use_date'] = date('Y-m-d');
                }else{
                    $shop[$i]['use_date'] = date('Y-m-d', $order[0]['use_date']);
                }
            }
            $sumMoney='';
            $sumCar='';
            for($i=0;$i<count($shop);$i++){
                $sumMoney += ($shop[$i]['moneyNum']);
                $sumCar += ($shop[$i]['carNum']);
            }
            $this->assign('sumMoney',$sumMoney);
            $this->assign('sumCar',$sumCar);
            $this->assign('shop',$shop);
            $this->display('main');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 商店所有交易
     * 接口地址
     * http://192.168.0.104/SHS_Contact_PHP/index.php/Home/ServerProvider/shopRecord?verify_shop_id=1
     * @param verify_shop_id 商店id
     */
    public function shopRecord(){
        try {
            if($_SESSION["user"] == null){
                $this->assign('error','1');
                header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/login');
                exit;
            }
            $verify_shop_id = $_REQUEST['verify_shop_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $orderModel = M();
            $sql = 'SELECT od.id, od.total_fee FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.verify_shop_id='.$verify_shop_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    ORDER BY od.use_date DESC';
            $money = $orderModel->query($sql);
            $sumMoney = '';
            for($i=0;$i<count($money);$i++){
                $sumMoney += ($money[$i]['total_fee']);
            }
            $count = count($money);
            $sumCar = $count;

            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT od.id, ca.name, ca.plate_number, go.goods_name, sh.shop_name, od.total_fee, ca.car_type, od.use_date
                    FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.verify_shop_id='.$verify_shop_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $orderInfo = $orderModel->query($sql);
            for($i=0;$i<count($orderInfo);$i++){
                $orderInfo[$i]['use_date'] = date('Y-m-d',$orderInfo[$i]['use_date']);
            }
            if($sumMoney == ''){
                $sumMoney = '0';
            }
            $shop = M('biz_shop')->field('shop_name')->where('id='.$verify_shop_id)->find();
            $shop_name = $shop['shop_name'];
            $this->assign('verify_shop_id',$verify_shop_id);
            $this->assign('shop_name',$shop_name);
            $this->assign('sumMoney',$sumMoney);
            $this->assign('sumCar',$sumCar);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('orderInfo',$orderInfo);
            $this->display('shopRecord');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 商店当天所有交易
     * 接口地址
     * http://192.168.0.104/SHS_Contact_PHP/index.php/Home/ServerProvider/shopToday?verify_shop_id=1
     * @param verify_shop_id 商店id
     */
    public function shopToday(){
        try {
            if($_SESSION["user"] == null){
                $this->assign('error','1');
                header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/login');
                exit;
            }
            $today = strtotime(date('Y-m-d'));
            $verify_shop_id = $_REQUEST['verify_shop_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $orderModel = M();
            $sql = 'SELECT od.id, od.total_fee FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.verify_shop_id='.$verify_shop_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    AND od.use_date>'.$today.' ORDER BY od.use_date DESC';
            $money = $orderModel->query($sql);
            $sumMoney = '';
            for($i=0;$i<count($money);$i++){
                $sumMoney += ($money[$i]['total_fee']);
            }
            $count = count($money);
            $sumCar = $count;

            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT od.id, ca.name, ca.plate_number, go.goods_name, sh.shop_name, od.total_fee, ca.car_type, od.use_date
                    FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.verify_shop_id='.$verify_shop_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    AND od.use_date>'.$today.' ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $orderInfo = $orderModel->query($sql);
            for($i=0;$i<count($orderInfo);$i++){
                $orderInfo[$i]['use_date'] = date('Y-m-d',$orderInfo[$i]['use_date']);
            }
            if($sumMoney == ''){
                $sumMoney = '0';
            }
            $shop = M('biz_shop')->field('shop_name')->where('id='.$verify_shop_id)->find();
            $shop_name = $shop['shop_name'];
            $this->assign('verify_shop_id',$verify_shop_id);
            $this->assign('shop_name',$shop_name);
            $this->assign('sumMoney',$sumMoney);
            $this->assign('sumCar',$sumCar);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('orderInfo',$orderInfo);
            $this->display('shopToday');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 服务商家所有交易
     * 接口地址
     * http://192.168.0.104/SHS_Contact_PHP/index.php/Home/ServerProvider/allRecord?server_id=1
     * @param server_id 服务商id
     */
    public function allRecord(){
        try {
            if($_SESSION["user"] == null){
                $this->assign('error','1');
                header('Location:'.__ROOT__.'/index.php/Home/ServerProvider/login');
                exit;
            }
            $server_id = $_SESSION['user']['server_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $orderModel = M();
            $sql = 'SELECT od.id, od.total_fee FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.server_id='.$server_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    ORDER BY od.use_date DESC';
            $money = $orderModel->query($sql);
            $sumMoney = '';
            for($i=0;$i<count($money);$i++){
                $sumMoney += ($money[$i]['total_fee']);
            }
            $count = count($money);
            $sumCar = $count;
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT od.id, ca.name, ca.plate_number, go.goods_name, sh.shop_name, od.total_fee, ca.car_type, od.use_date
                    FROM biz_order od, biz_car ca, biz_shop sh, biz_shop_goods go
                    WHERE od.delete_flag=0 AND od.state=2 AND od.server_id='.$server_id.' AND od.verify_shop_id=sh.id AND od.car_id=ca.id AND od.goods_id=go.id
                    ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $orderInfo = $orderModel->query($sql);
            for($i=0;$i<count($orderInfo);$i++){
                $orderInfo[$i]['use_date'] = date('Y-m-d',$orderInfo[$i]['use_date']);
            }
            $this->assign('sumMoney',$sumMoney);
            $this->assign('sumCar',$sumCar);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('orderInfo',$orderInfo);
            $this->display('allRecord');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }
}