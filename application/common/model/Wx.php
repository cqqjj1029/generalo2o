<?php
namespace app\common\model;
use app\common\model\BaseCUD;

class Wx extends BaseCUD
{
	/**
	 * 通过store_wx表关联对应的管理员，多对多
	 * @return [type] [description]
	 */
	public function stores()
	{
		return $this->belongsToMany('app\common\model\Store','\app\common\model\StoreWx');
	}

	public function store_ids()
	{
		return $this->hasMany('app\common\model\StoreWx','wx_id');
	}
}