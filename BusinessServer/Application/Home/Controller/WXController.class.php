<?php
namespace Home\Controller;
use Think\Controller;

require THINK_PATH.'Library/Vendor/wxpay/lib/WxPay.Api.php';
require THINK_PATH.'Library/Vendor/wxpay/WxPay.JsApiPay.php';

Vendor('alisdk.TopSdk');
Vendor('jssdk');
Vendor('wxpay.notify');


class WXController extends Controller {
    //wxd5db3b57ffdfafb3 2ccd9fe700dda9b0e9db40212dba1f4b 测试用
    private $WX_APPID = 'wx5764fdc7f223e062';
    private $WX_APPSecret = 'ef6373955987b110fef9c0108ae15a02';

    public function index(){

    }

    /**
     * @brief 跳到关注页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/subscribeWX
     *
     */
    public function subscribeWX(){

        $this->display('subscribeWX');
    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userVerify
     * @param collectID 收藏id
     */
    public function userVerify(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_URL_PREFIX."userVerify&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $model = M();
        $sql = 'SELECT user_id FROM biz_user_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
        $user = $model->query($sql)[0];

        //如果系统中存在这个人 跳转到主页
        if(!empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userHome");
            exit;
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->display('userVerify');

    }

    /**
     * @brief 用户验证接口
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/usepass
     * @param collectID 收藏id
     */
    public function userpass(){

        $username = $_REQUEST['username'];
        $userModel = M('biz_user_info');
        $user = $userModel->field('id')->where('username='.$username)->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        if($user){
            $this->assign('username',$username);
            $this->display('userLogin');
        }else{
            $this->assign('username',$username);
            $this->display('userRegister');
        }
    }

    /**
     * @brief 用户登录
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userLogin
     * @param collectID 收藏id
     */
    public function userLogin(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];

        $model = M();
        $sql = 'SELECT user_id FROM biz_user_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
        $user = $model->query($sql)[0];

        //如果系统中存在这个人 跳转到主页
        if(!empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userHome");
            exit;
        }

        $username = $_REQUEST['username'];
        $password = md5($_REQUEST['password']);
        $userModel = M('biz_user_info');
        $user = $userModel->field('id')->where('username='.'"'.$username.'"'.' and password='.'"'.$password.'"')->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        if($user){
            //绑定微信
            $user['wx_open_id'] = $openID;
            $extraRet = $userModel->save($user);
            if($extraRet){
                header("Location: ".HTTP_URL_PREFIX."userHome");
            }else{
                $this->assign('username',$username);
                $this->display('userLogin');
            }
        }else{
            $this->assign('username',$username);
            $this->display('userLogin');
        }

    }

    /**
     * @brief 发送验证码
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/requertSms
     * @param phone_num 手机号
     */
    public function requestSms(){

        try{
            $phone_num = $_REQUEST['phone_num'];
            if(empty($phone_num)){
                returnJson(0,"手机号不能为空！");
                return;
            }else{

                //判断是否被注册
                $findUser = M('biz_user_info');
                $user = $findUser->where(array('username='.$phone_num))->find();
                if($user){
                    returnJson(0 ,'该手机已被申请!');
                    return;
                }

                $verify = get_rand_code(4, 1);
                $c = new \TopClient();
                $c->format = 'json';
                $c->appkey = '23298649';
                $c->secretKey = '2baa68b8ae1790f1512c585d576d19b6';
                $req = new \AlibabaAliqinFcSmsNumSendRequest();
                $req->setSmsType("normal");
                $req->setSmsFreeSignName("注册验证");
                $req->setSmsParam('{"code":"'.$verify.'","product":"商务圈"}');
                $req->setRecNum($phone_num);
                $req->setSmsTemplateCode("SMS_4445955");
                $resp = $c->execute($req);
                //发送成功
                if($resp->result->success == true) {
                    $add = D('biz_sms');
                    $data = array();
                    $data['phone_num'] = $phone_num;
                    $data['verify_code']  = $verify;
                    $data['add_date'] = time();
                    $add->add($data);
                    returnJson(1,'验证码已发送至您的手机！','');

                }else{
                    //失败
                    returnJson(0,"发送失败！");
                }

            }

        }catch (Exception $e){

            returnJson(0,"数据异常！");
        }

    }

    /**
     * @brief 用户注册
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userRegister
     * @param collectID 收藏id
     */
    public function userRegister(){

        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $verify = $_REQUEST['verify'];

        //先授权获取openID
        $code = $_REQUEST['code'];
        if(!empty($code)){

            $userModel = M('biz_user_info');
            $user = $userModel->field('id')->where('username='.$username)->find();
            //用户存在
            if($user){
                header("Location: ".HTTP_URL_PREFIX."userHome");
                return;
            }

            $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
            $openID = json_decode($content)->openid;
            if(empty($openID)){
                echo '不好意思，您微信未授权openID';
                return;
            }
            //openID存入
            $_SESSION['open_id'] = $openID;
        }else{
            $url = HTTP_URL_PREFIX."userRegister?username=".$username."&verify=".$verify."&password=".$_REQUEST['password'];
            header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
            exit;
        }

        $model = M();
        $sql = 'SELECT user_id FROM biz_user_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
        $user = $model->query($sql)[0];

        //如果系统中存在这个人 跳转到主页
        if(!empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userHome");
            exit;
        }

        $verifyModel = M();
           //查看是否验证成功
        $sql = 'SELECT * FROM biz_sms WHERE phone_num='.$username.'
        and verify_code='.$verify.' and delete_flag=0 and add_date>'.(time()-180);
        $data = $verifyModel->query($sql)[0];

        //验证成功注册
        if($data['id'] > 0){
            //注册逻辑
            $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.json_decode($content)->access_token.'&openid='.$openID;
            $msg = file_get_contents($msgUrl);
            $wxUser = json_decode($msg);
            $user = array('username'=>$username, 'password'=>md5($password), 'name'=>$wxUser->nickname,
                            'phone_num'=>$username, 'wx_open_id'=>$openID, 'add_date'=>time());
            $userModel = M('biz_user_info');
            $retID = $userModel->add($user);

            //wxJs签名
            $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage',$signPackage);

            if($retID){
                //注册成功
                header("Location: ".HTTP_URL_PREFIX."applyCarView");
            }else{
                $this->assign('verifyError',2);
                $this->assign('username',$username);
                $this->display('userRegister');
            }
        }else{
            //1是验证码错误 2是创建失败
            $this->assign('verifyError',1);
            $this->assign('username',$username);
            $this->display('userRegister');
        }

    }

    /**
     * @brief 跳到主页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userHome
     *
     */
    public function userHome(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('mobile', $user['mobile']);
        $this->display('userHome');
    }

    /**
     * @brief 跳到记录主页
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/recordHome
     *
     */
    public function recordHome(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $model = M();
        $sql = 'SELECT o.id, s.shop_image_thumb, c.mobile, s.shop_name, o.use_date FROM biz_car c, biz_order o, biz_shop s
                WHERE o.user_id='.$user['user_id'].' AND o.shop_id=s.id AND o.car_id=c.id AND o.delete_flag=0 AND o.state='.ORDER_HAS_USE;
        $list = $model->query($sql);
        for($i=0; $i<count($list); $i++){
            $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
        }
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('list',$list);
        $this->display('record');
    }



    /**
     * @brief 跳到特权主页
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/stewardHome
     *
     */
    public function stewardHome(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->display('steward');
    }

    /**
     * @brief 跳到特权主页
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/privilegeHome
     *
     */
    public function privilegeHome(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->display('privilege');
    }


    /**
     * @brief 跳到到增加汽车页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userHome
     *
     */
    public function applyCarView(){
        //处理选择的车型
        $brand = $_REQUEST['brand'];
        $type = $_REQUEST['type'];

        if($brand !== null){
            //读取本地文件
            $cars = file_get_contents('cars.json');
            $carsList = json_decode($cars, true);

            $car_type_code = '000'.$brand.'000'.$type;

            $_SESSION['car_type'] = $carsList[$brand]['name'].' '.$carsList[$brand]['sub'][$type];
            $_SESSION['car_type_code'] = $car_type_code;
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->display('addCar');

    }

    /**
     * @brief 增加汽车操作
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/userHome
     * @param name 姓名
     * @param mobile 电话号
     * @param plate_number 车牌号
     * @param vehicle_number 车架号
     * @param car_type 车类型
     */
    public function applyCar(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $name = $_REQUEST['name'];
        $mobile = $_REQUEST['mobile'];
        $plate_number = '粤B'.$_REQUEST['plate_number'];
        $vehicle_number = $_REQUEST['vehicle_number'];
        $car_type = $_REQUEST['car_type'];
        $car_type_code = $_REQUEST['car_type_code'];

        //|| empty($car_type)
        if(empty($name) || empty($mobile) || empty($plate_number) || empty($car_type)){
            header("Location: ".HTTP_URL_PREFIX."addCar?empty=1");
            exit;
        }

        //增加
        $apply = array('user_id'=>$user['user_id'],'name'=>$name, 'mobile'=>$mobile, 'plate_number'=>$plate_number
                       ,'vehicle_number'=>$vehicle_number, 'car_type'=>$car_type, 'state'=>1, 'car_type_code'=>$car_type_code, 'add_date'=>time());
        $carModel = M('biz_car');
        $ret = $carModel->add($apply);

        $_SESSION['name'] = '';
        $_SESSION['mobile'] = '';
        $_SESSION['plate_number'] = '';
        $_SESSION['vehicle_number'] = '';
        $_SESSION['car_type'] = '';
        $_SESSION['car_type_code'] = '';

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        if($ret){
            header("Location: ".HTTP_URL_PREFIX."myCars?isAdd=1");
        }else{
            header("Location: ".HTTP_URL_PREFIX."userHome");
        }

    }

    /**
     * @brief 我的爱车页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/myCars
     *
     */
    public function myCars(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $carModel = M('biz_car');
        $list = $carModel->where('delete_flag=0 AND user_id="'.$user['user_id'].'"')->select();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('isAdd',$_REQUEST['isAdd']);
        $this->assign('list',$list);
        $this->assign('mobile', $user['mobile']);
        $this->display('myCars');
    }

    /**
     * @brief 车详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/carDetail
     * @param car_id 车id
     */
    public function carInfo(){

        $car_id = $_REQUEST['car_id'];

        $carModel = M('biz_car');
        $car = $carModel->where('delete_flag=0 AND id="'.$car_id.'"')->find();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('car',$car);
        $this->display('carInfo');
    }

    /**
     * @brief 车类型列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/carsTypeList
     * @param car_id 车id
     */
    public function carBrands(){

        //放到session里
        $_SESSION['name'] = $_REQUEST['name'];
        $_SESSION['mobile'] = $_REQUEST['mobile'];
        $_SESSION['plate_number'] = $_REQUEST['plate_number'];
        $_SESSION['vehicle_number'] = $_REQUEST['vehicle_number'];

        $cars = file_get_contents('cars.json');
        $carsList = json_decode($cars, true);

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('cars',$carsList);
        $this->display('carBrands');
    }

    /**
     * @brief 车类型详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/carDetailTypeList
     * @param car_id 车id
     */
    public function carBrandTypes(){

        $cars = file_get_contents('cars.json');

        $carsList = json_decode($cars, true);
        $type = $_REQUEST["type"];

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('types', $carsList[$type]["sub"]);
        $this->assign('brand', $type);
        $this->display('carBrandTypes');
    }

    /**
     * @brief 商店列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getShops
     *
     */
    public function getShops(){

        $model = M('biz_shop');
        $shops = $model->where('delete_flag=0')->select();
        $this->assign('shops', $shops);
        $this->display('carStore');

    }

    /**
     * @brief 商店详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getShopDetail
     * @param shop_id 车id
     */
    public function getShopDetail(){

        $shop_id = $_REQUEST['shop_id'];

        $model = M('biz_shop');
        $shop = $model->where('delete_flag=0 AND id='.$shop_id)->find();

        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND shop_id='.$shop_id)->find();

        $this->assign('shop', $shop);
        $this->assign('goods', $goods);
        $this->display('shopWithin');
    }

    /**
     * @brief 选择我的车
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/choiceMyCar
     * @param shop_id 商店ID
     * @param goods_id 商品ID
     *
     */
    public function choiceMyCar(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $carModel = M('biz_car');
        $list = $carModel->where('state='.CAR_CHECK_OK.' AND delete_flag=0 AND user_id="'.$user['user_id'].'"')->select();

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('list',$list);
        $this->assign('shop_id', $_REQUEST['shop_id']);
        $this->assign('goods_id', $_REQUEST['goods_id']);
        $this->display('selectCar');
    }

    /**
     * @brief 创建订单
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/createOrder
     * @param shop_id 商店ID
     * @param goods_id 商品ID
     * @param car_id 汽车ID
     */
    public function createOrder(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $shop_id = $_REQUEST['shop_id'];
        $goods_id = $_REQUEST['goods_id'];
        $car_id = $_REQUEST['car_id'];

        $model = M('biz_shop');
        $shop = $model->where('delete_flag=0 AND id='.$shop_id)->find();
        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND id='.$goods_id)->find();
        $carModel = M('biz_car');
        $car = $carModel->where('delete_flag=0 AND id='.$car_id)->find();

        //内部订单生成规则 goodsID+user_id+time()
        $bizOrder = $goods_id.$user['user_id'].time();

        $openId = $_SESSION['open_id'];

        $tools = new \JsApiPay();
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("洗车");
        $input->SetAttach("深圳");
        $input->SetOut_trade_no($bizOrder);
        $input->SetTotal_fee($goods['discount_price']*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($bizOrder);
        $input->SetNotify_url("http://a.pinweihuanqiu.com/BusinessServer/index.php/Home/WX/wxNotify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        $orderModel = M('biz_order');
        $newOrder = array('shop_id'=>$shop_id,'goods_id'=>$goods_id,'user_id'=>$user['user_id'],'mch_id'=>\WxPayConfig::MCHID,
                          'open_id'=>$openId, 'original_price'=>$goods['original_price'], 'total_fee'=>$goods['discount_price'],
                          'out_trade_no'=>$bizOrder, 'car_id'=>$car_id, 'add_date'=>time(), 'type'=>1, 'server_id'=>$shop['server_id']);
        $ret = $orderModel->add($newOrder);
        if(!$ret){
            echo '订单生成失败';
            exit;
        }
        $newOrder['OFF'] = number_format(10*$newOrder['total_fee']/$newOrder['original_price'], 1);
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('shop',$shop);
        $this->assign('goods',$goods);
        $this->assign('car',$car);
        $this->assign('order',$newOrder);
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->display('ticketDetails');
    }

    /**
     * @brief 获取订单列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getOrderList
     *
     */
    public function getOrderList(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $orderModel = M();
        $sql = 'SELECT o.id, s.shop_name, o.state, FORMAT(10*o.total_fee/o.original_price,1) OFF, s.shop_image_thumb
                FROM biz_order o, biz_shop s
                WHERE s.id=o.shop_id AND (state='.ORDER_HAS_PAY.' OR state='.ORDER_HAS_USE.')
                AND o.delete_flag=0 AND o.user_id="'.$user['user_id'].'" ORDER BY o.state,o.add_date DESC';
        $list = $orderModel->query($sql);
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('list',$list);
        $this->display('myTicket');
    }

    /**
     * @brief 支付成功
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/paySuccessList
     *
     */
    public function paySuccessAndGetOrderList(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $orderModel = M('biz_order');
        $order = $orderModel->where('out_trade_no="'.$_REQUEST['out_trade_no'].'"')->find();
        if($order){
            $order['state'] = ORDER_HAS_PAY;
            $order['update_date'] = time();
            $order['pay_date'] = time();
            $orderModel->save($order);
        }

        $sql = 'SELECT o.id, s.shop_name, o.state, FORMAT(10*o.total_fee/o.original_price,1) OFF, s.shop_image_thumb
                FROM biz_order o, biz_shop s
                WHERE s.id=o.shop_id AND (state='.ORDER_HAS_PAY.' OR state='.ORDER_HAS_USE.')
                AND o.delete_flag=0 AND o.user_id="'.$user['user_id'].'" ORDER BY o.state,o.add_date DESC';
        $list = $orderModel->query($sql);
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('list',$list);
        $this->display('myTicket');
    }



    /**
     * @brief 获取订单详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getOrderDetail
     * @param order_id
     */
    public function getOrderDetail(){

        $order_id = $_REQUEST['order_id'];

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        //订单详情 商家信息 商品信息 购买车辆
        $orderModel = M('biz_order');
        $order = $orderModel->where('delete_flag=0 AND user_id="'.$user['user_id'].'" AND id="'.$order_id.'"')->find();
        $model = M('biz_shop');
        $shop = $model->where('delete_flag=0 AND id='.$order['shop_id'])->find();
        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND id='.$order['goods_id'])->find();
        $carModel = M('biz_car');
        $car = $carModel->where('delete_flag=0 AND id='.$order['car_id'])->find();

        $order['use_date'] = date('Y-m-d', $order['use_date']);
        $order['OFF'] = number_format(10*$order['total_fee']/$order['original_price'], 1);

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('shop',$shop);
        $this->assign('goods',$goods);
        $this->assign('car',$car);
        $this->assign('order',$order);
        $this->assign('isDetail',1);

        $this->display('ticketDetails');
    }


    /**
     * @brief 单辆车消费记录列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getCarRecordList
     * @param car_id 汽车id
     */
    public function getCarRecordList(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $car_id = $_REQUEST['car_id'];

        $model = M();
        $sql = 'SELECT o.id, s.shop_image_thumb, c.mobile, s.shop_name, o.use_date FROM biz_car c, biz_order o, biz_shop s
                WHERE c.id='.$car_id.' AND o.user_id='.$user['user_id'].' AND o.shop_id=s.id AND o.car_id=c.id AND o.delete_flag=0 AND o.state='.ORDER_HAS_USE;;
        $list = $model->query($sql);

        for($i=0; $i<count($list); $i++){
            $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('list',$list);
        $this->display('singleRecord');
    }

    /**
     * @brief 记录列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/getRecordList
     * @param order_id 订单id
     */
    public function getRecordDetail(){

        $user = getWXUser();
        //如果系统中不存在这个人跳转到注册
        if(empty($user)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $order_id = $_REQUEST['order_id'];

        $model = M();
        $sql = 'SELECT * FROM biz_car c, biz_order o, biz_shop s
                WHERE o.id='.$order_id.' AND o.user_id='.$user['user_id'].' AND o.shop_id=s.id
                AND o.car_id=c.id AND o.delete_flag=0';
        $record = $model->query($sql)[0];

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('record',$record);
        $this->display('consumptionDetails');
    }


    /**
     * @brief 微信支付回调通知
     */
    public function wxNotify(){

        $notify = new \PayNotifyCallBack();
        $notify->Handle(false);

        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = \WxPayResults::Init($xml);

        $out_trade_no = $result['out_trade_no'];
        $model = M('biz_order');
        $order = $model->where('out_trade_no="'.$out_trade_no.'"')->find();
        //订单成功
        if($order){
            $order['transaction_id'] = $result["transaction_id"];
            $order['nonce_str'] = $result["nonce_str"];
            $order['sign'] = $result["sign"];
            $order['state'] = ORDER_HAS_PAY;
            $order['update_date'] = time();
            $order['pay_date'] = time();
            $model->save($order);
        }
    }


    public function queryOrder(){

        //订单号
        $transaction_id = $_GET['orderID'];
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);
        $data = \WxPayApi::orderQuery($input);
        print_r($data);
    }

    public function orderTest(){

        $openId = $_SESSION['open_id'];

        $tools = new \JsApiPay();
        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no(time());
        $input->SetTotal_fee("1");
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url("http://a.pinweihuanqiu.com/BusinessServer/index.php/Home/WX/wxNotify");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        print_r($jsApiParameters);
        $this->assign('jsApiParameters', $jsApiParameters);
        $this->display('ticketDetails');
    }

}

