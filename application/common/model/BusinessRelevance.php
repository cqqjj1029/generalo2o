<?php
namespace app\common\model;
use think\model\Pivot;

class BusinessRelevance extends Pivot
{
	public function business()
	{
		return $this->hasOne('Business');
	}

	public function business_relevance()
	{
		return $this->hasOne('Business','business_relevance_id','business_id');
	}
}