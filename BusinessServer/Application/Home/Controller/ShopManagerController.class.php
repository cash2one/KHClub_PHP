<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class ShopManagerController extends Controller {

    public function index(){
        if(isset($_SESSION['shop'])){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/main');
            exit;
        }
        header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
    }

    function login(){
        if($_SESSION['shop']){
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
    function loginVerify(){

        if(!isset($_POST['username']) && empty($_POST['username']) && !isset($_POST['password']) && empty($_POST['password'])){
            $this->assign('error','1');
            $this->display('Login');
            exit;
        }

        $username = $_POST['username'];
        $password = $_POST['password'];

        $model = M('biz_shop');
        $user = $model->where('username="'.$username.'" AND password="'.$password.'"')->find();

        //用户存在
        if($user){
            $_SESSION['shop'] = $user;
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/main');
        }else{
            $this->assign('error','1');
            $this->display('Login');
        }
    }

    /**
     * @brief 主页 查询商家当日订单列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/shopServeRecord
     * @page 页码
     * @size 数量
     */
    public function main(){
        try{

            if(!isset($_SESSION['shop'])){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
                exit;
            }
            $user = $_SESSION['shop'];

            if(!isset($_REQUEST['page'])){
                $page = 1;
            }else{
                $page = $_REQUEST['page'];
            }

            $size = 10;

            $start = ($page-1)*$size;
            $end   = $size;
            $shopModel = M();

            $t = time();
            $startTime = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
            $endTime = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));

            $sql = 'SELECT COUNT(1) count FROM biz_order
                    WHERE verify_shop_id='.$user['id'].' and state='.ORDER_HAS_USE.'
                    AND use_date > '.$startTime.' AND use_date < '.$endTime.'
                    ORDER BY use_date';
            $count = $shopModel->query($sql)['count'];
            if($count == false || $count == 0){
                $count = 1;
            }
            $page_count  = ceil($count/$size);

            $sql = 'SELECT od.id, ca.name, ca.plate_number, ca.mobile, od.use_date, od.total_fee
                    FROM biz_order od LEFT JOIN biz_car ca ON(od.car_id=ca.id)
                    WHERE od.verify_shop_id='.$user['id'].' and od.state='.ORDER_HAS_USE.'
                    AND use_date > '.$startTime.' AND use_date < '.$endTime.'
                    ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $list = $shopModel->query($sql);

            for($i=0; $i<count($list); $i++){
                $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
            }

            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('list',$list);
            $this->assign('user', $user);
            $this->display('main');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 查询商家全部订单列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/shopServeRecord
     * @page 页码
     * @size 数量
     */
    public function allRecord(){
        try{

            if(!isset($_SESSION['shop'])){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
                exit;
            }
            $user = $_SESSION['shop'];

            if(!isset($_REQUEST['page'])){
                $page = 1;
            }else{
                $page = $_REQUEST['page'];
            }
            $size = 10;
            $start = ($page-1)*$size;
            $end   = $size;
            $shopModel = M();

            $sql = 'SELECT COUNT(1) count, SUM(total_fee*100)/100 total  FROM biz_order
                    WHERE verify_shop_id='.$user['id'].' and state='.ORDER_HAS_USE.'
                    ORDER BY use_date';
            $quanlityAndtotal = $shopModel->query($sql)[0];

            $count = $quanlityAndtotal['count'];
            $total = $quanlityAndtotal['total'];

            if(empty($count)){
                $count = 0;
                $total = 0;
                $page_count = 1;
            }else{
                $page_count = ceil($count/$size);
            }

            $sql = 'SELECT od.id, ca.name, ca.plate_number, ca.car_type, ca.mobile, od.use_date, od.total_fee
                    FROM biz_order od LEFT JOIN biz_car ca ON(od.car_id=ca.id)
                    WHERE od.verify_shop_id='.$user['id'].' and od.state='.ORDER_HAS_USE.'
                    ORDER BY od.use_date DESC LIMIT '.$start.','.$end;
            $list = $shopModel->query($sql);

            for($i=0; $i<count($list); $i++){
                $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
            }

            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('count',$count);
            $this->assign('total',$total);
            $this->assign('list',$list);
            $this->assign('user', $user);
            $this->display('allRecord');

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

        if(!isset($_REQUEST['out_trade_no']) && empty($_REQUEST['out_trade_no'])){
            $this->assign('fail', true);
            $this->display('checkOrder');
            exit;
        }
        $out_trade_no = $_REQUEST['out_trade_no'];

        if(!isset($_SESSION['shop'])){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
            exit;
        }
        $user = $_SESSION['shop'];

        //优惠券处理 查看是否是全免券
        if(substr($out_trade_no,0,2) == 'yh'){
            $model = M('biz_coupon');
            $coupon = $model->where('delete_flag=0 AND state=0 AND type=1 AND coupon_code="'.substr($out_trade_no, 2).'"')->find();
            if($coupon){

                $coupon['id'] = 'yh'.$coupon['id'];
                $coupon['total_fee'] = '无';
                $coupon['pay_date'] = date('Y-m-d', $coupon['send_date']);

                $this->assign('user',array('shop_name'=>'品位环球'));
                $this->assign('goods',array('goods_name'=>'精洗'));
                $this->assign('car',array('name'=>'无', 'car_type'=>'无', 'plate_number'=>'无'));
                $this->assign('order',$coupon);
                $this->display('checkOrder');
            }else{
                $this->assign('fail', true);
                $this->display('checkOrder');
                exit;
            }
        }else{
            $model = M('biz_order');
            $order = $model->where('server_id='.$user['server_id'].' AND out_trade_no="'.$out_trade_no.'" AND state='.ORDER_HAS_PAY)->find();
            if(empty($order)){
                $this->assign('fail', true);
                $this->display('checkOrder');
                exit;
            }
            $order['pay_date'] = date('Y-m-d', $order['pay_date']);

            $goodsModel = M('biz_shop_goods');
            $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
            $carModel = M('biz_car');
            $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

            $this->assign('user',$user);
            $this->assign('goods',$goods);
            $this->assign('car',$car);
            $this->assign('order',$order);
            $this->display('checkOrder');
        }
    }

    /**
     * @brief 确认订单
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/ShopManager/confirmOrder
     * @param order_id 订单号
     */
    public function confirmOrder(){

        if(!isset($_POST['order_id']) && empty($_POST['order_id'])){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/searchOrder');
            exit;
        }
        $order_id = $_POST['order_id'];

        if(!isset($_SESSION['shop'])){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
            exit;
        }
        $user = $_SESSION['shop'];

        //优惠券处理 查看是否是全免券
        if(substr($order_id,0,2) == 'yh'){
            $model = M('biz_coupon');
            $coupon = $model->where('delete_flag=0 AND state=0 AND type=1 AND id="'.substr($order_id, 2).'"')->find();

            if($coupon){
                $coupon['use_date'] = time();
                $coupon['state'] = 1;

                $goodsModel = M('biz_shop_goods');
                $goods = $goodsModel->where('delete_flag=0 AND shop_id='.$user['id'])->find();

                $newOrder = array('shop_id'=>$user['id'],'goods_id'=>$goods['id'],'user_id'=>$coupon['user_id'],'verify_shop_id'=>$user['id'],
                                'original_price'=>$goods['original_price'], 'total_fee'=>$goods['discount_price'],'state'=>ORDER_HAS_USE,
                                'out_trade_no'=>$goods['id'].$coupon['user_id'].time(), 'car_id'=>'0', 'add_date'=>time(),
                                'use_date'=>time(), 'type'=>0, 'server_id'=>$user['server_id'], 'coupon_id'=>$coupon['id']);
                $orderModel = M('biz_order');

                $model->startTrans();
                $couponRet = $model->save($coupon);
                //统一更新订单和优惠券
                if($couponRet){
                    $orderRet = $orderModel->add($newOrder);
                    if($orderRet){
                        header('Location:'.__ROOT__.'/index.php/Home/ShopManager/orderDetails?order_id='.$orderRet);
                        $model->commit();
                        exit;
                    }
                }
                $model->rollback();
            }
            //失败
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/searchOrder');
            exit;
        }

        $model = M('biz_order');
        $order = $model->where('server_id='.$user['server_id'].' AND id="'.$order_id.'" AND state='.ORDER_HAS_PAY)->find();
        if(empty($order)){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/searchOrder');
            exit;
        }
        $order['state']=ORDER_HAS_USE;
        $order['use_date']=time();
        $order['update_date']=time();
        $order['verify_shop_id']=$user['id'];
        $ret = $model->save($order);
        if($ret){
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/orderDetails?order_id='.$order_id);
        }else{
            header('Location:'.__ROOT__.'/index.php/Home/ShopManager/searchOrder');
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

            if(!isset($_REQUEST['order_id']) && empty($_REQUEST['order_id'])){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/searchOrder');
                exit;
            }
            $order_id = $_REQUEST['order_id'];

            if(!isset($_SESSION['shop'])){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/login');
                exit;
            }
            $user = $_SESSION['shop'];

            //订单必须是同一服务商才能查询
            $orderModel = M('biz_order');
            $order = $orderModel->where('delete_flag=0 AND server_id='.$user['server_id'].' AND id="'.$order_id.'"')->find();
            if(empty($order)){
                header('Location:'.__ROOT__.'/index.php/Home/ShopManager/main');
                exit;
            }

            $order['use_date'] = date('Y-m-d', $order['use_date']);

            $goodsModel = M('biz_shop_goods');
            $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
            $carModel = M('biz_car');
            $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

            $this->assign('user',$user);
            $this->assign('goods',$goods);
            $this->assign('car',$car);
            $this->assign('order',$order);
            $this->display('orderDetail');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

}

