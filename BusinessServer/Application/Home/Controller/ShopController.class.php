<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class ShopController extends Controller {

    private $WX_APPID = 'wx5764fdc7f223e062';
    private $WX_APPSecret = 'ef6373955987b110fef9c0108ae15a02';

    public function index(){

    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/shopEnter
     */
    public function shopEnter(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            $code = $_REQUEST['code'];
            if(!empty($code)){
                $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
                $openID = json_decode($content)->openid;
                if(empty($openID)){
                    echo '不好意思，您微信未授权openID';
                    return;
                }
                //openID存入
                $_SESSION['open_id'] = $openID;
            }else{
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_SHOP_URL_PREFIX."shopEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $shop = getShopUser();

        //如果系统中存在这个商家
        if(!empty($shop)){
            header("Location: ".HTTP_SHOP_URL_PREFIX."shopHome");
            exit;
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->display('shopVerify');

    }

    /**
     * @brief 用户验证接口
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/shopVerify
     * @param collectID 收藏id
     */
    public function shopVerify(){

        $username = $_REQUEST['username'];
        $userModel = M('biz_shop');
        $user = $userModel->field('id')->where('username='.$username)->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        if($user){
            $this->assign('username',$username);
            $this->display('shopLogin');
        }else{
            echo '该用户不存在';
        }
    }

    /**
     * @brief 用户登录
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/shopLogin
     * @param collectID 收藏id
     */
    public function shopLogin(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];

        $shop = getShopUser();
        //如果系统中存在这个商家
        if(!empty($shop)){
            header("Location: ".HTTP_SHOP_URL_PREFIX."shopHome");
            exit;
        }

        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $shopModel = M('biz_shop');
        $shop = $shopModel->field('id')->where('username='.'"'.$username.'"'.' and password='.'"'.$password.'"')->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        if($shop){
            //绑定微信
            $shop['wx_open_id'] = $openID;
            $extraRet = $shopModel->save($shop);
            if($extraRet){
                header("Location: ".HTTP_SHOP_URL_PREFIX."shopHome");
            }else{
                $this->assign('username',$username);
                $this->display('shopLogin');
            }
        }else{
            $this->assign('username',$username);
            $this->display('shopLogin');
        }
    }

    /**
     * @brief 用户登录
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/shopLogin
     * @param collectID 收藏id
     */
    public function shopHome(){

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->display('shopHome');
    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/searchOrder
     * @param out_trade_no 商家查询账单号
     */
    public function searchOrder(){

        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            $code = $_REQUEST['code'];
            if(!empty($code)){
                $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
                $openID = json_decode($content)->openid;
                if(empty($openID)){
                    echo '不好意思，您微信未授权openID';
                    return;
                }
                //openID存入
                $_SESSION['open_id'] = $openID;
            }else{
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_SHOP_URL_PREFIX."shopEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $out_trade_no = $_REQUEST['out_trade_no'];

        $shop = getShopUser();
        $model = M('biz_order');
        $order = $model->where('server_id='.$shop['server_id'].' AND out_trade_no="'.$out_trade_no.'" AND state='.ORDER_HAS_PAY)->find();
        if(empty($order)){
            $this->display('searchOrderFail');
            exit;
        }

        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
        $carModel = M('biz_car');
        $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('shop',$shop);
        $this->assign('goods',$goods);
        $this->assign('car',$car);
        $this->assign('order',$order);
        $this->display('searchOrderDetails');

    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/searchOrder
     * @param order_id 订单号
     */
    public function confirmOrder(){

        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            $code = $_REQUEST['code'];
            if(!empty($code)){
                $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
                $openID = json_decode($content)->openid;
                if(empty($openID)){
                    echo '不好意思，您微信未授权openID';
                    return;
                }
                //openID存入
                $_SESSION['open_id'] = $openID;
            }else{
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_SHOP_URL_PREFIX."shopEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $order_id = $_POST['order_id'];
        if(empty($order_id)){
            header("Location: ".HTTP_SHOP_URL_PREFIX."shopHome");
            exit;
        }

        $shop = getShopUser();
        $model = M('biz_order');
        $order = $model->where('server_id='.$shop['server_id'].' AND id="'.$order_id.'" AND state='.ORDER_HAS_PAY)->find();
        if(empty($order)){
            header("Location: ".HTTP_SHOP_URL_PREFIX."shopHome");
            exit;
        }
        $order['state']=ORDER_HAS_USE;
        $order['use_date']=time();
        $order['update_date']=time();
        $order['verify_shop_id']=$shop['id'];
        $ret = $model->save($order);
       if($ret){
           header("Location: ".HTTP_SHOP_URL_PREFIX."shopServeRecord");
       }else{
           $this->display('searchOrderFail');
       }
    }

    /**
     * @brief 查找商家已经服务过的用户
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXManager/shopServeRecord?goods_id=1
     */
    public function shopServeRecord(){
        try{
            $shop = getShopUser();
            $shopModel = M();
            $sql = 'SELECT od.id, ca.name, ca.plate_number, ca.mobile FROM biz_order od, biz_car ca
                    WHERE od.verify_shop_id='.$shop['id'].' and od.state='.ORDER_HAS_USE.' AND od.car_id=ca.id ORDER BY od.use_date DESC';
            $userInfo = $shopModel->query($sql);
            $this->assign('userInfo',$userInfo);
            $this->display('tradeRecord');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 服务详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXManager/ServeDetails?order_id=1
     * @param order_id 订单ID
     */
    public function serveDetails(){
        try{
            $order_id = $_REQUEST['order_id'];
            if(empty($order_id)){
                returnJson(0,'订单ID不能为空！');
                return;
            }

            $shop = getShopUser();

            $orderModel = M('biz_order');
            $order = $orderModel->where('delete_flag=0 AND id="'.$order_id.'"')->find();

            $goodsModel = M('biz_shop_goods');
            $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
            $carModel = M('biz_car');
            $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

            //wxJs签名
            $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage',$signPackage);

            $this->assign('shop',$shop);
            $this->assign('goods',$goods);
            $this->assign('car',$car);
            $this->assign('order',$order);
            $this->display('orderDetails');


        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }
}

