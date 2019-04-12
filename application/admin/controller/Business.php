<?php
namespace app\admin\controller;

use app\admin\controller\Base;
use app\common\model\Business as BusinessModel;
use app\common\model\BusinessRelevance as BusinessRelevanceModel;
use think\Request;
use think\Loader;
use think\Db;
use think\Session;

class Business extends Base
{
	public function business_list()
	{
		$this->view->assign('pagetitle','业务目录管理');
		$business = new BusinessModel;
		$list = $business->where("business_father_id","eq","0")->select();
		foreach($list as $i1=>$val1) {
			$level2 = $business->where("business_father_id","eq",$val1->business_id)->select();
			foreach($level2 as $i2=>$val2) {
				$level3 = $business->where("business_father_id","eq",$val2->business_id)->select();
				foreach($level3 as $i3=>$val3) {
					//根据ID找关联ID
				}
				$level2[$i2]['level3'] = $level3;
			}
			$list[$i1]['level2'] = $level2;
		}
		$this->view->assign('list',$list);
		$count = count(BusinessModel::all());
		$this->view->assign('count',$count);
		return $this->view->fetch();
	}

	public function business_add()
	{
		$this->view->assign('pagetitle', '添加业务目录');
		// 读取相关业务数据，也就是全部business数据
		$business = new BusinessModel;
		$list = $business->where("business_father_id","eq","0")->select();
		foreach($list as $i1=>$val1) {
			$level2 = $business->where("business_father_id","eq",$val1->business_id)->select();
			foreach($level2 as $i2=>$val2) {
				$level2[$i2]['level3'] = $business->where("business_father_id","eq",$val2->business_id)->select();
			}
			$list[$i1]['level2'] = $level2;
		}
		$this->view->assign('list',$list);
		return $this->view->fetch();
	}

	public function do_business_add()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
		// 读取所有表单数据
    	$data = input();
    	$result['data'] = $data;
    	$validate = Loader::validate('app\common\validate\Business');
    	$v = $validate->scene('init')->check($data);
    	if(true!==$v) {
    		$result['message'] .= $validate->getError();
    	} else {
    		//数据验证通过，准备执行插入
    		$business = new BusinessModel($data);
    		try{
    			// 插入business表
				$result['rows'] = $business->isUpdate(false)->allowField(true)->save();
				// 如果插入business表成功，则开始执行business_relevance的遍历和写入
				$result['message'] = '<li>业务目录成功</li>';
				if($business->business_level==3) {
					if($result['rows'] == 1 && input('?business_relevance_id')) {
						// 准备相关业务数据
						$list = [];
						foreach($data['business_relevance_id'] as $business_relevance_id) {
							array_push($list, [
								'business_id'=>$business->business_id,
								'business_relevance_id'=>$business_relevance_id
							]);
						}
						// 实例化角色菜单模型
						$business_relevance = new BusinessRelevanceModel;
						// 批量写入数据
						$data['business_relevance_id'] = $business_relevance->saveAll($list);
						// 设置反馈信息
						$result['message'] .= '<li>相关业务设置成功</li>';
					}
				}
				$result['status'] = true;
			} catch(\Exception $e) {
				$result['status'] = false;
				$result['message'] .= $e->getMessage();
			}
    	}
    	return $result;
	}
}