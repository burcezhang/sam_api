<?php
namespace Common\Controller;

use Think\Controller;
use Think\Model;
use Think;

class CommonController extends Controller {

    function _initialize() {        
		header("Content-type: text/html; charset=utf-8");
		$itimie=$this->ajaxtime();
		$lang = I('get.l');
		if($lang){
			//F('Language',$lang);
			cookie('Language',$lang,3600*24*30);
			$any_lang = $lang;
		}else{
			//$any_lang = F('Language');
			$any_lang = cookie('Language');
		}
		if(!$any_lang){
			$any_lang = 'zh_CN';
			//F('Language',$any_lang);
			cookie('Language',$lang,3600*24*30);
		}
		$this->assign('Language',$any_lang);
		// 读取公共语言包
		$lang_common_file = LANG_PATH.$any_lang.'/'.MODULE_NAME.'/'.'common.php';
		//var_dump($lang_common_file);
        if (is_file($lang_common_file))
            L(include $lang_common_file);
		// 读取当前模块语言包
		$lang_file = LANG_PATH.$any_lang.'/'.MODULE_NAME.'/'.CONTROLLER_NAME.'/'.ACTION_NAME.'.php';
		//var_dump($lang_file);
        if (is_file($lang_file))
            L(include $lang_file);
			
		//检查登陆及权限
		$userinfo = cookie('userinfo');
		if( strtoupper(CONTROLLER_NAME) == 'INDEX' || strtoupper(CONTROLLER_NAME) == 'WELCOME' || strtoupper(CONTROLLER_NAME) == 'PUBLIC' ){
			//$ii2 = '54F1E600'; //web center test
			//if($itimie>base_convert($ii2,16,10)){exit;}
		}else{
			
			if(!(int)$userinfo['id']){
				$this->redirect('Index/index','', 1, L('common_alert_login_timeout').'......');
				exit;
			}else{	
				if(strtoupper($userinfo['user_type'])=='ADMINS'){
					//true;
				}else if('OPERATOR'==strtoupper(CONTROLLER_NAME) && strtoupper($userinfo['is_service'])=='Y'){
					//true;
				}else if(strtoupper($userinfo['user_type'])==strtoupper(CONTROLLER_NAME) && strtoupper(CONTROLLER_NAME)=='WEBUSER'){
					//true;
				}else if(stripos(',,,'.strtoupper($userinfo['role']).',',','.strtoupper(CONTROLLER_NAME))>0){
					//true;
				}else if(stripos(',,,'.strtoupper($userinfo['role']).',','NEWS')>0){
					if((strtoupper(CONTROLLER_NAME)=='SCHEDULE') || (strtoupper(CONTROLLER_NAME)=='MEAL')){
						//true;
					}else{
						//false;
						$this->redirect('Welcome/index','', 1, L('common_alert_access').'......');
						exit;
					}
				}else if(strtoupper($userinfo['user_type'])=='WEBUSER'){
					if((strtoupper(CONTROLLER_NAME)=='CUSTOMERMANAGER') || (strtoupper(CONTROLLER_NAME)=='WEBUSER') || (strtoupper(CONTROLLER_NAME)=='ADMIN')){
						//true;
					}else{
						//false;
						$this->redirect('Welcome/index','', 1, L('common_alert_access').'......');
						exit;
					}
				}else if(strtoupper($userinfo['user_type'])=='WORKMAN'){
					if((strtoupper(CONTROLLER_NAME)=='OPERATOR') || (strtoupper(CONTROLLER_NAME)=='ADMIN')){
						//true;
					}else if(strtoupper(CONTROLLER_NAME)=='LOGIN'){
						echo("Someone Logged in using same operator account ");
						exit;
					}else{
						//false;
						$this->redirect('Welcome/index','', 1, L('common_alert_access').'......');
						exit;
					}						
				}else{
						//false;
						$this->redirect('Welcome/index','', 1, L('common_alert_access').'......');
						exit;
				}
			}
		}
		$userid = (int)$userinfo['id'];
		$userstate = (int)$userinfo['service_status_flg'];
		$this->assign('userid',$userid);
		$this->assign('userstate',$userstate);
		$this->assign('userinfo',$userinfo);
    }

    

}
