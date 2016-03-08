<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/5/4
 * Time: 23:06
 */

//电话正则
define('PHONE_MATCH','/^((17[0-9])|(13[0-9])|(14[0-9])|(15[0-9])|(18[0,0-9]))\d{8}$/');


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

    $model = M();
    $sql = 'SELECT user_id FROM biz_user_info WHERE delete_flag=0 AND wx_open_id="'.$openID.'"';
    $user = $model->query($sql)[0];
    return $user;
}