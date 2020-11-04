<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;
include('ThinkPHP/Library/Plugins/Menu.class.php');

class WelcomeController extends CommonController {
    public function index(){

		$userinfo = cookie('userinfo');
		$any_lang = cookie('Language');
		$mu=\Menu::get($any_lang);
		$menu=$mu->getMenu($userinfo);
		$a = array_keys($menu);
		$id=$a[0];
		$this->assign("id",$id);
		$this->assign("menu",$menu);

		$this->assign("menu_name",$menu[$id]);
		$num = count($menu);
		$this->assign("menu_count", $num);

		$submenu=$mu->getSubMenu($id,$userinfo);
		$a = array_keys($submenu);
		$this->assign("subid",$a[0]);
		$a = array_values($submenu);
		$this->assign("subtitle",$a[0]);
		$this->assign("submenu",$submenu);
		$this->assign("userinfo",$userinfo);
//
//		//autoPassAlert(); //系统自动处理相应告警
		$model = M('Sys_var');
		$audio_set_run = $model->where("var_group='audio_set' and var_name='runing'")->getField('value1');
		if($audio_set_run){
			$audio_set_run = unserialize($audio_set_run);
			$player['login']=$audio_set_run['login']['kfile'];
			$this->assign("player",$player);
		}

		$this->display();
	}
    public function subMenu(){
		$any_lang = cookie('Language');
		$userinfo = cookie('userinfo');
		$mu=\Menu::get($any_lang);
		//$admin=Cache::get()->RW(OPCode::$YUN_SHOP_ID.'/db_admin_group.php');
		//$admin=$admin[OPCode::$USINFO['admin']];
		//$mu=Menu::getInstance();
		$menu=$mu->getSubMenu(I('post.id'),$userinfo);
		echo json_encode($menu);
	}


}