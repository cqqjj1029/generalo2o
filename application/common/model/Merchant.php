<?php
namespace app\common\model;
use app\common\model\BaseCUD;
use app\common\model\District;
use app\admin\model\Admin;

class Merchant extends BaseCUD
{
	/**
	 * 关联merchant_district_id对应的地区，一对一
	 * @return [type] [description]
	 */
	public function district()
	{
		return $this->hasOne('District','district_id','merchant_district_id');
	}

	/**
	 * 关联merchant_creator_admin_id对应的创建者，一对一
	 * @return [type] [description]
	 */
	public function creator()
	{
		return $this->hasOne('app\admin\model\Admin', 'admin_id', 'merchant_creator_admin_id');
	}

	/**
	 * 通过merchant_admin表关联对应的管理员，多对多
	 * @return [type] [description]
	 */
	public function admins()
	{
		return $this->belongsToMany('app\admin\model\Admin','\app\admin\model\MerchantAdmin');
	}

	public function admin_ids()
	{
		return $this->hasMany('app\admin\model\MerchantAdmin','merchant_id');
	}

	/**
	 * merchant_login_ip获取器
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function getMerchantLoginIpAttr($value) {
		if($value!==null) {
			return long2ip($value);
		} else {
			return '';
		}
	}

	/**
	 * merchant_login_time获取器，时间戳转换字符串
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function getMerchantLoginTimeAttr($value) {
		if($value) {
			return date(\think\Config::get('database.datetime_format'),$value);
		} else {
			return '';
		}
	}

	/**
	 * merchant_password字段修改器，只对密码字段加密
	 * @param [type] $value 返回加密后字符串
	 */
	public function setMerchantPasswordAttr($value) {
		return to_encrypt($value);
	}

	/**
	 * merchant_status获取器
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function getMerchantStatusAttr($value) {
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