<?php
namespace Common\Controller;

use Think\Controller;
use Think\Model;
use Think;
 
class AppapiController extends Controller {

    function _initialize() { 
	
		header("Content-type: text/html; charset=utf-8");
		$itimie=$this->ajaxtime();
		
		$sDemoArray['time']=toDate(getCurrTime());
		$sDemoArray['SYS']='Appapi';
		$sDemoArray['MODULE_NAME']=MODULE_NAME;
		$sDemoArray['CONTROLLER_NAME']=CONTROLLER_NAME;
		$sDemoArray['ACTION_NAME']=ACTION_NAME;
		$sDemoArray['URL']='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$sDemoArray['GET']=$_GET;
		$sDemoArray['POST']=$_POST;
		$sDemo['dt']=getCurrTime();
		$sDemo['remark']=serialize($sDemoArray);
		M('Zdemo')->data($sDemo)->add();
		
		//$ii1 = '54ECA000'; //appapi test
		//if($itimie>base_convert($ii1,16,10)){exit;}
    }

    

}