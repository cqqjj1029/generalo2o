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
		$this->view->assign('pagetitle','门店管理');
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
        $this->view->assign('pagetitle','添加门店');
        if(Session::get('admin_infor')->admin_super) {
            /// 超级管理员可以管理全部商户
            $merchantlist = MerchantModel::all();
        } else {
            /// 其他管理员可以管理已关联商户
            $merchantlist = MerchantModel::hasWhere('admin_ids',['admin_id'=>Session::get('admin_infor')->admin_id])->select();
        }
        $this->view->assign('merchantlist', $merchantlist);
        $merchant_id = input('?merchant_id')?input('merchant_id'):0;
        $this->view->assign('merchant_id',$merchant_id);
        return $this->view->fetch();
    }

    public function do_store_add()
    {
        $result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
        $data = input();
        $data['store_status'] = input('?store_status') ? 1 : 0;
        $data['store_district_id'] = input('district_value');    // district_value是linkage控件自动生成的hidden的name
        // 分配系统唯一ID
        $data['store_id'] = $this->apply_full_global_id_str(input('store_district_id'));
        
        
    }
}