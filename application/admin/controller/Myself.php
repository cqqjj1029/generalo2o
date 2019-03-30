<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use think\Session;
use think\Request;
use think\Db;
use think\Cache;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Role as RoleModel;


class Myself extends Base
{
    public function index()
    {
    	$this->view->assign('pagetitle','个人信息');
        $admin = AdminModel::get(Session::get('admin_infor')->admin_id);
        $this->view->assign('admin', $admin);
        return $this->view->fetch();
    }

    public function password()
    {
        $this->view->assign('pagetitle','修改密码');
        return $this->view->fetch();
    }

    /**
     * 用户登出操作
     * @return [type] [description]
     */
    public function do_logout()
    {
        Session::delete('admin_infor');
        Session::delete('admin_role');
        Session::delete('admin_menus');
        Session::clear();

        $this->success('已安全退出',url('admin/index/index'));
        // echo "<script>top.location.href='".url('admin/index/index')."';</script>";
    }

    /**
     * 执行个人信息修改
     * @return [type] [description]
     */
    public function do_infor_edit()
    {
        $result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
        // 读取所有表单数据
        $data = input();
        $admin_id = Session::get('admin_infor')->admin_id;
        $admin = new AdminModel;
        try {
            $result['rows'] = $admin->isUpdate(true)->save([
                    'admin_name'           =>  $data['admin_name'],
                    'admin_email'           =>  $data['admin_email'],
                    'admin_telephone'       =>  $data['admin_telephone'],
                    'admin_description'     =>  $data['admin_description']
                ],['admin_id'=>$admin_id]);
            if($result['rows']) {
                $result['message'] = '操作成功';
                $result['status'] = true;
                // 更新session中的信息以保证信息同步
                $admin = AdminModel::get($admin_id);
                // admin的admin_super强制为1
                $admin->admin_super = $admin->admin_account=='admin' ? 1 : $admin->admin_super;
                Session::set('admin_infor', $admin);
                $result['data'] = Session::get('admin_infor');
            }
        } catch(\Exception $e) {
            $result['status'] = false;
            $result['message'] .= $e->getMessage();
        }
        return $result;
    }

    /**
     * 执行修改个人密码的操作
     * @return [type] [description]
     */
    public function do_password_edit()
    {
        $result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
        // 读取所有表单数据
        $data = input();
        $admin = Session::get('admin_infor');
        $admin_origin = AdminModel::get(['admin_account'=>$admin->admin_account]);
        if($admin_origin) {
            // 能根据用户名查到记录，说明用户名正确
            if(to_encrypt_compare($data['admin_password_old'], $admin_origin->admin_account, $admin_origin->admin_password)) {
                // 用户名密码正确
                // 判断停用状态
                if($admin_origin->admin_status == '正常') {
                    // 一切正常，这时才可以修改密码
                    try {
                        $result['rows'] = $admin_origin->save(['admin_password'=>$data['admin_password']],['admin_id'=>$admin_origin->admin_id]);
                        if($result['rows']) {
                            $result['status'] = true;
                            $result['message'] = '操作成功';
                            Session::set('admin_infor', $admin_origin);
                            $result['data'] = $admin_origin;
                        }
                    } catch(\Exception $e) {
                        $result['status'] = false;
                        $result['message'] = $e->getMessage();
                    }
                } else {
                    $result['message'] .= ' 账号状态异常';
                }
            } else {
                $result['message'] .= ' 原密码错误';
            }
        } else {
            $result['message'] .= ' 账号参数异常';
        }
        return $result;
    }

    public function do_sync_session()
    {
        $result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
        $admin_id = Session::get('admin_infor')->admin_id;
        try {
            $admin = AdminModel::get(['admin_id'=>$admin_id]);// admin用户天生就是超级管理员
            $admin->admin_super = $admin->admin_account=='admin' ? 1 : $admin->admin_super;
            // 对超级管理员手动配置角色，对非超级管理员通过数据表关联角色
            $role = $admin->admin_super ? new RoleModel(['role_name'=>'超级管理员']) : $admin->role;
            // 根据角色关联菜单，超级管理手动设置菜单权限为null
            $menus = $admin->admin_super ? null : ($admin->role ? $admin->role->menus : null);
            // 将管理员、角色、菜单写入Session
            Session::set('admin_infor', $admin);    // 账号信息
            Session::set('admin_role', $role);  // 角色信息
            Session::set('admin_menus', $menus);    // 菜单权限
            $result['status'] = true;
            $result['message'] = '操作成功';
        } catch(\Exception $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function do_clear_cache()
    {
        $result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
        try {
            Cache::clear();
            array_map('unlink', glob(TEMP_PATH.DS.'*.php'));
            $result['status'] = true;
            $result['message'] = '操作成功';
        } catch(\Exception $e) {
            $result['status'] = false;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
}
