<?php
/**
 * Created by PhpStorm.
 * User: khclub
 * Date: 2016/3/7
 * Time: 10:11
 */
namespace Home\Controller;
use Think\Controller;
use Think\Exception;

Vendor('jssdk');
import('Org.JPush.JPush');

class WXManagerController extends Controller{
    private $app_key = 'a3387af8e9748171ad82d8e7';
    private $master_secret = 'f2ac08cb5fc22a1e20fdb915';

    /**
     * @brief 车辆审核状态
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/carAudit?user_id=13&state=3
     * @param id  汽车ID
     * @param state 状态
     * @param search 搜索内容 1为用户 2为汽车
     */
    public function carAudit(){
        try{
            $car = array();
            $id = $_REQUEST['id'];
            $search = $_REQUEST['search'];
            $mobile = $_REQUEST['mobile'];
            $car['state'] = $_REQUEST['state'];
            $car['update_date'] = time();
            if($car['state'] == CAR_CHECK_OK){
                //审核通过日期
                $car['pass_date'] = time();
            }
            $carModel = M('biz_car');
            $car = $carModel->where('id='.$id)->save($car);
            if($car){
                $carInfos = $carModel->field('user_id, id, plate_number')->where('id='.$id)->find();
                $carInfo['content'] = $carInfos;
            }

            //审核通过推送通知
            if($car['state'] == CAR_CHECK_OK){
                $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
                $ACC_TOKEN = $jssdk->getAccessToken();

                $sql = 'SELECT ui.wx_open_id FROM biz_user_info ui, biz_car c WHERE c.user_id=ui.user_id AND c.id='.$id;
                $openID = $carModel->query($sql)[0]['wx_open_id'];
                if($openID){
                    $data = '{
                                    "touser":"'.$openID.'",
                                    "msgtype":"text",
                                    "text":
                                    {
                                        "content":"您的爱车已通过审核,<a href=\"'.HTTP_URL_PREFIX.'userVerify\">点击查看</a>"
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

                //这里判断是否这是一个被推荐的新用户的第一辆车 如果是则为推荐的代理者增加收益
                $car = $carModel->find($id);
                //判断是被推荐的用户
                $userModel = M('biz_user_info');
                $user = $userModel->where('user_id='.$car['user_id'])->find();

                $shareModel = M('biz_proxy_share');
                $share = $shareModel->where('share_open_id="'.$user['wx_open_id'].'"')->find();
                //不为空
                if(!empty($share)){
                    //判断是否是第一辆车
                    $cars = $carModel->where('state='.CAR_CHECK_OK.' AND user_id="'.$car['user_id'].'"')->select();
                    if(count($cars) == 1 && $cars[0]['id'] == $id){
                        //是第一辆车 增加收益
                        $tradeModel = M('biz_proxy_trade');

                        //查看是否存在了
                        $oldTrade = $tradeModel->where('register_id='.$user['user_id'])->find();
                        if(empty($oldTrade)){
                            //增加一级收益
                            $firstLevel = array('user_id'=>$share['user_id'], 'level'=>1, 'amount'=>'20', 'state'=>1,
                                'register_id'=>$user['user_id'], 'add_date'=>time());
                            $tradeModel->add($firstLevel);
                            //查看上级代理
                            $proxyModel = M('biz_proxy_info');
                            $proxy = $proxyModel->where('user_id='.$share['user_id'])->find();
                            if(!empty($proxy['higher_proxy_id'])){
                                //增加二级收益
                                $secondLevel = array('user_id'=>$proxy['higher_proxy_id'], 'lower_proxy_id'=>$share['user_id'], 'level'=>2,
                                    'amount'=>'10', 'state'=>1, 'register_id'=>$user['user_id'], 'add_date'=>time());
                                $tradeModel->add($secondLevel);
                            }

                            //消息通知
                            $proxyOpenID = $proxy['wx_open_id'];
                            if($proxyOpenID){
                                $data = '{
                                    "touser":"'.$proxyOpenID.'",
                                    "msgtype":"text",
                                    "text":
                                    {
                                        "content":"您获得一份收益,<a href=\"'.HTTP_PROXY_URL_PREFIX.'myTradeList\">点击查看</a>"
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
                        }

                    }
                }

            }
            if($_REQUEST['state'] == 2){
                $carInfo['type'] = 1;
                $carInfo['content']['message'] = '您的车辆已通过审核';
                $carInfo = json_encode($carInfo);
                $title = '品位环球';
                $message = '您的车辆已通过审核';
                $client = new \JPush($this->app_key, $this->master_secret);
                $client->push()
                    ->setPlatform('ios','android')
                    ->addAlias('globalTest'.$carInfos['user_id'])
                    ->setNotificationAlert('Hi, JPush')
                    ->addAndroidNotification($message, $title, 1, array())
                    ->addIosNotification($message, $title, 1, true, 'iOS category', array())
                    ->setMessage($carInfo, $title, 'type', array())
                    ->send();
            }else{
                $carInfo['type'] = 2;
                $carInfo['content']['message'] = '您的车辆未通过审核';
                $carInfo = json_encode($carInfo);
                $title = '品位环球';
                $message = '您的车辆未通过审核';
                $client = new \JPush($this->app_key, $this->master_secret);
                $client->push()
                    ->setPlatform('ios','android')
                    ->addAlias('globalTest'.$carInfos['user_id'])
                    ->setNotificationAlert('Hi, JPush')
                    ->addAndroidNotification($message, $title, 1, array())
                    ->addIosNotification($message, $title, 1, true, 'iOS category', array())
                    ->setMessage($carInfo, $title, 'type', array())
                    ->send();

            }
            if($search == 1){
                header("Location: searchUser?mobile=".$mobile);
                return;
            }else if($search == 2){
                header("Location: searchCar?mobile=".$mobile);
                return;
            }else{
                header("Location: checkHome");
            }
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 检查车辆首页
     * 接口地址
     * http://114.215.95.23/BusinessServer/SHS_Contact_PHP/index.php/Home/WXManager/checkHome
     * @param page  页码
     * @param size 每页数量
     */
    public function checkHome(){
        try{
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 5;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $carNumModel = M();
            $sql = 'SELECT id FROM biz_car WHERE state=1';
            $count = count($carNumModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $carModel = M('biz_car');
            $carInfo = $carModel->where('state=1')->field('id,name,mobile,plate_number,driving_license_url,car_type,state,add_date')->order('add_date  DESC')->limit($start,$end)->select();
            for($j=0;$j<count($carInfo);$j++){
                $carInfo[$j]['add_date'] = date('Y-m-d',$carInfo[$j]['add_date']);
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('carInfo',$carInfo);
            $this->display('checkHome');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 会员信息
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/searchHome
     * @param page  页码
     * @param size 每页数量
     */
    public function searchHome(){
        try{
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $carNumModel = M();
            $sql = 'SELECT COUNT(ca.mobile) FROM biz_user_info us, biz_car ca
                    WHERE us.user_id=ca.user_id GROUP BY ca.user_id';
            $count = count($carNumModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $userModel = M();
            $sql = 'SELECT us.user_id, us.username, COUNT(ca.mobile) carnum, us.add_date FROM biz_user_info us, biz_car ca
                    WHERE us.user_id=ca.user_id GROUP BY ca.user_id ORDER BY us.add_date DESC LIMIT '.$start.','.$end;
            $userInfo = $userModel->query($sql);
            for($j=0;$j<count($userInfo);$j++){
                $userInfo[$j]['add_date'] = date('Y-m-d',$userInfo[$j]['add_date']);
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('userInfo',$userInfo);
            $this->display('searchHome');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 搜索用户
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/searchCar?mobile=18697942051
     * @param mobile 用户电话
     * @param page  页码
     * @param size 每页数量
     */
    public function searchCar(){
        try{
            $mobile = $_REQUEST['mobile'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 5;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $carNumModel = M();
            $sql = 'SELECT id FROM biz_car WHERE state!=0 AND mobile='.$mobile;
            $count = count($carNumModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $carModel = M('biz_car');
            $carInfo = $carModel->where('mobile='.$mobile.' and state!=0')->field('id,name,mobile,plate_number,driving_license_url,car_type,state,add_date,state')->limit($start,$end)->select();
            for($j=0;$j<count($carInfo);$j++){
                $carInfo[$j]['add_date'] = date('Y-m-d',$carInfo[$j]['add_date']);
            }
            $this->assign('mobile',$mobile);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('carInfo',$carInfo);
            $this->display('searchCar');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 查找用户车辆信息
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/searchUser?mobile=18697942051
     * @param mobile 用户电话
     * @param page  页码
     * @param size 每页数量
     */
    public function searchUser(){
        try{
            $mobile = $_REQUEST['mobile'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 5;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $userModel = M('biz_user_info');
            $user = $userModel->field('user_id')->where('username='.$mobile)->find();
            if($user){
                $carNumModel = M();
                $sql = 'SELECT id FROM biz_car WHERE state!=0 and user_id='.$user['user_id'];
                $count = count($carNumModel->query($sql));
                if($count == false){
                    $count = 1;
                }
                $page_count  = ceil($count/$size);
                $carModel = M('biz_car');
                $carInfo = $carModel->where('user_id='.$user['user_id'].' and state!=0')->field('id,name,mobile,plate_number,driving_license_url,car_type,add_date,state')->limit($start,$end)->select();

                for($j=0;$j<count($carInfo);$j++){
                    $carInfo[$j]['add_date'] = date('Y-m-d',$carInfo[$j]['add_date']);
                    $carInfo[$j]['tel'] = $mobile;
                }
            }else{
                $carInfo = '';
                $page_count = 1;
            }

            $this->assign('user_id',$user['user_id']);
            $this->assign('mobile',$mobile);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('carInfo',$carInfo);
            $this->display('searchUser');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 查找用户车辆信息
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/searchUser?mobile=18697942051
     * @param mobile 用户电话
     * @param page  页码
     * @param size 每页数量
     */
    public function giveCoupon(){
        if(!isset($_REQUEST['user_id'])){
            returnJson(0);
            exit;
        }
        $user_id = $_REQUEST['user_id'];
        $model = M('biz_coupon');

        $coupon_code = $model->field('coupon_code')->order('id DESC')->limit(1)->find()['coupon_code'];
        $coupon = array('coupon_code'=>$coupon_code+1, 'user_id'=>$user_id, 'type'=>'1',
                        'state'=>'0', 'send_date'=>time(), 'add_date'=>time());
        $ret = $model->add($coupon);
        if($ret){
            returnJson(1);

            //消息推送
            $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
            $ACC_TOKEN = $jssdk->getAccessToken();

            $sql = 'SELECT wx_open_id FROM biz_user_info WHERE user_id='.$user_id;
            $openID = $model->query($sql)[0]['wx_open_id'];
            if($openID){
                $data = '{
                            "touser":"'.$openID.'",
                            "msgtype":"text",
                            "text":
                            {
                                "content":"尊敬的品位环球豪车会员，您的管家赠送给您免费精洗券一张，<a href=\"'.HTTP_URL_PREFIX.'getCouponList\">查看详情</a>，或查看商家，<a href=\"'.HTTP_URL_PREFIX.'getShops\">去服务</a>。"
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


        }else{
            returnJson(0);
        }
    }

    ///////////////////////////////////////////////.代理.//////////////////////////////////////////////////////

    /**
     * @brief 入口
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/main
     *
     */
    public function main(){
        $this->display('main');
    }

    /**
     * @brief 代理申请详情
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/proxyApply
     * @param page 页码
     * @param size 每页数量
     */
    public function proxyApply(){
        try {
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M('biz_proxy_info');
            $count=count($proxyModel->field("user_id")->where('state=0 and delete_flag=0')->order('add_date desc')->select());
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $proxyInfo = $proxyModel->field("user_id, name, mobile, company, position,add_date")->where('state=0 and delete_flag=0')->order('add_date desc')->limit($start,$end)->select();
            for($i=0;$i<count($proxyInfo);$i++){
                $proxyInfo[$i]['add_date'] = date('Y-m-d',$proxyInfo[$i]['add_date']);
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('proxyInfo',$proxyInfo);
            $this->display('proxyApply');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 代理详情
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/proxy
     * @param page 页码
     * @param size 每页数量
     */
    public function proxy(){
        try {
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M();
            $sql = 'SELECT user_id position FROM biz_proxy_info
                    WHERE state=1 AND delete_flag=0 ORDER BY add_date DESC';
            $count = count($proxyModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT user_id, name, mobile, wx_open_id, company, position FROM biz_proxy_info
                    WHERE state=1 AND delete_flag=0 ORDER BY add_date DESC LIMIT '.$start.','.$end;
            $proxyInfo = $proxyModel->query($sql);
            for($i=0;$i<count($proxyInfo);$i++){
                $sql = 'SELECT SUM(amount) amount FROM biz_proxy_trade WHERE user_id='.$proxyInfo[$i]['user_id'].' AND state=1';
                $amount = $proxyModel->query($sql);
                $proxyInfo[$i]['amount'] = $amount[0]['amount'];
                $proxyInfo[$i]['add_date'] = date('Y-m-d',$proxyInfo[$i]['add_date']);

            }
            for($i=0;$i<count($proxyInfo);$i++){
                if($proxyInfo[$i]['amount'] == null){
                    $proxyInfo[$i]['amount'] = '无';
                }
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('proxyInfo',$proxyInfo);
            $this->display('proxy');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 金额详情
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/proxyDetails?user_id=3
     * @param user_id 代理id
     * @param page 页码
     * @param size 每页数量
     */
    public function proxyDetails(){
        try {
            $user_id = $_REQUEST['user_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M('biz_proxy_info');
            $proxyInfo = $proxyModel->field("user_id, name, mobile, company, wx_open_id, position")->where('user_id='.$user_id)->find();
            $amountModel = M('biz_proxy_trade');
            $count = count($amountModel->field('amount')->where('user_id='.$user_id.' and delete_flag=0')->order('state asc, add_date desc')->select());
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $amountInfo = $amountModel->field('amount,state,add_date,lower_proxy_id')->where('user_id='.$user_id.' and delete_flag=0')->order('state asc, add_date desc')->limit($start,$end)->select();
            for($i=0;$i<count($amountInfo);$i++){
                if($amountInfo[$i]['lower_proxy_id'] == null){
                    $amountInfo[$i]['lower_proxy_name'] = '你';
                    $amountInfo[$i]['add_date'] = date('Y-m-d',$amountInfo[$i]['add_date']);
                }else{
                    $lower_proxy_name = $proxyModel->field("name")->where('user_id='.$amountInfo[$i]['lower_proxy_id'])->find();
                    $amountInfo[$i]['lower_proxy_name'] = $lower_proxy_name['name'];
                    $amountInfo[$i]['add_date'] = date('Y-m-d',$amountInfo[$i]['add_date']);
                }
            }

            $leftMoney = $amountModel->field("CASE WHEN SUM(amount) > 0 THEN SUM(amount) ELSE 0 END total")->where('delete_flag=0 AND state=1 AND user_id='.$user_id)->find();

            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('total', $leftMoney['total']);
            $this->assign('proxyInfo',$proxyInfo);
            $this->assign('amountInfo',$amountInfo);
            $this->display('proxyDetails');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 代理审核
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/proxyAudit?user_id=4&state=1
     * @param user_id 代理id
     * @param state 审核状态 1为审核通过 2为不通过
     */
    public function proxyAudit(){
        try {
            $proxy = array();
            $proxy['user_id'] = $_POST['user_id'];
            $proxy['state'] = $_POST['state'];
            $proxyModel = M('biz_proxy_info');
            $yes = $proxyModel->save($proxy);

            $model = M('biz_proxy_info');

            if($yes){
                returnJson(1,'审核成功！');

                //申请二维码
                $jssdk = new \JSSDK(WX_APPID, WX_APPSecret);
                $ACC_TOKEN = $jssdk->getAccessToken();
                $sql = 'SELECT * FROM biz_proxy_info WHERE delete_flag=0 AND user_id='.$proxy['user_id'];
                $user = $model->query($sql)[0];
                $openID = $user['wx_open_id'];

                if($proxy['state'] == 1){

                    //生成二维码
                    $data = '{
                                "action_name":"QR_LIMIT_SCENE",
                                "action_info":
                                {"scene": {"scene_id": '.$user['user_id'].'}}
                             }';

                    $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$ACC_TOKEN;

                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, $url);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
                    curl_setopt($curl, CURLOPT_POST, 1);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    $result = curl_exec($curl);
                    curl_close($curl);
                    $qrcodeRet = json_decode($result, true);
                    if(!empty($qrcodeRet['errcode'])){
                        exit;
                    }
                    $package = file_get_contents("https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$qrcodeRet['ticket']);

                    $filename = './ProxyQrcode/'.$user['user_id'].'.png';
                    $local_file = fopen($filename, 'w');
                    if(false !== $local_file){
                        if(false !== fwrite($local_file, $package)){
                            fclose($local_file);
                            //代理用户表
                            $user['share_qrcode'] = $filename;
                            $user['update_date'] = time();
                            $model->save($user);
                        }
                    }

                    if($openID){
                        $data = '{
                                    "touser":"'.$openID.'",
                                    "msgtype":"text",
                                    "text":
                                    {
                                        "content":"您的代理申请成功,<a href=\"'.HTTP_PROXY_URL_PREFIX.'proxyCheckSuccess\">点击查看</a>"
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

                    //推送给上级代理
                    if($user['higher_proxy_id']){
                        $higherUser = $model->find($user['higher_proxy_id']);
                        $data = '{
                                    "touser":"'.$higherUser['wx_open_id'].'",
                                    "msgtype":"text",
                                    "text":
                                    {
                                        "content":"恭喜你推荐'.$user['name'].'成为代理,<a href=\"'.HTTP_PROXY_URL_PREFIX.'myProxyList\">点击查看</a>"
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

                }else{

                    //推送消息
                    if($openID){
                        $data = '{
                                    "touser":"'.$openID.'",
                                    "msgtype":"text",
                                    "text":
                                    {
                                        "content":"您的代理申请失败,<a href=\"'.HTTP_PROXY_URL_PREFIX.'proxyEnter\">点击查看</a>"
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

                }

            }else{
                returnJson(0,'审核失败！');
            }

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }


    /**
     * @brief 提现申请
     * 接口地址
     * http://114.215.95.23/BusinessServer/index.php/Home/WXManager/withdrawRequest
     * @param page 页码
     * @param size 每页数量
     */
    public function withdrawRequest(){
        try {
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M();
            $sql = 'SELECT fo.user_id FROM biz_proxy_info fo, biz_proxy_trade tr, biz_withdraw_notice wi
                    WHERE fo.state=1 AND fo.delete_flag=0 AND fo.user_id=tr.user_id AND fo.user_id=wi.user_id AND tr.state=1 AND wi.withdraw_state=0
                    GROUP BY tr.user_id ORDER BY fo.add_date DESC';
            $count = count($proxyModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT fo.user_id, fo.name, fo.mobile, fo.company, fo.position, SUM(tr.amount) amount, wi.withdraw_state
                    FROM biz_proxy_info fo, biz_proxy_trade tr, biz_withdraw_notice wi
                    WHERE fo.state=1 AND fo.delete_flag=0 AND fo.user_id=tr.user_id AND fo.user_id=wi.user_id AND tr.state=1 AND wi.withdraw_state=0
                    GROUP BY tr.user_id ORDER BY fo.add_date DESC LIMIT '.$start.','.$end;
            $proxyInfo = $proxyModel->query($sql);
            for($i=0;$i<count($proxyInfo);$i++){
                $proxyInfo[$i]['add_date'] = date('Y-m-d',$proxyInfo[$i]['add_date']);
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('proxyInfo',$proxyInfo);
            $this->display('withdrawRequest');

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 为代理提现
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXManager/withdrawCommit
     * @param target_id 要提现的账户ID
     */
    function withdrawCommit(){

        //未登录
        if(!isset($_SESSION['manager'])){
            header('Location: '.__ROOT__.'/index.php/Home/login/login');
            exit;
        }
        if(!isset($_POST['target_id'])){
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/proxy');
            exit;
        }
        $target_id = $_POST['target_id'];

        $withdrawModel = M();
        //提现
        $sql = 'UPDATE biz_proxy_trade SET state=2,update_date='.time().',withdraw_date='.time().'
                WHERE user_id="'.$target_id.'" AND state=1 AND delete_flag=0';
        $num = $withdrawModel->execute($sql);

        $sql = 'UPDATE biz_withdraw_notice SET withdraw_state=1 WHERE user_id="'.$target_id.'"';
        $withdrawModel->execute($sql);

        if($num < 1){
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/proxyDetails?user_id='.$target_id);
            exit;
        }else{
            //提现成功
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/proxyDetails?user_id='.$target_id);
            exit;
        }

    }


    //////////////////////////////////.公众号统计.//////////////////////////////////////////////////////
    /**
     * @brief 公众号统计
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXManager/wechatData
     *
     */
    public function wechatData(){
        $this->display('wechatData');
    }
    /**
     * @brief 公众号统计
     * 接口地址
     * http://localhost/BusinessServer/index.php/Home/WXManager/wechatDataInfo?category=1&page=1
     *
     */
    public function wechatDataInfo(){
        try {
            $category = $_REQUEST['category'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $startDay = strtotime(date('2016-04-11'));
            $t = time();
            $today = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));
            $shareModel = M('');
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            $count = ceil(($today - $startDay)/86400);
            $page_count  = ceil($count/$size);
            for($j=1;$j<=$page_count;$j++){
                if($page == $page_count && $j == $page_count){
                    $startDays = $today-(86400*10*($j-1));
                    $endDay = $startDay;
                }elseif($j == $page){
                    $startDays =$today-(86400*10*($j-1));
                    $endDay = $today-86400*10*$j;
                }
            }
            $attention = array();
            $quantity = array();
            for($i=$startDays;$i>$endDay;$i=$i-86400){
                $startTime = mktime(0,0,0,date("m",$i),date("d",$i),date("Y",$i));
                $endTime = mktime(23,59,59,date("m",$i),date("d",$i),date("Y",$i));
                if($category == 1 ){
                    $agency_id = '24';
                    // 查询关注数量及日期
                    $sql = 'select COUNT(id) count from biz_proxy_share WHERE user_id='.$agency_id.' AND add_date>'.$startTime.'
                            AND add_date<'.$endTime;
                    $attentionQuantity = $shareModel->query($sql)[0];
                    //查询注册用户数量
                    $sql = 'SELECT us.user_id FROM biz_proxy_share pr, biz_user_info us
                            WHERE pr.user_id='.$agency_id.' AND pr.share_open_id=us.wx_open_id AND us.delete_flag=0 AND us.add_date > '.$startTime.' AND us.add_date<'.$endTime;
                    $registerQuantitys = $shareModel->query($sql);
                    //查询会员数量
                    $sql = 'SELECT cab.id FROM (select MIN(pass_date) pass_date,user_id from biz_car WHERE state=2 group by user_id) caa, biz_proxy_share pr, biz_user_info us, biz_car cab
                            WHERE pr.user_id='.$agency_id.' AND pr.share_open_id=us.wx_open_id AND us.delete_flag=0 AND us.user_id=cab.user_id
                            AND cab.state=2 AND caa.pass_date=cab.pass_date AND caa.user_id=cab.user_id AND cab.pass_date > '.$startTime.' AND cab.pass_date<'.$endTime.' GROUP BY cab.user_id';
                    $memberQuantity = count($shareModel->query($sql));
                    if($memberQuantity){
                        $member['count'] = $memberQuantity;
                    }else{
                        $member['count'] = 0;
                    }
                    if($registerQuantitys){
                        $registerQuantitys['count'] = count($registerQuantitys);
                    }else{
                        $registerQuantitys['count'] = 0;
                    }
                    $attention['count'] = $attentionQuantity['count'];
                    $attention['days'] = date('Y-m-d',$i);
                    $attention['registerQuantity'] = $registerQuantitys['count'];
                    $attention['memberQuantity'] = $member['count'];
                    array_push($quantity,$attention);
                }else{
                    $agency_id = '23';
                    // 查询关注数量及日期
                    $sql = 'select COUNT(id) count from biz_proxy_share WHERE user_id='.$agency_id.' AND add_date>'.$startTime.'
                            AND add_date<'.$endTime;
                    $attentionQuantity = $shareModel->query($sql)[0];
                    //查询注册用户数量
                    $sql = 'SELECT us.user_id FROM biz_proxy_share pr, biz_user_info us
                            WHERE pr.user_id='.$agency_id.' AND pr.share_open_id=us.wx_open_id AND us.delete_flag=0 AND us.add_date > '.$startTime.' AND us.add_date<'.$endTime;
                    $registerQuantitys = $shareModel->query($sql);
                    //查询会员数量
                    $sql = 'SELECT cab.id FROM (select MIN(pass_date) pass_date,user_id from biz_car WHERE state=2 group by user_id) caa, biz_proxy_share pr, biz_user_info us, biz_car cab
                            WHERE pr.user_id='.$agency_id.' AND pr.share_open_id=us.wx_open_id AND us.delete_flag=0 AND us.user_id=cab.user_id
                            AND cab.state=2 AND caa.pass_date=cab.pass_date AND caa.user_id=cab.user_id AND cab.pass_date > '.$startTime.' AND cab.pass_date<'.$endTime.' GROUP BY cab.user_id';
                    $memberQuantity = count($shareModel->query($sql));
                    if($memberQuantity){
                        $member['count'] = $memberQuantity;
                    }else{
                        $member['count'] = 0;
                    }
                    if($registerQuantitys){
                        $registerQuantitys['count'] = count($registerQuantitys);
                    }else{
                        $registerQuantitys['count'] = 0;
                    }
                    $attention['count'] = $attentionQuantity['count'];
                    $attention['days'] = date('Y-m-d',$i);
                    $attention['registerQuantity'] = $registerQuantitys['count'];
                    $attention['memberQuantity'] = $member['count'];
                    array_push($quantity,$attention);
                }
            }
            $result = array('list'=>$quantity, 'page'=>$page, 'page_count'=>$page_count);
            returnJson(1,"查询成功", $result);
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    ////////////////////////////////////////////消费统计//////////////////////////////////////////////////////
    /**
     * @brief 检查车辆首页
     * 接口地址
     * http://114.215.95.23/BusinessServer/SHS_Contact_PHP/index.php/Home/WXManager/payList
     * @param page  页码
     * @param size 每页数量
     */
    public function payList(){
        try{

            if($_SESSION['manager'] != 1){
                echo '请登录';
                exit;
            }
            if(!isset($page)){
                $page = 1;
            }else{
                $page = $_REQUEST['page'];
            }
            if(!isset($size)){
                $size = 10;
            }else{
                $size = $_REQUEST['size'];
            }

            $start = ($page-1)*$size;
            $end   = $size;
            $model = M();
            $sql = 'SELECT COUNT(1) count FROM biz_shop s, biz_server_provider sp
                    WHERE s.server_id=sp.server_id AND s.delete_flag=0 AND sp.delete_flag=0';
            $count = $model->query($sql)[0]['count'];
            if($count == 0){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT s.id, s.shop_name, CASE WHEN SUM(o.total_fee)>0 THEN SUM(o.total_fee) ELSE 0 END total
                    FROM biz_server_provider sp, biz_shop s LEFT JOIN biz_order o
                    ON(s.id=o.shop_id AND o.state='.ORDER_HAS_USE.' AND o.delete_flag=0)
                    WHERE s.server_id=sp.server_id AND s.delete_flag=0 AND sp.delete_flag=0 GROUP BY s.id LIMIT '.$start.','.$end;

            $list = $model->query($sql);
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('list',$list);
            $this->display('payTable');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 检查车辆首页
     * 接口地址
     * http://114.215.95.23/BusinessServer/SHS_Contact_PHP/index.php/Home/WXManager/payList
     * @param page  页码
     * @param size 每页数量
     */
    public function payDetail(){
        try{

            if($_SESSION['manager'] != 1){
                echo '请登录';
                exit;
            }
            if(!isset($_REQUEST['page'])){
                $page = 1;
            }else{
                $page = $_REQUEST['page'];
            }
            if(!isset($_REQUEST['size'])){
                $size = 10;
            }else{
                $size = $_REQUEST['size'];
            }

            if(!isset($_REQUEST['shop_id'])){
                echo '商店不存在';
                exit;
            }

            $shop_id = $_REQUEST['shop_id'];

            $start = ($page-1)*$size;
            $end   = $size;
            $model = M();
            $sql = 'SELECT COUNT(1) count FROM biz_order o WHERE o.shop_id='.$shop_id.' AND o.state='.ORDER_HAS_USE;
            $count = $model->query($sql)[0]['count'];
            if($count == 0){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT c.name, c.plate_number, c.mobile, o.total_fee, o.use_date
                    FROM biz_order o LEFT JOIN biz_car c ON (o.car_id=c.id)
                    WHERE o.shop_id='.$shop_id.' AND o.state='.ORDER_HAS_USE.' ORDER BY o.use_date DESC LIMIT '.$start.','.$end;
            $list = $model->query($sql);

            for($i=0; $i<count($list); $i++) {
                $list[$i]['use_date'] = date('Y-m-d',$list[$i]['use_date']);
            }

            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('shop_id',$shop_id);
            $this->assign('list',$list);
            $this->display('payDetail');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }
}