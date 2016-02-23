<?php
namespace Home\Controller;
use think\Controller;
use Think\Exception;
import('Org.Taobao.top.TopClient');
import('Org.Taobao.top.ResultSet');
import('Org.Taobao.top.RequestCheckUtil');
import('Org.Taobao.top.TopLogger');
import('Org.Taobao.top.request.AlibabaAliqinFcSmsNumSendRequest');

class MobileApiController extends Controller{

    private $APPKEY = '23312078';
    private $SECRET = 'cd3b6b73c21bcc265fde56ec5f66544b';

    public function index(){

        $this->display();
    }
//////////////////////////////////////.用户注册登录.//////////////////////////////////////////////////////////////////

    /**
     * @brief 是否存在用户
     * 接口地址
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/isUser?username=18697942051
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
            $findUser = M('mk_user_info');
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
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/registerUser?username=18697942051&password=123456&code=3996
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @param code 验证码
     */
    public function registerUser(){
        try{
            $username = $_REQUEST['username'];
            $password = md5($_REQUEST['password']);
            $code = $_REQUEST['code'];
            $userModel = M('mk_user_info');
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
            $sql = 'SELECT id FROM mk_sms WHERE mobile='.$username.' AND verify_code='.$code.'
                    AND delete_flag=0 AND add_date>'.(time()-60);
            $date = $codeModel->query($sql)['0'];
            //注册
            if($date){
                $userInfo = array();
                $userInfo['username'] = $username;
                $userInfo['password'] = $password;
                $userInfo['mobile'] = $username;
                $userInfo['login_token'] = base64_encode($username.time());
                $userInfo['add_date'] = time();
                $userId=$userModel -> add($userInfo);
                $user = $userModel ->field('id,username,login_token')->where('id='.$userId)->find();
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
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/registerSms?phone_num=18697942051
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
                $userModel = M('mk_user_info');
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
            $req->setSmsParam("{'code':'".$code."','product':'脈库'}");
            $req->setRecNum($phone_num);
            $req->setSmsTemplateCode("SMS_5058464");
            $resp = $c->execute($req);
            if($resp->result->success == true){
                $addModel = M('mk_sms');
                $data = array();
                $data['mobile'] = $phone_num;
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
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/findPwdUser?username=18697942051&password=123456&code=3996
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @param code 验证码
     */
    public function findPwdUser(){
        try{
            $username = $_REQUEST['username'];
            $password = md5($_REQUEST['password']);
            $code = $_REQUEST['code'];
            $userModel = M('mk_user_info');
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
            $sql = 'SELECT id FROM mk_sms WHERE mobile='.$username.' AND verify_code='.$code.'
                    AND delete_flag=0 AND add_date>'.(time()-60);
            $date = $codeModel->query($sql)['0'];
            //修改密码
            if($date){
                $userInfo = array();
                $userInfo['username'] = $username;
                $userInfo['password'] = $password;
                $userInfo['login_token'] = base64_encode($username.time());
                $userInfo['update_date'] = time();
                $userId=$userModel ->where('username='.$username)->save($userInfo);
                $user = $userModel ->field('id,username,login_token')->where('id='.$userId)->find();
                if($user) {
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
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/findPwdSms?phone_num=18697942051
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
                $userModel = M('mk_user_info');
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
            $req->setSmsParam("{'code':'".$code."','product':'脈库'}");
            $req->setRecNum($phone_num);
            $req->setSmsTemplateCode("SMS_5058462");
            $resp = $c->execute($req);
            if($resp->result->success == true){
                $addModel = M('mk_sms');
                $data = array();
                $data['mobile'] = $phone_num;
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
     * http://114.215.95.23/SHS_Contact_PHP/index.php/Home/MobileApi/loginUser?username=18697942051&password=123456
     * @param username 用户名(手机号码)
     * @param password 密码
     */
    public function loginUser(){
        try{
            $username = $_REQUEST['username'];
            $password = md5($_REQUEST['password']);

            $userModel = M('mk_user_info');
            $user = $userModel->field('id,username,delete_flag')->where("username='%s' and password='%s'",array($username,$password))->find();
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
}