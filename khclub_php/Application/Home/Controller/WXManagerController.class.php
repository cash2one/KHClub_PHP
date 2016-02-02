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
                $page = 2;
            }
            if(empty($size)){
                $size = 2;
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
            $sql = 'SELECT wi.id, uc.name, wi.add_date, wi.withdraw_state FROM kh_withdraw_notice wi, kh_user_info uc
                    WHERE uc.id = wi.user_id ORDER BY wi.add_date DESC LIMIT '.$start.','.$end;
            $request = $requestModel->query($sql);
            for($j=0; $j<count($request); $j++) {
                $request[$j]['add_date'] = date('Y年m月d日', $request[$j]['add_date']);
            }
            $request[0]['page'] = $page;
            $request[0]['page_count'] = $page_count;
            returnJson(1,"查询成功", $request);

        }catch (Exception $e) {

            returnJson(0,"数据异常=_=", $e);
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
        $sql = 'SELECT id FROM kh_lucky lu WHERE user_id='.$user_id.' AND delete_flag=0 AND state=1
                ORDER BY add_date DESC';
        $count = count($userModel->query($sql));
        //获取最后一页
        $page_count  = ceil($count/$size);
        $sql = 'SELECT add_date, amount, send_id FROM kh_lucky lu WHERE user_id='.$user_id.' AND delete_flag=0 AND state=1
                ORDER BY add_date DESC LIMIT '.$start.','.$end;
        $user = $userModel->query($sql);
        for($j=0; $j<count($user); $j++) {
            $id = $user[$j]['send_id'];
            $sql='SELECT name FROM kh_user_info WHERE id='.$id;
            $recommended = $userModel->query($sql);
            $user[$j]['recommended'] = $recommended[0]['name'];
            $user[$j]['add_date'] = date('Y-m-d', $user[$j]['add_date']);
            $sumAmount += $user[$j]['amount'];
        }
        $this->assign('page',$page);
        $this->assign('page_count',$page_count);
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
        for($j=0; $j<count($user); $j++) {
            $id = $user[$j]['send_id'];
            $sql='SELECT name FROM kh_user_info WHERE id='.$id;
            $recommended = $userModel->query($sql);
            $user[$j]['recommended'] = $recommended[0]['name'];
            $user[$j]['add_date'] = date('Y-m-d', $user[$j]['add_date']);
            $sumAmount += $user[$j]['amount'];
        }
        $this->assign('page',$page);
        $this->assign('page_count',$page_count);
        $this->assign('sumAmount',$sumAmount);
        $this->assign('userInfo',$userInfo);
        $this->assign('user',$user);
        $this->display('withdrawRecord');
    }
}