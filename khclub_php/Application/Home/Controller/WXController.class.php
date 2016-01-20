<?php
namespace Home\Controller;
use Think\Controller;
Vendor('alisdk.TopSdk');
Vendor('jssdk');

define("HTTP_URL_PREFIX","http://a.pinweihuanqiu.com/khclub_php/index.php/Home/WX/");

class WXController extends Controller {

    private $WX_APPID = 'wxa1cc9ce0fd9a1372';
    private $WX_APPSecret = 'd734cd2152eb5557a78477ed09136196';

    protected $autoCheckFields =false;
    public function index(){
        echo 'nihao';
    }

    //http://www.pinweihuanqiu.com/khclub_php/index.php/Home/WX/weixinTest
    public function weixinTest(){
//        $_SESSION['test'] = '1111';
//        setcookie('cookie','testestcookie');
//        $code = $_REQUEST['code'];
//        if(empty($code)){
//            echo '不好意思，您微信未授权';
//            return;
//        }
//        $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxa1cc9ce0fd9a1372&secret=d734cd2152eb5557a78477ed09136196&code=".$code."&grant_type=authorization_code");
//
//        $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.json_decode($content)->access_token.'&openid='.json_decode($content)->openid;
//        echo $msgUrl;
//        $msg = file_get_contents($msgUrl);
//        echo $msg;


//        $APPID="wxa1cc9ce0fd9a1372";
//        $APPSECRET="d734cd2152eb5557a78477ed09136196";
//        $open_id = $_SESSION['open_id'];
//        $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret;
//
//        $json=file_get_contents($TOKEN_URL);
//        $result=json_decode($json);
//        $ACC_TOKEN=$result->access_token;
        //是否订阅测试
//        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$ACC_TOKEN.'&openid='.$open_id.'&lang=zh_CN';
////        echo $url; <a href="http://www.5icool.org">酷站代码网</a>
//        $msg = file_get_contents($url);
//        echo json_decode($msg)->subscribe;

//        $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$ACC_TOKEN.'&openid='.$_SESSION['open_id'];
//        $msg = file_get_contents($msgUrl);
//        $wxUser = json_decode($msg);
//        print_r($wxUser);
//        print_r($result->access_token);
//
//        $data = '{
//            "touser":"oqi98uEfL2sj5ez6BxRBW7sN5_ww",
//            "msgtype":"text",
//            "text":
//            {
//                "content":"<a href=\"http://www.5icool.org\">酷站代码网</a>"
//            }
//        }';
//
////        "touser":"oqi98uEfL2sj5ez6BxRBW7sN5_ww",
////            "msgtype":"text",
////            "news":{
////            "articles": [
////                     {
////                         "title":"111",
////                         "description":"收藏了你的名片",
////                         "url":"http://www.baidu.com",
////                         "picurl":""
////                     }
////                     ]
////            }
//
//        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$ACC_TOKEN;
//
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt($curl, CURLOPT_POST, 1);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        $result = curl_exec($curl);
//        if (curl_errno($curl)) {
//            return 'Errno'.curl_error($curl);
//        }
//        curl_close($curl);
//        print_r($result);
//        $final = json_decode($result);
//        echo $final;

//        $this->display("Test");
    }

    public function cardDetail(){

        $this->display("card");

    }

