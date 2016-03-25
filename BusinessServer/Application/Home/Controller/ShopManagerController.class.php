<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class ShopManagerController extends Controller {

    public function index(){
        if($_SESSION['user']){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/main');
            exit;
        }
        $this->display('Login');
    }

    /**
     * @brief 管理系统登录页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/login
     */
    function login(){
        $username = $_POST['username'];
        $password = $_POST['password'];
        if(empty($username) || empty($password)){
            $this->assign('error','1');
            $this->display('Login');
            exit;
        }

        $model = M('biz_shop');
        $user = $model->where('username="'.$username.'" AND password="'.$password.'"')->find();

        //用户存在
        if($user){
            $_SESSION['user'] = $user;
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/main');
        }else{
            $this->assign('error','1');
            $this->display('Login');
        }
    }

    /**
     * @brief 主页 查询商家订单列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/shopServeRecord
     * @page 页码
     * @size 数量
     */
    public function main(){
        try{

            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];

            $user = $_SESSION['user'];
            if(empty($user)){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
                exit;
            }

            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $shopModel = M();

            $sql = 'SELECT od.id, ca.name, ca.plate_number, ca.mobile, od.use_date FROM biz_order od, biz_car ca
                    WHERE od.verify_shop_id='.$user['id'].' and od.state='.ORDER_HAS_USE.' AND od.car_id=ca.id
                    ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $list = $shopModel->query($sql);

            for($i=0; $i<count($list); $i++){
                $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
            }
            $this->assign('list',$list);
            $this->display('main');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 查询订单页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/searchOrder
     * @param out_trade_no 商家查询账单号
     */
    public function searchOrder(){

        $out_trade_no = $_REQUEST['out_trade_no'];
        if(empty($out_trade_no)){
            $this->assign('fail', true);
            $this->display('');
            exit;
        }

        $user = $_SESSION[''];
        $model = M('biz_order');
        $order = $model->where('server_id='.$user['server_id'].' AND out_trade_no="'.$out_trade_no.'" AND state='.ORDER_HAS_PAY)->find();
        if(empty($order)){
            $this->assign('fail', true);
            $this->display('');
            exit;
        }

        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
        $carModel = M('biz_car');
        $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

        $this->assign('user',$user);
        $this->assign('goods',$goods);
        $this->assign('car',$car);
        $this->assign('order',$order);
        $this->display('');

    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/searchOrder
     * @param order_id 订单号
     */
    public function confirmOrder(){

        $order_id = $_POST['order_id'];
        if(empty($order_id)){
            header('Location:');
            exit;
        }

        $user = $_SESSION['user'];
        $model = M('biz_order');
        $order = $model->where('server_id='.$user['server_id'].' AND id="'.$order_id.'" AND state='.ORDER_HAS_PAY)->find();
        if(empty($order)){
            header("Location: ");
            exit;
        }
        $order['state']=ORDER_HAS_USE;
        $order['use_date']=time();
        $order['update_date']=time();
        $order['verify_shop_id']=$user['id'];
        $ret = $model->save($order);
        if($ret){
            header("Location: ");
        }else{
            header('Location:');
        }
    }

    /**
     * @brief 服务详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/orderDetails?order_id=1
     * @param order_id 订单ID
     */
    public function orderDetails(){
        try{
            $order_id = $_REQUEST['order_id'];
            if(empty($order_id)){
                returnJson(0,'订单ID不能为空！');
                exit;
            }

            $user = $_SESSION['user'];
            if(empty($user)){
                exit;
            }

            //订单必须是同一服务商才能查询
            $orderModel = M('biz_order');
            $order = $orderModel->where('delete_flag=0 AND server_id='.$user['server_id'].' AND id="'.$order_id.'"')->find();
            if(empty($order)){

                exit;
            }

            $goodsModel = M('biz_shop_goods');
            $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
            $carModel = M('biz_car');
            $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

            $this->assign('user',$user);
            $this->assign('goods',$goods);
            $this->assign('car',$car);
            $this->assign('order',$order);
            $this->display('');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

}

