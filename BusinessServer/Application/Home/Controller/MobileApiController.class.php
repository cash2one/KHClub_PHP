<?php
namespace Home\Controller;
use Think\Controller;
use Think\Exception;

require THINK_PATH.'Library/Vendor/wxpay/lib/WxPay.Api.php';
require THINK_PATH.'Library/Vendor/wxpay/WxPay.JsApiPay.php';

Vendor('alisdk.TopSdk');
import('Org.JPush.JPush');
Vendor('wxpay.appNotify');

class MobileApiController extends Controller{
    //阿里大鱼KEY
    private $APPKEY = '23338076';
    private $SECRET = 'dc124fbe1bf22b515a8db2c6db660f43';

    //极光推送KEY
    private $app_key = '64323ace631a71b5ade21733';
    private $master_secret = '1e9c2da1dc363f072e0d5ccb';

    public function index(){

        $this->display();
    }
//////////////////////////////////////.用户注册登录.//////////////////////////////////////////////////////////////////

    /**
     * @brief 是否存在用户
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/isUser?username=18697942051
     * @param username 用户名(手机号码)
     */
    public function isUser(){
        try{
            $username = $_REQUEST['username'];
            if(empty($username)) {
                returnJson(0, "username can't be empty！");
                return;
            }
            //判断是否被注册
            $findUser = M('biz_user_info');
            $user = $findUser->where(array('username='.$username))->find();
            //1跳转到填写密码 2跳转到注册页面
            if($user){
                returnJson(1 ,'已有用户',array('direction'=>'1'));
            }else{
                returnJson(1 ,'注册用户',array('direction'=>'2'));
            }
            return;
        }catch (Exception $e){

            returnJson(0,"exception！",$e);
        }
    }

