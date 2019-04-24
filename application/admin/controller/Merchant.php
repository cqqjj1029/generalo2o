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
	 * 渲染编辑商户页
	 * @return [type] [description]
	 */
	public function merchant_edit()
	{
		$this->view->assign('pagetitle', '编辑商户');

		$id = input('?id') ? input('id') : 0;
		// 读取商户数据
    	$merchant = MerchantModel::get($id);
    	if($merchant) {
			// 准备admin列表，排除超级管理员
			$admin = $this->getMyAdminList(false);
			// 分配管理员列表
			$this->view->assign('admin_list',$admin);
			// 分配商户数据
			$this->view->assign('merchant', $merchant);
			// 将商户对应的管理员id组织成数据
			$admin_ids = array_column($merchant->admins, 'admin_id');
			// 分配管理员ID数据到前台
            $this->view->assign('admin_ids', $admin_ids);
            // 渲染页面
    		return $this->view->fetch();
    	} else {
    		$this->error('参数错误');
    		return;
    	}
	}

	/**
	 * 渲染修改密码页
	 * @return [type] [description]
	 */
	public function merchant_password()
	{
		$this->view->assign('pagetitle', '编辑商户密码');

		$id = input('?id') ? input('id') : 0;
		// 读取商户数据
    	$merchant = MerchantModel::get($id);
    	if($merchant) { 
			// 分配商户数据
			$this->view->assign('merchant', $merchant);
			// 渲染页面
    		return $this->view->fetch();
    	} else {
    		$this->error('参数错误');
    		return;
    	}
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
		$data['merchant_district_id'] = input('district_value');	// district_value是linkage控件自动生成的hidden的name
    	$v = $validate->scene('init')->check($data);
		if(!$v) {
			$result['message'] = $validate->getError();
			return $result;
		}
		// 分配系统唯一ID
		$data['merchant_id'] = $this->apply_full_global_id_str($data['merchant_district_id']);
		// 记录“创建者”
		$data['merchant_creator_admin_id'] = Session::get('admin_infor')->admin_id;
    	$result['data'] = $data;
		// 创建Merchant模型实例
		$merchant = new MerchantModel;
		try {
			// 执行模型新增操作并返回行数
			$result['rows'] = $merchant->isUpdate(false)->allowField(true)->save($data);
			if($result['rows']==1) {
				// merchant添加成功
				$result['message'] = '<li>商户添加成功</li>';
				// 插入merchant_admin
				if(input('?admin_id')) {
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
				}
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
	
	/**
	 * 执行编辑商户和分配商户管理员操作
	 * @return [type] [description]
	 */
	public function do_merchant_edit()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
    	$data = input();
    	$data['merchant_status'] = input('?merchant_status') ? 1 : 0;
    	// 用验证器对数据进行校验
    	$validate = Loader::validate('Merchant');
    	$merchant = MerchantModel::get(['merchant_id'=>$data['merchant_id']]);
		$merchant->merchant_name = $data['merchant_name'];
		$merchant->merchant_email = $data['merchant_email'];
    	$merchant->merchant_mobile = $data['merchant_mobile'];
    	$merchant->merchant_district_id = $data['merchant_district_id'];
    	$merchant->merchant_status = $data['merchant_status'];
    	$merchant->merchant_description = $data['merchant_description'];
    	$v = $validate->scene('nopassword')->check($data);
    	$result['data'] = $merchant;
    	if(!$v) {
    		$result['message'] .= $validate->getError();
    		return $result;
    	}
    	try{
			$result['rows'] = $merchant->allowField(true)->isUpdate(true)->save($merchant,['merchant_id'=>$data['merchant_id']]);
			if($result['rows']) {
				$result['message'] .= '<li>商户信息修改成功</li>';
				// 更新商户对应管理员
				$merchant_admin = new MerchantAdminModel;
				$adminlist = $this->getMyAdminList(false);
				$adminids = array_column($adminlist, 'admin_id');
				// 删除当前本商户对应的可访问管理员
				$merchant_admin->where(['merchant_id'=>$data['merchant_id'], 'admin_id'=>['in', $adminids]])->delete();
				// 准备新的商户管理员数据
				if(input('?admin_id')) {
					$list = [];
					foreach($data['admin_id'] as $val) {
						array_push($list, ['merchant_id'=>$data['merchant_id'], 'admin_id'=>$val]);
					}
					if($list) {
						$merchant_admin->saveAll($list);
					}
				}
				// 设置反馈信息
                $result['message'] .= '<li>商户管理员设置成功</li>';
				$result['status'] = true;
			}
		} catch(\Exception $e) {
			$result['message'] .= $e->getMessage();
		}
		return $result;
	}

	public function do_merchant_password()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
    	$data = input();
    	// 用验证器对数据进行校验
    	$validate = Loader::validate('Merchant');
    	$merchant = MerchantModel::get(['merchant_id'=>$data['merchant_id']]);
    	$v = $validate->scene('password')->check($data);
    	if(!$v) {
    		$result['message'] = $validate->getError();
    		return $result;
    	}
    	$merchant->merchant_password = $data['merchant_password'];
    	$result['data'] = $merchant;
    	try{
			$result['rows'] = $merchant->allowField(true)->isUpdate(true)->save(['merchant_password'=>$merchant->merchant_password],['merchant_id'=>$data['merchant_id']]);
			if($result['rows']) {
				$result['status'] = true;
				$result['message'] = '操作成功';
			}
		} catch(\Exception $e) {
			$result['message'] .= $e->getMessage();
		}
		return $result;
	}

	/**
	 * 执行删除商户的操作
	 * @return [type] 返回$result对象
	 */
	public function do_merchant_delete()
	{
		$result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
		if(!input('?id')) {
			$result['message'].='<li>参数错误1</li>'.input('?id');
			return $result;
		}
		$id = input('id');
		try {
			$result['data'] = MerchantModel::get($id);
			if(!$result['data']) {
				$result['message'].='<li>参数错误2</li>';
				return $result;
			}
			// 软删除商户主表数据
			// 软删除时不删除对应的关联记录
			$result['rows'] = MerchantModel::destroy(['merchant_id'=>$id]);
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

	/**
	 * 执行切换商户可用状态操作
	 * @return [type] 返回$result对象
	 */
	public function do_merchant_status()
	{
    	$result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
    	$id = input('id');	// 获取页面传入的id
		// 用异常处理的方式执行以下操作
		try {
			$merchant = MerchantModel::get($id);	// 查询id对应的记录
			$merchant['merchant_status'] = $merchant->getData('merchant_status')==1 ? 0 : 1;
			
			$result['rows'] = $merchant->isUpdate(true)->allowField(true)->save(['merchant_status' => $merchant->getData('merchant_status')],['merchant_id'=>$id]);
			if($result['rows']) {
				$result['message'] = $merchant['merchant_name'].'已切换为“'.$merchant['merchant_status'].'”状态';	// 生成友好的提示信息
				$result['status'] = true;
			}
			
			$result['data'] = $merchant;
		} catch(\Exception $e) {
			$result['message'] .= $e->getMessage();	//将异常信息赋值给$message
		}
		return $result;
	}
}