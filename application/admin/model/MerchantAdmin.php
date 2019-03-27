<?php
# Role 和 Menu对应的“中间模型”
# 需要引入think\model\Pivot并extends
namespace app\admin\model;
use think\model\Pivot;

class MerchantAdmin extends Pivot
{
	public function merchant()
	{
		return $this->hasOne('app\common\model\Merchant');
	}

	public function admin()
	{
		return $this->hasOne('Admin');
	}
}