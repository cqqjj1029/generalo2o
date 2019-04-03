<?php
namespace app\wx\controller;

use app\common\controller\General;

class Base extends General
{
	public function _initialize()
	{
		parent::_initialize();
	}

	/**
	 * 检验微信服务器
	 * @return [type] 正确为true
	 */
	protected function checkSignature()
	{
		$data = input();
		// 从本站配置中读取token
		$data['token'] = $this->get_config('wx_token');
		// 将timestamp, nonce, token组成数组
		$temp_arr = array($data['timestamp'], $data['nonce'], $data['token']);
		// 对数组进行字典排序
		sort($temp_arr, SORT_STRING);
		// 将排序后数组连成字符串并进行sha1加密
		$temp_str = sha1(implode($temp_arr));
		// 将加密后字符串与signature进行比较
		if($temp_str == $data['signature']) {
			// 结果相等，证明请求来自于微信服务器，原文返回echostr
			return $data['echostr'];
		} else {
			// 结果不相等，说明请求来历不明，返回false
			return false;
		}
	}
}