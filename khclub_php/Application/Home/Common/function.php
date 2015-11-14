<?php
/**
 * Created by PhpStorm.
 * User: Think
 * Date: 2015/5/4
 * Time: 23:06
 */
function testEcho(){

    return 'echo123';
}

function get_image_type(){

    return 1;
}
function get_voice_type(){

    return 2;
}
function get_video_type(){

    return 3;
}
//电话正则
define('PHONE_MATCH','/^((17[0-9])|(13[0-9])|(14[0-9])|(15[0-9])|(18[0,0-9]))\d{8}$/');
//haha号id
define('HELLOHA_ID','/^[_a-zA-Z0-9]{6,20}+$/');


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

/*
 * 云巴推送
 *
 * //添加好友 1
 * //回复状态 2
 * //回复别人的回复 3
 * //点赞 4
 * */
function pushMessage($target_id, $message, $type, $apnsAlert='您有一条新消息'){
    //发送的内容
//    $content = array('type'=>$type,
//                     'content'=>array(
//                         'uid'=>'',
//                         'news_id'=>'',
//                         'news_content'=>'',
//                         'news_image'=>''
//                     ));
    $content = array('type'=>$type,
                    'content'=>$message);

//    'appkey'=>'562de0fa85f5aa6c14e24d1f', 正式地址
//        'seckey'=>'sec-zgF1bsq1fUDRXdpaEeGzg2Cp7B3cNvnBqBbXJGv0P2NcLfxF',
//测试 5616180ae6b2257059049482 sec-iIIth7gtjFEYlSIJNsVQ6P64WfwzOnQGGHkAt61Hf8MmUj7g

    $data = array ( 'method'=>'publish',
        'appkey'=>'5616180ae6b2257059049482',
        'seckey'=>'sec-iIIth7gtjFEYlSIJNsVQ6P64WfwzOnQGGHkAt61Hf8MmUj7g',
        'topic'=>KH.$target_id,
        'msg'=>$content,
        'opts'=>array('apn_json'=>array('aps'=>array('sound'=>'bingbong.aiff','badge'=>1, 'alert'=>$apnsAlert))));
    $data_string = json_encode($data);
    $ch = curl_init('http://rest.yunba.io:8080');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    //推送记载
    \Think\Log::record(json_encode($result),'INFO');
    return $result;
}
