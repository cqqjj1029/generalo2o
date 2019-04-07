<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\common\model\Store as StoreModel;
use app\common\model\Merchant as MerchantModel;
use app\admin\model\Admin as AdminModel;
use app\admin\model\MerchantAdmin as MerchantAdminModel;
use think\Request;
use think\Loader;
use think\Db;
use think\Session;

class Store extends Base
{
	public function store_list()
	{
		$this->view->assign('pagetitle','店铺管理');
		if(Session::get('admin_infor')->admin_super) {
            /// 超级管理员可以管理全部商户
        	$list = StoreModel::all(function($query){
			    $query->order(['store_merchant_id'=>'desc','store_status'=>'desc','store_title'=>'asc','store_subtitle'=>'asc']);
			});
    	} else {
        	/// 其他管理员可以管理已关联商户
        	$merchantlist = MerchantModel::hasWhere('admin_ids',['admin_id'=>Session::get('admin_infor')->admin_id])->select();
        	$merchant_ids = array_column($merchantlist,'merchant_id');
        	$list = StoreModel::where(['store_merchant_id'=>['in',$merchant_ids]])->select();
        }
        $this->view->assign('list', $list);
        $this->view->assign('count', count($list));
        return $this->view->fetch();
	}

    public function store_add()
    {
        return $this->view->fetch();
    }
}