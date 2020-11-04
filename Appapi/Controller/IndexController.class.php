<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class IndexController extends AppapiController {
    public function index(){
        
		//$Data = M('Device_mas');
        //$arrData = $Data->find();
		//$this->show(json_encode($arrData),'utf-8');
		if(I('get.showlog')=='misone'){
			$pagesize = ((int)I('get.pagesize'))==0?50:((int)I('get.pagesize'));
			$model = M('Zdemo');
			$datalist = $model->where("remark like '%Appapi%'")->limit($pagesize)->order("id desc")->select();
			foreach($datalist as $k=>$v){
				unset($dataarray);
				unset($url);
				$dataarray = unserialize($v['remark']);
				/*
				$url = C('WEB_DOMAIN').$dataarray['MODULE_NAME'].'/'.$dataarray['CONTROLLER_NAME'].C('URL_PATHINFO_DEPR').$dataarray['ACTION_NAME'].'.'.C('URL_HTML_SUFFIX').'?';
				foreach($dataarray['get'] as $k2=>$v2){
					$url .= '&'.$k2.'='.$v2;
				}
				*/
				$url = $dataarray['URL'];
				echo '['.$dataarray['time'].'] '.$url.' <a href="'.$url.'" target="_blank">[OPEN URL]</a><br/><hr /><br />';
			}
		}else{
			$this->show('Hello Anycare APP','utf-8');
		}
	}
}