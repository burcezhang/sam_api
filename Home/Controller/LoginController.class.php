<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class LoginController extends CommonController {
    public function index(){
        $this->redirect('Index/index','', 1, 'Loading......');
	}

	
}