    /**
     * @brief 名片详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/getCardInfo
     * @param user_id 用户id
     */
    public  function getCardInfo(){
        try{

            $user_id = $_REQUEST['user_id'];
            $userModel = M('kh_user_info');
            $user = $userModel->find($user_id);

            if($user){
                $card = array();
                if(empty($user['name'])){
                    $card['name'] = '暂无信息';
                }else{
                    $card['name'] = $user['name'];
                }
                if(empty($user['head_image'])){
                    $card['head_image'] = '';
                }else{
                    //120.25.213.171
                    $card['head_image'] = '/Uploads/'.$user['head_image'];
                }
                if(empty($user['company_name'])){
                    $card['company_name'] = '暂无信息';
                }else{
                    $card['company_name'] = $user['company_name'];
                }
                if(empty($user['job'])){
                    $card['job'] = '暂无信息';
                }else{
                    $card['job'] = $user['job'];
                }
                if($user['phone_state'] == 1){
                    $card['phone_num'] = 'xxxxxx';
                }else{
                    if(empty($user['phone_num'])){
                        $card['phone_num'] = '暂无信息';
                    }else{
                        $card['phone_num'] = $user['phone_num'];
                    }
                }
                if($user['address_state'] == 1){
                    $card['address'] = 'xxxxxx';
                }else{
                    if(empty($user['address'])){
                        $card['address'] = '暂无信息';
                    }else{
                        $card['address'] = $user['address'];
                    }
                }
                if($user['email_state'] == 1){
                    $card['e_mail'] = 'xxxxxx';
                }else{
                    if(empty($user['e_mail'])){
                        $card['e_mail'] = '暂无信息';
                    }else{
                        $card['e_mail'] = $user['e_mail'];
                    }
                }

                returnJson(1,"查询成功", $card);
            }else{
                returnJson(0,"查询失败");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 圈子详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/details
     * @param circle_id 圈子id
     */
    public function details(){
        $circle_id = $_REQUEST['circle_id'];
        $circle = M('kh_personal_circle');
        $circle = $circle->where('id='.$circle_id.' and delete_flag=0')->getField('id,user_id,circle_name,circle_detail,circle_cover_image,circle_web,phone_num,address,wx_num,wx_qrcode,follow_quantity');
        if($circle) {
            $circleList = array();
            foreach ($circle as $v) {
                if (empty($v['circle_name'])) {
                    $circleList['circle_name'] = '暂无信息';
                } else {
                    $circleList['circle_name'] = $v['circle_name'];
                }
                if (empty($v['circle_detail'])) {
                    $circleList['circle_detail'] = '暂无信息';
                } else {
                    $circleList['circle_detail'] = $v['circle_detail'];
                }
                if (empty($v['circle_cover_image'])) {
                    $circleList['circle_cover_image'] = '';
                } else {
                    $circleList['circle_cover_image'] = $v['circle_cover_image'];
                }
                if (empty($v['circle_web'])) {
                    $circleList['circle_web'] = '暂无信息';
                } else {
                    $circleList['circle_web'] = $v['circle_web'];
                }
                if (empty($v['phone_num'])) {
                    $circleList['phone_num'] = '暂无信息';
                } else {
                    $circleList['phone_num'] = $v['phone_num'];
                }
                if (empty($v['address'])) {
                    $circleList['address'] = '暂无信息';
                } else {
                    $circleList['address'] = $v['address'];
                }
                if (empty($v['wx_num'])) {
                    $circleList['wx_num'] = '暂无信息';
                } else {
                    $circleList['wx_num'] = $v['wx_num'];
                }
                if (empty($v['wx_qrcode'])) {
                    $circleList['wx_qrcode'] = '';
                } else {
                    $circleList['wx_qrcode'] = $v['wx_qrcode'];
                }
                if (empty($v['follow_quantity'])) {
                    $circleList['follow_quantity'] = '0';
                } else {
                    $circleList['follow_quantity'] = $v['follow_quantity'];
                }
                $this->assign('circleList',$circleList);
                $this->display();
            }
        }else{
            $this->error('该圈子不存在');
        }
    }

    public function noticeDetail(){

        $this->display("notice");

    }

    /**
     * @brief 公告详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/notice?notice_id=4
     * @param circle_id 公告id
     */
    public  function getNoticeInfo(){
        try {
            $notice_id = $_REQUEST['notice_id'];
            $noticeModel = M('kh_circle_notice');
            $notice = $noticeModel->find($notice_id);
            if($notice){
                $notices = array();
                if(empty($notice['content_text'])){
                    $notices['content_text'] = '暂无信息';
                }else{
                    $notices['content_text'] = $notice['content_text'];
                }
                if(empty($notice['comment_quantity'])){
                    $notices['comment_quantity'] = '暂无评论';
                }else{
                    $notices['comment_quantity'] = $notice['comment_quantity'];
                }
                if(empty($notice['like_quantity'])){
                    $notices['like_quantity'] = '暂无信息';
                }else{
                    $notices['like_quantity'] = $notice['like_quantity'];
                }

                returnJson(1,'查询成功！',$notices);
            }else{
                returnJson(0,'查询失败！');
            }
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    ///////////////////////////////////////////微信服务号部分/////////////////////////////////////////////

    /**
     * @brief 名片详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/mycard?target_id=23
     * @param circle_id 公告id
     * @param target_id 名片id
     */
    public function mycard(){
        $target_id = $_REQUEST['target_id'];

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
                $url = HTTP_URL_PREFIX."mycard?target_id=".$target_id."&isShared=".$_REQUEST['isShared'];
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M('kh_wx_card_member');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];
        //都为空
        if(empty($target_id) && empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        if(empty($target_id)){
            header("Location: ".HTTP_URL_PREFIX."mycard?target_id=".$userExtra['id']);
            exit;
        }

        $userModel = M();
        $sql='SELECT uc.id, uc.head_sub_image, uc.name, uc.job, uc.phone_num, uc.e_mail, uc.company_name, uc.address, ex.web, ex.qq, ex.wechat FROM kh_user_info uc LEFT JOIN kh_user_extra_info ex ON (ex.user_id=uc.id)
              WHERE uc.id='.$target_id.' AND uc.delete_flag=0';
        $userInfo = $userModel->query($sql);
        foreach($userInfo as $v){
            if (empty($v['head_sub_image'])) {
                $userInfo['head_sub_image'] = '';
            } else {
                if(!strstr($v['head_sub_image'], 'http')){
                    $userInfo['head_sub_image'] = __ROOT__.'/Uploads/'.$v['head_sub_image'];
                }else{
                    $userInfo['head_sub_image'] = $v['head_sub_image'];
                }
            }
            if (empty($v['name'])) {
                $userInfo['name'] = '暂无信息';
            } else {
                $userInfo['name'] = $v['name'];
            }
            if (empty($v['job'])) {
                $userInfo['job'] = '暂无信息';
            } else {
                $userInfo['job'] = $v['job'];
            }
            if (empty($v['phone_num'])) {
                $userInfo['phone_num'] = '暂无信息';
            } else {
                $userInfo['phone_num'] = $v['phone_num'];
            }
            if (empty($v['e_mail'])) {
                $userInfo['e_mail'] = '暂无信息';
            } else {
                $userInfo['e_mail'] = $v['e_mail'];
            }
            if (empty($v['company_name'])) {
                $userInfo['company_name'] = '暂无信息';
            } else {
                $userInfo['company_name'] = $v['company_name'];
            }
            if (empty($v['address'])) {
                $userInfo['address'] = '暂无信息';
            } else {
                $userInfo['address'] = $v['address'];
            }
            if (empty($v['web'])) {
                $userInfo['web'] = '暂无信息';
            } else {
                $userInfo['web'] = $v['web'];
            }
            if (empty($v['qq'])) {
                $userInfo['qq'] = '暂无信息';
            } else {
                $userInfo['qq'] = $v['qq'];
            }
            if (empty($v['wechat'])) {
                $userInfo['wechat'] = '暂无信息';
            } else {
                $userInfo['wechat'] = $v['wechat'];
            }
            if ($v['id'] == $userExtra['id']) {
                $userInfo['ifmy'] = true;
            } else {
                $userInfo['ifmy'] = false;
            }
            $userInfo['target_id'] = $v['id'];
        }
        $userInfo['user_id'] = $userExtra['id'];

        //0为未收藏 1已收藏 2未登录
        if(empty($userExtra)){
            $userInfo['collect'] = '2';
        }else{
            $cardModel = M('kh_card');
            $card = $cardModel->where('user_id='.$userExtra['id'].' and target_id='.$target_id.' and delete_flag=0')->find();
            if(empty($card)){
                $userInfo['collect'] = '0';
            }else{
                $userInfo['collect'] = '1';
            }
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('isShared',$_REQUEST['isShared']);
        $this->assign('userInfo',$userInfo);
        $this->display("mycard");
    }

    /**
     * @brief 收藏名片
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/collectCard?
     * @param user_id 用户ID
     * @param target_id 收藏id
     */
    public function collectCard(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            echo '微信未授权';
            exit;
        }

        //不是用户去注册
        $memberModel = M();
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ui.head_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        //要收藏的名片
        $target_id = $_REQUEST['target_id'];

        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify?collectID=".$target_id);
            exit;
        }

        if(empty($target_id)){
            $this->error("收藏不能为空");
        }

        $cardModel = M('kh_card');
        $card = $cardModel->where('user_id='.$userExtra['id'].' and target_id='.$target_id)->find();
        if($card){
            if(!$card['delete_flag'] == 0){
                $card['delete_flag'] = 0;
                $card['update_date'] = time();
                $ret = $cardModel->save($card);
                if(!$ret){
                    $this->error("收藏失败!");
                }
            }else{
                header("Location: ".HTTP_URL_PREFIX."mycard?target_id=".$target_id);
                exit;
            }

        }else{
            $card = array('user_id'=>$userExtra['id'],'target_id'=>$target_id, 'add_date'=>time());
            $ret = $cardModel->add($card);
            if(!$ret){
                $this->error("收藏失败!");
            }
        }

        $openModel = M('kh_user_extra_info');
        $open = $openModel->where('user_id="'.$target_id.'"')->find();
        //获取授权
//        $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret;
//        $json=file_get_contents($TOKEN_URL);
//        $result=json_decode($json);
//        $ACC_TOKEN=$result->access_token;

        //获取AccessToken
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();

        if($open){
            //推送给对方
            $data = '{
                        "touser":"'.$open['wx_open_id'].'",
                        "msgtype":"text",
                        "text":
                        {
                            "content":"'.$userExtra['name'].'收藏了您的名片<a href=\"'.HTTP_URL_PREFIX.'mycard?isShared=1&target_id='.$userExtra['id'].'\">点击查看</a>"
                        }
                    }';

            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$ACC_TOKEN;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($curl);
            curl_close($curl);

            $sql = "SELECT name FROM kh_user_info WHERE id=".$target_id;
            $name = $openModel->query($sql)[0]['name'];
            //推送给自己 这个地方冗余了 等需要的时候再进行封装
            $data = '{
                        "touser":"'.$openID.'",
                        "msgtype":"text",
                        "text":
                        {
                            "content":"您收藏了'.$name.'的名片<a href=\"'.HTTP_URL_PREFIX.'mycard?target_id='.$target_id.'\">点击查看</a>"
                        }
                    }';

            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$ACC_TOKEN;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($curl);
            curl_close($curl);
        }

        //是否订阅了公众号 没订阅去订阅
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$ACC_TOKEN.'&openid='.$openID.'&lang=zh_CN';
        $msg = file_get_contents($url);
        if(json_decode($msg)->subscribe == 1){
            header("Location: ".HTTP_URL_PREFIX."mycard?target_id=".$target_id);
        }else{
            header("Location: ".HTTP_URL_PREFIX."subscribeWX");
        }

    }

    /**
     * @brief 删除名片
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/deleteCard?
     * @param user_id 用户ID
     * @param target_id 收藏id
     */
    public function deleteCard(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            echo '微信未授权';
            exit;
        }
        $memberModel = M('kh_wx_card_member');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        $target_id = $_REQUEST['target_id'];

        if(empty($target_id)){
            $this->error("删除不能为空");
        }

        $cardModel = M('kh_card');
        $card = $cardModel->where('user_id='.$userExtra['id'].' and target_id='.$target_id.' and delete_flag=0')->find();
        if($card){
            $card['delete_flag'] = 1;
            $card['delete_date'] = time();
            $ret = $cardModel->save($card);
            if(!$ret){
                $this->error("删除失败!");
            }
        }

        header("Location: ".HTTP_URL_PREFIX."mycard?target_id=".$target_id);
    }

    /**
     * @brief 通讯录名片页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/contackQRcode
     */
    public  function contactQRcode(){
        $user_id = $_REQUEST['user_id'];
        if(empty($user_id)){
            echo '该用户不存在';
            exit;
        }

        $userModel = M();
        $sql='SELECT uc.id, uc.head_sub_image, uc.name, uc.job, uc.phone_num, uc.e_mail, uc.company_name, uc.address, ex.web, ex.qq, ex.wechat FROM kh_user_info uc LEFT JOIN kh_user_extra_info ex ON (ex.user_id=uc.id)
              WHERE uc.id='.$user_id.' AND uc.delete_flag=0';
        $userInfo = $userModel->query($sql)[0];
        if(empty($userInfo)){
            echo '该用户不存在';
            exit;
        }else{

            //wxJs签名
            $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage',$signPackage);

            $this->assign("userInfo",$userInfo);
            $this->display("contactQRcode");
        }

    }

    /**
     * @brief 名片首页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardHome
     */
    public  function cardHome(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardHome&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        //是否订阅了公众号 没订阅去订阅
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$ACC_TOKEN.'&openid='.$openID.'&lang=zh_CN';
        $msg = file_get_contents($url);
        if(json_decode($msg)->subscribe == 0) {
            header("Location: ".HTTP_URL_PREFIX."subscribeWX");
            exit;
        }

        $findExtraUser = M('kh_user_extra_info');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $findExtraUser->query($sql);
        //存在
        if($userExtra){

            //存入信息
            $_SESSION['userInfo'] = $userExtra[0];
            //$userExtra['user_id']
            $sql = 'SELECT u.id user_id, u.head_sub_image, u.name, u.sex, u.job, u.company_name, u.signature, u.e_mail, u.phone_num, u.address, u.email_state, u.phone_state, u.address_state
                    FROM kh_card c, kh_user_info u WHERE c.delete_flag=0 AND c.target_id=u.id AND c.user_id='.$userExtra[0]['id'].' ORDER BY c.add_date';
            $cardList = $findExtraUser->query($sql);
            for($i=0; $i<count($cardList); $i++){
                $card = $cardList[$i];
                if(!strstr($card['head_sub_image'], 'http')){
                    $cardList[$i]['head_sub_image'] = __ROOT__.'/Uploads/'.$cardList[$i]['head_sub_image'];
                }
            }
            $this->assign('cardList',$cardList);
            $this->display("cardHolder");

        }else{
            header("Location: ".HTTP_URL_PREFIX."userVerify");
        }

    }

    /**
     * @brief 修改名片页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/modifyCardPage
     */
    public  function modifyCardPage(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."modifyCardPage&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $findExtraUser = M();
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $findExtraUser->query($sql);
        if(empty($userExtra)){
            //注册登录
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }else{
            //存入信息
            $_SESSION['userInfo'] = $userExtra[0];
        }
        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->display("infoUpdate");
    }

    /**
     * @brief 修改名片提交
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/modifyCardPage
     */
    public  function putPersonalInfo(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."modifyCardPage&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $cardsModel = M();
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $cardsModel->query($sql)[0];
        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $userModel = M('kh_user_info');
        $user = $userModel->find($userExtra['id']);

        $userExtraModel = M('kh_user_extra_info');
        $userExtra = $userExtraModel->where('user_id='.$userExtra['id'])->find();

        if(!$user || !$userExtra){
            echo '用户信息有误';
            return;
        }

        $userModel->startTrans();
        $user['name'] = $_REQUEST['name'];
        $user['job'] = $_REQUEST['job'];
        $user['phone_num'] = $_REQUEST['phone'];
        $user['company_name'] = $_REQUEST['company'];
        $user['e_mail'] = $_REQUEST['email'];
        $user['address'] = $_REQUEST['address'];
        $ret = $userModel->save($user);

        $userExtra['web'] = $_REQUEST['web'];
        $userExtra['qq'] = $_REQUEST['qq'];
        $userExtra['wechat'] = $_REQUEST['wechat'];
        $extraRet = $userExtraModel->save($userExtra);

        if($ret !== false && $extraRet !== false){
            $userModel->commit();

            //是否订阅了公众号 没订阅去订阅
            $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
            $ACC_TOKEN = $jssdk->getAccessToken();
            $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$ACC_TOKEN.'&openid='.$openID.'&lang=zh_CN';
            $msg = file_get_contents($url);
            if(json_decode($msg)->subscribe == 0) {
                header("Location: ".HTTP_URL_PREFIX."subscribeWX");
                exit;
            }else{
                header("Location: ".HTTP_URL_PREFIX."mycard?target_id=".$user['id']);
            }
        }else{
            echo '修改失败';
            $userModel->rollback();
        }
    }

    /**
     * @brief 名片群首页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupHome
     */
    public  function cardGroupHome(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardGroupHome&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $cardsModel = M('kh_wx_card_group');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $cardsModel->query($sql)[0];
        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $sql = 'SELECT cg.id, cg.group_title FROM kh_wx_card_group cg, kh_wx_card_member cm
                WHERE cg.id=cm.group_id AND cg.delete_flag=0 AND cm.delete_flag=0 AND cm.member_id='.$userExtra['id'];
        $cards = $cardsModel->query($sql);
        for($i=0; $i<count($cards); $i++){
            $memberModel = M('kh_wx_card_member');
            $memberCount = $memberModel->field('count(1) count')->where('delete_flag=0 AND group_id='.$cards[$i]['id'])->find();
            $cards[$i]['count'] = $memberCount['count'];
        }

        $this->assign("cards",$cards);
        $this->display("cardGroupHome");
    }

    /**
     * @brief 名片群成员页面首页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupMembers
     * @param group_id 成员id
     */
    public function cardGroupMembers(){
        $group_id = $_REQUEST['group_id'];
        if(empty($group_id)){
            echo '圈子不能为空';
            return;
        }

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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardGroupApplyDetail?groupID=".$group_id."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M('kh_wx_card_member');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        //不存在这个人
        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."cardGroupApplyDetail?groupID=".$group_id);
            exit;
        }else{
            $isMember = $memberModel->where('delete_flag=0 AND group_id='.$group_id.' AND member_id='.$userExtra['id'])->find();
            if(!$isMember){
                header("Location: ".HTTP_URL_PREFIX."cardGroupApplyDetail?groupID=".$group_id);
                exit;
            }
        }

