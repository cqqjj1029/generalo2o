<?php
namespace app\common\model;
use app\common\model\Base;

class Business extends Base
{
	public function relevances()
	{
		return $this->belongsToMany('Business','\app\common\model\BusinessRelevance','business_relevance_id','business_id');
	}

	public function businesses()
	{
		return $this->belongsToMany('Business','\app\common\model\BusinessRelevance','business_id','business_relevance_id');
	}

	// business_status
	public function getBusinessStatusAttr($value) {
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