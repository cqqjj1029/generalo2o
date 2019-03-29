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
		return $this->hasOne('Admin', 'admin_id', 'merchant_creator_admin_id');
	}

	/**
	 * 通过merchant_admin表关联对应的管理员，一对多
	 * @return [type] [description]
	 */
	public function admins()
	{
		return $this->belongsToMany('Admin','\app\admin\model\MerchantAdmin');
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
	 * merchant_password字段修改器,返回md5(sha1(p).sha1(n))
	 * @param [type] $value [description]
	 * @param [type] $data  [description]
	 */
	public function setMerchantPasswordAttr($value, $data) {
		return to_encrypt($value, $data['admin_name']);
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