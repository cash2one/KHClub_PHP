<?php
//wx5764fdc7f223e062 ef6373955987b110fef9c0108ae15a02 正式
//wxd5db3b57ffdfafb3 2ccd9fe700dda9b0e9db40212dba1f4b 测试用
define("WX_APPID", "wxd5db3b57ffdfafb3"); //a.pinweihuanqiu.com
define("WX_APPSecret","2ccd9fe700dda9b0e9db40212dba1f4b");

define("HTTP_HOST", "http://114.215.95.23"); //a.pinweihuanqiu.com
define("HTTP_URL_PREFIX", HTTP_HOST."/BusinessServer/index.php/Home/WX/");
define("HTTP_SHOP_URL_PREFIX", HTTP_HOST."/BusinessServer/index.php/Home/Shop/");
define("HTTP_PROXY_URL_PREFIX", HTTP_HOST."/BusinessServer/index.php/Home/WXProxy/");

//审核状态 0是取消审核 1是正在审核 2是通过审核 3是未通过审核
define("CAR_CHECK_CANCEL", "0");
define("CAR_CHECKING", "1");
define("CAR_CHECK_OK", "2");
define("CAR_CHECK_FAIL", "3");

//订单状态 0未付款 1已付款 2已使用 3已失效
define("ORDER_NO_PAY", "0");
define("ORDER_HAS_PAY", "1");
define("ORDER_HAS_USE", "2");
define("ORDER_EXPIRE", "3");


/**
 * @brief json固定返回格式
 *
 */
function returnJson($status = 1, $message = '', $result=null){

    //日志记载
    foreach($_REQUEST as $key => $value) {
        \Think\Log::record($key.'->'.$value,'INFO');
    }
    //返回值记载 太多了
//    \Think\Log::record(json_encode($result),'INFO');

    if(isset($result)){

        $result = array_replace_null($result);//替换空字符串
        if(isset($result['list'])){
            $result['list'] = array_replace_null($result['list']);//替换空字符串
        }

    }else{
        $result = "";
    }

    $jsonMap = array();
    $jsonMap['status'] = $status;
    $jsonMap['result'] = $result;
    $jsonMap['message'] = $message;

    echo json_encode($jsonMap);
}


//过滤数组中的空元素
function array_replace_null($result)
{

    if(is_array($result) || is_object($result))
    {
        foreach ($result as $key => $val) {

            if (!isset($val)) {
                $result[$key] = '';
            } else {
                $result[$key] = array_replace_null($result[$key]);
            }
        }
    }elseif(!isset($result)){

        $result = '';
    }

    return $result;

}


/**
 * @brief 获取随机数
 * @param length 随机数长度
 * @param model 模式 0 大小写数字 1 纯数字 2 纯小写字母 3 大写字母 4 大小写字母 5 大写字母数字 6 小写字母数字
 *
 */
function get_rand_code ($length = 6, $mode = 0)
{
    switch ($mode) {
        case '1':
            $str = '1234567890';
            break;
        case '2':
            $str = 'abcdefghijklmnopqrstuvwxyz';
            break;
        case '3':
            $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case '4':
            $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;
        case '5':
            $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
            break;
        case '6':
            $str = 'abcdefghijklmnopqrstuvwxyz1234567890';
            break;
        default:
            $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
            break;
    }
    $randString = '';
    $len = strlen($str)-1;
    for($i = 0;$i < $length;$i ++){
        $num = mt_rand(0, $len);
        $randString .= $str[$num];
    }
    return $randString ;
}

//获取微信用户
function getWXUser()
{
    //先授权获取openID
    $openID = $_SESSION['open_id'];
    if(empty($openID)){
        return null;
    }

    $model = M();
    $sql = 'SELECT user_id FROM biz_user_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
    $user = $model->query($sql)[0];
    return $user;
}

//获取商家用户
function getShopUser()
{
    //先授权获取openID
    $openID = $_SESSION['open_id'];
    if(empty($openID)){
        return null;
    }
    $model = M();
    $sql = 'SELECT id, server_id, shop_name FROM biz_shop WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
    $user = $model->query($sql)[0];
    return $user;
}

//获取代理用户
function getProxyUser()
{
    //先授权获取openID
    $openID = $_SESSION['open_id'];
    if(empty($openID)){
        return null;
    }
    $model = M();
    $sql = 'SELECT * FROM biz_proxy_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
    $user = $model->query($sql)[0];
    return $user;
}
