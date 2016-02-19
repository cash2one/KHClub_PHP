<?php
namespace Home\Controller;
use think\Controller;
use Think\Exception;

class ContactController extends Controller{

    private $APPKEY = '23312078';
    private $SECRET = 'cd3b6b73c21bcc265fde56ec5f66544b';

    public function index(){

        $this->display();
    }
//////////////////////////////////////.用户注册登录.//////////////////////////////////////////////////////////////////
    /**
     * @brief 注册用户
     * 接口地址
     * http://localhost/SHS_Contact_PHP/index.php/Home/Contact/registerUser?username=15810710447&password=123456
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     */
    public function registerUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            echo $username.'<br/>'.$password;
            print_r($_REQUEST);

        }catch (Exception $e){
            returnJson(0,"exception！",$e);
        }
    }


    public function indexs(){
//        $appkey = "23312078";//你的App key
//        $secret = "cd3b6b73c21bcc265fde56ec5f66544b";//你的App Secret:
        $code = rand(0000,9999);
//        echo $code;
//        echo $appkey.'<br/>'.$secret;
//        exit;
        import('Org.Taobao.top.TopClient');
        import('Org.Taobao.top.ResultSet');
        import('Org.Taobao.top.RequestCheckUtil');
        import('Org.Taobao.top.TopLogger');
        import('Org.Taobao.top.request.AlibabaAliqinFcSmsNumSendRequest');
        //将需要的类引入，并且将文件名改为原文件名.class.php的形式
        $c = new \TopClient;
        $c->appkey = $this->APPKEY;
        $c->secretKey = $this->SECRET;
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setExtend("123456");//确定发给的是哪个用户，参数为用户id
        $req->setSmsType("normal");
        /*
        进入阿里大鱼的管理中心找到短信签名管理，输入已存在签名的名称，这里是身份验证。
        */
        $req->setSmsFreeSignName("身份验证");
        $req->setSmsParam("{'code':'".$code."','product':'SHS_Contact'}");
        //这里设定的是发送的短信内容：验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！”
        $req->setRecNum("18697942051");//参数为用户的手机号码
        $req->setSmsTemplateCode("SMS_5058467");
        $resp = $c->execute($req);
        var_dump($resp);
    }

}