    /**
     * @brief 注册用户
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/registerUser?username=18697942051&password=123456&code=3996
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @param code 验证码
     */
    public function registerUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            $code = $_REQUEST['code'];
            $userModel = M('biz_user_info');
            //查看手机是否注册
            $user = $userModel->where('username='.$username)->find();
            if($user){
                returnJson(0,'该手机已注册！');
                return;
            }
            if(strlen($password) < 6){
                returnJson(0,'');
                return;
            }
            //查看验证是否成功
            $codeModel = M();
            $sql = 'SELECT id FROM biz_sms WHERE phone_num='.$username.' AND verify_code='.$code.'
                    AND delete_flag=0 AND add_date>'.(time()-1800);
            $date = $codeModel->query($sql)['0'];
            //注册
            if($date){
                $userInfo = array();
                $userInfo['username'] = $username;
                $userInfo['password'] = $password;
                $userInfo['mobile'] = $username;
                $userInfo['login_token'] = base64_encode($username.time());
                $userInfo['add_date'] = time();
                $userId = $userModel -> add($userInfo);
                $user = $userModel ->field('user_id,username,login_token')->where('user_id='.$userId)->find();
                if($user){
                    returnJson(1, '注册成功！', $user);
                    return;
                }else{
                    returnJson(0,'注册失败！');
                    return;
                }
            }else{
                returnJson(0,'验证码错误！');
            }
            return;

        }catch (Exception $e){
            returnJson(0,"exception！",$e);
        }
    }

    /**
     * @brief 发送注册验证码
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/registerSms?phone_num=18697942051
     * @param phone_num 手机号码
     */
    public function registerSms(){
        try{
            $phone_num = $_REQUEST['phone_num'];
            if(empty($phone_num)){
                returnJson(0,'手机号码不能为空！');
                return;
            }else{
                //查看用户是否存在
                $userModel = M('biz_user_info');
                $user = $userModel->where('username='.$phone_num)->find();
                if($user){
                    returnJson(0,'该手机已经注册！');
                    return;
                }
            }
            $code = rand(1000,9999);
            $c = new \TopClient();
            $c->appkey = $this->APPKEY;
            $c->secretKey = $this->SECRET;
            $req = new \AlibabaAliqinFcSmsNumSendRequest();
            $req->setExtend("123456");
            $req->setSmsType("normal");
            $req->setSmsFreeSignName("注册验证");
            $req->setSmsParam("{'code':'".$code."','product':'品位环球'}");
            $req->setRecNum($phone_num);
            $req->setSmsTemplateCode("SMS_4445955");
            $resp = $c->execute($req);
            if($resp->result->success == true){
                $addModel = M('biz_sms');
                $data = array();
                $data['phone_num'] = $phone_num;
                $data['verify_code'] = $code;
                $data['add_date'] = time();
                $addModel->add($data);
                returnJson(1,'验证码已发送至您的手机！',$phone_num);
                return;
            }else{
                returnJson(0,'验证码发送失败！');
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 找回密码
     * 接口地址
     * http://114.215.95.23/192.168.0.104/BusinessServer/index.php/Home/MobileApi/findPwdUser?username=18697942051&password=123456&code=3996
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @param code 验证码
     */
    public function findPwdUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            $code = $_REQUEST['code'];
            $userModel = M('biz_user_info');
            //判断该手机是否存在
            $user = $userModel->where('username='.$username)->find();
            if(!$user){
                returnJson(0,'该手机不存在！');
                return;
            }
            if(strlen($password) < 6){
                returnJson(0,'');
                return;
            }
            //查看验证是否成功
            $codeModel = M();
            $sql = 'SELECT id FROM biz_sms WHERE phone_num='.$username.' AND verify_code='.$code.'
                    AND delete_flag=0 AND add_date>'.(time()-1800);
            $date = $codeModel->query($sql)['0'];
            //修改密码
            if($date){
                $userInfo = array();
                $userInfo['username'] = $username;
                $userInfo['password'] = $password;
                $userInfo['login_token'] = base64_encode($username.time());
                $userInfo['update_date'] = time();
                $userId=$userModel ->where('username='.$username)->save($userInfo);
                $user = $userModel ->field('user_id,username,login_token')->where('username='.$username)->find();
                if($userId) {
                    returnJson(1, '密码修改成功！', $user);
                    return;
                }else{
                    returnJson(0,'密码修改失败！');
                    return;
                }
            }else{
                returnJson(0,'验证码错误！');
            }
            return;

        }catch (Exception $e){
            returnJson(0,"exception！",$e);
        }
    }

    /**
     * @brief 发送找回密码验证码
     * 接口地址
     * http://192.168.0.104/BusinessServer/index.php/Home/MobileApi/findPwdSms?phone_num=18697942051
     * @param phone_num 手机号码
     */
    public function findPwdSms(){
        try{
            $phone_num = $_REQUEST['phone_num'];
            if(empty($phone_num)){
                returnJson(0,'手机号码不能为空！');
                return;
            }else{
                //查看用户是否存在
                $userModel = M('biz_user_info');
                $user = $userModel->where('username='.$phone_num)->find();
                if(!$user){
                    returnJson(0,'该手机不存在！');
                    return;
                }
            }
            $code = rand(1000,9999);
            $c = new \TopClient();
            $c->appkey = $this->APPKEY;
            $c->secretKey = $this->SECRET;
            $req = new \AlibabaAliqinFcSmsNumSendRequest();
            $req->setExtend("123456");
            $req->setSmsType("normal");
            $req->setSmsFreeSignName("变更验证");
            $req->setSmsParam("{'code':'".$code."','product':'品位环球'}");
            $req->setRecNum($phone_num);
            $req->setSmsTemplateCode("SMS_4445953");
            $resp = $c->execute($req);
            if($resp->result->success == true){
                $addModel = M('biz_sms');
                $data = array();
                $data['phone_num'] = $phone_num;
                $data['verify_code'] = $code;
                $data['add_date'] = time();
                $addModel->add($data);
                returnJson(1,'验证码已发送至您的手机！',$phone_num);
                return;
            }else{
                returnJson(0,'验证码发送失败！');
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 用户登录
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/loginUser?username=18697942051&password=e10adc3949ba59abbe56e057f20f883e
     * @param username 用户名(手机号码)
     * @param password 密码
     */
    public function loginUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];

            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id,username,delete_flag')->where("username='%s' and password='%s'",array($username,$password))->find();
            if($user['delete_flag'] == 1){
                returnJson(0,'该用户已被停用！');
                return;
            }
            if($user){
                $user['login_token'] = base64_encode($username.time());
                $userModel->save($user);
                returnJson(1,'登录成功！',$user);
                return;
            }else{
                returnJson(0,'密码或用户名错误！');
            }
            return;

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 商店列表
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/getShopList
     * page 页码
     * size 数量
     */
    public function getShopList(){
        try{
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
            $shopModel = M('biz_shop');
            $shopInfo = $shopModel->field('id,shop_name,address,shop_image_thumb,longitude,latitude')->where('delete_flag=0')->limit($start,$end)->select();
            for($i=0;$i<count($shopInfo);$i++){
                $shopInfo[$i]['shop_image_thumb'] = HTTP_HOST.'/BusinessServer/Uploads/'.$shopInfo[$i]['shop_image_thumb'];
            }
            //是否是最后一页
            $result['list'] = $shopInfo;
            if(!isset($shopInfo)){
                $result['list'] = array();
            }
            if(count($shopInfo) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }

            returnJson(1,'获取成功！',$result);

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }

    }

    /**
     * @brief 商店详情
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/MobileApi/getShopDetail?shop_id=1
     * @param shop_id 车店id
     */
    public function getShopDetail(){
        try{

            $shop_id = $_REQUEST['shop_id'];

            $shopModel = M('biz_shop');
            $shopInfo = $shopModel->field('id,shop_name,address,shop_image,shop_phone,longitude,latitude')->where('delete_flag=0 AND id='.$shop_id)->find();
            $shopInfo['shop_image'] = HTTP_HOST.'/BusinessServer/Uploads/'.$shopInfo['shop_image'];
            $goodsModel = M('biz_shop_goods');
            $goodInfo = $goodsModel->field('id,goods_name,original_price,discount_price')->where('delete_flag=0 AND shop_id='.$shop_id)->find();
            $result['shop'] = $shopInfo;
            $result['good'] = $goodInfo;
            if($result){
                returnJson(1,'查询成功！',$result);
            }else{
                returnJson(0,'查询失败！');
            }
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }

    }

    //////////////////////////////////////.车辆类型.////////////////////////////////////////////////////
    /**
     * @brief
     * 接口地址
     * http://192.168.0.104/BusinessServer/index.php/Home/MobileApi/carCategory
     */
    public function carCategory(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $levelModel = M('biz_car_level_1');
            $list = $levelModel->field('first_code,name,image,has_second')->where('delete_flag=0')->order('order_flag')->select();
            if($list){
                for($i=0;$i<count($list);$i++){
                    $list[$i]['image'] = HTTP_HOST.'/BusinessServer/Public/logo/'.$list[$i]['image'];
                }
                returnJson(1,'查询成功！',$list);
                return;
            }else{
                $list = array();
                returnJson(1,'暂无品牌！',$list);
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }

    }

    /**
     * @brief
     * 接口地址
     * http://192.168.0.104/BusinessServer/index.php/Home/MobileApi/carClassify
     * @param first_code 品牌代码
     */
    public function carClassify(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $first_code = $_REQUEST['first_code'];
            //判断用户first_code是否为空
            if(empty($first_code)){
                returnJson(0,"车辆类型不能为空！");
                return;
            }
            $levelModel = M('biz_car_level_2');
            $list = $levelModel->field('first_code,second_code,name')->where('delete_flag=0 and first_code='.$first_code)->order('order_flag')->select();
            if($list){
                returnJson(1,'查询车辆型号成功！',$list);
                return;
            }else{
                $list = array();
                returnJson(1,'暂无车辆型号！',$list);
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /////////////////////////////////////.APP.///////////////////////////////////////////////////////

    /**
     * @brief 添加车辆
     * 接口地址
     * http://192.168.0.104/BusinessServer/index.php/Home/MobileApi/addCar
     * @param user_id 用户id
     * @param name 姓名
     * @param plate_number 车牌号
     * @param car_type 车类型
     * @param car_type_code 车型号代码
     */
    public function addCar(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $car = array();
            $car['user_id'] = $_REQUEST['user_id'];
            $car['name'] = $_REQUEST['name'];
            $car['plate_number'] = $_REQUEST['plate_number'];
            $car['car_type'] = $_REQUEST['car_type'];
            $car['car_type_code'] = $_REQUEST['car_type_code'];
            //查找电话号码
            $mobile = $userModel->field('mobile')->where('user_id='.$car['user_id'])->find();
            $car['mobile'] = $mobile['mobile'];
            //判断用户id是否为空
            if(empty($car['user_id'])){
                returnJson(0,"用户不能为空！");
                return;
            }
            //判断车主名是否为空
            if(empty($car['name'])){
                returnJson(0,"车主名不能为空！");
                return;
            }
            //判断车主名是否为汉字
            if(eregi("[^\x80-\xff]",trim($car['name']))){
                returnJson(0,"请输入汉字！");
                return;
            }
            //判断车主名是否为2-4位汉字
            if(strlen(trim($car['name']))<6 || strlen(trim($car['name']))>12){
                returnJson(0,"车主名格式不正确！");
                return;
            }
            //判断车主车牌号是否为空
            if(empty($car['plate_number'])){
                returnJson(0,"车牌不能为空！");
                return;
            }
            //判断车主车牌号长度是否为数字跟英文
            if(!(eregi("[^\x80-\xff]",trim($car['plate_number'])))){
                returnJson(0,"车牌格式不正确！");
                return;
            }
            //判断车主车牌号第一位是否为英文
            $num = substr($car['plate_number'],0,1);
            if(!preg_match('/[a-zA-Z]/', $num)){
                returnJson(0,"车牌区号不正确！");
                return;
            }
            //判断车主车牌号长度是否为六位数
            if(strlen(trim($car['plate_number'])) != 6){
                returnJson(0,"请输入正确的车牌！");
                return;
            }

            $car['plate_number'] = '粤'.strtoupper($car['plate_number']);
            //判断车型是否为空
            if(empty($car['car_type'])){
                returnJson(0,"车型不能为空！");
                return;
            }
            //判断车型号代码是否为空
            if(empty($car['car_type_code'])){
                returnJson(0,"车型号代码不能为空！");
                return;
            }
            //判断行驶证是否为空
            if(count($_FILES) < 1){
                returnJson(0,"行驶证不能为空");
                return;
            }
            //图片上传
            $filename = $car['user_id'].time();
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './drivingLicense/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->autoSub   =     false;
                $upload->saveName  =     $filename;
                $info   =   $upload->upload();
            }
            if($info) {
                foreach ($info as $file) {
                    $car['driving_license_url'] = 'drivingLicense/'.$file['savename'];
                }
            }
            $car['state'] = 2;
            $car['add_date'] = time();
            if(!$info){
                returnJson(0,"行驶证上传失败！");
                return;
            }
            $carModel = M('biz_car');
            $ret = $carModel->add($car);

            if($ret){
                returnJson(1,"车辆添加成功！",$ret);
                return;
            }else{
                returnJson(0,"车辆添加失败！");
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 我的爱车页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/myCars?user_id=1
     * user_id 用户id
     */
    public function myCars(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $user_id = $_REQUEST['user_id'];
            if(empty($user_id)){
                returnJson(0,'用户不能为空！');
                return;
            }
            $carModel = M('biz_car');
            $list = $carModel->field('id,user_id,plate_number,car_type,state')->where('delete_flag=0 AND user_id="'.$user_id.'" AND state=2 OR delete_flag=0 AND user_id="'.$user_id.'" AND state=1')->select();
            if($list){
                returnJson(1,'查询成功！',$list);
                return;
            }else{
                $list = array();
                returnJson(1,'该用户暂无车辆！',$list);
            }
            return;

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 爱车详情页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/carInfo?car_id=1
     * car_id 车id
     */
    public function carInfo(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $car_id = $_REQUEST['car_id'];
            if(empty($car_id)){
                returnJson(0,'车辆不能为空！');
                return;
            }
            $carModel = M('biz_car');
            $carInfo = $carModel->field('id,user_id,name,mobile,plate_number,car_type,car_type_code,driving_license_url,state')->where('delete_flag=0 AND id="'.$car_id.'"')->find();
            if($carInfo){
                $carInfo['driving_license_url'] = HTTP_HOST.'/BusinessServer/'.$carInfo['driving_license_url'];
                returnJson(1,'查询成功！',$carInfo);
                return;
            }else{
                returnJson(0,'查询失败！');
            }
            return;

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 重新提交车辆
     * 接口地址
     * http://192.168.0.104/BusinessServer/index.php/Home/MobileApi/updateCar
     * @param car_id 车辆id
     * @param user_id 用户id
     * @param name 姓名
     * @param plate_number 车牌号
     * @param car_type 车类型
     * @param car_type_code 车型号代码
     */
    public function updateCar(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            $car_id = $_REQUEST['car_id'];
            $car = array();
            $car['user_id'] = $_REQUEST['user_id'];
            $car['name'] = $_REQUEST['name'];
            $car['plate_number'] = $_REQUEST['plate_number'];
            $car['car_type'] = $_REQUEST['car_type'];
            $car['car_type_code'] = $_REQUEST['car_type_code'];
            //查找电话号码
            $mobile = $userModel->field('mobile')->where('user_id='.$car['user_id'])->find();
            $car['mobile'] = $mobile['mobile'];

            //判断车辆id是否为空
            if(empty($car_id)){
                returnJson(0,"车辆不能为空！");
                return;
            }
            //判断用户id是否为空
            if(empty($car['user_id'])){
                returnJson(0,"用户不能为空！");
                return;
            }
            //判断车主名是否为空
            if(empty($car['name'])){
                returnJson(0,"车主名不能为空！");
                return;
            }
            //判断车主名是否为汉字
            if(eregi("[^\x80-\xff]",trim($car['name']))){
                returnJson(0,"请输入汉字！");
                return;
            }
            //判断车主名是否为2-4位汉字
            if(strlen(trim($car['name']))<6 || strlen(trim($car['name']))>12){
                returnJson(0,"车主名格式不正确！");
                return;
            }
            //判断车主车牌号是否为空
            if(empty($car['plate_number'])){
                returnJson(0,"车牌不能为空！");
                return;
            }
            //判断车主车牌号长度是否为数字跟英文
            if(!(eregi("[^\x80-\xff]",trim($car['plate_number'])))){
                returnJson(0,"车牌格式不正确！");
                return;
            }
            //判断车主车牌号长度是否为五位数
            if(strlen(trim($car['plate_number'])) != 5){
                returnJson(0,"请输入正确的车牌！");
                return;
            }

            $car['plate_number'] = '粤B'.strtoupper($car['plate_number']);
            //判断车型是否为空
            if(empty($car['car_type'])){
                returnJson(0,"车型不能为空！");
                return;
            }
            //判断车型号代码是否为空
            if(empty($car['user_id'])){
                returnJson(0,"车型号代码不能为空！");
                return;
            }
            $carModel = M('biz_car');
            //判断行驶证是否为空
            if(count($_FILES) < 1){

            }else {
                //图片上传
                $filename = $car['user_id'] . time();
                if (!empty($_FILES)) {
                    $upload = new \Think\Upload();// 实例化上传类
                    $upload->maxSize = 10 * 1024 * 1024;// 设置附件上传大小
                    $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                    $upload->rootPath = './drivingLicense/'; // 设置附件上传根目录
                    $upload->savePath = ''; // 设置附件上传（子）目录
                    $upload->autoSub = false;
                    $upload->saveName = $filename;
                    $info = $upload->upload();
                }
                if ($info) {
                    foreach ($info as $file) {
                        $car['driving_license_url'] = 'drivingLicense/' . $file['savename'];
                    }
                }
                if(!$info){
                    returnJson(0,"行驶证上传失败！");
                    return;
                }
            }
            $car['state'] = 1;
            $car['update_date'] = time();
            $ret = $carModel->where('id='.$car_id)->save($car);

            if($ret){
                returnJson(1,"车辆修改成功！",$car_id);
                //向系统推送新通知
                pushMessage(SYSTEM_NOTIFY, "需要审核的新车辆", 1);
                return;
            }else{
                returnJson(0,"车辆修改失败！");
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 选择我的车
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WX/choiceMyCar
     * @param user_id 用户ID
     *
     */
    public function choiceMyCar(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            $user_id = $_REQUEST['user_id'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            //判断用户login_user是否为空
            if(empty($user_id)){
                returnJson(0,"用户不能为空！");
                return;
            }
            $carModel = M('biz_car');
            $list = $carModel->field('id,user_id,plate_number,car_type')->where('state=2 AND delete_flag=0 AND user_id="'.$user_id.'"')->select();
            if($list){
                returnJson(1,'车辆获取成功！',$list);
                return;
            }else{
                $list = array();
                returnJson(1,'暂无车辆！',$list);
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 服务中列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/serviceList
     * @param user_id 用户ID
     *
     */
    public function serviceList(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            $user_id = $_REQUEST['user_id'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            //判断用户login_user是否为空
            if(empty($user_id)){
                returnJson(0,"用户不能为空！");
                return;
            }
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
            $sql = 'SELECT od.id,od.total_fee,sh.shop_name,od.pay_date,sh.shop_image_thumb FROM biz_order od, biz_shop sh
                    WHERE od.user_id='.$user_id.' and od.shop_id=sh.id AND od.state=1 AND od.coupon_id=0 ORDER BY pay_date DESC LIMIT '.$start.','.$end;
            $list = $orderModel->query($sql);
            for($i=0;$i<count($list);$i++){
                $list[$i]['pay_date'] = date('Y-m-d', $list[$i]['pay_date']);
                $list[$i]['shop_image_thumb'] = HTTP_HOST.'/BusinessServer/Uploads/'.$list[$i]['shop_image_thumb'];
            }
            $result['list'] = $list;
            if(count($list) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            if($list){
                returnJson(1,'服务订单获取成功！',$result);
                return;
            }else{
                returnJson(1,'暂无服务订单！',$result);
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 已服务列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/alreadyServiceList
     * @param user_id 用户ID
     *
     */
    public function alreadyServiceList(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            $user_id = $_REQUEST['user_id'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            //判断用户login_user是否为空
            if(empty($user_id)){
                returnJson(0,"用户不能为空！");
                return;
            }
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
            $sql = 'SELECT od.id,od.total_fee,sh.shop_name,od.use_date,sh.shop_image_thumb FROM biz_order od, biz_shop sh
                    WHERE od.user_id='.$user_id.' and od.shop_id=sh.id AND od.state=2 AND od.coupon_id=0 ORDER BY use_date DESC LIMIT '.$start.','.$end;
            $list = $orderModel->query($sql);
            for($i=0;$i<count($list);$i++){
                $list[$i]['use_date'] = date('Y-m-d', $list[$i]['use_date']);
                $list[$i]['shop_image_thumb'] = HTTP_HOST.'/BusinessServer/Uploads/'.$list[$i]['shop_image_thumb'];
            }
            $result['list'] = $list;
            if(count($list) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            if($list){
                returnJson(1,'已服务订单获取成功！',$result);
                return;
            }else{
                returnJson(1,'暂无已服务订单！',$result);
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 订单服务详情
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/serviceDetails
     * @param order_id 用户ID
     *
     */
    public function serviceDetails(){
        try{
            $login_token = $_REQUEST['login_token'];
            $login_user = $_REQUEST['login_user'];
            $order_id = $_REQUEST['order_id'];
            //判断用户login_token是否为空
            if(empty($login_token)){
                returnJson(0,"login_token不能为空！");
                return;
            }
            //判断用户login_user是否为空
            if(empty($login_user)){
                returnJson(0,"login_user不能为空！");
                return;
            }
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('login_token="'.$login_token.'" and user_id='.$login_user)->find();
//            if(!$user){
//                returnJson(0,'该用户不存在！');
//                return;
//            }
            //判断用户login_user是否为空
            if(empty($order_id)){
                returnJson(0,"用户不能为空！");
                return;
            }
            $ordersModel = M();
            $sql = 'SELECT od.id, od.out_trade_no, od.product_id, od.state, sh.shop_name, sh.shop_phone, go.goods_name, od.total_fee, ca.car_type
                    FROM biz_order od, biz_shop sh, biz_shop_goods go, biz_car ca
                    WHERE od.id='.$order_id.' and od.shop_id=sh.id AND od.goods_id=go.id AND od.car_id=ca.id';
            $orderInfo = $ordersModel->query($sql);
            if($orderInfo){
                returnJson(1,'查询服务订单详情成功！',$orderInfo);
                return;
            }else{
                returnJson(0,'查询失败！');
            }
            return;
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 创建订单
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/MobileApi/createOrder
     * @param shop_id 商店ID
     * @param goods_id 商品ID
     * @param car_id 汽车ID
     */
    public function createOrder(){

        if(!isset($_REQUEST['user_id']) && !isset($_REQUEST['shop_id']) && !isset($_REQUEST['goods_id']) && !isset($_REQUEST['car_id'])){
            returnJson(0, '参数不完整');
            exit;
        }

        $user = array('user_id'=>$_REQUEST['user_id']);

        $shop_id = $_REQUEST['shop_id'];
        $goods_id = $_REQUEST['goods_id'];
        $car_id = $_REQUEST['car_id'];

//        $user = array('user_id'=>1);
//        $shop_id = 1;
//        $goods_id = 1;
//        $car_id = 1;

        $model = M('biz_shop');
        $shop = $model->where('delete_flag=0 AND id='.$shop_id)->find();
        $goodsModel = M('biz_shop_goods');
        $goods = $goodsModel->where('delete_flag=0 AND id='.$goods_id)->find();

        //内部订单生成规则 goodsID+user_id+time()
        $bizOrder = $goods_id.$user['user_id'].time();

        //统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($goods['goods_name']);
        $input->SetAttach($shop['shop_name'].$shop['id']);
        $input->SetOut_trade_no($bizOrder);
        $input->SetTotal_fee($goods['discount_price']*100);
//        $input->SetTotal_fee(1);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($bizOrder);
        $input->SetNotify_url(HTTP_HOST."/BusinessServer/index.php/Home/MobileApi/wxNotify");
        $input->SetTrade_type("APP");
        $order = \WxPayApi::unifiedAppOrder($input);

        $orderModel = M('biz_order');
        $newOrder = array('shop_id'=>$shop_id,'goods_id'=>$goods_id,'user_id'=>$user['user_id'],'mch_id'=>\WxPayConfig::APP_MCHID,
            'open_id'=>'', 'original_price'=>$goods['original_price'], 'total_fee'=>$goods['discount_price'],
            'out_trade_no'=>$bizOrder, 'car_id'=>$car_id, 'add_date'=>time(), 'type'=>1, 'server_id'=>$shop['server_id']);
        $ret = $orderModel->add($newOrder);
        if(!$ret){
            returnJson(0, '订单生成失败');
        }else{
            //二次签名
            $data["appid"] = \WxPayConfig::APP_APPID;
            $data["noncestr"] = \WxPayApi::getNonceStr();
            $data["package"] = "Sign=WXPay";
            $data["partnerid"] = \WxPayConfig::APP_MCHID;
            $data["prepayid"] = $order['prepay_id'];
            $data["timestamp"] = time();

            $Parameters = $data;
            //签名步骤一：按字典序排序参数
            ksort($Parameters);
            $buff = "";
            foreach ($Parameters as $k => $v)
            {
                $buff .= strtolower($k) . "=" . $v . "&";
            }
            $String = substr($buff, 0, strlen($buff)-1);
            //签名步骤二：在string后加入KEY
            $String = $String."&key=".\WxPayConfig::APP_KEY;
            //签名步骤三：MD5加密
            $data["sign"] = strtoupper(md5($String));

            returnJson(1, '订单生成成功', $data);
        }
    }

    /**
     * @brief 微信支付回调通知
     */
    public function wxNotify(){

        $notify = new \AppPayNotifyCallBack();
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

            $order['update_date'] = time();
            $order['pay_date'] = time();
            if($order['goods_id'] == 0){
                $order['state']=ORDER_HAS_USE;
                $order['use_date']=time();
                $order['verify_shop_id']=$order['shop_id'];
            }else{
                $order['state'] = ORDER_HAS_PAY;
            }
            $model->save($order);

        }
    }

}