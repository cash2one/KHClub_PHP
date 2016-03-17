<?php
namespace Home\Controller;
use Think\Controller;

Vendor('jssdk');

class WXProxyController extends Controller {

    public function index(){

    }

    /**
     * @brief 代理入口
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/proxyEnter
     */
    public function proxyEnter(){
        //先授权获取openID
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            $code = $_REQUEST['code'];
            if(!empty($code)){
                $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".WX_APPID."&secret=".WX_APPSecret."&code=".$code."&grant_type=authorization_code");
                $openID = json_decode($content)->openid;
                if(empty($openID)){
                    echo '不好意思，您微信未授权openID';
                    return;
                }
                //openID存入
                $_SESSION['open_id'] = $openID;
            }else{
                header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid=".WX_APPID."&redirect_uri=".HTTP_PROXY_URL_PREFIX."proxyEnter&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect");
                exit;
            }
        }

        $user = getProxyUser();

        //如果系统中存在这个人 跳转到主页
        if($user['state'] == 1){
            header("Location: ".HTTP_PROXY_URL_PREFIX."proxyHome");
            exit;
        }

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('user', $user);

        if(empty($user)){
            $this->display('addAgency');
            exit;
        }

        if($user['state'] == 0){
            $this->display('agencyDetails');
        }else if($user['state'] == 2){
            $this->display('auditFailure');
        }
    }

    /**
     * @brief 申请代理入口
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/applyProxyPage
     */
    public function applyProxyPage(){

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);

        $this->display('addAgency');
    }

    /**
     * @brief 申请成为代理
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/proxyEnter
     */
    public function becomeProxy(){

        $openID = $_SESSION['open_id'];
        $user = getProxyUser();

        //0为审核中 1为审核通过 2为未通过
        //重新提交
        if($user['state'] == 2){

            $user['name'] = $_REQUEST['name'];
            $user['mobile'] = $_REQUEST['mobile'];
            $user['company'] = $_REQUEST['company'];
            $user['position'] =  $_REQUEST['position'];
            $user['state'] = 0;
            $user['update_date'] = time();
            $model = M('biz_proxy_info');
            $model->save($user);

        }else if(empty($user)){

            $name = $_REQUEST['name'];
            $mobile = $_REQUEST['mobile'];
            $company = $_REQUEST['company'];
            $position = $_REQUEST['position'];

            $user = array('wx_open_id'=>$openID, 'name'=>$name, 'mobile'=>$mobile,
                          'company'=>$company, 'position'=>$position, 'add_date'=>time(), 'state'=>0);
            $model = M('biz_proxy_info');
            $ret = $model->add($user);
            if(!$ret){
                echo '申请失败';
                exit;
            }
        }

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('user', $user);
        $this->display('agencyDetails');
    }

    /**
     * @brief 推荐说明
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/recommendExplain
     */
    public function recommendExplain(){
        $this->display('recommendExplain');
    }

    /**
     * @brief 代理用户主页
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/proxyHome
     */
    public function proxyHome(){

        $user = getProxyUser();

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('user', $user);
        $this->display('agencyHome');
    }

    /**
     * @brief 代理审核页面
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/proxyCheckPage
     */
    public function proxyCheckSuccess(){

        $user = getProxyUser();

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('user', $user);
        $this->display('auditPass');
    }

    /**
     * @brief 我的代理列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/myProxyList
     */
    public function myProxyList(){

        $user = getProxyUser();

        $model = M('biz_proxy_info');
        $proxyList = $model->where('state=1 AND delete_flag=0 AND higher_proxy_id='.$user['user_id'])
                    ->order('add_date DESC')->select();

        for($i=0; $i<count($proxyList); $i++){
            $proxyList[$i]['add_date'] = date('Y-m-d', $proxyList[$i]['add_date']);
        }

        if(count($proxyList) > 0){
            $this->assign('proxyList', $proxyList);
            $this->display('myAgency');
        }else{
            //wxJs签名
            $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage',$signPackage);
            $this->assign('user', $user);
            $this->display('notAgency');
        }
    }

    /**
     * @brief 我的交易列表
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/myTradeList
     */
    public function myTradeList(){

        $user = getProxyUser();

        $model = M('biz_proxy_trade');
        $sql = 'SELECT * FROM biz_proxy_trade pt LEFT JOIN biz_proxy_info p ON(pt.lower_proxy_id=p.user_id)
                WHERE pt.user_id='.$user['user_id'].' ORDER BY pt.add_date DESC';
        $tradeList = $model->query($sql);

        for($i=0; $i<count($tradeList); $i++){
            if(strlen($tradeList[$i]['name']) > 0){
                $tradeList[$i]['content'] = $tradeList[$i]['name'].'推荐了新用户';
            }else{
                $tradeList[$i]['content'] = '你推荐了新用户';
            }

            $tradeList[$i]['add_date'] = date('Y-m-d', $tradeList[$i]['add_date']);
        }

        if(count($tradeList) > 0){

            $total = $model->field('SUM(amount) total')->where('user_id='.$user['user_id'].' AND state=1')->find();
            $this->assign('total', $total['total']);
            $this->assign('tradeList', $tradeList);
            $this->display('myWallet');
        }else{

            //wxJs签名
            $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
            $signPackage = $jssdk->GetSignPackage();
            $this->assign('signPackage',$signPackage);
            $this->assign('user', $user);
            $this->display('notEarnings');
        }

    }

    /**
     * @brief 交易提现
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/tradeWithdraw
     */
    public function tradeWithdraw(){

        $user = getProxyUser();
        $notice = array('user_id'=>$user['user_id'],'withdraw_state'=>'0', 'add_date'=>time());
        $noticeModel = M('biz_withdraw_notice');
        $ret = $noticeModel->add($notice);
        if($ret){
            returnJson(1,'申请成功');
        }else{
            returnJson(0,'申请失败');
        }
    }

    /**
     * @brief 交易提现
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/shareQrcode
     */
    public function shareQrcode(){

        $user_id = $_REQUEST['user_id'];
        if(empty($user_id)){
            echo '分享异常';
            exit;
        }
        $model = M();
        $sql = 'SELECT * FROM biz_proxy_info WHERE delete_flag=0 AND user_id="'.$user_id.'"';
        $user = $model->query($sql)[0];
        $user['share_qrcode'] = __ROOT__.substr($user['share_qrcode'], 1);

        //wxJs签名
        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
        $signPackage = $jssdk->GetSignPackage();
        $this->assign('signPackage',$signPackage);
        $this->assign('user', $user);
        $this->display('qrcode');
    }

    /**
     * @brief 申请带参数的微信二维码 测试用
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXProxy/applyQrcode
     */
    public function applyQrcode(){
//        $user = getProxyUser();
//        $user['user_id']=1;
//
//        //审核通过推送通知
//        $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
//        $ACC_TOKEN = $jssdk->getAccessToken();
//
//        $data = '{
//                    "action_name":"QR_LIMIT_SCENE",
//                    "action_info":
//                    {"scene": {"scene_id": '.$user['user_id'].'}}
//                }';
//
//        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$ACC_TOKEN;
//
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
//        curl_setopt($curl, CURLOPT_POST, 1);
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
//        $result = curl_exec($curl);
//        curl_close($curl);
//        $qrcodeRet = json_decode($result, true);
//        if(!empty($qrcodeRet['errcode'])){
//            echo '二维码获取失败';
//            exit;
//        }
//        $package = file_get_contents("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$qrcodeRet['ticket']);
//
//        $filename = './ProxyQrcode/'.$user['user_id'].'.png';
//        $local_file = fopen($filename, 'w');
//        if(false !== $local_file){
//            if(false !== fwrite($local_file, $package)){
//                fclose($local_file);
//                //代理用户表
//                $model = M('biz_proxy_info');
//                $user['share_qrcode'] = $filename;
//                $user['update_date'] = time();
//                $model->save($user);
//            }
//        }
    }

}

