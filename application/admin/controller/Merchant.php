<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\common\model\Merchant as MerchantModel;
use app\admin\model\Admin as AdminModel;
use app\admin\model\MerchantAdmin as MerchantAdminModel;
use think\Request;
use think\Loader;
use think\Db;
use think\Session;

class Merchant extends Base
{
	/**
	 * 渲染商户列表页
	 * @return [type] [description]
	 */
	public function merchant_list()
	{
		$this->view->assign('pagetitle','商户管理');
		if(Session::get('admin_infor')->admin_super) {
            /// 超级管理员可以管理全部商户
        	$list = MerchantModel::all();
    	} else {
        	/// 其他管理员可以管理已关联商户
        	$list = MerchantModel::hasWhere('admin_ids',['admin_id'=>Session::get('admin_infor')->admin_id])->select();
        }
        foreach($list as $n=>$val) {
        	$list[$n]['child'] = $val->admins;
        }
        $this->view->assign('list', $list);
        $this->view->assign('count', count($list));
        return $this->view->fetch();
	}

	/**
	 * 渲染添加商户页
	 * @return [type] [description]
	 */
	public function merchant_add()
	{
		$this->view->assign('pagetitle', '添加商户');
		// 读出当前权限中可为商户分配的管理员
		$this->view->assign('admin_list', $this->getMyAdminList(false));
		return $this->view->fetch();
	}

	/**
	 * 执行添加商户操作
	 * @return [type] [description]
	 */
	public function do_merchant_add()
	{
		$result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
    	$data = input();
    	$data['merchant_status'] = input('?merchant_status') ? 1 : 0;
    	// 用验证器对数据进行校验
    	$validate = Loader::validate('app\common\validate\Merchant');
    	$v = $validate->scene('init')->check($data);
		if(!$v) {
			$result['message'] = $validate->getError();
			return $result;
		}
		// 分配系统唯一ID
		$data['merchant_id'] = $this->apply_full_global_id_str(input('merchant_district_id'));
		// 记录“创建者”
		$data['merchant_creator_admin_id'] = Session::get('admin_infor')->admin_id;
    	$result['data'] = $data;
		// 创建Merchant模型实例
		$merchant = new MerchantModel;
		try {
			// 执行模型新增操作并返回行数
			$result['rows'] = $merchant->isUpdate(false)->allowField(true)->save($data);
			if($result['rows']==1) {
				// merchant添加成功，接下来插入merchant_admin
				$result['message'] = '<li>商户添加成功</li>';
				$list = [];
				foreach($data['admin_id'] as $admin_id) {
					array_push($list, [
						'merchant_id'=>$merchant->merchant_id,
						'admin_id'=>$admin_id
					]);
				}
				// 实例化角色菜单模型
				$merchant_admin = new MerchantAdminModel;
				// 批量写入数据
				$data['menu_id'] = $merchant_admin->saveAll($list);
				// 设置反馈信息
				$result['message'] .= '<li>商户管理员设置成功</li>';
				$result['status'] = true;
			}
		} catch(\Exception $e) {
			$result['status'] = false;
			$result['message'] = $e->getMessage();
		}
		return $result;
	}

	public function do_merchant_delete()
	{
		$result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
		if(!input('?id')) {
			$result['message'].='<li>参数错误1</li>'.input('?id');
			return $result;
		}
		$merchant_id = input('id');
		try {
			$result['data'] = MerchantModel::get($merchant_id);
			if(!$result['data']) {
				$result['message'].='<li>参数错误2</li>';
				return $result;
			}
			$result['rows'] = MerchantModel::destroy(['merchant_id'=>$merchant_id]);
			if(!$result['rows']) {
				$result['message'].='<li>参数错误3</li>'.input('?id');
				return $result;				
			}
			$result['status'] = true;
			$result['message'] = '操作成功';
		} catch (\Exception $e) {
			$result['message'] .= $e->getMessage();	// 获取异常信息
		}
		return $result;
	}
}