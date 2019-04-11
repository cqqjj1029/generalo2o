<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\common\model\Business as BusinessModel;
use think\Request;
use think\Loader;
use think\Db;
use think\Session;

class Business extends Base
{
	public function business_list()
	{
		$this->view->assign('pagetitle','业务目录管理');
		return $this->view->fetch();
	}
}