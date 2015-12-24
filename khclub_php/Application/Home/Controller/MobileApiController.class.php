<?php
namespace Home\Controller;
use Org\Util\Date;
use Org\Util\Date1;
use Org\Util\Easemob;
use Org\Util\Haha;
use Org\Util\QRcode;
use Org\Util\TDea;
use Org\Util\Yunba;
use Think\Controller;
use Think\Exception;
use Think\Log;
use Think\Model;

define("KH","kh");
define("KH_GROUP","khGroup");

class MobileApiController extends Controller {

    public function index(){

    }
/////////////////////////////////////////////登录注册部分////////////////////////////////////////////////////////////

    /**
     * @brief 注册用户
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/registerUser?username=15810710447&password=123456
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * ////@param verify_code 验证码 没了
     */
    public function registerUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            //判断是否被注册
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('username='.$username))->find();
            if($user){
                returnJson(0 ,'User has exist');
                return;
            }

            //长度不对 因为是md5 所以不判断上限
            if(strlen($password) < 6) {
                returnJson(0 ,'');
                return;
            }

            //因为使用mob.com的验证码 所以自己的机制取消
            $registerModel = D('kh_user_info');
            $data = array();
            $data['username'] = $username;
            $data['password'] = $password;
            $data['phone_num'] = $username;
            $data['name']     = '';//默认姓名
            $data['login_token'] = base64_encode($username.time());
            $data['add_date'] = time();

            //保存
            $registerModel->add($data);

            $loginModel = M('kh_user_info');
            $user = $loginModel->where('username='.$username)->find();

            //注册环信
            $hx = new Easemob();
            $hx->accreditRegister(array('username'=>KH.$user['id'], 'password'=>'123456'));

            //二维码生成
            // 纠错级别：L、M、Q、H
            $level = 'L';
            // 点的大小：1到10,用于手机端4就可以了
            $size = 6;
            $PNG_WEB_DIR = './KHCQRCode/';
            $data = KH.$user['id'];
            $filename = $PNG_WEB_DIR.$data.'.png';
            QRcode::png($data, $filename, $level, $size);
            $user['qr_code'] = $filename;

            returnJson(1,"register successful", $user);

            return;

        }catch (Exception $e){

            returnJson(0,"exception！",$e);
        }
    }

    /**
     * @brief 找回密码
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/findPwd?username=15810710447&password=123456&verify_code=123456
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @param verify_code 验证码
     */
    public function findPwd(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];

            //长度不对 因为是md5 所以不判断上限
            if(strlen($password) < 6) {
                returnJson(0 ,'');
                return;
            }

            //判断是否有该手机
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('username='.$username))->find();
            if(!$user){
                returnJson(0 ,'该手机不存在');
                return;
            }

            $userModel = D('kh_user_info');
            $data = array();
            $data['username'] = $username;
            $data['password'] = $password;
            $data['login_token'] = base64_encode($username.time());
            $data['update_date'] = time();

            $result = $userModel->where('username="'.$username.'"')->save($data);
            if($result) {
                $loginModel = M('kh_user_info');
                $user = $loginModel->where('username='.$username)->find();
                returnJson(1,"密码修改成功", $user);
            }else{
                returnJson(0,"密码修改失败T_T");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 是否存在用户
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/isUser?username=15810710447
     * @param username 用户名(手机号码)
     */
    public function isUser(){
        try{
            $username = $_REQUEST['username'];
            if(empty($username)) {
                returnJson(0, "username can't be empty！");
                return;
            }
            //判断是否被注册
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('username='.$username))->find();
            //1跳转到填写密码 2跳转到注册页面
            if($user){
                returnJson(1 ,'已有用户',array('direction'=>'1'));
            }else{
                returnJson(1 ,'注册用户',array('direction'=>'2'));
            }
            return;
        }catch (Exception $e){

            returnJson(0,"exception！",$e);
        }
    }

    /**
     * @brief 用户登录
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/loginUser?username=13736661241&password=123456
     * @param username 用户名(手机号码)
     * @param password 密码 6-24位
     * @return message 1为被封禁 2为密码错误
     */
    public function loginUser(){
        try{
            $username = $_REQUEST['username'];
            $password = $_REQUEST['password'];
            //判断用户名密码
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('username='.$username ,'password="'.$password.'"'))->find();
            if($user['delete_flag'] == 1){
                //很遗憾 您因为行为不当已被管理员封禁
                returnJson(0,"1");
                return;
            }

            if($user){

                $user['login_token'] = base64_encode($username.time());
                $registerModel = D('kh_user_info');
                $registerModel->save($user);

                //注册环信 每次登录注册 防止注册的时候不成功
                $hx = new Easemob();
                $hx->accreditRegister(array('username'=>KH.$user['id'], 'password'=>'123456'));

                returnJson(1,"登录成功", $user);
                return;
            }else{
                //用户名或密码错误
                returnJson(0,"2");
            }
            return;

        }catch (Exception $e){

            returnJson(0,"exception！",$e);
        }
    }

