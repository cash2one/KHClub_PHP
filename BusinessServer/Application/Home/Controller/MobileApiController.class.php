<?php
namespace Home\Controller;
use Think\Controller;
use Think\Exception;
Vendor('alisdk.TopSdk');
import('Org.JPush.JPush');

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
                $shopInfo[$i]['shop_image_thumb'] = 'http://192.168.0.104/BusinessServer/Uploads/'.$shopInfo[$i]['shop_image_thumb'];
            }
            //是否是最后一页
            $result['list'] = $shopInfo;
            if(count($shopInfo) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            if($shopInfo){
                returnJson(1,'获取成功！',$result);
            }else{
                returnJson(0,'获取失败！');
            }

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
            $shopInfo['shop_image'] = 'http://192.168.0.104/BusinessServer/Uploads/'.$shopInfo['shop_image'];
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


}