        //圈子信息
        $cardsModel = M('kh_wx_card_group');
        $group = $cardsModel->where('delete_flag=0')->find($group_id);
        $memberModel = M('kh_wx_card_member');
        $memberCount = $memberModel->field('count(1) count')->where('delete_flag=0 AND group_id='.$group['id'])->find();
        $group['count'] = $memberCount['count'];

        //成员信息
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.company_name, ui.head_sub_image FROM kh_user_info ui, kh_wx_card_member cm
                WHERE ui.id=cm.member_id AND cm.group_id='.$group_id.' AND cm.delete_flag=0 ORDER BY cm.member_id='.$group['creator_id'].' DESC';
        $cardList = $cardsModel->query($sql);
        //头像过滤
        for($i=0; $i<count($cardList); $i++){
            $card = $cardList[$i];
            if(!strstr($card['head_sub_image'], 'http')){
                $cardList[$i]['head_sub_image'] = __ROOT__.'/Uploads/'.$cardList[$i]['head_sub_image'];
            }
        }

        //wxJs签名
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->assign('userInfo',$userExtra);

        $this->assign("group", $group);
        $this->assign("manager",$cardList[0]);
        array_shift($cardList);
        $this->assign("cardList",$cardList);
        $this->display("cardGroupMembers");
    }

    /**
     * @brief 名片群申请加入详情页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupApplyDetail
     */
    public function cardGroupApplyDetail(){
        $groupID = $_REQUEST['groupID'];
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardGroupApplyDetail?groupID=".$groupID."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M('kh_wx_card_member');
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        //0未登录 1是成员 2不是成员
        if(empty($userExtra)){
            $this->assign("isMember",'0');
        }else{
            $isMember = $memberModel->where('delete_flag=0 AND group_id='.$groupID.' AND member_id='.$userExtra['id'])->find();
            if($isMember){
                header("Location: ".HTTP_URL_PREFIX."cardGroupMembers?group_id=".$groupID);
                exit;
//                $this->assign("isMember",'1');
            }else{
                $this->assign("isMember",'2');
            }
        }

        $sql = 'SELECT cg.id, ui.name, ui.job, ui.head_sub_image, cg.group_title, cg.group_desc FROM kh_wx_card_group cg, kh_user_info ui
                    WHERE ui.id=cg.creator_id AND cg.id='.$groupID.' AND cg.delete_flag=0';
        $groupDetail = $memberModel->query($sql);
        $groupDetail = $groupDetail[0];
        if(!strstr($groupDetail['head_sub_image'], 'http')){
            $groupDetail['head_sub_image'] = __ROOT__.'/Uploads/'.$groupDetail['head_sub_image'];
        }

        $this->assign("groupDetail",$groupDetail);
        $this->display("applyGroup");
    }

    /**
     * @brief 加入圈子
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupApplyDetail
     * @param groupID 群组ID
     */
    public function joinCardGroup(){
        $groupID = $_REQUEST['groupID'];
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardGroupApplyDetail?groupID=".$groupID."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M('kh_wx_card_member');
        // 1是成员 2不是成员
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
            WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];
        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }else{
            $member = $memberModel->where('group_id='.$groupID.' AND member_id='.$userExtra['id'])->find();
            if($member){
                if($member['delete_flag'] == '1'){
                    $member['delete_flag'] = '0';
                    $memberModel->save($member);
                }
            }else{
                $member = array('group_id'=>$groupID, 'member_id'=>$userExtra['id'], 'add_date'=>time());
                $memberModel->add($member);
            }
        }

        $sql = 'SELECT cg.id, ui.name, ui.job, ui.head_sub_image, cg.group_title, cg.group_desc FROM kh_wx_card_group cg, kh_user_info ui
                    WHERE ui.id=cg.creator_id AND cg.id='.$groupID.' AND cg.delete_flag=0';
        $groupDetail = $memberModel->query($sql);
        $groupDetail = $groupDetail[0];
        if(!strstr($groupDetail['head_sub_image'], 'http')){
            $groupDetail['head_sub_image'] = __ROOT__.'/Uploads/'.$groupDetail['head_sub_image'];
        }

        //微信推送
        $openModel = M();
        $sql = 'SELECT ue.wx_open_id,cg.group_title FROM kh_user_extra_info ue, kh_wx_card_group cg WHERE cg.creator_id=ue.user_id AND cg.id='.$groupID;
        $open = $openModel->query($sql)[0];
        $targetOpenID = $open['wx_open_id'];

        //获取AccessToken
        $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
        $ACC_TOKEN = $jssdk->getAccessToken();

        if(!empty($targetOpenID)){
            $data = '{
                        "touser":"'.$targetOpenID.'",
                        "msgtype":"text",
                        "text":
                        {
                            "content":"\"'.$userExtra['name'].'\"加入了您的\"'.$open['group_title'].'\"<a href=\"'.HTTP_URL_PREFIX.'mycard?target_id='.$userExtra['id'].'\">点击查看</a>"
                        }
                    }';

            $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$ACC_TOKEN;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_exec($curl);
            curl_close($curl);
        }

        //是否订阅了公众号 没订阅去订阅
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$ACC_TOKEN.'&openid='.$openID.'&lang=zh_CN';
        $msg = file_get_contents($url);
        if(json_decode($msg)->subscribe == 1){
//            $this->assign("isMember",'1');
//            $this->assign("groupDetail",$groupDetail);
//            $this->display("applyGroup");
            header("Location: ".HTTP_URL_PREFIX."cardGroupMembers?group_id=".$groupID);
        }else{
            header("Location: ".HTTP_URL_PREFIX."subscribeWX");
        }
    }

    /**
     * @brief 创建名片群页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/createCardGroupPage
     */
    public function createCardGroupPage(){
        $this->display("createCardGroup");

    }

    /**
     * @brief 创建名片群
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/postCardGroup
     */
    public function postCardGroup(){
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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."cardHome&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M();
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        if(empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."userVerify");
            exit;
        }

        $groupTitle = $_REQUEST['name'];
        if(empty($groupTitle)){
            echo '标题不能为空';
            return;
        }

        $group = array('group_title'=>$groupTitle, 'group_desc'=>$_REQUEST['desc'], 'creator_id'=>$userExtra['id']);

        $cardModel = M('kh_wx_card_group');
        $cardModel->startTrans();
        $ret = $cardModel->add($group);
        if($ret){

            $memberModel = M('kh_wx_card_member');
            $member = array('group_id'=>$ret,'member_id'=>$userExtra['id'], 'add_date'=>time());
            $memberRet = $memberModel->add($member);

            if($memberRet){

                $cardModel->commit();
                header("Location: ".HTTP_URL_PREFIX."cardGroupMembers?group_id=".$ret);

            }else{
                $cardModel->rollback();
                echo '创建失败';
            }

        }else{
            $cardModel->rollback();
            echo '创建失败';
        }

    }


    /**
     * @brief 名片群详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupDetail
     */
    public function cardGroupDetail(){

        $group_id = $_REQUEST['group_id'];
        $groupModel = M();
        $sql = 'SELECT cg.id, ui.name, ui.job, cg.group_title, cg.group_desc FROM kh_wx_card_group cg, kh_user_info ui
                    WHERE ui.id=cg.creator_id AND cg.id='.$group_id.' AND cg.delete_flag=0';
        $groupDetail = $groupModel->query($sql);
        $this->assign("groupDetail",$groupDetail[0]);
        $this->display("cardGroupDetail");
    }

    /**
     * @brief 删除名片群
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/deleteCardGroup
     */
    public function deleteCardGroup(){
    }

    /**
     * @brief 跳到关注页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/subscribeWX
     */
    public function subscribeWX(){

        $this->display('subscribeWX');

    }

    /**
     * @brief 手机验证页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/userVerify
     * @param collectID 收藏id
     */
    public function userVerify(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        $collectID = $_REQUEST['collectID'];

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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."userVerify?collectID=".$collectID."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $memberModel = M();
        $sql = 'SELECT ui.id, ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
        $userExtra = $memberModel->query($sql)[0];

        //如果系统中存在这个人 跳转到主页
        if(!empty($userExtra)){
            header("Location: ".HTTP_URL_PREFIX."cardHome");
            exit;
        }

        //收藏的人
        $this->assign('collectID',$_REQUEST['collectID']);

        $this->display('userVerify');

    }

    /**
     * @brief 用户验证接口
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/usepass
     * @param collectID 收藏id
     */
    public function userpass(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        $collectID = $_REQUEST['collectID'];

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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."userVerify?collectID=".$collectID."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $username = $_REQUEST['username'];
        $userModel = M('kh_user_info');
        $user = $userModel->field('id')->where('username='.$username)->find();

        //要收藏的人
        $this->assign('collectID',$collectID);
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
     * http://localhost/khclub_php/index.php/Home/WX/userLogin
     * @param collectID 收藏id
     */
    public function userLogin(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        $collectID = $_REQUEST['collectID'];

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
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".HTTP_URL_PREFIX."userLogin&collectID=".$collectID."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $username = $_REQUEST['username'];
        $password = md5($_REQUEST['password']);
        $userModel = M('kh_user_info');
        $user = $userModel->field('id')->where('username='.'"'.$username.'"'.' and password='.'"'.$password.'"')->find();

        //要收藏的人
        $this->assign('collectID',$collectID);
        if($user){
            //绑定微信
            $findExtraUser = M('kh_user_extra_info');
            $userExtra = array('wx_open_id'=>$openID, 'user_id'=>$user['id']);
            $extraRet = $findExtraUser->add($userExtra);
            if($extraRet){
                header("Location: ".HTTP_URL_PREFIX."cardHome");
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
     * http://localhost/khclub_php/index.php/Home/WX/requertSms
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
                $findUser = M('kh_user_info');
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
                    $add = D('kh_sms');
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
     * http://localhost/khclub_php/index.php/Home/WX/userRegister
     * @param collectID 收藏id
     */
    public function userRegister(){

        $username = $_REQUEST['username'];
        $password = $_REQUEST['password'];
        $verify = $_REQUEST['verify'];
        //要收藏的ID
        $collectID = $_REQUEST['collectID'];

        //先授权获取openID
        $code = $_REQUEST['code'];
        if(!empty($code)){

            $userModel = M('kh_user_info');
            $user = $userModel->field('id')->where('username='.$username)->find();
            //用户存在
            if($user){
                header("Location: ".HTTP_URL_PREFIX."cardHome");
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
            $url = "".HTTP_URL_PREFIX."userRegister?collectID=".$collectID."&username=".$username."&verify=".$verify."&password=".$_REQUEST['password'];
            header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxa1cc9ce0fd9a1372&redirect_uri=".urlencode($url)."&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
            exit;
        }

        $verifyModel = M();
           //查看是否验证成功
        $sql = 'SELECT * FROM kh_sms WHERE phone_num='.$username.'
        and verify_code='.$verify.' and delete_flag=0 and add_date>'.(time()-60);
        $data = $verifyModel->query($sql)[0];

        //验证成功注册
        if($data['id'] > 0){
            //注册逻辑
            $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.json_decode($content)->access_token.'&openid='.$openID;
            $msg = file_get_contents($msgUrl);
            $wxUser = json_decode($msg);

            $user = array('username'=>$username, 'password'=>md5($password), 'name'=>$wxUser->nickname, 'phone_num'=>$username,
                        'sex'=>$wxUser->sex-1, 'head_image'=>$wxUser->headimgurl, 'head_sub_image'=>$wxUser->headimgurl);
            $userModel = M('kh_user_info');
            $userModel->startTrans();
            $retID = $userModel->add($user);
            if($retID){
                $findExtraUser = M('kh_user_extra_info');
                $userExtra = array('wx_open_id'=>$openID, 'user_id'=>$retID);
                $extraRet = $findExtraUser->add($userExtra);
                if($extraRet){
                    $userModel->commit();
                    //是否要收藏一下名片
                    if(!empty($collectID)){
                        $cardModel = M('kh_card');
                        $card = array('user_id'=>$retID,'target_id'=>$collectID, 'add_date'=>time());
                        $cardModel->add($card);
                    }
//                    header("Location: ".HTTP_URL_PREFIX."cardHome");
                    header("Location: ".HTTP_URL_PREFIX."modifyCardPage?collectID=".$collectID);
                }else{
                    $userModel->rollback();
                    $this->assign('verifyError',2);
                    $this->assign('username',$username);
                    $this->display('userRegister');
                }
            }else{
                $userModel->rollback();
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


}