/////////////////////////////////////////////个人信息部分////////////////////////////////////////////////////////////

    /**
     * @brief 获取用户二维码
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getUserQRCode?
     * @param user_id 用户id
     */
    public function getUserQRCode(){
        try{

            $user_id = $_REQUEST['user_id'];
            if(empty($user_id)){
                returnJson(0,"用户不能为空");
                return;
            }
            $userModel = M('kh_user_info');
            $user = $userModel->find($user_id);
            if(empty($user)){
                returnJson(0,"没有该用户");
                return;
            }
            //存在
            if(file_exists($user['qr_code'])){
                returnJson(1,"查询成功。",substr($user['qr_code'],2));
            }else{
                //生成二维码
                $data = KH.base64_encode($user_id);
                // 纠错级别：L、M、Q、H
                $level = 'L';
                // 点的大小：1到10,用于手机端4就可以了
                $size = 6;
                $PNG_WEB_DIR = './KHCQRCode/';
                $filename = $PNG_WEB_DIR.$data.'.png';
                QRcode::png($data, $filename, $level, $size);
                //生成失败
                if(!file_exists($filename)){
                    returnJson(0,"查询失败。");
                    return;
                }
                $user['qr_code'] = $filename;
                $user['update_date'] = time();
                $ret = $userModel->save($user);
                if($ret){
                    returnJson(1,"查询成功。",substr($user['qr_code'],2));
                }else{
                    returnJson(0,"查询失败。");
                }
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 获取用户图片组
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getNewsCoverList?uid=19
     * @param uid 用户id
     */
    public function getNewsCoverList(){
        try{
            $uid = $_REQUEST['uid'];
            //附件列表2
            $findImagesModel = M('kh_attachment');
            $images = $findImagesModel->field('url, sub_url')->where(array('delete_flag=0 and type=1 and user_id='.$uid))->limit('3')->order('add_date desc')->select();
            $list = array();
            if(empty($images)){
                $list['list'] = array();
            }else{
                $list['list'] = $images;
            }

            //动态数量
            $newsCountModel = M('kh_news_content');
            $newsCount = $newsCountModel->field('count(1) count')->where('delete_flag=0 AND user_id='.$uid)->find();
            if($newsCount){
                $list['news_count'] = $newsCount['count'];
            }else{
                $list['news_count'] = '0';
            }

            if($images){
                returnJson(1,"查询成功", $list);
            }else{
                returnJson(1,"还没有动态", $list);
            }
            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     *
     * @brief 获取用户个人信息
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/personalInfo?uid=19
     * @param uid 查看的用户id
     * @param current_id 访问的人id
     */
    public function personalInfo(){
        try{
            $uid = $_REQUEST['uid'];
            $current_id = $_REQUEST['current_id'];
            $userModel = M('kh_user_info');
            $user = $userModel->find($uid);
            $user['password'] = '';

            $findUser = M('kh_user_info');
            $checkUser = $findUser->find($current_id);
            if(!$checkUser){
                returnJson(0 ,'该用户不存在');
                return;
            }
            if($checkUser['delete_flag'] == 1){
                returnJson(0 ,'您因为不当操作，已经被管理员拉黑');
                return;
            }

            if($user){
                //附件列表
                $findImagesModel = M('kh_attachment');
                $images = $findImagesModel->field('url, sub_url')->where(array('delete_flag=0 and type=1 and user_id='.$uid))->limit('3')->order('add_date desc')->select();
                if(isset($images)){
                    $user['image_list'] = $images;
                }else{
                    $user['image_list'] = array();
                }

                //是否已经是好友
                $relationModel = M('kh_relationship');
                $relation = $relationModel->where('user_id='.$current_id.' and target_id='.$uid.' and delete_flag=0')->find();
                if($relation){
                    $user['isFriend'] = '1';
                    $user['remark'] = $relation['friend_remark'];
                }else{
                    $user['isFriend'] = '0';
                    $user['remark'] = '';
                }
                //是否加了名片
                $cardModel = M('kh_card');
                $card = $cardModel->where('delete_flag=0 and user_id='.$current_id.' and target_id='.$uid)->find();
                if($card){
                    $user['isCollected'] = '1';
                }else{
                    $user['isCollected'] = '0';
                }

                //动态数量
                $newsCountModel = M('kh_news_content');
                $newsCount = $newsCountModel->field('count(1) count')->where('delete_flag=0 AND user_id='.$uid)->find();
                if($newsCount){
                    $user['news_count'] = $newsCount['count'];
                }else{
                    $user['news_count'] = '0';
                }

                if($images){
                    returnJson(1,"查询成功", $user);
                }else{
                    returnJson(1,"还没有动态", $user);
                }
            }else{
                returnJson(0,"查询失败");
            }
            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 用户自己的新闻数组
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/userNewsList?user_id=19
     * @param page 页码 默认1
     * @param size 每页数量 默认10
     * @param user_id 用户id
     */
    public function userNewsList(){
        try{

            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $user_id = $_REQUEST['user_id'];
            if(empty($user_id)){
                returnJson(0,"用户id不能为空");
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }

            $start = ($page-1)*$size;
            $end   = $size;
            $sql = 'SELECT user.id user_id, user.name, user.head_image,user.head_sub_image, news.id ,
                    news.content_text, news.location, news.comment_quantity,
                    news.browse_quantity, news.like_quantity, news.add_date
                    FROM kh_news_content news,kh_user_info user WHERE user.id='.$user_id.' and news.user_id=user.id and news.delete_flag=0
                    ORDER BY news.add_date DESC LIMIT '.$start.','.$end;
            //获取用户详细信息
            $findNews = M();
            $newsList = $findNews->query($sql);

            if(isset($newsList)){
                //SELECT id,type,sub_url,url,size,add_date from jlxc_attachment WHERE entity_id=7 and delete_flag = 0
                if(count($newsList) > 0){
                    //处理图片
                    for($i=0; $i<count($newsList); $i++) {
                        $news = $newsList[$i];
                        //该状态发的图片
                        $imageSql = 'SELECT id,type,sub_url,url,size,add_date
                                      from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY url';
                        $images = $findNews->query($imageSql);
                        //返回尺寸
                        for($j=0; $j<count($images); $j++) {
                            try{
                                $image = new \Think\Image();
                                $path = $images[$j]['url'];
                                $image->open('./Uploads/'.$path);
                                $images[$j]['width']  = $image->size()[0];
                                $images[$j]['height'] = $image->size()[1];
                            }catch (Exception $e){
                                $images[$j]['width']  = '100';
                                $images[$j]['height'] = '100';
                            }
                        }

                        //获取该状态是否这个人赞了
                        $likeModel = M('kh_news_like');
                        $oldLike = $likeModel->where('delete_flag=0 and news_id='.$news['id'].' and user_id='.$user_id)->find();
                        if($oldLike){
                            $newsList[$i]['is_like'] = '1';
                        }else{
                            $newsList[$i]['is_like'] = '0';
                        }
                        $newsList[$i]['images'] = $images;
                        $newsList[$i]['add_date'] = date('Y-m-d H:i:s', $newsList[$i]['add_date']);
                    }
                }

                $result = array();
                $result['list'] = $newsList;

                //是否是最后一页
                if(count($newsList) < $size){
                    $result['is_last'] = '1';
                }else{
                    $result['is_last'] = '0';
                }

                returnJson(1,"查询成功", $result);
                return;
            }else{
                returnJson(0,"查询失败T_T");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 删除发布的状态
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/deleteSecondComment
     * @param news_id 新闻id
     *
     */
    public  function deleteNews(){
        try{
            $news = array();
            $news['id'] = $_REQUEST['news_id'];
            $news['delete_date'] = time();
            $news['delete_flag'] = 1;
            //修改状态为删除
            $newsModel = M('kh_news_content');
            $ret = $newsModel->save($news);

            $imageModel = M('kh_attachment');
            $images = $imageModel->where('entity_id='.$news['id'])->select();
            for($i=0; $i<count($images); $i++){
                $images[$i]['delete_flag'] = 1;
                $images[$i]['delete_date'] = time();
                $imageModel->save($images[$i]);
            }

            if($ret){
                returnJson(1,"删除成功!");
                return;
            }else{
                returnJson(0,"删除失败!");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 修改个人信息
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/changePersonalInformation?
     * @param uid 用户id
     * @param field 参数名
     * @param value 参数值
     */
    public function changePersonalInformation(){
        try{
            $uid = $_REQUEST['uid'];
            $field = $_REQUEST['field'];
            $value = $_REQUEST['value'];
            //获取用户详细信息
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('id='.$uid))->find();
            $user[$field] = $value;
            $user['update_date'] = time();
            $updateModel = D('kh_user_info');
            $ret = $updateModel->save($user);
            if($ret){
                returnJson(1,"保存成功");
                return;
            }else{
                returnJson(0,"保存失败!");
            }
            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 修改个人额外信息
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/changePersonalExtraInformation?
     * @param uid 用户id
     * @param field 参数名
     * @param value 参数值
     */
    public function changePersonalExtraInformation(){
        try{
            $uid = $_REQUEST['uid'];
            $field = $_REQUEST['field'];
            $value = $_REQUEST['value'];
            //获取用户详细信息
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('id='.$uid))->find();
            if(!$user){
                returnJson(0,"该用户不存在!");
                return;
            }
            $findExtraUser = M('kh_user_extra_info');
            $userExtra = $findExtraUser->where(array('user_id='.$uid))->find();
            $ret = '';
            //存在
            if($userExtra){
                $userExtra[$field] = $value;
                $userExtra['update_date'] = time();
                $ret = $findExtraUser->save($userExtra);
            }else{
                $newExtra = array('user_id'=>$uid, $field=>$value, 'add_date'=>time());
                $ret = $findExtraUser->add($newExtra);
            }

            if($ret){
                returnJson(1,"保存成功");
            }else{
                returnJson(0,"保存失败!");
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 获取个人额外信息
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getPersonalExtraInformation?
     * @param uid 用户id
     */
    public function getPersonalExtraInformation(){
        try{
            $uid = $_REQUEST['uid'];

            $findExtraUser = M('kh_user_extra_info');
            $userExtra = $findExtraUser->where('user_id='.$uid)->find();
            if($userExtra){
                returnJson(1,"获取成功", $userExtra);
            }else{
                returnJson(0,"保存失败!");
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 修改个人状态信息
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/changePersonalInformationState?
     * @param uid 用户id
     * @param field 参数名
     * @param value 参数值
     * @param state_field 状态参数名
     * @param state_value 参数值
     */
    public function changePersonalInformationState(){
        try{
            $uid = $_REQUEST['uid'];
            $field = $_REQUEST['field'];
            $value = $_REQUEST['value'];
            $stateField = $_REQUEST['state_field'];
            $stateValue = $_REQUEST['state_value'];
            //获取用户详细信息
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('id='.$uid))->find();
            $user[$field] = $value;
            $user[$stateField] = $stateValue;
            $user['update_date'] = time();
            $updateModel = D('kh_user_info');
            $ret = $updateModel->save($user);
            if($ret){
                returnJson(1,"保存成功");
                return;
            }else{
                returnJson(0,"保存失败!");
            }
            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }


    /**
     * @brief 修改个人信息中的图片:头像 背景图
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/changeInformationImage?
     * @param uid 用户id
     * @param field 参数名
     */
    public function changeInformationImage(){
        try{

            $uid = $_REQUEST['uid'];
            $field = $_REQUEST['field'];
            if(strlen($field) < 1){
                returnJson(0,"保存失败!");
                return;
            }
            //获取用户详细信息
            $findUser = M('kh_user_info');
            $user = $findUser->where(array('id='.$uid))->find();

            $info = null;
            $upload = null;

            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }
            $retPath = '';
            //上传成功
            if($info) {
                $image = new \Think\Image();
                foreach($info as $file){

                    $user[$field] = $file['savepath'].$file['savename'];
                    $path = $file['savepath'].$file['savename'];
                    //如果是头像则制作缩略图
                    if($field == 'head_image'){
                        $image->open('./Uploads/'.$path);
                        //缩略图地址前半部分
                        $preffix = substr($path, 0, strlen($path)-4);
                        //后缀
                        $suffix  = substr($path, strlen($path)-4);
                        //拼接
                        $subpath = $preffix.'_sub'.$suffix;
                        $user['head_sub_image'] = $subpath;
                        $image->thumb(360, 360)->save('./Uploads/'.$subpath, null, 90);
                    }
                    $retPath = $path;
                }
            }else{
                returnJson(0,"保存失败!");
                return;
            }

            $user['update_date'] = time();
            $ret = $findUser->save($user);

            $image = array('image'=>$user[$field]);
            if($field == 'head_image'){
                $image = array('image'=>$retPath, 'subimage'=>$user['head_sub_image']);
            }

            if($ret){
                returnJson(1,"保存成功", $image);
                return;
            }else{
                returnJson(0,"保存失败!");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 举报功能
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/reportOffence?
     * @param uid 举报人的id
     * @param report_uid 要举报的用户id
     * @param report_content 举报内容
     */
    public function reportOffence(){
        try{

            $uid = $_REQUEST['uid'];
            $report_uid = $_REQUEST['report_uid'];
            $report_content = $_REQUEST['report_content'];

            if(empty($uid)){
                returnJson(0,"举报人不能为空");
                return;
            }

            if(empty($report_uid)){
                returnJson(0,"被举报人不能为空");
                return;
            }

            if(empty($report_content)){
                returnJson(0,"举报内容不能为空");
                return;
            }

            $report = array('uid'=>$uid,'report_uid'=>$report_uid, 'report_content'=>$report_content);

            $reportModel = M('kh_report');
            $ret = $reportModel->add($report);

            if($ret){
                returnJson(1,"举报成功,我们会尽快为您处理！");
                return;
            }else{
                returnJson(0,"举报失败!");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 举报功能
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getLastestVersion
     * @param sys 系统 1为安卓 2为iOS
     */
    public function getLastestVersion(){
        try{
            $sys = $_REQUEST['sys'];
            if(empty($sys)){
                $sys = '1';
            }
            $versionModel = M('kh_version');
            $sysModel = $versionModel->where('device_code='.$sys)->find();

            if(count($sysModel)>0){
                returnJson(1,"获取成功！", $sysModel);
                return;
            }else{
                returnJson(0,"获取失败!");
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 收藏名片
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/collectCard?
     * @param user_id 用户ID
     * @param target_id 收藏id
     */
    public function collectCard(){
        try{

            $user_id = $_REQUEST['user_id'];
            $target_id = $_REQUEST['target_id'];

            if(empty($user_id)){
                returnJson(0,"用户不能为空");
                return;
            }

            if(empty($target_id)){
                returnJson(0,"收藏不能为空");
                return;
            }
            if($user_id == $target_id){
                returnJson(0,"不能收藏自己");
                return;
            }

            $cardModel = M('kh_card');
            $card = $cardModel->where('user_id='.$user_id.' and target_id='.$target_id)->find();
            if($card){
                if($card['delete_flag'] == 0){
                    returnJson(1,"收藏成功");
                    return;
                }else{
                    $card['delete_flag'] = 0;
                    $card['update_date'] = time();
                    $ret = $cardModel->save($card);
                    if($ret){
                        returnJson(1,"收藏成功");
                        return;
                    }else{
                        returnJson(0,"收藏失败!");
                    }
                }

            }else{
                $card = array('user_id'=>$user_id,'target_id'=>$target_id, 'add_date'=>time());
                $ret = $cardModel->add($card);
                if($ret){
                    returnJson(1,"收藏成功");
                    return;
                }else{
                    returnJson(0,"收藏失败!");
                }
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 删除名片
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/collectCard?
     * @param user_id 用户ID
     * @param target_id 收藏id
     */
    public function deleteCard(){
        try{

            $user_id = $_REQUEST['user_id'];
            $target_id = $_REQUEST['target_id'];

            if(empty($user_id)){
                returnJson(0,"用户不能为空");
                return;
            }

            if(empty($target_id)){
                returnJson(0,"删除不能为空");
                return;
            }

            $cardModel = M('kh_card');
            $card = $cardModel->where('user_id='.$user_id.' and target_id='.$target_id.' and delete_flag=0')->find();
            if($card){
                $card['delete_flag'] = 1;
                $card['delete_date'] = time();
                $ret = $cardModel->save($card);
                if($ret){
                    returnJson(1,"删除成功");
                    return;
                }else{
                    returnJson(0,"删除失败!");
                }
            }else{
                returnJson(1,"删除成功");
                return;
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 名片列表
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/cardList
     * @param user_id 用户id
     * @param page 页数
     * @param size 数量
     */
    public  function getCardList(){
        try{
            $user_id = $_REQUEST['user_id'];
            //用户为空
            if(empty($user_id)){
                returnJson(0,"用户为空");
                return;
            }

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

            $cardModel = M();
            $sql = 'SELECT u.id user_id, u.head_sub_image, u.name, u.sex, u.job, u.company_name, u.signature, u.e_mail, u.phone_num, u.address, u.email_state, u.phone_state, u.address_state
                    FROM kh_card c, kh_user_info u WHERE c.delete_flag=0 AND c.target_id=u.id AND c.user_id='.$user_id.' ORDER BY c.add_date LIMIT '.$start.','.$end;
            $cardList = $cardModel->query($sql);

            $relationshipModel = M('kh_relationship');
            for ($i=0; $i<count($cardList); $i++) {
                $card = $cardList[$i];
                $targetUser = $card['user_id'];
                $relation = $relationshipModel->where('user_id='.$user_id.' and target_id='.$targetUser)->find();
                if($relation){
                    if($relation['delete_flag'] == 0){
                        $cardList[$i]['is_friend'] = '1';
                    }else{
                        $cardList[$i]['is_friend'] = '0';
                    }
                }else{
                    $cardList[$i]['is_friend'] = '0';
                }
            };

            $result = array();
            $result['list'] = $cardList;
            //是否是最后一页
            if(count($cardList) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            //添加过
            returnJson(1,"获取成功", $result);

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
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
                    $card['head_image'] = 'http://localhost/khclub_php/Uploads/'.$user['head_image'];
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


/////////////////////////////////////////////首页状态流部分////////////////////////////////////////////////////////////
    /**
     * @brief 发布状态
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/publishNews?
     * @param uid 用户id
     * @param content_text 内容
     * @param location 地理位置
     * @param  //file
     */
    public function publishNews(){
        try{

            $uid = $_REQUEST['uid'];
            $content_text = $_REQUEST['content_text'];
            $location = $_REQUEST['location'];
            if(empty($_FILES) && empty($content_text)) {
                returnJson(0 ,'内容不能为空');
                return;
            }
            //发布到的圈子
            $circles = $_REQUEST['circles'];

            $findUser = M('kh_user_info');
            $user = $findUser->find($uid);
            if(!$user){
                returnJson(0 ,'该用户不存在');
                return;
            }
            if($user['delete_flag'] == 1){
                returnJson(0 ,'您因为不当操作，已经被管理员拉黑');
                return;
            }

            $info = null;
            $upload = null;
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }

            //获取用户详细信息
            $news = array();
            $news['user_id'] = $uid;
            $news['content_text'] = $content_text;
            $news['location'] = $location;
            $news['add_date'] = time();

            //添加数据
            $newsModel = D('kh_news_content');
            $attachmentModel = D('kh_attachment');

            $newsModel->startTrans();
            $news_id = $newsModel->add($news);

            if($news_id){
                //发布的圈子
                if(!empty($circles)){
                    $cirlcesArr = explode(',', $circles);
                    $newsCircles = array();
                    foreach($cirlcesArr as $circleId){
                        $circleEntity = array('news_id'=>$news_id, 'circle_id'=>$circleId, 'add_date'=>time());
                        array_push($newsCircles, $circleEntity);
                    }
                    $newsExtraModel = M('kh_news_extra');
                    $extraRet = $newsExtraModel->addAll($newsCircles);
                    if(!$extraRet){
                        $newsModel->rollback();
                        returnJson(0,'发布失败!');
                        return;
                    }
                }

                $attachment = array();
                //返回值
                $retJson = array();
                //上传成功
                if($info) {
                    $image = new \Think\Image();
                    foreach($info as $file){
//                    $user['head_image'] = $file['savepath'].$file['savename'];
                        $path = $file['savepath'].$file['savename'];
                        //返回值添加
                        $retJson[$file['savename']] = $path;
                        $image->open('./Uploads/'.$path);
                        //缩略图地址前半部分
                        $preffix = substr($path, 0, strlen($path)-4);
                        //后缀
                        $suffix  = substr($path, strlen($path)-4);
                        //拼接
                        $subpath = $preffix.'_sub'.$suffix;

                        $single_file = array();
                        $single_file['user_id'] = $uid;
                        $single_file['entity_id'] = $news_id;
                        $single_file['type'] = get_image_type();
                        $single_file['sub_url'] = $subpath;
                        $single_file['url'] = $path;
                        $single_file['size'] = filesize('./Uploads/'.$path);
                        $single_file['add_date'] = time();
                        $image->thumb(360, 360)->save('./Uploads/'.$subpath, null, 90);

                        array_push($attachment, $single_file);

                    }
                    $aret = $attachmentModel->addAll($attachment);
                    if($aret){

                        $newsModel->commit();
                        returnJson(1,'发布成功', $retJson);
                        return;
                    }else{
                        $newsModel->rollback();
                        returnJson(0,'发布失败!');
                        return;
                    }
                }

                $newsModel->commit();
                returnJson(1,'发布成功', '');
                return;

            }else{
                $newsModel->rollback();
                returnJson(0,'发布失败!');
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }


    /**
     * @brief 新闻列表
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/newsList
     * @param page 页码 默认1
     * @param size 每页数量 默认10
     * @param user_id 用户id
     * @param frist_time 第一条状态的时间
     */
    public function newsList(){
        try{

            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $user_id = $_REQUEST['user_id'];
            $frist_time = $_REQUEST['frist_time'];

            if(empty($user_id)){
                returnJson(0,"用户Id不能为空");
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            if(empty($frist_time)){
                $frist_time = time();
            }

            $start = ($page-1)*$size;
            $end   = $size;
            $sql = 'SELECT user.id user_id, user.name, user.head_image,user.head_sub_image, user.job, news.id ,
                    user.company_name, news.content_text, news.location, news.comment_quantity,
                    news.browse_quantity, news.like_quantity, news.add_date
                    FROM kh_news_content news,kh_user_info user WHERE news.add_date<='.$frist_time.' and news.user_id = user.id and news.delete_flag = 0
                    ORDER BY news.add_date DESC LIMIT '.$start.','.$end;

            //获取用户详细信息
            $findNews = M();
            $newsList = $findNews->query($sql);

            if(isset($newsList)){
                //SELECT id,type,sub_url,url,size,add_date from jlxc_attachment WHERE entity_id=7 and delete_flag = 0
                if(count($newsList) > 0){

                    //处理图片
                    for($i=0; $i<count($newsList); $i++) {
                        $news = $newsList[$i];
                        //该状态发的图片
                        $imageSql = 'SELECT id,type,sub_url,url,size,add_date
                                      from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY url';
                        $images = $findNews->query($imageSql);
                        //返回尺寸
                        for($j=0; $j<count($images); $j++) {
                            try{
                                $image = new \Think\Image();
                                $path = $images[$j]['url'];
                                $image->open('./Uploads/'.$path);
                                $images[$j]['width']  = $image->size()[0];
                                $images[$j]['height'] = $image->size()[1];
                            }catch (Exception $e){
                                $images[$j]['width']  = '100';
                                $images[$j]['height'] = '100';
                            }
                        }

                        //获取该状态是否这个人赞了
                        $likeModel = M('kh_news_like');
                        $oldLike = $likeModel->where('delete_flag=0 and news_id='.$news['id'].' and user_id='.$user_id)->find();
                        if($oldLike){
                            $newsList[$i]['is_like'] = '1';
                        }else{
                            $newsList[$i]['is_like'] = '0';
                        }

                        //该状态所属圈子
                        $circleSql = 'SELECT pc.circle_name circle_name FROM kh_personal_circle pc, kh_news_extra ne
                                      WHERE ne.news_id='.$news['id'].' AND pc.id=ne.circle_id AND pc.delete_flag=0';
                        $circles = $likeModel->query($circleSql);
                        $circleArr = array();
                        foreach($circles as $circleModel){
                            array_push($circleArr, $circleModel['circle_name']);
                        }
                        $newsList[$i]['circles'] = $circleArr;
                        //赋值
                        $newsList[$i]['images'] = $images;
                        $newsList[$i]['add_time'] = $newsList[$i]['add_date'];
                        $newsList[$i]['add_date'] = date('Y-m-d H:i:s', $newsList[$i]['add_date']);
                    }
                }

                $result = array();
                $result['list'] = $newsList;
                //是否是最后一页
                if(count($newsList) < $size){
                    $result['is_last'] = '1';
                }else{
                    $result['is_last'] = '0';
                }
                returnJson(1,"查询成功", $result);
                return;
            }else{
                returnJson(0,"查询失败T_T");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 新闻详情
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/newsDetail
     * @param news_id 新闻id
     * @param user_id 用户id
     */
    public function newsDetail(){
        try{

            $news_id = $_REQUEST['news_id'];
            $user_id = $_REQUEST['user_id'];
            if(empty($news_id)){
                returnJson(0,"查询状态不能为空!");
                return;
            }
            if(empty($user_id)){
                returnJson(0,"用户不能为空!");
                return;
            }
            $newsModel = M('kh_news_content');
            $news = $newsModel->where('id='.$news_id.' and delete_flag = 0')->find();
            if(!$news){
                returnJson(0,"该状态不存在TAT!");
                return;
            }

            //从新查出关联信息
            $sql = 'SELECT user.id user_id, user.name, user.job, user.company_name, user.head_image,user.head_sub_image, news.id ,
                    news.content_text, news.location, news.comment_quantity,
                    news.browse_quantity, news.like_quantity, news.add_date
                    FROM kh_news_content news, kh_user_info user
                    WHERE news.id='.$news_id.' and news.user_id = user.id and news.delete_flag = 0';

            $news = $newsModel->query($sql)[0];

            $findNews = M();
            //获取该状态的评论
            $commentSql = 'SELECT c.id, u.name, u.head_image, u.head_sub_image, u.job, c.add_date, c.user_id,c.comment_content,c.target_id, u2.name target_name
                            from kh_news_comment c LEFT JOIN kh_user_info u2 ON (c.target_id=u2.id), kh_user_info u WHERE c.user_id=u.id and c.delete_flag = 0
                            and c.news_id='.$news['id'].' ORDER BY c.add_date';
            $comments = $findNews->query($commentSql);
            $comments = array_replace_null($comments);

            //该状态发的图片
            $imageSql = 'SELECT id,type,sub_url,url,size,add_date
                                      from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY url';
            $images = $findNews->query($imageSql);
            //返回尺寸
            for($j=0; $j<count($images); $j++) {
                try{
                    $image = new \Think\Image();
                    $path = $images[$j]['url'];
                    $image->open('./Uploads/'.$path);
                    $images[$j]['width']  = $image->size()[0];
                    $images[$j]['height'] = $image->size()[1];
                }catch (Exception $e){
                    $images[$j]['width']  = '100';
                    $images[$j]['height'] = '100';
                }
            }
            //获取该状态点赞的人
            //SELECT * FROM jlxc_news_like WHERE news_id=22 AND delete_flag = 0 LIMIT 8
            $likeSql = 'SELECT l.user_id,u.head_image ,u.head_sub_image,u.name, u.job FROM kh_news_like l,kh_user_info u
                        WHERE l.user_id = u.id and l.news_id='.$news['id'].' AND l.delete_flag = 0 order by l.add_date DESC';
            $likes = $findNews->query($likeSql);
            //获取该状态是否这个人赞了
            $likeModel = M('kh_news_like');
            $oldLike = $likeModel->where('delete_flag=0 and news_id='.$news['id'].' and user_id='.$user_id)->find();
            if($oldLike){
                $news['is_like'] = '1';
            }else{
                $news['is_like'] = '0';
            }
            for($i=0; $i<count($comments); $i++){
                $comments[$i]['add_date'] = date('Y-m-d H:i:s', $comments[$i]['add_date']);
            }

            //该状态所属圈子
            $circleSql = 'SELECT pc.circle_name circle_name FROM kh_personal_circle pc, kh_news_extra ne
                                      WHERE ne.news_id='.$news_id.' AND pc.id=ne.circle_id AND pc.delete_flag=0';
            $circles = $likeModel->query($circleSql);
            $circleArr = array();
            foreach($circles as $circleModel){
                array_push($circleArr, $circleModel['circle_name']);
            }
            $news['circles'] = $circleArr;
            $news['images'] = $images;
            $news['comments'] = $comments;
            $news['likes'] = $likes;
            $news['add_date'] = date('Y-m-d H:i:s', $news['add_date']);
            returnJson(1,"查询成功", $news);
            return;

        }catch (Exception $e){

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 发布一级评论
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/sendComment
     * @param news_id 状态id
     * @param user_id 用户id
     * @param target_id 评论的用户id
     * @param comment_content 评论的内容
     *
     */
    public  function sendComment(){
        try{
            $comment = array();
            $comment['news_id'] = $_REQUEST['news_id'];
            $comment['user_id'] = $_REQUEST['user_id'];
            $comment['target_id'] = $_REQUEST['target_id'];
            $comment['comment_content'] = $_REQUEST['comment_content'];
            $comment['add_date'] = time();

            if(empty($comment['news_id'])){
                returnJson(0 ,'状态不存在');
                return;
            }

            $findUser = M('kh_user_info');
            $checkUser = $findUser->find($comment['user_id']);
            if(!$checkUser){
                returnJson(0 ,'该用户不存在');
                return;
            }
            if($checkUser['delete_flag'] == 1){
                returnJson(0 ,'您因为不当操作，已经被管理员拉黑');
                return;
            }

//            $permissSql = 'SELECT COUNT(1) isExist FROM kh_news_content nc, kh_news_extra ne, kh_user_circle uc
//                          WHERE nc.id='.$comment['news_id'].' AND ne.news_id=nc.id AND ne.circle_id=uc.circle_id
//                          AND uc.user_id='.$comment['user_id'].' AND nc.delete_flag=0 AND uc.delete_flag=0';
//            $isExist = $findUser->query($permissSql)[0]['isExist'];
//            if($isExist == '0'){
//                returnJson(0 ,'您没有关注这个圈子', '1');
//                return;
//            }

            $newsModel = M('kh_news_content');
            $news = $newsModel->where('id='.$comment['news_id'].' and delete_flag = 0')->find();
            if(!$news){
                returnJson(0,"该状态不存在TAT!");
                return;
            }
            if(empty($comment['comment_content'])){
                returnJson(0,"评论内容不能为空");
                return;
            }

            $commentModel = D('kh_news_comment');
            $ret = $commentModel->add($comment);
            if($ret){

                $news['comment_quantity'] ++;
                $newsModel->save($news);
                $comment = $commentModel->find($ret);
                $comment['add_date'] = date('Y-m-d H:i:s', $comment['add_date']);
                returnJson(1,"发送成功", $comment);

                //如果评论的自己 则推送通知
                if($news['user_id'] != $comment['user_id']){
                    $imagePath = '';
                    //该状态发的图片
                    $imageSql = 'SELECT sub_url
                                  from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY id DESC';
                    $images = $commentModel->query($imageSql);
                    if(!empty($images)){
                        $imagePath = $images[0]['sub_url'];
                    }

                    //获取头像
                    $userModel = M('kh_user_info');
                    //发的人
                    $user = $userModel->field('name,head_sub_image')->where('id='.$comment['user_id'])->find();
                    //主人
                    $newsUser = $userModel->field('name')->where('id='.$news['user_id'])->find();
                    //要发送的内容
                    $content = array(
                        'uid'=>$comment['user_id'],
                        'name'=>$user['name'],
                        'head_image'=>$user['head_sub_image'],
                        'comment_content'=>$comment['comment_content'],
                        'news_id'=>$news['id'],
                        'news_content'=>$news['content_text'],
                        'news_image'=>$imagePath,
                        'news_user_name'=>$newsUser['name'],
                        'push_time'=>date('Y-m-d H:i:s', time())
                    );
                    //推送通知
                    pushMessage($news['user_id'],$content, 2, '有人为你评论了');

                    //如果不为空 并且 如果不是评论的状态发送人 则推送通知
                    if(!empty($comment['target_id']) && $news['user_id'] != $comment['target_id']){

                        //要发送的内容
                        $content = array(
                            'uid'=>$comment['user_id'],
                            'head_image'=>$user['head_sub_image'],
                            'name'=>$user['name'],
                            'comment_content'=>$comment['comment_content'],
                            'news_id'=>$news['id'],
                            'news_content'=>$news['content_text'],
                            'news_image'=>$imagePath,
                            'news_user_name'=>$newsUser['name'],
                            'push_time'=>date('Y-m-d H:i:s', time())
                        );
                        //推送通知
                        pushMessage($comment['target_id'],$content,2, '有人为你评论了');
                    }
                }else{
                    //如果不为空 并且 如果不是评论的自己 则推送通知
                    if(!empty($comment['target_id']) && $comment['user_id'] != $comment['target_id']){
                        $imagePath = '';
                        //该状态发的图片
                        $imageSql = 'SELECT sub_url
                                  from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY id DESC';
                        $images = $commentModel->query($imageSql);
                        if(!empty($images)){
                            $imagePath = $images[0]['sub_url'];
                        }

                        //获取头像
                        $userModel = M('kh_user_info');
                        //发的人
                        $user = $userModel->field('name,head_sub_image')->where('id='.$comment['user_id'])->find();
                        //主人
                        $newsUser = $userModel->field('name')->where('id='.$news['user_id'])->find();

                        //要发送的内容
                        $content = array(
                            'uid'=>$comment['user_id'],
                            'head_image'=>$user['head_sub_image'],
                            'name'=>$user['name'],
                            'comment_content'=>$comment['comment_content'],
                            'news_id'=>$news['id'],
                            'news_content'=>$news['content_text'],
                            'news_image'=>$imagePath,
                            'news_user_name'=>$newsUser['name'],
                            'push_time'=>date('Y-m-d H:i:s', time())
                        );
                        //推送通知
                        pushMessage($comment['target_id'],$content,2, '有人为你评论了');
                    }
                }

                return;
            }else{
                returnJson(0,"发送失败!");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }

    }

    /**
     * @brief 删除评论
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/deleteComment
     * @param cid 评论id
     * @param news_id 新闻ID
     */
    public  function deleteComment(){
        try{
            $comment = array();
            $comment['id'] = $_REQUEST['cid'];
            if(empty($comment['id'])){
                returnJson(0,"目标评论不能为空");
                return;
            }
            $comment['delete_date'] = time();
            $comment['delete_flag'] = 1;

            $commentModel = M('kh_news_comment');
            $commentModel->startTrans();
            $ret = $commentModel->save($comment);

            //那条新闻
            $news_id = $_REQUEST['news_id'];
            $newsModel = M('kh_news_content');
            //评论数减一
            $news = $newsModel->where('id='.$news_id)->find();
            if($news['comment_quantity'] > 0){
                $news['comment_quantity'] = $news['comment_quantity']-1;
            }
            //不能为负数
            if($news['comment_quantity'] < 0){
                $news['comment_quantity'] = 0;
            }

            $news['update_date'] = time();
            $nret = $newsModel->save($news);

            if($ret && $nret){
                $commentModel -> commit();
                returnJson(1,"删除成功!");
                return;
            }else{
                $commentModel -> rollback();
                returnJson(0,"删除失败!");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 点赞或者取消赞
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/likeOrCancel
     * @param isLike 点赞还是取消 1是赞 0是取消
     * @param news_id 状态id
     * @param user_id 用户id
     *http://192.168.1.105/jlxc_php/index.php/Home/MobileApi/likeOrCancel?comment_content=10101111132123&news_id=23&user_id=1&is_second=0&isLike=1
     */
    public  function likeOrCancel(){
        try{
            $like = array();
            $like['news_id'] = $_REQUEST['news_id'];
            $like['user_id'] = $_REQUEST['user_id'];
            $like['add_date'] = time();
            $isLike = $_REQUEST['isLike'];

            $likeModel = M('kh_news_like');
            $likeModel->startTrans();

            $newsModel = M('kh_news_content');
            //状态
            $news = $newsModel->where('id='.$like['news_id'].' and delete_flag = 0')->find();
            if(!$news){
                returnJson(0,"该状态不存在TAT!");
                $likeModel->rollback();
                return;
            }

            $oldLike = $likeModel->where('news_id='.$like['news_id'].' and user_id='.$like['user_id'])->find();
            if($oldLike){

                if($oldLike['delete_flag'] == !$isLike){
                    returnJson(0,"点过了");
                    $likeModel->rollback();
                    return;
                }

                $oldLike['delete_flag'] = !$isLike;
                //将状态点赞数减一或者加以
                if($isLike) {
                    $news['like_quantity'] ++;
                    $oldLike['resume_date'] = time();
                }else{
                    $news['like_quantity'] --;
                    $oldLike['delete_date'] = time();
                }
                //保存点赞
                $ret = $likeModel->save($oldLike);
                if($ret) {
                    $ret = $newsModel->save($news);
                    if($ret){
                        returnJson(1,"操作成功");
                        $likeModel->commit();
                        return;
                    }else{
                        returnJson(0,"操作失败");
                        $likeModel->rollback();
                        return;
                    }
                }else{
                    returnJson(0,"点赞失败");
                    $likeModel->rollback();
                    return;
                }
            }else{
                if($isLike){
                    //保存点赞
                    $ret = $likeModel->add($like);
                    if($ret){
                        $news['like_quantity'] ++;
                        $ret = $newsModel->save($news);
                        if($ret){

                            returnJson(1,"点赞成功");
                            $likeModel->commit();

                            //如果不是自己点赞 则推送通知
                            if($news['user_id'] != $like['user_id']){
                                $imagePath = '';
                                //该状态发的图片
                                $imageSql = 'SELECT sub_url
                                  from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY id DESC';
                                $images = $likeModel->query($imageSql);
                                if(!empty($images)){
                                    $imagePath = $images[0]['sub_url'];
                                }

                                //获取头像
                                $userModel = M('kh_user_info');
                                $user = $userModel->field('name, head_sub_image')->where('id='.$like['user_id'])->find();

                                //主人
                                $newsUser = $userModel->field('name')->where('id='.$news['user_id'])->find();

                                //要发送的内容
                                $content = array(
                                    'uid'=>$like['user_id'],
                                    'name'=>$user['name'],
                                    'comment_content'=>'',
                                    'head_image'=>$user['head_sub_image'],
                                    'news_id'=>$news['id'],
                                    'news_content'=>$news['content_text'],
                                    'news_image'=>$imagePath,
                                    'news_user_name'=>$newsUser['name'],
                                    'push_time'=>date('Y-m-d H:i:s', time())
                                );
                                //推送通知
                                pushMessage($news['user_id'],$content,4, '有人为你点赞了');
                            }

                            return;
                        }else{
                            returnJson(0,"点赞失败");
                            $likeModel->rollback();
                            return;
                        }

                    }else{
                        returnJson(0,"点赞失败");
                        $likeModel->rollback();
                        return;
                    }

                }else{
                    returnJson(0,"本来就没点");
                    $likeModel->rollback();
                    return;
                }
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }
/////////////////////////////////////////////首页'圈子'部分////////////////////////////////////////////////////////////

    /**
     * @brief 圈子列表
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getCircleList
     * @param page 页数
     * @param size 数量
     * 该方法是紧急情况使用 后期会修改或者作废 已作废
     */
    public  function getCircleList(){
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

            $cardModel = M();
            //这里先这么写
            $sql = 'SELECT id, title, intro, image, manager_name, phone_num, address, wx_num, web
                    FROM kh_circle WHERE delete_flag=0 LIMIT '.$start.','.$end;
            $cardList = $cardModel->query($sql);

            $result = array();
            $result['list'] = $cardList;
            //是否是最后一页
            if(count($cardList) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            //添加过
            returnJson(1,"获取成功", $result);

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 获取圈子成员列表
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getCircleMembers
     * @param page 页数
     * @param size 数量
     * @param circle_id 圈子ID
     */
    public  function getCircleMembers(){
        try{

            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $circleId = $_REQUEST['circle_id'];
            if(empty($circleId)){
                returnJson(0,"圈子不存在");
                return;
            }

            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }

            $start = ($page-1)*$size;
            $end   = $size;

            $memberModel = M();
            $sql = 'SELECT user.id user_id, user.name, user.job, user.head_sub_image FROM kh_user_circle uc, kh_user_info user
                    WHERE uc.circle_id="'.$circleId.'" AND user.id=uc.user_id AND uc.delete_flag=0 AND user.delete_flag=0 ORDER BY uc.add_date LIMIT '.$start.','.$end;
            $memberList = $memberModel->query($sql);

            $result = array();
            $result['list'] = $memberList;
            //是否是最后一页
            if(count($memberList) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            //添加过
            returnJson(1,"获取成功", $result);

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 创建一个圈子
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/postNewCircle?
     * @param user_id 用户id
     * @param circle_name 圈子名称
     * @param circle_detail 圈子描述
     * @param phone_num 电话
     * @param address 地址
     * @param wx_num 微信号
     * @param circle_web 网址
     */
    public function postNewCircle(){
        try{

            $user_id = $_REQUEST['user_id'];
            $circle_name = $_REQUEST['circle_name'];
            $circle_detail = $_REQUEST['circle_detail'];
            $address = $_REQUEST['address'];
            $wx_num = $_REQUEST['wx_num'];
            $circle_web = $_REQUEST['circle_web'];
            $phone_num = $_REQUEST['phone_num'];

            if(empty($user_id)){
                returnJson(0,"创建者不能为空！");
                return;
            }
            if(empty($circle_name)){
                returnJson(0,"圈子名不能为空");
                return;
            }
            if(mb_strlen($circle_name ,'utf-8')>8){
                returnJson(0,"圈子名长度不能超过8个字");
                return;
            }
            //只有一个图片的时候
            if(count($_FILES) <= 1){

                if(empty($_FILES)){
                    returnJson(0,"封面不能为空");
                    return;
                }

                //这一个图片是二维码
                if(substr($_FILES[array_keys($_FILES)[0]]['name'], 0, 6) == 'qrcode'){
                    returnJson(0,"封面不能为空");
                    return;
                }
            }

            //用户圈子表
            $circleModel = M('kh_personal_circle');
            //新圈子
            $newCircle = array('user_id'=>$user_id,'circle_name'=>$circle_name,'follow_quantity'=>1,
                'circle_detail'=>$circle_detail, 'address'=>$address,
                'wx_num'=>$wx_num, 'circle_web'=>$circle_web, 'phone_num'=>$phone_num);

            $info = null;
            $upload = null;
            //图片
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }
            //上传成功
            if($info) {
                $image = new \Think\Image();
                foreach($info as $file){

                    $path = $file['savepath'].$file['savename'];

                    //二维码和封面分开处理
                    if(substr($file['savename'], 0, 6) == 'qrcode'){
                        //二维码图片地址
                        $newCircle['wx_qrcode'] = $path;
                    }else{
                        $image->open('./Uploads/'.$path);
                        //缩略图地址前半部分
                        $preffix = substr($path, 0, strlen($path)-4);
                        //后缀
                        $suffix  = substr($path, strlen($path)-4);
                        //拼接
                        $subpath = $preffix.'_sub'.$suffix;
                        //缩略图保存
                        $image->thumb(360, 360)->save('./Uploads/'.$subpath, null, 90);

                        //封面部分
                        //图片地址
                        $newCircle['circle_cover_image'] = $path;
                        //缩略图
                        $newCircle['circle_cover_sub_image'] = $subpath;
                    }
                }
            }else{
                returnJson(0,"图片不能为空!");
                return;
            }

            //添加
            $newCircle['add_date'] = time();
            $circleModel->startTrans();
            $circle_id = $circleModel->add($newCircle);
            //添加成功
            if($circle_id){
                //关注自己的圈子
                $myTopicModel = M('kh_user_circle');
                $myTopic = array('user_id'=>$user_id, 'circle_id'=>$circle_id, 'add_date'=>time());
                $ret = $myTopicModel->add($myTopic);
                //关注成功
                if($ret){
                    $newCircle = $circleModel->find($circle_id);
                    $circleModel->commit();
                    returnJson(1,"创建成功", $newCircle);
                    return;
                }else{
                    $circleModel->rollback();
                    returnJson(0,"创建失败!");
                }

            }else{
                $circleModel->rollback();
                returnJson(0,"发布失败!");
            }

            return;

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 修改圈子信息
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/modifyCircle?
     * @param circle_id 圈子id
     * @param circle_name 圈子名称
     * @param circle_detail 圈子描述
     * @param phone_num 电话
     * @param address 地址
     * @param wx_num 微信号
     * @param circle_web 网址
     */
    public function modifyCircle(){
        try{

            $circle_id = $_REQUEST['circle_id'];
            $circle_name = $_REQUEST['circle_name'];
            $circle_detail = $_REQUEST['circle_detail'];
            $address = $_REQUEST['address'];
            $wx_num = $_REQUEST['wx_num'];
            $circle_web = $_REQUEST['circle_web'];
            $phone_num = $_REQUEST['phone_num'];

            if(empty($circle_name)){
                returnJson(0,"圈子名不能为空");
                return;
            }
            if(mb_strlen($circle_name ,'utf-8')>8){
                returnJson(0,"圈子名长度不能超过8个字");
                return;
            }

            //用户圈子表
            $circleModel = M('kh_personal_circle');
            $oldCircle = $circleModel->where('id='.$circle_id)->find();
            //圈子信息更新
            $oldCircle['circle_name'] = $circle_name;
            $oldCircle['circle_detail'] = $circle_detail;
            $oldCircle['address'] = $address;
            $oldCircle['wx_num'] = $wx_num;
            $oldCircle['circle_web'] = $circle_web;
            $oldCircle['phone_num'] = $phone_num;

            $info = null;
            $upload = null;
            //图片
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }
            //上传成功
            if($info) {
                $image = new \Think\Image();
                foreach($info as $file){

                    $path = $file['savepath'].$file['savename'];

                    //二维码和封面分开处理
                    if(substr($file['savename'], 0, 6) == 'qrcode'){
                        //二维码图片地址
                        $oldCircle['wx_qrcode'] = $path;
                    }else{
                        $image->open('./Uploads/'.$path);
                        //缩略图地址前半部分
                        $preffix = substr($path, 0, strlen($path)-4);
                        //后缀
                        $suffix  = substr($path, strlen($path)-4);
                        //拼接
                        $subpath = $preffix.'_sub'.$suffix;
                        //缩略图保存
                        $image->thumb(360, 360)->save('./Uploads/'.$subpath, null, 90);

                        //封面部分
                        //图片地址
                        $oldCircle['circle_cover_image'] = $path;
                        //缩略图
                        $oldCircle['circle_cover_sub_image'] = $subpath;
                    }
                }
            }

            //添加
            $oldCircle['update_date'] = time();
            $ret = $circleModel->save($oldCircle);
            //修改成功
            if($ret){
                $oldCircle = $circleModel->find($circle_id);
                returnJson(1,"修改成功", $oldCircle);
            }else{
                returnJson(0,"发布失败!");
            }

        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }

    /**
     * @brief 获取用户关注圈子
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getPersonalCircleList?user_id=4
     * @param user_id 用户id
     */
    public function getPersonalCircleList(){
        try{

            $user_id = $_REQUEST['user_id'];
            if(empty($user_id)){
                returnJson(0,"用户id不能为空");
                return;
            }
            //查询已关注的圈子
            $sql = 'SELECT pc.id, pc.circle_name, pc.circle_cover_sub_image, pc.follow_quantity FROM kh_user_circle uc, kh_personal_circle pc
                    WHERE uc.user_id='.$user_id.' AND uc.circle_id=pc.id AND pc.delete_flag=0 AND uc.delete_flag=0';
            //获取圈子详细信息
            $findCircle = M();
            $followList = $findCircle->query($sql);
            //查询没有关注的圈子
            $sql = 'SELECT id, circle_name, circle_cover_sub_image, follow_quantity FROM kh_personal_circle
                    WHERE id NOT IN (SELECT circle_id FROM kh_user_circle WHERE user_id='.$user_id.' AND delete_flag=0 ) ORDER BY RAND() LIMIT 100';
            //获取圈子详细信息
            $findCircle = M();
            $unfollowList = $findCircle->query($sql);
            returnJson(1,"查询成功", array('unfollowList'=>$unfollowList,'followList'=>$followList));

        }catch (Exception $e){

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 获取圈子主页信息
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getPersonalCircleList?user_id=4
     * @param page 页码 默认1
     * @param size 每页数量 默认10
     * @param circle_id 圈子id
     * @param user_id  用户id
     */
    public function getCircleHomeList(){
        try {
            $user_id = $_REQUEST['user_id'];
            $circle_id = $_REQUEST['circle_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $frist_time = $_REQUEST['frist_time'];
            if(empty($circle_id)){
                returnJson(0,"用户Id不能为空");
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            if(empty($frist_time)){
                $frist_time = time();
            }
            //获取圈子主页信息
            $sql = 'SELECT pc.id, pc.user_id, pc.circle_name, pc.circle_detail, pc.circle_cover_image, pc.circle_cover_sub_image, pc.phone_num,
                    pc.address,wx_num, pc.wx_qrcode, uc.name, pc.follow_quantity, pc.circle_web
                    FROM kh_personal_circle pc, kh_user_info uc WHERE pc.id='.$circle_id.' AND pc.user_id=uc.id';
            $circleModel = M();
            $findCircle = $circleModel->query($sql);
            if($findCircle){
                $findCircle = $findCircle[0];
            }else{
                returnJson(0,"目标圈子不存在");
                return;
            }
            //获取是否关注圈子
            $followModel = M('kh_user_circle');
            $isFollow = $followModel->where('user_id='.$user_id.' and circle_id='.$circle_id. ' and delete_flag=0')->select();
            if($isFollow){
                $findCircle['is_follow']='1';
            }else{
                $findCircle['is_follow']='0';
            };
            //获取达人信息
            $sql = 'SELECT ui.id, ui.head_sub_image FROM kh_user_circle uc, kh_personal_circle pc, kh_user_info ui
                    WHERE pc.id='.$circle_id.' AND uc.circle_id=pc.id AND ui.id=uc.user_id AND pc.delete_flag=0 AND uc.delete_flag=0
                    ORDER BY uc.add_date DESC LIMIT 10';
            $memberModel = M();
            $circleMembers = $memberModel->query($sql);

            //获取最新的一条公告
            $noticeModel = M('kh_circle_notice');
            $newNotice = $noticeModel->field('content_text, user_id')->where('circle_id='.$circle_id)->order('add_date desc')->find();

            //获取说说，动态信息
            $start = ($page-1)*$size;
            $end   = $size;
            $sql = 'SELECT user.id user_id, user.name, user.head_image,user.head_sub_image, user.job, news.id ,
                    user.company_name, news.content_text, news.location, news.comment_quantity,
                    news.browse_quantity, news.like_quantity, news.add_date
                    FROM kh_news_content news,kh_user_info user, kh_news_extra nc
                    WHERE news.add_date<='.$frist_time.' and nc.circle_id='.$circle_id.' and nc.news_id=news.id and news.user_id=user.id and news.delete_flag=0 and nc.delete_flag=0
                    ORDER BY news.add_date DESC LIMIT '.$start.','.$end;
            $contentModel = M();
            $circleContent = $contentModel->query($sql);
            if(isset($circleContent)){
                if(count($circleContent) > 0){

                    //处理图片
                    for($i=0; $i<count($circleContent); $i++) {
                        $news = $circleContent[$i];
                        //该状态发的图片
                        $imageSql = 'SELECT id,type,sub_url,url,size,add_date
                                      from kh_attachment WHERE delete_flag = 0 and entity_id='.$news['id'].' ORDER BY url';
                        $images = $contentModel->query($imageSql);
                        //返回尺寸
                        for($j=0; $j<count($images); $j++) {
                            try{
                                $image = new \Think\Image();
                                $path = $images[$j]['url'];
                                $image->open('./Uploads/'.$path);
                                $images[$j]['width']  = $image->size()[0];
                                $images[$j]['height'] = $image->size()[1];
                            }catch (Exception $e){
                                $images[$j]['width']  = '100';
                                $images[$j]['height'] = '100';
                            }
                        }

                        //获取该状态是否这个人赞了
                        $likeModel = M('kh_news_like');
                        $oldLike = $likeModel->where('delete_flag=0 and news_id='.$news['id'].' and user_id='.$user_id)->find();
                        if($oldLike){
                            $circleContent[$i]['is_like'] = '1';
                        }else{
                            $circleContent[$i]['is_like'] = '0';
                        }

                        //赋值
                        $circleContent[$i]['images'] = $images;
                        $circleContent[$i]['add_time'] = $circleContent[$i]['add_date'];
                        $circleContent[$i]['add_date'] = date('Y-m-d H:i:s', $circleContent[$i]['add_date']);
                    }
                }

                $result = array();
                $result['list'] = $circleContent;
                $result['circle']= $findCircle;
                $result['circleMembers'] = $circleMembers;
                $result['newNotice'] = $newNotice;
                //是否是最后一页
                if(count($circleContent) < $size){
                    $result['is_last'] = '1';
                }else{
                    $result['is_last'] = '0';
                }
                returnJson(1,"查询成功",$result);
                return;
            }else{
                returnJson(0,"查询失败T_T");
            }

        }catch (Exception $e){
            returnJson(0,"数据异常=_=",$e);
        }
    }

    /**
     * @brief 关注或者取消关注
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/likeOrCancel
     * @param isFollow 关注或者取消关注 1是关注 0是取消关注
     * @param circle_id 圈子id
     * @param user_id 用户id
     * */
    public function followOrUnfollowCircle(){
        try{
            $circle = array();
            $circle['circle_id'] = $_REQUEST['circle_id'];
            $circle['user_id'] = $_REQUEST['user_id'];

            $isFollow = $_REQUEST['isFollow'];

            $circleModel = M('kh_user_circle');
            $circleModel->startTrans();

            $personalModel = M('kh_personal_circle');
            //状态
            $sql = 'SELECT pc.id, pc.user_id FROM kh_personal_circle pc WHERE pc.id='.$circle['circle_id'].' and pc.user_id='.$circle['user_id'];
            $myPersonalModel = M();
            $myCircle = $myPersonalModel->query($sql);
            if($myCircle){
                returnJson(0,"不能关注自己的圈子");
                $circleModel->rollback();
                return;
            }

            $personal = $personalModel->where('id='.$circle['circle_id'].' and delete_flag = 0')->find();
            if(!$personal){
                returnJson(0,"该圈子不存在");
                $circleModel->rollback();
                return;
            }

            $oldCircle = $circleModel->where('circle_id='.$circle['circle_id'].' and user_id='.$circle['user_id'])->find();
            if($oldCircle){

                if($oldCircle['delete_flag'] == !$isFollow){
                    returnJson(0,"关注过了");
                    $circleModel->rollback();
                    return;
                }

                $oldCircle['delete_flag'] = !$isFollow;
                //将关注状态数减一或者加以
                if($isFollow) {
                    $personal['follow_quantity'] ++;
                    $oldCircle['resume_date'] = time();
                }else{
                    $personal['follow_quantity'] --;
                    $oldCircle['delete_date'] = time();
                }
                //保存关注
                $ret = $circleModel->save($oldCircle);
                if($ret) {
                    $ret = $personalModel->where('id='.$circle['circle_id'])->save($personal);
                    if($ret){
                        returnJson(1,"操作成功");
                        $circleModel->commit();
                        return;
                    }else{
                        returnJson(0,"操作失败");
                        $circleModel->rollback();
                        return;
                    }
                }else{
                    returnJson(0,"关注失败");
                    $circleModel->rollback();
                    return;
                }
            }else{
                if($isFollow){
                    //保存关注
                    $circle['add_date'] = time();
                    $ret = $circleModel->add($circle);
                    if($ret){
                        $personal['follow_quantity'] ++;
                        $ret = $personalModel->where('id='.$circle['circle_id'])->save($personal);
                        if($ret){
                            returnJson(1,"关注成功");
                            $circleModel->commit();
                            return;
                        }else{
                            returnJson(0,"关注失败");
                            $circleModel->rollback();
                            return;
                        }

                    }else{
                        returnJson(0,"关注失败");
                        $circleModel->rollback();
                        return;
                    }

                }else{
                    returnJson(0,"本来就没关注");
                    $circleModel->rollback();
                    return;
                }
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }

    }


    /**
     * @brief 获取关注圈子列表
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getCircleFollowList?user_id=4
     * @param user_id 用户id
     */
    public function getMyFollowCircleList()
    {
        try {

            $user_id = $_REQUEST['user_id'];
            if (empty($user_id)) {
                returnJson(0, "用户id不能为空");
                return;
            }
            //查询已关注的圈子
            $sql = 'SELECT pc.id, pc.circle_name, pc.circle_cover_sub_image, pc.follow_quantity FROM kh_user_circle uc, kh_personal_circle pc
                    WHERE uc.user_id=' . $user_id . ' AND uc.circle_id=pc.id AND pc.delete_flag=0 AND uc.delete_flag=0';
            //获取圈子详细信息
            $findCircle = M();
            $followList = $findCircle->query($sql);
            returnJson(1, "查询成功", array('list' => $followList));

        } catch (Exception $e) {

            returnJson(0, "数据异常", $e);
        }
    }

    /**
     * @brief 获得我的圈子列表
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getMyCircleList?
     * @param user_id 用户id
     */
    public function getMyCircleList(){
        try{
            $user_id = $_REQUEST['user_id'];
            if(empty($user_id)){
                returnJson(0,"用户Id不能为空");
                return;
            }
            $personalModel = M();
            $sql = 'SELECT id, circle_name, follow_quantity, circle_cover_sub_image FROM kh_personal_circle
                      WHERE user_id='.$user_id.' AND delete_flag=0';
            $personal_circle = $personalModel->query($sql);
            if($personal_circle){
                returnJson(1,"获取成功", $personal_circle);
            }else{
                returnJson(0,"获取失败!");
            }

        }catch (Exception $e){

            returnJson(0,"数据异常",$e);
        }
    }

    /**
     * @brief 创建一个公告
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/postNewNotice
     * @param circle_id 圈子id
     * @param user_id 圈主id
     * @param content 公告内容
     */
    public  function postNewNotice(){
        try{
            $circle_id = $_REQUEST['circle_id'];
            $user_id = $_REQUEST['user_id'];
            $content_text = $_REQUEST['content_text'];
            if(empty($user_id)){
                returnJson(0,'创建者不能为空！');
                return;
            }
            if(empty($content_text)){
                returnJson(0,'公告内容不能为空！');
                return;
            }
            if(empty($circle_id)){
                returnJson(0,'圈子不能为空！');
                return;
            }
            if(mb_strlen($content_text ,'utf-8')>140){
                returnJson(0,"圈子名长度不能超过140个字！");
                return;
            }
            //公告信息
            $newNotice = array('user_id'=>$user_id,'circle_id'=>$circle_id,'content_text'=>$content_text);
            $newNotice['add_date'] = time();
            //公告内容表
            $noticeModel = M('kh_circle_notice');
            $notice_id = $noticeModel->add($newNotice);
            if($notice_id){
                returnJson(1,'创建成功！',$notice_id);
                return;
            }else{
                returnJson(0,'创建失败！');
            }
        }catch (Exception  $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 获取公告列表信息
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getNoticeList
     * @param page 页码 默认1
     * @param size 每页数量 默认10
     * @param circle_id 圈子id
     * @param user_id 圈子id
     */
    public function getNoticeList(){
        try{
            $circle_id = $_REQUEST['circle_id'];
            $user_id = $_REQUEST['user_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $frist_time = $_REQUEST['frist_time'];
            if(empty($circle_id)){
                returnJson(0,'圈子ID不能为空！');
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            if(empty($frist_time)){
                $frist_time = time();
            }
            $start = ($page-1)*$size;
            $end = $size;
            //查询圈子的公告列表
            $sql = 'SELECT id, user_id, content_text, add_date, comment_quantity, like_quantity FROM kh_circle_notice
                    WHERE add_date<='.$frist_time.' AND circle_id='.$circle_id.' AND delete_flag=0
                    ORDER BY add_date DESC LIMIT '.$start.','.$end;
            $noticeModel = M('kh_circle_notice');
            $noticeList = $noticeModel->query($sql);
            //获取该状态是否这个人赞了
            for($i=0; $i<count($noticeList); $i++) {
                $news = $noticeList[$i];
                $likeModel = M('kh_notice_like');
                $oldLike = $likeModel->where('delete_flag=0 and news_id=' . $news['id'] . ' and user_id=' . $user_id)->find();
                if ($oldLike) {
                    $noticeList[$i]['is_like'] = '1';
                } else {
                    $noticeList[$i]['is_like'] = '0';
                }
                $noticeList[$i]['add_date'] = date('Y-m-d H:i:s',$noticeList[$i]['add_date']);
            }
            $result['list'] = $noticeList;
            //判断是否是最后一页
            if(count($noticeList) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            returnJson(1,'查询成功！',$result);
            return;

        }catch (Exception $e){
            returnJson(0,'数据异常！',$e);
        }
    }

    /**
     * @brief 获取公告详情信息
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/getNoticeDetails
     * @param page 页码 默认1
     * @param size 每页数量 默认10
     * @param id 公告id
     */
    public function getNoticeDetails(){
        try{
            $id = $_REQUEST['id'];
            $user_id = $_REQUEST['user_id'];
            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $frist_time = $_REQUEST['frist_time'];
            if(empty($id)){
                returnJson(0,'公告ID不能为空');
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }
            if(empty($frist_time)){
                $frist_time = time();
            }
            //获取公告详情
            $noticeModel = M('kh_circle_notice');
            $notice = $noticeModel->field('user_id, circle_id, content_text, add_date, comment_quantity, like_quantity, browse_quantity')->where('id='.$id)->find();
            $notice['add_date'] = date('Y-m-d H:i:s', $notice['add_date']);
            //每查询一次关注加一
            if($notice){
                $notice['browse_quantity']++;
                $noticeModel->where('id='.$id)->save($notice);
            }
            //获取该状态是否这个人赞了
            $likeModel = M('kh_notice_like');
            $oldLike = $likeModel->where('delete_flag=0 and notice_id='.$id.' and user_id='.$user_id)->find();
            if($oldLike){
                $notice['is_like'] = '1';
            }else{
                $notice['is_like'] = '0';
            }

            $start = ($page-1)*$size;
            $end = $size;
            //获取公告评论列表
            $sql = 'SELECT nc.comment_content, nc.add_date, uc.name, uc.head_sub_image FROM kh_notice_comment nc, kh_user_info uc
                    WHERE nc.add_date<='.$frist_time.' AND nc.notice_id='.$id.' AND nc.delete_flag=0 AND uc.id=nc.user_id
                    ORDER BY nc.add_date DESC LIMIT '.$start.','.$end;
            $commentModel = M('kh_notice_comment');
            $commentList = $commentModel->query($sql);
            for($j=0;$j<count($commentList);$j++){
                $commentList[$j]['add_date'] = date('Y-m-d H:i:s',$commentList[$j]['add_date']);
            }
            $result['notice'] = $notice;
            $result['commentList'] = $commentList;
            //判断是否是最后一页
            if(count($commentList) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }
            returnJson(1,'查询成功',$result);
            return;

        }catch (Exception $e){
            returnJson(0,'数据异常',$e);
        }
    }

    /**
     * @brief 公告点赞或者取消赞
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/noticeLikeOrCancel
     * @param isLike 点赞还是取消 1是赞 0是取消
     * @param notice_id 公告id
     * @param user_id 用户id
     */
    public  function noticeLikeOrCancel(){
        try{
            $like = array();
            $like['notice_id'] = $_REQUEST['notice_id'];
            $like['user_id'] = $_REQUEST['user_id'];
            $isLike = $_REQUEST['isLike'];
            $like['add_date'] = time();

            $likeModel = M('kh_notice_like');
            $likeModel->startTrans();

            $noticeModel = M('kh_circle_notice');

            //查询公告状态
            $notice = $noticeModel->where('id='.$like['notice_id'].' AND delete_flag = 0')->find();
            if(!$notice){
                returnJson(0,'该公告不存在!');
                $likeModel -> rollback();
                return;
            }
            //查询点赞情况
            $oldLike = $likeModel->where('notice_id='.$like['notice_id'].' ANd user_id='.$like['user_id'])->find();
            if($oldLike){
                if($oldLike['delete_flag'] == !$isLike){
                    returnJson(0,'已经点过了！');
                    $likeModel -> rollback();
                    return;
                }

                $oldLike['delete_flag'] = !$isLike;
                //点赞加一或减一
                if($isLike){
                    $notice['like_quantity'] ++;
                    $oldLike['resume_date'] = time();
                }else{
                    $notice['like_quantity'] --;
                    $oldLike['delete_date'] = time();
                }
                //保存点赞
                $ret = $likeModel -> save($oldLike);
                if($ret){
                    $ret = $noticeModel -> save($notice);
                    if($ret){
                        returnJson(1,'操作成功！');
                        $likeModel -> commit();
                        return;
                    }else{
                        returnJson(0,'操作失败！');
                        $likeModel -> rollback();
                        return;
                    }
                }else{
                    returnJson(0,'点赞失败！');
                    $likeModel -> rollback();
                    return;
                }

            }else{
                if($isLike){
                    //保存点赞
                    $ret = $likeModel -> add($like);
                    if($ret){
                        $notice['like_quantity'] ++;
                        $ret = $noticeModel -> save($notice);
                        if($ret){
                            returnJson(1,'点赞成功！');
                            $likeModel -> commit();

                            //如果不是自己点赞，则推送通知
                            if($notice['user_id'] != $like['user_id']){
                                //获取用户头像
                                $userModel = M('kh_user_info');
                                $user = $userModel->field('name, head_sub_image')->where('id='.$like['user_id'])->find();

                                //主人
                                $noticeUsr = $userModel->field('name')->where('id='.$notice['user_id'])->find();

                                //要推送的内容
                                $content = array(
                                    'uid'=>$like['user_id'],
                                    'name'=>$user['name'],
                                    'head_image'=>$user['head_sub_image'],
                                    'notice_id'=>$notice['id'],
                                    'notice_content'=>$notice['content_text'],
                                    'notice_user_name'=>$noticeUsr['name'],
                                    'push_time'=>date('Y-m-d H:i:s', time())
                                );
                                //推送通知
                                pushMessage($notice['user_id'],$content,4,'有人为你点赞了！');
                            }
                            return;
                        }else{
                            returnJson(0,'点赞失败！');
                            $likeModel -> rollback();
                            return;
                        }
                    }else{
                        returnJson(0,'点赞失败！');
                        $likeModel -> rollback();
                        return;
                    }
                }else{
                    returnJson(0,'本来就没有点赞!');
                    $likeModel -> rollback();
                    return;
                }
            }

        }catch (Exception $e){
            returnJson(0,'数据异常',$e);
        }
    }

    /**
     * @brief 发布公告评论
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/sendNoticeComment
     * @param notice_id 公告id
     * @param user_id 用户id
     * @param target_id 评论的用户id
     * @param comment_content 评论的内容
     */
    public function sendNoticeComment(){
        try{
            $comment = array();
            $comment['notice_id'] = $_REQUEST['notice_id'];
            $comment['user_id'] = $_REQUEST['user_id'];
            $comment['target_id'] = $_REQUEST['target_id'];
            $comment['comment_content'] = $_REQUEST['comment_content'];
            $comment['add_date'] = time();
            if(empty($comment['notice_id'])){
                returnJson(0,'公告ID不能为空！');
                return;
            }
            $findUser = M('kh_user_info');
            $checkUser = $findUser->find($comment['user_id']);
            if(!$checkUser){
                returnJson(0 ,'该用户不存在');
                return;
            }
            if($checkUser['delete_flag'] == 1){
                returnJson(0 ,'您因为不当操作，已经被管理员拉黑');
                return;
            }

            //查看公告状态
            $noticeModel = M('kh_circle_notice');
            $notice = $noticeModel->where('id='.$comment['notice_id'].' and delete_flag=0')->find();
            if(!$notice){
                returnJson(0,'该公告不存在!');
                return;
            }
            if(empty($comment['comment_content'])){
                returnJson(0,'评论内容不能为空！');
                return;
            }
            $commentModel = M('kh_notice_comment');
            $ret = $commentModel->add($comment);
            if($ret){
                $notice['comment_quantity'] ++;
                $noticeModel->save($notice);
                $comment = $commentModel->find($ret);
                $comment['add_date'] = date('Y-m-d H:i:s',$comment['add_date']);
                returnJson(1,'发送评论成功！',$comment);
                if($notice['user_id'] != $comment['user_id']){
                    //发送评论的人
                    $userModel = M('kh_user_info');
                    $user = $userModel->field('name, head_sub_image')->where('id='.$comment[user_id])->find();
                    //公告主人
                    $newsUser = $userModel->field('name')->where('id='.$notice['user_id'])->find();
                    //推送内容
                    $content = array(
                        'uid'=>$comment['user_id'],
                        'name'=>$user['name'],
                        'head_image'=>$user['head_sub_image'],
                        'comment_content'=>$comment['comment_content'],
                        'notice_id'=>$notice['id'],
                        'notice_content'=>$notice['content_text'],
                        'notice_user_name'=>$newsUser['name'],
                        'push_time'=>date('Y-m-d H:i:s', time())
                    );
                    //推送通知
                    pushMessage($notice['user_id'],$comment,2,'有人评论了你！');
                    //如果不为空 并且 如果不是评论的状态发送人 则推送通知
                    if(!empty($comment['target_id']) && $notice['user_id'] != $comment['target_id']){

                        //发送评论的人
                        $userModel = M('kh_user_info');
                        $user = $userModel->field('name, head_sub_image')->where('id='.$comment[user_id])->find();
                        //公告主人
                        $newsUser = $userModel->field('name')->where('id='.$notice['user_id'])->find();
                        //推送内容
                        $content = array(
                            'uid'=>$comment['user_id'],
                            'name'=>$user['name'],
                            'head_image'=>$user['head_sub_image'],
                            'comment_content'=>$comment['comment_content'],
                            'notice_id'=>$notice['id'],
                            'notice_content'=>$notice['content_text'],
                            'notice_user_name'=>$newsUser['name'],
                            'push_time'=>date('Y-m-d H:i:s', time())
                        );
                        //推送通知
                        pushMessage($comment['target_id'],$comment,2,'有人评论了你！');
                    }
                }else{
                    if(!empty($comment['target_id']) && $comment['user_id'] != $comment['target_id']){
                        //发送评论的人
                        $userModel = M('kh_user_info');
                        $user = $userModel->field('name, head_sub_image')->where('id='.$comment[user_id])->find();
                        //公告主人
                        $newsUser = $userModel->field('name')->where('id='.$notice['user_id'])->find();
                        //推送内容
                        $content = array(
                            'uid'=>$comment['user_id'],
                            'name'=>$user['name'],
                            'head_image'=>$user['head_sub_image'],
                            'comment_content'=>$comment['comment_content'],
                            'notice_id'=>$notice['id'],
                            'notice_content'=>$notice['content_text'],
                            'notice_user_name'=>$newsUser['name'],
                            'push_time'=>date('Y-m-d H:i:s', time())
                        );
                        //推送通知
                        pushMessage($comment['target_id'],$comment,2,'有人评论了你！');
                    }
                }

            }else{
                returnJson(0,'发送评论失败！');
            }

        }catch (Exception $e){
            returnJson(0,'数据异常',$e);
        }
    }

/////////////////////////////////////////////好友部分////////////////////////////////////////////////////////////
    /**
     * @brief 关注好友 双向
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/addFriend
     * @param user_id 用户id
     * @param friend_id 用户id
     */
    public  function addFriend(){
        try{
            $addFriend = array();
            $addFriend['user_id'] = $_REQUEST['user_id'];
            $addFriend['target_id'] = $_REQUEST['target_id'];
            $addFriend['add_date'] = time();

            if($addFriend['user_id'] == $addFriend['target_id']) {
                returnJson(0,"不能关注自己");
                return;
            }

            $addModel = M('kh_relationship');
            $userAdd = $addModel->where('user_id='.$addFriend['user_id'].' and target_id='.$addFriend['target_id'])->find();
            $targetAdd = $addModel->where('user_id='.$addFriend['target_id'].' and target_id='.$addFriend['user_id'])->find();

            $addModel->startTrans();
            //先处理发起人
            if($userAdd){
                if($userAdd['delete_flag'] != 0){
                    $userAdd['delete_flag'] = 0;
                    $userAdd['resume_date'] = time();
                    $ret = $addModel->save($userAdd);
                    if(!$ret){
                        $addModel->rollback();
                        returnJson(0,"关注失败");
                        return;
                    }
                }
            }else{
                $ret = $addModel->add($addFriend);
                if(!$ret){
                    $addModel->rollback();
                    returnJson(0,"关注失败");
                    return;
                }
            }
            //再接受人
            if($targetAdd){
                if($targetAdd['delete_flag'] != 0){
                    $targetAdd['delete_flag'] = 0;
                    $targetAdd['resume_date'] = time();
                    $ret = $addModel->save($targetAdd);
                    if(!$ret){
                        $addModel->rollback();
                        returnJson(0,"关注失败");
                        return;
                    }else{
                        $addModel->commit();
                        returnJson(1,"关注成功");
                    }
                }else{
                    returnJson(1,"关注成功");
                }
            }else{
                $targetFriend = array('user_id'=>$addFriend['target_id'], 'target_id'=>$addFriend['user_id'], 'add_date'=>time());
                $ret = $addModel->add($targetFriend);
                if(!$ret){
                    $addModel->rollback();
                    returnJson(0,"关注失败");
                    return;
                }else{
                    $addModel->commit();
                    returnJson(1,"关注成功");
                }
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 添加好友
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/deleteFriend
     * @param user_id 用户id
     * @param friend_id 用户id
     *
     */
    public  function deleteFriend(){
        try{
            $deleteFriend = array();
            $deleteFriend['user_id'] = $_REQUEST['user_id'];
            $deleteFriend['target_id'] = $_REQUEST['target_id'];

            if($deleteFriend['user_id'] == $deleteFriend['target_id']) {
                returnJson(0,"不能删除自己");
                return;
            }

            $deleteModel = M('kh_relationship');
            $userDelete = $deleteModel->where('user_id='.$deleteFriend['user_id'].' and target_id='.$deleteFriend['target_id'])->find();
            $targetDelete = $deleteModel->where('user_id='.$deleteFriend['target_id'].' and target_id='.$deleteFriend['user_id'])->find();
            //添加过
            if($userDelete && $targetDelete){
                $deleteModel->startTrans();
                //删除用户
                $userDelete['delete_flag'] = 1;
                $userDelete['delete_date'] = time();
                $ret = $deleteModel->save($userDelete);
                //删除目标
                $targetDelete['delete_flag'] = 1;
                $targetDelete['delete_date'] = time();
                $targetRet = $deleteModel->save($targetDelete);
                if($ret && $targetRet){
                    $deleteModel->commit();
                    returnJson(1,"删除成功！");
                }else{
                    $deleteModel->rollback();
                    returnJson(0,"删除失败=.=");
                }

            }else{
                returnJson(1,"没有关注过");
            }

            return;
        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 获取图片和姓名
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getImageAndName
     * @param user_id 用户id
     * @param self_id 自己id
     */
    public  function getImageAndName(){
        try{
            $user_id = $_REQUEST['user_id'];
            $self_id = $_REQUEST['self_id'];
            if(empty($user_id)){
                returnJson(0,"查询的人不能为空");
                return;
            }
            if(empty($self_id)){
                $self_id = 0;
            }

            $userModel = M('kh_user_info');
            $user = $userModel->field('head_sub_image,name')->where('delete_flag = 0 and id='.$user_id)->find();

            $relationModel = M('kh_relationship');
            $relation = $relationModel->where('delete_flag=0 and user_id='.$self_id.' and target_id='.$user_id)->find();
            if($relation){
                if(!empty($relation['friend_remark'])){
                    $user['name'] = $relation['friend_remark'];
                }
            }

            if($user){
                returnJson(1,"获取成功", $user);
            }else{
                returnJson(0,"没这人");
            }

            return;
        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 修改备注
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/addRemark
     * @param user_id 用户id
     * @param target_id 好友id
     * @param 备注 friend_remark
     */
    public  function addRemark(){
        try{
            $user_id = $_REQUEST['user_id'];
            $friend_id = $_REQUEST['target_id'];
            $friend_remark = $_REQUEST['friend_remark'];

            $friendModel = M('kh_relationship');
            $friend = $friendModel->where('delete_flag =0 and user_id='.$user_id.' and target_id='.$friend_id)->find();
            //添加过
            if($friend){
                $friend['friend_remark'] = $friend_remark;
                $friend['update_date'] = time();
                $ret = $friendModel->save($friend);
                if($ret){
                    returnJson(1,"修改成功");
                }else{
                    returnJson(0,"修改失败");
                }
            }else{
                returnJson(0,"没有该好友");
            }
            return;
        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 通过群号 或者手机号查询
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/findUserOrGroup
     * @param target_id 手机号或者群号
     */
    public  function findUserOrGroup(){
        try{
            $target_id = $_REQUEST['target_id'];
            if(empty($target_id)){
                returnJson(0,"Keyword Empty");
                return;
            }

            $friendModel = M('kh_user_info');
            $friend = $friendModel->field('id user_id,name,head_sub_image,company_name')->where('delete_flag =0 and phone_num='.$target_id)->find();
            //存在
            if($friend){
                //type 0为人 1为群组
                $friend['type'] = '0';
                returnJson(1,"存在该用户", $friend);
                return;
            }
            $groupModel = M('kh_group_info');
            $hx = new Easemob();
            $detail = $hx->chatGroupsDetails($target_id);
            $jsonDetail = json_decode($detail, true);
            //存在
            if($jsonDetail['data']){
                $group = $groupModel->field('group_name,group_cover')->where('delete_flag =0 and group_id='.$target_id)->find();
                if(!$group){
                    $group['group_name'] = $jsonDetail['data'][0]['name'];
                    $group['group_cover'] = '';
                }
                $group['type'] = '1';
                returnJson(1,"存在该群组", $group);
            }else{
                returnJson(0,"没有查询到");
            }
        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 获取好友列表 全部
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getFriendsList
     * @param user_id 用户id
     *
     */
    public  function getAllFriendsList(){
        try{
            $user_id = $_REQUEST['user_id'];

            $friendModel = M('kh_relationship');
            $sql = 'SELECT u.id user_id, u.name,u.head_sub_image,r.friend_remark from kh_user_info u,kh_relationship r
                    WHERE r.delete_flag=0 and r.user_id='.$user_id.' and r.target_id=u.id';
            $friendList = $friendModel->query($sql);
            //添加过
            if($friendList){
                returnJson(1,"获取成功", array('list'=>$friendList));
            }else{
                returnJson(0,"本来就没有");
            }
            return;
        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

/////////////////////////////////////////////群组部分////////////////////////////////////////////////////////////
    /**
     * @brief 创建群
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/createGroup
     * @param user_id 用户id
     * @param group_name 好友id
     * @param group_detail 名字
     * @param members 成员
     */
    public  function createGroup(){
        try{

            $user_id = $_REQUEST['user_id'];
            $group_name = $_REQUEST['group_name'];
            $membersReq = $_REQUEST['members'];
            $group_detail = '';
            $members = array();
            if(!empty($membersReq)){
                $members = explode(',', $membersReq);
            }
            //用户为空
            if(empty($user_id)){
                returnJson(0,"用户为空");
                return;
            }
            //用户标题
            if(empty($group_name)){
                returnJson(0,"群组标题为空");
                return;
            }
            //环信创建群
            $hx = new Easemob();
            $hxJson = $hx->createGroups(array('groupname'=>$group_name, 'desc'=>$group_detail, 'public'=>true,
                                        'owner'=>KH.$user_id, 'approval'=>true, 'members'=>$members));
            $hxRet = json_decode($hxJson, true);
            if(empty($hxRet['data']['groupid'])){
                returnJson(0,"创建失败");
                return;
            }

            $groupModel = M('kh_group_info');

            $info = null;
            //存图片
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }

            $path = '';
            //上传成功
            if($info) {
                foreach($info as $file){
                    $user['group_cover'] = $file['savepath'].$file['savename'];
                    $path = $file['savepath'].$file['savename'];
                }
            }
            // 纠错级别：L、M、Q、H
            $level = 'L';
            // 点的大小：1到10,用于手机端4就可以了
            $size = 6;
            $PNG_WEB_DIR = './KHCQRCode/';
            $data = KH_GROUP.$hxRet['data']['groupid'];
            $filename = $PNG_WEB_DIR.$data.'.png';
            QRcode::png($data, $filename, $level, $size);
//            //生成失败
//            if(!file_exists($filename)){
//                returnJson(0,"查询失败。");
//                return;
//            }
            $group = array('group_id'=>$hxRet['data']['groupid'], 'group_name'=>$group_name, 'group_detail'=>$group_detail
                           , 'user_id'=>$user_id, 'group_cover'=>$path, 'group_qr_code'=>$filename, 'max_quantity'=>'200', 'add_date'=>time());
            $groupModel->add($group);
            returnJson(1,"创建成功", $group);

            return;
        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 获取群组图片和名字和二维码
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getGroupImageAndNameAndQrcode
     * @param group_id 用户id
     *
     */
    public  function getGroupImageAndNameAndQrcode(){
        try{
            $group_id = $_REQUEST['group_id'];
            $groupModel = M('kh_group_info');
            $group = $groupModel->field('group_qr_code,group_cover,group_name')->where('delete_flag = 0 and group_id='.$group_id)->find();

            if($group){
                returnJson(1,"获取成功", $group);
            }else{
                returnJson(0,"没这群组");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 修改群组名字
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/updateGroupName
     * @param group_id 用户id
     * @param group_name 群组名字
     */
    public  function updateGroupName(){
        try{
            $group_id = $_REQUEST['group_id'];
            $group_name = $_REQUEST['group_name'];
            if(empty($group_name)){
                returnJson(0,"名不能为空");
                return;
            }
            $groupModel = M('kh_group_info');
            $group = $groupModel->where('group_id='.$group_id)->find();
            $group['group_name'] = $group_name;
            //修改
            $groupModel->save($group);
            returnJson(1,"修改成功", $group);

        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 创建群
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/updateGroupCover
     * @param group_id group_id
     */
    public  function updateGroupCover(){
        try{

            $group_id = $_REQUEST['group_id'];
            $groupModel = M('kh_group_info');
            $info = null;
            //存图片
            if(!empty($_FILES)){
                $upload = new \Think\Upload();// 实例化上传类
                $upload->maxSize   =     10*1024*1024 ;// 设置附件上传大小
                $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
                $upload->savePath  =     ''; // 设置附件上传（子）目录
                $upload->saveName  =     '';
                // 上传文件
                $info   =   $upload->upload();
            }
            $path = '';
            //上传成功
            if($info) {
                foreach($info as $file){
                    $path = $file['savepath'].$file['savename'];
                }
            }
            $group = $groupModel->where('group_id='.$group_id)->find();
            $group['group_cover'] = $path;
            //修改
            $ret = $groupModel->save($group);
            if($ret){
                returnJson(1,"修改成功", $group);
            }else{
                returnJson(0,"没这群组");
            }

        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

/////////////////////////////////////////////发现部分////////////////////////////////////////////////////////////
    /**
     * @brief 获取当前在使用的通讯录用户
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/MobileApi/getContactUser
     * @param user_id 用户id
     * @param contact 通讯录//["13745365657","13745365657"]
     */
    public  function getContactUser(){
        try{
            $user_id = $_REQUEST['user_id'];
            $contact = json_decode($_REQUEST['contact']);
            if(empty($user_id)){
                returnJson(0,"用户Id不能为空");
                return;
            }
            if(empty($contact)){
                returnJson(0,"查询号码不能为空");
                return;
            }

            $contact = json_decode($_REQUEST['contact']);
            $inContact = implode(',',$contact);

            $contactModel = M('jlxc_user');
            $sql = 'SELECT u.id user_id, u.name, u.phone_num, u.head_image,u.head_sub_image, CASE r.delete_flag WHEN 0 THEN 1 ELSE 0 END AS is_friend
                    FROM kh_user_info u LEFT JOIN kh_relationship r ON( u.id=r.target_id AND r.user_id='.$user_id.')
                    WHERE u.phone_num in ('.$inContact.') ORDER BY is_friend';
            $contactArr = $contactModel->query($sql);
            returnJson(1,"查询成功", array('list'=>$contactArr));

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
        }
    }

    /**
     * @brief 查询
     * 接口地址
     * http://localhost/jlxc_php/index.php/Home/MobileApi/findUserList
     * @param user_id 用户id
     * @param content 搜索内容
     */
    public function findUserList(){
        try{

            $page = $_REQUEST['page'];
            $size = $_REQUEST['size'];
            $user_id = $_REQUEST['user_id'];
            $content = $_REQUEST['content'];
            if(empty($user_id)){
                returnJson(0,"用户Id不能为空");
                return;
            }
            if(empty($content)){
                returnJson(0,"查询内容不能为空");
                return;
            }
            if(empty($page)){
                $page = 1;
            }
            if(empty($size)){
                $size = 10;
            }

            $start = ($page-1)*$size;
            $end   = $size;
            //最近访问列表
            $sameSchoolModel = M();
            $sql = 'SELECT u.id uid, u.name, u.head_image,u.head_sub_image, CASE r.delete_flag WHEN 0 THEN 1 ELSE 0 END AS is_friend
                    FROM jlxc_user u LEFT JOIN jlxc_relationship r ON( u.id=r.friend_id AND r.user_id='.$user_id.')
                    WHERE u.id<>'.$user_id.' AND u.name LIKE "%'.$content.'%" ORDER BY is_friend LIMIT '.$start.','.$end;
            $sameSchools = $sameSchoolModel->query($sql);

            $result = array();
            $result['list'] = $sameSchools;

            //是否是最后一页
            if(count($sameSchools) < $size){
                $result['is_last'] = '1';
            }else{
                $result['is_last'] = '0';
            }

            returnJson(1,"查询成功", $result);
        }catch (Exception $e){

            returnJson(0,"数据异常！",$e);
        }
    }


    /////////////////////////////////////////////TEST DEMO/////////////////////////////////////////////////

    public function get1(){

        //注册环信
        $hx = new Easemob();
        echo $hx->accreditRegister(array('username'=>'test', 'password'=>'123456'));

        echo pushMessage(10,"gahaha",2);
        return;
//        echo urlencode($_REQUEST['username']);
//        echo json_decode(json_encode($_REQUEST['username']));
        //获取用户详细信息
        $findUser = M('jlxc_user');
        $user = $findUser->where(array('id=10'))->find();
        echo urldecode(json_encode($user));
        return;
//        return;
//        $user['name'] = urlencode($_REQUEST['username']);
        $user['name'] = $_REQUEST['username'];
        $user['update_date'] = time();
        $updateModel = D('jlxc_user');
        $ret = $updateModel->save($user);
        $user = $findUser->where(array('id=10'))->find();
        if($ret){
            returnJson(1,"保存成功",$user);
            return;
        }else{
            returnJson(0,"保存失败!");
        }

        return;
        echo pushMessage(1,"gahaha",2);
        return;

        echo $_REQUEST['username'];
        $get = M('testtable');
        $data = $get->find();
        if($data){
            echo json_encode($data);

        }else{
            echo '没有数据';
        }
    }

    //http://localhost/jlxc_php/index.php/Home/MobileApi/testImage
    public function testImage(){
        //注册
        $hx = new Easemob();
        $hx->accreditRegister(array('username'=>'kh2', 'password'=>'123456'));

        return;
        $opts = array('opts'=>array('apn_json'=>array('aps'=>array('sound'=>'bingbong.aiff','badge'=>'1', 'alert'=>'test'))));
        echo json_encode($opts);
        return;

        $path = './Uploads/2015-05-13/11431526535.png';
        echo substr($path, 0, strlen($path)-4);

        $image = new \Think\Image();
        $image->open('./Uploads/2015-05-13/11431526535.png');
        $ret = $image->thumb(360, 360)->save('./Uploads/2015-05-13/11431526535_sub.png', null, 90);
        echo $ret;
        return;

        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize   =     3145728 ;// 设置附件上传大小
//        $upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath  =     './Uploads/'; // 设置附件上传根目录
        $upload->savePath  =     ''; // 设置附件上传（子）目录
        $upload->saveName  = '';
//        $upload->thumbPrefix = 'm_';
        $upload->thumb = true; //是否对上传文件进行缩略图处理
        // 上传文件
        $info   =   $upload->upload();
        if(!$info) {// 上传错误提示错误信息

//            $this->error();
            //http://localhost/www/test/index.php/Home/Index/testImage.html0
//            echo 'fail';
            print_r($upload);
            echo $upload->getError();
        }else{
            // 上传成功
            foreach($info as $file) {

                $path = $file['savepath'].$file['savename'];
                $image = new \Think\Image();
                $image->open('./Uploads/2015-05-13/11431526535.png');
                echo 'width:'.$image->width();

            }
            $okJson = array();
            $okJson['ok'] = 'ok';
            foreach($info as $file){
                $okJson[$file['savename']] = $file['savepath'];
            }
            echo json_encode($okJson);
//            $this->success('上传成功！');
        }

        ////curl -l -H "Content-type: application/json" -X POST -d '{"method":"publish", "appkey":"55ab4554c75ecd535d69b955", "seckey":"sec-UVHzd2ioXYJlOYvLjWggCcvBDAyzXDXsvhpdu9DMKr8esMoV", "topic":"jlxc10", "msg":"just test", "opts":{"apn_json":{"aps":{"sound":"bingbong.aiff","badge": 2, "alert":"haha"}}}}' http://rest.yunba.io:8080
        //"opts":{"apn_json":{"aps":{"sound":"bingbong.aiff","badge": 2, "alert":"haha"}}}
        //{"opts":{"apn_json":{"aps":{"sound":"bingbong.aiff","badge":"1","alert":"test"}}}}


    }


}

