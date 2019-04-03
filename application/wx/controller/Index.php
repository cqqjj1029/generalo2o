<?php
namespace app\wx\controller;
use app\wx\controller\Base;

class Index extends Base
{
    public function index()
    {
    	$check_result = $this->checkSignature();
    	if($check_result) {
		    header('content-type:text');
		    echo $check_result;
		    exit;
		} else {
			return;
		}
    }
}
