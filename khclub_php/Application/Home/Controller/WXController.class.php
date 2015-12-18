<?php
namespace Home\Controller;
use Think\Controller;
class WXController extends Controller {

    protected $autoCheckFields =false;
    public function index(){
        echo 'nihao';

    }

    //http://www.pinweihuanqiu.com/khclub_php/index.php/Home/WX/weixinTest
    public function weixinTest(){

        $code = $_REQUEST['code'];
        if(empty($code)){
            echo '不好意思，您微信未授权';
            return;
        }
        $content = file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=wxa1cc9ce0fd9a1372&secret=d734cd2152eb5557a78477ed09136196&code=".$code."&grant_type=authorization_code");
        echo $content;
    }

    public function cardDetail(){

        $this->display("card");

    }

    /**
     * @brief 名片详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getCardInfo
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

}

