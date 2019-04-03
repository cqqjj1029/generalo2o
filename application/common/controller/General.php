<?php
namespace app\common\controller;

use think\Controller;
use app\common\model\Config as ConfigModel;
use app\common\model\District as DistrictModel;
use app\common\model\Trade as TradeModel;

class General extends Controller
{
	public function _initialize()
	{
		parent::_initialize();
		// 整站title, keywords, description
		$this->view->assign('title', $this->get_config('title'));
		$this->view->assign('keywords', $this->get_config('keywords'));
		$this->view->assign('description', $this->get_config('description'));
		// 备案号
		$this->view->assign('beian', $this->get_config('beian'));
		// 统计代码
		$this->view->assign('tongji', $this->get_config('tongji'));
	}

	/**
	 * 根据键名取得系统配置项的值
	 * @param  [type]  $key                    [指定config_key]
	 * @param  boolean $use_html_entity_decode [是否对结果进行html_entity_decode操作，默认为true]
	 * @return [type]                          [description]
	 */
    protected function get_config($key, $use_html_entity_decode=true)
    {
        $value = '';
        try{
            $config = ConfigModel::get(['config_key'=>$key]);
            $value = $use_html_entity_decode ? html_entity_decode($config->config_value) : $config->config_value;
        } catch (\Exception $e) {
            $value = $e->getMessage();
        }
        return $value;
    }

    /**
     * 设置一个系统配置项
     * @param [type]  $key        键名
     * @param [type]  $value      值
     * @param boolean $new_if_non 如果不存在则新增，默认为false
     */
    protected function set_config($key, $value, $new_if_non=false, $name='', $deletable=-1)
    {
    	$result = ['status'=>false,'message'=>'操作失败','data','rows'=>0];
    	// 根据config_key查询数据，如果没查到，则定义新实例
		$config = ConfigModel::get(['config_key'=>$key]);
        if(!$config && $new_if_non) {
            // 不存在，但是设置了不存在则新增
            $config = new ConfigModel;
        }
        $config->config_key = $key;
        $config->config_value = $value;
        // 如果调用函数时定义了config_name则同时更新数据库中的name
        if($name!='') $config->config_name = $name; // name设置了值就赋值，否则不动
        if($deletable!=-1) $config->config_deletable = $deletable;  // deletable设置了值就赋值，否则不动
		
		$result['data'] = $config;
    	try {
    		if($new_if_non) {
	    		$result['rows'] = $config->save();
	    	} else {
	    		$result['rows'] = $config->isUpdate(true)->save();
	    	}
    		if($result['rows']) {
    			$result['status'] = true;
    			$result['message'] = '操作成功';
    		}
    	} catch(\Exception $e) {
    		$result['message'] = $e->getMessage();
    	}
    	return $result;
    }

    /**
     * 申请一个global_id并将现有global_id值增加step
     * @param  integer $section     中间需要加的区代码
     * @param  integer $sn_length   序号字符串长度，左补0，默认为2
     * @param  integer $step        序号需要增长的步长
     * @return string               字符串格式为：[时间戳].[section].[sn]
     */
    protected function apply_full_global_id_str($section=0, $sn_length=2, $step=1)
    {
        /*
        字符串格式为：[时间戳].[section].[sn]
         */
    	$section_str = $section==0 ? '' : str_pad($section,6,'0',STR_PAD_LEFT);	//section长6个数字
    	$sn_str = str_pad($this->apply_global_sn($step),$sn_length,'0',STR_PAD_LEFT);	//序号长6个数字
    	$full = time().$section_str.$sn_str;
    	return $full;
    }

    /**
     * 取得当前系统全局ID的数值
     * @param  integer $step 增长步长
     * @return [type]        返回ID数值
     */
    protected function apply_global_sn($step=1)
    {
    	$config = ConfigModel::get(['config_key'=>'max_global_id']);
    	if(!$config) {
    		// 没取到，调用set_config创建配置项
    		$this->set_config('max_global_id', 0, true, '当前ID序号', 0);
    		$config = ConfigModel::get(['config_key'=>'max_global_id']);
    	}
    	// 根据update_time和当前时间戳的比较判断ID是否需要改变
    	if($config->getData()['update_time']==time()) {
    		// update_time和当前时间戳在同一秒，需要改变ID
	    	$config->config_value+=$step;
	    } else {
	    	// update_time和当前时间戳不在同一秒，将序号复位到1
	    	$config->config_value = 1;
	    }
	    // 强行更新update_time
	    $config->update_time = time();
	    $config->save();
	    // 返回ID数值
    	return $config->config_value;
    }

    /**
     * 根据father_id查询对应的地区数据
     * @param  integer $father_id 上一级地区ID，默认86为查询省级
     * @return [type]             返回数据集
     */
    protected function get_district($father_id=86)
    {
        $district = new DistrictModel;
        $list = $district->where(['district_father_id'=>$father_id])->order(['district_id'=>'asc'])->select();
        return $list;
    }

    /**
     * 根据district_id查询对应的父级地区数据
     * @param  integer $id 要查询的district_id
     * @return [type]      返回数据集
     */
    protected function get_district_father($id=110000)
    {
        $district = DistrictModel::get($id);
        $father = DistrictModel::get($district->district_father_id);
        return $father;
    }

    protected function get_trade($father_id=0)
    {
        $trade = new TradeModel;
        if($father_id) {
            $list = $trade->where('trade_father_id', 'eq', $father_id)->order(['trade_id'=>'asc'])->select();
        } else {
            $list = $trade->where('trade_father_id','null')->order(['trade_id'=>'asc'])->select();
        }
        return $list;
    }
}