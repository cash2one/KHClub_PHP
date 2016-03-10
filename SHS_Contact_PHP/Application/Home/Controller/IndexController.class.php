<?php
namespace Home\Controller;
use Think\Controller;

require THINK_PATH.'Library/Vendor/wxpay/lib/WxPay.Api.php';
require THINK_PATH.'Library/Vendor/wxpay/WxPay.JsApiPay.php';

Vendor('wxpay.notify');

class IndexController extends Controller {

    public function index(){

        $notify = new \PayNotifyCallBack();
        $notify->Handle(false);
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $result = \WxPayResults::Init($xml);

        $success = $notify->Queryorder($result["transaction_id"]);

//        //订单号
//        $transaction_id = $result["transaction_id"];
//        $input = new \WxPayOrderQuery();
//        $input->SetTransaction_id($transaction_id);
//        $data = \WxPayApi::orderQuery($input);
//        print_r($data);
//        exit();

        //商户自定义订单号
        $out_trade_no = $_REQUEST["out_trade_no"];
        $input = new \WxPayOrderQuery();
        $input->SetOut_trade_no($out_trade_no);
        $data = \WxPayApi::orderQuery($input);
        print_r($data);
        exit();
    }

    public function getOrder(){

        //①、获取用户openid
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
        $input->SetTotal_fee("1");
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
        printf_info($order);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        print_r($jsApiParameters);

    }
}