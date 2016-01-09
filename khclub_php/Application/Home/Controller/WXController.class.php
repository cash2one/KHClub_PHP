<?php
namespace Home\Controller;
use Think\Controller;
class WXController extends Controller {

    private $WX_APPID = 'wxa1cc9ce0fd9a1372';
    private $WX_APPSecret = 'd734cd2152eb5557a78477ed09136196';

    protected $autoCheckFields =false;
    public function index(){
        echo 'nihao';
    }

    //http://www.pinweihuanqiu.com/khclub_php/index.php/Home/WX/weixinTest
    public function weixinTest(){
        $_SESSION['test'] = '1111';
        setcookie('cookie','testestcookie');
        $code = $_REQUEST['code'];
        if(empty($code)){
            echo '不好意思，您微信未授权';
            return;
        }
//        $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxa1cc9ce0fd9a1372&secret=d734cd2152eb5557a78477ed09136196&code=".$code."&grant_type=authorization_code");
//
//        $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.json_decode($content)->access_token.'&openid='.json_decode($content)->openid;
//        echo $msgUrl;
//        $msg = file_get_contents($msgUrl);
//        echo $msg;
//
//
//        $APPID="wxa1cc9ce0fd9a1372";
//        $APPSECRET="d734cd2152eb5557a78477ed09136196";
//
//        $TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$APPID."&secret=".$APPSECRET;
//
//        $json=file_get_contents($TOKEN_URL);
//        $result=json_decode($json);
//        echo $result.'testestests';
//        $ACC_TOKEN=$result->access_token;
//        print_r($result->access_token);
//
//        $data = '{
//            "touser":"'.json_decode($content)->openid.'",
//            "msgtype":"text",
//            "text":
//            {
//                "content":"hello aaa"
//            }
//        }';
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
//
//        $final = json_decode($result);
//        echo $final;

        $this->display("Test");
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
     * @brief 名片首页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardHome
     */
    public  function cardHome(){
        $code = $_REQUEST['code'];
        if(empty($code)){
            echo '不好意思，您微信未授权';
            return;
        }
        $openID = $_SESSION['open_id'];
        if(empty($openID)){
            $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
            $openID = json_decode($content)->openid;
            if(empty($openID)){
                echo '不好意思，您微信未授权openID';
                return;
            }
            //openID存入
            $_SESSION['open_id'] = $openID;
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
                    FROM kh_card c, kh_user_info u WHERE c.delete_flag=0 AND c.target_id=u.id AND c.user_id='.'3'.' ORDER BY c.add_date';
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
            $msgUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.json_decode($content)->access_token.'&openid='.$openID;
            $msg = file_get_contents($msgUrl);
            $wxUser = json_decode($msg);

            $user = array('name'=>$wxUser->nickname,'sex'=>$wxUser->sex-1, 'head_image'=>$wxUser->headimgurl, 'head_sub_image'=>$wxUser->headimgurl);
            $userModel = M('kh_user_info');

            $userModel->startTrans();
            $retID = $userModel->add($user);
            if($retID){
                $userExtra = array('wx_open_id'=>$openID, 'user_id'=>$retID);
                $extraRet = $findExtraUser->add($userExtra);
                if($extraRet){
                    $userExtra = $findExtraUser->query($sql);
                    //存入信息
                    $_SESSION['userInfo'] = $userExtra[0];

                    $userModel->commit();
                    $this->display("cardHolder");
                }else{
                    $userModel->rollback();
                    echo '创建失败';
                }
            }else{
                $userModel->rollback();
                echo '创建失败';
            }
        }

    }

    /**
     * @brief 修改名片页面
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/modifyCardPage
     */
    public  function modifyCardPage(){
        $this->display("infoUpdate");
    }

    /**
     * @brief 修改名片提交
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/modifyCardPage
     */
    public  function putPersonalInfo(){
        $id = $_SESSION['userInfo']['id'];
        if(empty($id)){
            echo '该用户不存在';
            return;
        }
        $userModel = M('kh_user_info');
        $user = $userModel->find($id);

        $userExtraModel = M('kh_user_extra_info');
        $userExtra = $userExtraModel->where('user_id='.$id)->find();

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
        if($ret && $extraRet){
            echo 'test ok';
            $userModel->commit();
        }else{
            echo 'test fail';
            $userModel->rollback();
        }
    }

    /**
     * @brief 名片群首页
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/cardGroupHome
     */
    public  function cardGroupHome(){
        $id = $_SESSION['userInfo']['id'];
        if(empty($id)){
            echo '该用户不存在';
            return;
        }
        $cardsModel = M('kh_wx_card_group');
        $sql = 'SELECT cg.id, cg.group_title FROM kh_wx_card_group cg, kh_wx_card_member cm
                WHERE cg.id=cm.group_id AND cg.delete_flag=0 AND cm.delete_flag=0 AND cm.member_id='.$id;
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
    public  function cardGroupMembers(){
        $group_id = $_REQUEST['group_id'];
        if(empty($group_id)){
            echo '圈子不能为空';
            return;
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

        $this->assign("group", $group);
        $this->assign("manager",$cardList[0]);
        array_shift($cardList);
        $this->assign("cardList",$cardList);
        $this->display("cardGroupMembers");
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
        $id = $_SESSION['userInfo']['id'];
        if(empty($id)){
            echo '该用户不存在';
            return;
        }
        $groupTitle = $_REQUEST['name'];
        if(empty($groupTitle)){
            echo '标题不能为空';
            return;
        }

        $group = array('group_title'=>$groupTitle, 'group_desc'=>$_REQUEST['desc'], 'creator_id'=>$id);

        $cardModel = M('kh_wx_card_group');
        $cardModel->startTrans();
        $ret = $cardModel->add($group);
        if($ret){

            $memberModel = M('kh_wx_card_member');
            $member = array('group_id'=>$ret,'member_id'=>$id, 'add_date'=>time());
            $memberRet = $memberModel->add($member);

            if($memberRet){

                $cardModel->commit();
                $sql = 'SELECT cg.id, ui.name, ui.job, cg.group_title, cg.group_desc FROM kh_wx_card_group cg, kh_user_info ui
                    WHERE ui.id=cg.creator_id AND cg.id='.$ret.' AND cg.delete_flag=0';
                $groupDetail = $cardModel->query($sql);
                $this->assign("groupDetail",$groupDetail[0]);
                $this->display("cardGroupDetail");

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
     * @brief 删除名片群
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WX/deleteCardGroup
     */
    public function deleteCardGroup(){
    }


//    /**
//     * @brief 修改名片页面
//     * 接口地址
//     * http://localhost/khclub_php/index.php/Home/WX/modifyCardPage
//     */
//    public  function modifyCardPage(){
//        $code = $_REQUEST['code'];
//        if(empty($code)){
//            echo '不好意思，您微信未授权';
//            return;
//        }
//        $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->WX_APPID."&secret=".$this->WX_APPSecret."&code=".$code."&grant_type=authorization_code");
//        $openID = json_decode($content)->openid;
//        $findExtraUser = M('kh_user_extra_info');
//        $sql = 'SELECT ui.name, ui.job, ui.phone_num, ui.e_mail, ui.company_name, ui.address, ui.head_sub_image, ue.web, ue.qq, ue.wechat FROM kh_user_info ui, kh_user_extra_info ue
//                WHERE ui.id=ue.user_id AND ui.delete_flag=0 AND ue.wx_open_id="'.$openID.'"';
//        $userExtra = $findExtraUser->query($sql);
//        //存在
//        if($userExtra){
//            print_r($userExtra);
//            print_r($_SESSION['userInfo']);
//            $this->assign('userInfo', $userExtra[0]);
//            $this->display("infoUpdate");
//        }else{
//            echo '微信未授权';
//        }
//
//    }
}

