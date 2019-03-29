<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\common\model\Merchant as MerchantModel;
use app\admin\model\Admin as AdminModel;
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
		$list = MerchantModel::all();
		if(Session::get('admin_infor')->admin_super) {
            /// TODO:超级管理员可以管理全部商户
        } else {
        	/// TODO:其他管理员可以管理已关联商户
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

	public function do_merchant_add()
	{
		
	}
}