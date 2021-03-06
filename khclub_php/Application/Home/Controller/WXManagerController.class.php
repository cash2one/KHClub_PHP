<?php
/**
 * Created by PhpStorm.
 * User: lixiaohang
 * Date: 16/1/29
 * Time: 10:32
 */
namespace Home\Controller;
use Think\Controller;
use Think\Exception;

class WXManagerController extends Controller {

    public function withdrawRequest(){

        $this->display("withdrawRequest");

    }
    /**
     * @brief 提现请求详情
     * 接口地址
     * http://114.215.95.23/khclub_php/index.php/Home/WXManager/withdrawRequest
     * @param page  页码
     * @param size 每页数量
     */

    public function withdrawRequestInfo(){
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
            //获取总页数
            $requestModel = M();
            $sql = 'SELECT COUNT(wi.id) size FROM kh_withdraw_notice wi, kh_user_info uc
                    WHERE uc.id = wi.user_id ORDER BY wi.add_date DESC';
            $count = $requestModel->query($sql)[0]['size'];
            //获取最后一页
            $page_count  = ceil($count/$size);
            $sql = 'SELECT uc.id, uc.name, wi.add_date, wi.withdraw_state FROM kh_withdraw_notice wi, kh_user_info uc
                    WHERE uc.id = wi.user_id ORDER BY wi.add_date DESC, wi.withdraw_state DESC LIMIT '.$start.','.$end;
            $request = $requestModel->query($sql);
            for($j=0; $j<count($request); $j++) {
                $request[$j]['add_date'] = date('Y年m月d日', $request[$j]['add_date']);
            }

            $result = array('list'=>$request, 'page'=>$page, 'page_count'=>$page_count);

            returnJson(1,"查询成功", $result);

        }catch (Exception $e) {

            returnJson(0,"数据异常", $e);
        }
    }

    /**
     * @brief 提现请求详情
     * 接口地址
     * http://114.215.95.23/khclub_php/index.php/Home/WXManager/userList
     * @param page  页码
     * @param size 每页数量
     */
    public function userList(){
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
        $userModel = M();
        //获取总页数
        $sql = 'SELECT COUNT(amount) FROM kh_user_info uc, kh_user_extra_info ex, kh_lucky lu
                WHERE uc.id=ex.user_id AND uc.id=lu.user_id AND uc.delete_flag=0 AND ex.delete_flag=0 AND lu.state=1 GROUP BY uc.id
                ORDER BY amount DESC';
        $count = count($userModel->query($sql));
        //获取最后一页
        $page_count  = ceil($count/$size);
        $sql = 'SELECT uc.id, uc.name, ex.wx_open_id, SUM(amount) amount FROM kh_user_info uc, kh_user_extra_info ex, kh_lucky lu
                WHERE uc.id=ex.user_id AND uc.id=lu.user_id AND uc.delete_flag=0 AND ex.delete_flag=0 AND lu.state=1 GROUP BY uc.id
                ORDER BY amount DESC LIMIT '.$start.','.$end;
        $user = $userModel->query($sql);

        $this->assign('page',$page);
        $this->assign('page_count',$page_count);
        $this->assign('user',$user);

        $this->display('userList');
    }

    /**
     * @brief 提现请求详情
     * 接口地址
     * http://114.215.95.23/khclub_php/index.php/Home/WXManager/withdrawDetail
     * @param user_id 用户id
     * @param page  页码
     * @param size 每页数量
     */
    public function withdrawDetail(){
        $user_id = $_REQUEST['user_id'];
        $userModel = M();
        $sql = 'SELECT uc.name, ex.wx_open_id FROM kh_user_info uc, kh_user_extra_info ex
                WHERE uc.id='.$user_id.' AND uc.id=ex.user_id AND uc.delete_flag=0 AND ex.delete_flag=0
                LIMIT 1 ';
        $user = $userModel->query($sql);
        $userInfo = $user[0];
        $sql = 'SELECT add_date, amount, send_id FROM kh_lucky lu WHERE user_id='.$user_id.' AND delete_flag=0 AND state=1
                ORDER BY add_date DESC';
        $user = $userModel->query($sql);
        $sumAmount = 0;
        for($j=0; $j<count($user); $j++) {
            $id = $user[$j]['send_id'];
            $sql='SELECT name FROM kh_user_info WHERE id='.$id;
            $recommended = $userModel->query($sql);
            $user[$j]['recommended'] = $recommended[0]['name'];
            $user[$j]['add_date'] = date('Y-m-d', $user[$j]['add_date']);
            $sumAmount += $user[$j]['amount'];
        }
        $id = $user_id;
        $this->assign('id',$id);
        $this->assign('sumAmount',$sumAmount);
        $this->assign('userInfo',$userInfo);
        $this->assign('user',$user);
        $this->display('withdrawDetail');
    }

    /**
     * @brief 提现请求详情
     * 接口地址
     * http://114.215.95.23/khclub_php/index.php/Home/WXManager/withdrawRecord
     * @param user_id 用户id
     * @param page  页码
     * @param size 每页数量
     */
    public function withdrawRecord(){
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
        $userModel = M();
        $sql = 'SELECT uc.name, ex.wx_open_id FROM kh_user_info uc, kh_user_extra_info ex
                WHERE uc.id='.$user_id.' AND uc.id=ex.user_id AND uc.delete_flag=0 AND ex.delete_flag=0
                LIMIT 1 ';
        $user = $userModel->query($sql);
        $userInfo = $user[0];
        //获取总页数
        $sql = 'SELECT id FROM kh_lucky lu WHERE user_id='.$user_id.' AND delete_flag=0 AND state=2
                ORDER BY add_date DESC';
        $count = count($userModel->query($sql));
        //获取最后一页
        $page_count  = ceil($count/$size);
        $sql = 'SELECT withdraw_date, amount, send_id FROM kh_lucky lu WHERE user_id='.$user_id.' AND delete_flag=0 AND state=2
                ORDER BY add_date DESC LIMIT '.$start.','.$end;
        $user = $userModel->query($sql);
        $sumAmount = 0;
        for($j=0; $j<count($user); $j++) {
            $id = $user[$j]['send_id'];
            $sql='SELECT name FROM kh_user_info WHERE id='.$id;
            $recommended = $userModel->query($sql);
            $user[$j]['recommended'] = $recommended[0]['name'];
            $user[$j]['add_date'] = date('Y-m-d', $user[$j]['add_date']);
            $sumAmount += $user[$j]['amount'];
        }
        $id = $user_id;
        $this->assign('id',$id);
        $this->assign('page',$page);
        $this->assign('page_count',$page_count);
        $this->assign('sumAmount',$sumAmount);
        $this->assign('userInfo',$userInfo);
        $this->assign('user',$user);
        $this->display('withdrawRecord');
    }

    /**
     * @brief 管理系统
     * 接口地址
     * http://localhost/khclub_php/index.php/Home/WXManager/withdrawCommit
     * @param target_id 要提现的账户ID
     */
    function withdrawCommit(){

        //未登录
        if(empty($_SESSION['manager'])){
            header('Location: '.__ROOT__.'/index.php/Home/login/login');
            exit;
        }
        $target_id = $_POST['target_id'];
        if(empty($target_id)){
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/withdrawDetail?user_id='.$target_id);
            exit;
        }

        $withdrawModel = M();
        $sql = 'UPDATE kh_withdraw_notice SET withdraw_state=1 WHERE user_id="'.$target_id.'"';
        $withdrawModel->execute($sql);
        //提现
        $sql = 'UPDATE kh_lucky SET state=2,update_date='.time().',withdraw_date='.time().'
                WHERE user_id="'.$target_id.'" AND state=1 AND delete_flag=0';
        $num = $withdrawModel->execute($sql);
        if($num < 1){
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/withdrawRecord?user_id='.$target_id);
            exit;
        }else{
            //提现成功
            header('Location: '.__ROOT__.'/index.php/Home/WXManager/withdrawRecord?user_id='.$target_id);
            exit;
        }

    }
}