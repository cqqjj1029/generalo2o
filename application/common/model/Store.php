<?php
namespace app\common\model;
use app\common\model\BaseCUD;
use app\common\model\District;
use app\common\model\Merchant;
use app\admin\model\Admin;

class Store extends BaseCUD
{
	/**
	 * 关联store_district_id对应的地区，一对一
	 * @return [type] [description]
	 */
	public function district()
	{
		return $this->hasOne('District','district_id','store_district_id');
	}

	/**
	 * 关联store_merchant_id对应的商户，一对一
	 * @return [type] [description]
	 */
	public function merchant()
	{
		return $this->hasOne('Merchant', 'merchant_id', 'store_merchant_id');
	}

	/**
	 * 关联store_trade_id对应行业，一对一
	 * @return [type] [description]
	 */
	public function trade()
	{
		return $this->hasOne('Trade', 'trade_id', 'store_trade_id');
	}

	/**
	 * 关联store_creator_admin_id对应的创建者，一对一
	 * @return [type] [description]
	 */
	public function creator()
	{
		return $this->hasOne('app\admin\model\Admin', 'admin_id', 'store_creator_admin_id');
	}

	/**
	 * 通过store_wx表关联对应的管理员，多对多
	 * @return [type] [description]
	 */
	public function wxs()
	{
		return $this->belongsToMany('app\common\model\Wx','\app\common\model\StoreWx');
	}


	/**
	 * store_status获取器
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function getStoreStatusAttr($value) {
		$s = '';
		switch ($value) {
			case '1':
				$s = '正常';
				break;
			default:
				$s = '禁用';
				break;
		}
		return $s;
	}
	
}