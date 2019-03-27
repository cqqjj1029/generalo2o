<?php
namespace app\index\controller;
use app\index\controller\Base;

class Index extends Base
{
    public function index()
    {
        return $this->view->fetch();
    }

    public function vue()
    {
    	return $this->view->fetch();
    }

    function bootstrap()
    {
    	$this->view->assign('pagetitle','bootstrap测试');
    	return $this->view->fetch();
    }

    public function test()
    {
        dump($this->apply_full_global_id_str());
    }
}
