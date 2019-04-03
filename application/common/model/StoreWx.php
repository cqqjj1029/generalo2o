<?php
# Store 和 Wx对应的“中间模型”
# 需要引入think\model\Pivot并extends
namespace app\common\model;
use think\model\Pivot;

class StoreWx extends Pivot
{
	public function store()
	{
		return $this->hasOne('app\common\model\Store');
	}

	public function wx()
	{
		return $this->hasOne('app\common\model\Wx');
	}

	public function wx_ids()
	{
		return $this->hasMany('app\common\model\StoreWx','store_id');
	}
}