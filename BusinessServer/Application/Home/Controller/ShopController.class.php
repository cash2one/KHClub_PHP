<?php
namespace Home\Controller;
use Think\Controller;
class ShopController extends Controller {

    public function index(){

    }


    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/shopVerify
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_URL_PREFIX."shopEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $shop = getShopUser();

        //如果系统中存在这个商家
        if(!empty($shop)){
            header("Location: ".HTTP_URL_PREFIX."shopHome");
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
        $userModel = M('biz_shop_register');
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
            header("Location: ".HTTP_URL_PREFIX."shopHome");
            exit;
        }

        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $shopModel = M('biz_shop_register');
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
                header("Location: ".HTTP_URL_PREFIX."shopHome");
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
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/Shop/searchOrder
     * @param
     */
    public function searchOrder(){

        //先授权获取openID
//        $openID = $_SESSION['open_id'];
//        if(empty($openID)){
//            $code = $_REQUEST['code'];
//            if(!empty($code)){
//                $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
//                $openID = json_decode($content)->openid;
//                if(empty($openID)){
//                    echo '不好意思，您微信未授权openID';
//                    return;
//                }
//                //openID存入
//                $_SESSION['open_id'] = $openID;
//            }else{
//                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->WX_APPID."&redirect_uri=".HTTP_URL_PREFIX."shopEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
//                exit;
//            }
//        }
//
//        $shop = getShopUser();
//
//        //如果系统中存在这个商家
//        if(!empty($shop)){
//            header("Location: ".HTTP_URL_PREFIX."shopHome");
//            exit;
//        }
//
//        //wxJs签名
//        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
//        $signPackage = $jssdk->GetSignPackage();
//        $this->assign('signPackage',$signPackage);
//
//        $this->display('shopVerify');

    }

}

