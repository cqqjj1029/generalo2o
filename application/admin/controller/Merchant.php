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
	public function merchant_list()
	{
		$this->view->assign('pagetitle','商户管理');
		$list = MerchantModel::all();
		if(Session::get('admin_infor')->admin_super) {
            // 超级管理员可以管理全部商户
        } else {
        	// 其他管理员可以管理已关联商户
        }
        $this->view->assign('list', $list);
        $this->view->assign('count', count($list));
        return $this->view->fetch();
	}

	public function merchant_add()
	{
		$this->view->assign('pagetitle', '添加商户');

		return $this->view->fetch();
	}
}