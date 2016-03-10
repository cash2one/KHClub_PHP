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
                $jssdk = new \JSSDK($this->WX_APPID, $this->WX_APPSecret);
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
}