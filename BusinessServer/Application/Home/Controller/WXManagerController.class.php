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

class WXManagerController extends Controller{

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
            $carModel = M('biz_car');
            $carModel->where('id='.$id)->save($car);

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
                $size = 20;
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
            $carInfo = $carModel->where('state=1')->field('id,name,mobile,plate_number,vehicle_number,car_type,state,add_date')->limit($start,$end)->select();
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
                $size = 20;
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
                    WHERE us.user_id=ca.user_id GROUP BY ca.user_id LIMIT '.$start.','.$end;
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
                $size = 20;
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
            $carInfo = $carModel->where('mobile='.$mobile.' and state!=0')->field('id,name,mobile,plate_number,vehicle_number,car_type,state,add_date,state')->limit($start,$end)->select();
            for($j=0;$j<count($carInfo);$j++){
                $carInfo[$j]['add_date'] = date('Y-m-d',$carInfo[$j]['add_date']);
            }
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
                $size = 20;
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
                $carInfo = $carModel->where('user_id='.$user['user_id'].' and state!=0')->field('id,name,mobile,plate_number,vehicle_number,car_type,add_date,state')->limit($start,$end)->select();

                for($j=0;$j<count($carInfo);$j++){
                    $carInfo[$j]['add_date'] = date('Y-m-d',$carInfo[$j]['add_date']);
                    $carInfo[$j]['tel'] = $mobile;
                }
            }else{
                $carInfo = '';
                $page_count = 1;
            }
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('carInfo',$carInfo);
            $this->display('searchUser');
        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
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
                $size = 20;
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
                $size = 20;
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
            $sql = 'SELECT user_id, name, mobile, company, position FROM biz_proxy_info
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
                $size = 20;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M('biz_proxy_info');
            $proxyInfo = $proxyModel->field("user_id, name, mobile, company, position")->where('user_id='.$user_id)->find();
            $amountModel = M('biz_proxy_trade');
            $count = count($amountModel->field('amount')->where('user_id='.$user_id.' and delete_flag=0')->select());
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $amountInfo = $amountModel->field('amount,state,add_date,lower_proxy_id')->where('user_id='.$user_id.' and delete_flag=0')->limit($start,$end)->select();
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
            $this->assign('page',$page);
            $this->assign('page_count',$page_count);
            $this->assign('proxyInfo',$proxyInfo);
            $this->assign('amountInfo',$amountInfo);
            $this->display('proxyDetails');

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
                $size = 20;
            }
            $start = ($page-1)*$size;
            $end   = $size;
            $proxyModel = M();
            $sql = 'SELECT fo.user_id FROM biz_proxy_info fo, biz_proxy_trade tr, biz_withdraw_notice wi
                    WHERE fo.state=1 AND fo.delete_flag=0 AND fo.user_id=tr.user_id AND fo.user_id=wi.user_id AND tr.state=1 AND wi.withdraw_state=1
                    GROUP BY tr.user_id ORDER BY fo.add_date DESC';
            $count = count($proxyModel->query($sql));
            if($count == false){
                $count = 1;
            }
            $page_count  = ceil($count/$size);
            $sql = 'SELECT fo.user_id, fo.name, fo.mobile, fo.company, fo.position, SUM(tr.amount) amount, wi.withdraw_state
                    FROM biz_proxy_info fo, biz_proxy_trade tr, biz_withdraw_notice wi
                    WHERE fo.state=1 AND fo.delete_flag=0 AND fo.user_id=tr.user_id AND fo.user_id=wi.user_id AND tr.state=1 AND wi.withdraw_state=1
                    GROUP BY tr.user_id ORDER BY fo.add_date DESC';
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

            $model = M();

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
                            $model = M('biz_proxy_info');
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
}