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
		$count = count($list);	// 1级业务计数
		foreach($list as $i1=>$val1) {
			$level2 = $business->where("business_father_id","eq",$val1->business_id)->select();
			$count += count($level2);	// 2级业务计数
			foreach($level2 as $i2=>$val2) {
				$level3 = $business->where("business_father_id","eq",$val2->business_id)->select();
				$count += count($level3);	// 3级业务计数
				foreach($level3 as $i3=>$val3) {
					//根据ID找关联ID
					$relevances = $val3->relevances;	// 单向查询，只查当前id的relevance_id，不查当前relevance_id的id
					$level3[$i3]['relevances'] = $relevances;
					$businesses = $val3->businesses;	// 单向查询，只查当前id的被相关id
					$level3[$i3]['businesses'] = $businesses;

				}
				$level2[$i2]['level3'] = $level3;
				// 2级业务计数
			}
			$list[$i1]['level2'] = $level2;
		}
		$this->view->assign('list',$list);
		$this->view->assign('count',$count);
		return $this->view->fetch();
	}

	public function business_add()
	{
		$this->view->assign('pagetitle', '添加业务目录');
		// 接收fid参数，如果为0，则随意添加，如果有值，则只添加该fid的下级
		$father_id = input('?fid') ? input('fid') : 0;
		// 取得要添加业务的层级
		$level = $this->get_business_level($father_id)+1;
		if($level>3) {
			// 要添加的业务超过3级不可以添加
			$this->error('参数错误');
			return false;
		}
		$this->view->assign('level', $level);
		// 取fid对应的business_path命名为father_path
		$father_path = $this->get_business_path($father_id);
		// 把father_path传给页面
		$this->view->assign('father_path',$father_path);
		// 读取相关业务目录数据，也就是全部business数据
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

	public function business_edit()
	{
		$this->view->assign('pagetitle', '编辑业务目录');
		$id = input('?id') ? input('id') : 0;
		$business = BusinessModel::get($id);
		if(!$business) {
			$this->error('参数错误');
			return false;
		}
		$this->view->assign('business',$business);
		// 取得向上的所有路径
		$business_path = $this->get_business_path($id);
		$this->view->assign('business_path',$business_path);

		return $this->view->fetch();
	}

	public function business_relevance()
	{
		$this->view->assign('pagetitle', '设置业务相关');
		$id = input('?id') ? input('id') : 0;
		$business = BusinessModel::get($id);
		if(!$business || $business->business_level<3) {
			$this->error('参数错误');
			return false;
		}
		$this->view->assign('business',$business);
		$list = $business->where("business_father_id","eq","0")->select();
		foreach($list as $i1=>$val1) {
			$level2 = $business->where("business_father_id","eq",$val1->business_id)->select();
			foreach($level2 as $i2=>$val2) {
				$level2[$i2]['level3'] = $business->where("business_father_id","eq",$val2->business_id)->select();
			}
			$list[$i1]['level2'] = $level2;
		}
		$this->view->assign('list',$list);
		$relevance_ids = array_column($business->relevances, 'business_id');
		$this->view->assign('relevance_ids',$relevance_ids);
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
							if(input('?relevance_bind')) {
								// 双向冗余插入
								array_push($list, [
									'business_id'=>$business_relevance_id,
									'business_relevance_id'=>$business->business_id
								]);
							}
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

	public function do_business_edit()
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
    		$business = BusinessModel::get($data['business_id']);
    		/// TODO: 有下级的业务不能改变层级
    		/// TODO: 有关联的业务不能改变层级
    		$haschild = false;
    		$hasrelevance = false;
    		// 检查业务是否有下级，是否有关联
    		$child = BusinessModel::get(['business_father_id'=>$data['business_id']]);
    		if($child) $haschild = true;
    		$relevances = $business->relevances;
    		$businesses = $business->businesses;
    		if($relevances||$businesses) $hasrelevance = true;
    		if($haschild || $hasrelevance) {
    			if ($business->business_level != $data['business_level']) {
    				$result['message'] .= '有下级 或有 相关/被相关业务 时，不可以改变层级';
    				return $result;
    			}
    		}
    		$business->business_name = $data['business_name'];
    		$business->business_level = $data['business_level'];
    		$business->business_father_id = input('?business_father_id')?$data['business_father_id']:0;
    		$business->business_description = $data['business_description'];
    		try{
    			// 插入business表
				$result['rows'] = $business->isUpdate(true)->allowField(true)->save($business,['business_id'=>$business->business_id]);
				// 如果插入business表成功，则开始执行business_relevance的遍历和写入
				if($result['rows']) {
					$result['message'] = '业务目录修改成功';
					$result['status'] = true;
				} else {
					$result['message'] = '未改变任何值';
				}
			} catch(\Exception $e) {
				$result['status'] = false;
				$result['message'] .= $e->getMessage();
			}
    	}
    	return $result;
	}

	public function do_business_delete()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
		$id = input('?id') ? input('id') : 0;
		$business = BusinessModel::get($id);
		if(!$business) {
			$result['message'] .= '参数错误';
			return $result;
		}
		$haschild = false;
		$child = BusinessModel::get(['business_father_id'=>$id]);
    	if($child) $haschild = true;
    	if($haschild) {
    		$result['message'] .= '有 下级业务 时不可以删除，请先删除其下级业务';
    		return $result;
    	}
    	///
    	/// TODO: 已有关联商品时不允许删除
    	/// 
    	try{
	    	// 删除所有相关关联
	    	BusinessRelevanceModel::where(['business_id'=>$id])->delete();
	    	// 删除所有被相关关联
	    	BusinessRelevanceModel::where(['business_relevance_id'=>$id])->delete();
	    	// 删除业务
	    	BusinessModel::where(['business_id'=>$id])->delete();
	    	$result['status'] = true;
	    	$result['message'] = '操作成功';
	    } catch(\Exception $e) {
	    	$result['status'] = false;
			$result['message'] .= $e->getMessage();
	    }
	    return $result;
	}

	public function do_business_relevance()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
		$data = input();
		$business = BusinessModel::get($data['business_id']);
		if(!$business || $business->business_level<3) {
			$result['message'] .= '参数错误';
			return $result;
		}
		try {
			// 删除所有本业务的相关记录
			BusinessRelevanceModel::where(['business_id'=>$data['business_id']])->delete();
			// 获取相关数据，重新写入关联
			$list = [];
			if(input('?business_relevance_id')) {
				$data = input();
				// 准备关联数据
				foreach($data['business_relevance_id'] as $business_relevance_id) {
					array_push($list, [
						'business_id'=>$data['business_id'],
						'business_relevance_id'=>$business_relevance_id
					]);
				}
				$business_relevance = new BusinessRelevanceModel;
				// 批量写入数据
				$business_relevance->saveAll($list);
				// 设置反馈信息
				$result['status'] = true;
				$result['message'] = '相关业务设置成功';
			}
		} catch(\Exception $e) {
		    $result['status'] = false;
			$result['message'] .= $e->getMessage();
		}
		return $result;
	}

	public function do_business_relevance_clear()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
		$id = input('?id') ? input('id') : 0;
		$business = BusinessModel::get($id);
		if(!$business || $business->business_level<3) {
			$result['message'] .= '参数错误';
			return $result;
		}
		try {
			// 清除相关
			$row1 = BusinessRelevanceModel::where(['business_id'=>$id])->delete();
			$result['message'] .= '<li>清除了'.$row1.'条相关记录</li>';
			// 清除被相关
			$row2 = BusinessRelevanceModel::where(['business_relevance_id'=>$id])->delete();
			$result['message'] .= '<li>清除了'.$row2.'条被相关记录</li>';
			$result['status'] = true;
		} catch(\Exception $e) {
		    $result['status'] = false;
			$result['message'] .= $e->getMessage();
		}
		return $result;
	}

	public function do_business_status()
	{
		$result = ['status'=>false,'message'=>'操作失败：','data','rows'=>0];
		$id = input('?id') ? input('id') : 0;
		$business = BusinessModel::get($id);
		if(!$business) {
			$result['message'] .= '参数错误';
			return $result;
		}
		$result['data'] = $business;
		// 下级不动
		$status = $business->getData()['business_status'] ? 0 : 1;
		$business->business_status = $status;
		try{
			$result['rows'] = $business->isUpdate(true)->save(['business_status'=>$status],['business_id'=>$id]);
			$result['status'] = true;
			$result['message'] = $result['data']['business_name'].' 已成功切换为 '.$business->business_status.' 状态';
		} catch (\Exception $e) {
			$result['status'] = false;
			$result['message'] .= $e->getMessage();
		}
		return $result;
	}
}