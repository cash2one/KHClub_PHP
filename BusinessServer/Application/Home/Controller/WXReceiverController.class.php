<?php
namespace Home\Controller;
use Think\Controller;
use Think\Log;

require THINK_PATH.'Library/Vendor/wxpay/lib/WxPay.Api.php';

//define("TOKEN", "pinweihuanqiu");
//define("TOKEN", "test");

class WXReceiverController extends Controller {

    public function index(){

//        Log::write($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'],'INFO');
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = \WxPayResults::Init($xml);
//        Log::write(json_encode($result),'INFO');

        if($result['Event'] == 'subscribe') {

            $content = "您好，欢迎关注“品位环球”--专为高端商务人士打造专属定制式服务，提供覆盖精英生活方方面面。\n".
                        "“品位环球”旗下“豪车管家”是中国首家互联网+豪车管家会员服务平台，为中高端车车主提供“一站式”车管家服务。".
                        "关注公众号，注册成为会员即可获取私人管家24小时咨询及电话问诊等<a href=\"".HTTP_URL_PREFIX."privilegeHome\">会员服务</a>。\n".
                        "官方服务电话4008693911，期待您的来电。<a href=\"".HTTP_URL_PREFIX."userHome\">免费注册</a>成为会员。";

            echo '<xml>
              <ToUserName><![CDATA['.$result['FromUserName'].']]></ToUserName>
              <FromUserName><![CDATA['.$result['ToUserName'].']]></FromUserName>
              <CreateTime>'.time().'</CreateTime>
              <MsgType><![CDATA[text]]></MsgType>
              <Content><![CDATA['.$content.']]></Content>
              </xml>';
        }

        //通过分享订阅
        if($result['Event'] == 'subscribe' || $result['Event'] == 'SCAN'){
            if(empty($result['EventKey'])){
                exit;
            }

            $model = M('biz_proxy_share');
            $share = $model->where('share_open_id="'.$result['FromUserName'].'"')->find();
            if(empty($share)){

                $eventKey = $result['EventKey'];
                $eventKey = str_replace('qrscene_', '' ,$eventKey);

                $share = array('share_open_id'=>$result['FromUserName'], 'user_id'=>$eventKey, 'add_date'=>time());
                $model->add($share);
            }
            echo '';
            return;
        }

        if($result['Event'] == 'CLICK' && $result['MsgType'] == 'event'){

            if($result['EventKey'] == 'KEY_ME'){
                //关于我们部分暂时不需要了
                $content = '“品味•环球”专为高端商务人士打造的私人尊享服务平台，提供覆盖精英生活衣食住行方方面面的专属生活服务。专业，专注，高效的为用户带来超越期待的体验和感受。关注公众号，成为会员即可获取私人管家24小时资讯服务与协助等会员特权服务。<a href="http://c.eqxiu.com/s/27ULUB5f">详情请点击</a>';
            }else if($result['EventKey'] == 'KEY_MANAGER'){
                $content = '“豪车管家”是“品味•环球”旗下专为中高端车车主提供全方位车管家服务的商务管家平台，从洗车、保养、维修、线上诊断，到保险业务等，我们都将有专人为您提供服务。现已开通4008693911官方服务电话，期待您的来电。';
            }else if($result['EventKey'] == 'KEY_VIP'){
                $content = "拥有广东车牌的以下品牌车车主：\n1. 奔驰\n2. 宝马\n3. 奥迪\n4. 保时捷\n5. 路虎\n6. 兰博基尼\n7. 宾利\n8. 劳斯劳斯\n9. 法拉利\n10. 玛莎拉蒂\n"
                            ."【“品味•环球”其它城市及区域服务平台正在筹建中，敬请期待。】\n现已开通4008693911官方服务电话，期待您的来电。";
            }else if($result['EventKey'] == 'KEY_INTRO'){
                $content = "注册成为“品味•环球”尊贵的会员，您将享有平台内所有联盟商家提供的会员VIP礼遇，无需另外办理门店会员卡。\n".
                           "礼遇包括：\n".
                            "1、豪车管家24小时电话问诊及资讯，专业的豪车管家将为您提供一切关于您爱车的最佳解决方案。\n".
                            "2、会员卡价格享受爱车精洗项目，无需再办理门店会员卡。此礼遇适用于全平台联盟商家。\n".
                            "3、豪车维修与保养将由专业的豪车管家为您一站式打理，轻松，高效的完成每一个环节。\n".
                            "4、“品味•环球”作为第三方服务平台将为本平台所有联盟商家的服务及产品做质量担保，杜绝任何虚假，伪劣产品，为会员权益提供双重保证。\n";
            }else if($result['EventKey'] == 'KEY_CONTACT'){
                $content = "管家热线：4008693911\n官方网址：".'<a href="http://www.pinweihuanqiu.com">www.pinweihuanqiu.com</a>';
            }else{
                echo '';
                exit;
            }

            //反馈
            echo '<xml>
              <ToUserName><![CDATA['.$result['FromUserName'].']]></ToUserName>
              <FromUserName><![CDATA['.$result['ToUserName'].']]></FromUserName>
              <CreateTime>'.time().'</CreateTime>
              <MsgType><![CDATA[text]]></MsgType>
              <Content><![CDATA['.$content.']]></Content>
              </xml>';
        }

//        $echoStr = $_GET["echostr"];
//        $signature = $_GET["signature"];
//        $timestamp = $_GET["timestamp"];
//        $nonce = $_GET["nonce"];

//        $token = TOKEN;
//        $tmpArr = array($token, $timestamp, $nonce);
//        // use SORT_STRING rule
//        sort($tmpArr, SORT_STRING);
//        $tmpStr = implode( $tmpArr );
//        $tmpStr = sha1( $tmpStr );
//
//        if( $tmpStr == $signature ){
//            echo $echoStr;
//        }else{
//            exit;
//        }
    }

}

