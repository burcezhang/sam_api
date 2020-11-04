<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class OpermanagerController extends CommonController {
	public function index(){
		$userinfo = cookie('userinfo');
		$db_prefix  = C('DB_PREFIX');
		$M = new \Think\Model();
		
		$psize  = 20;
		$currpage = (int)I('get.p');
		if(!$currpage) $currpage=1;
		
		$aged_name = I('get.agedname');
		
		$inow = strtotime('-2hours',getCurrTime());		
		// and cmd_name!='DEV_PWR_OFF_RPT'
		$strsql = "select aged.elderly_name,aged.device_sn,pd.alert_dt from ".$db_prefix."elderly_info as aged ";
		$strsql.= "left join (select device_sn,max(dt) as alert_dt from ".$db_prefix."protocol_data where dt>$inow group by device_sn) as pd on aged.device_sn=pd.device_sn ";
		$strsql.= "where aged.elderly_name like '%$aged_name%'";
		if((int)$userinfo['loc_id']>1){
			$strsql.=" and use_area_id=".(int)$userinfo['loc_id'];
		}
		$sorder = "alert_dt desc,device_sn desc";
		
		$count_total = $M->table('('.$strsql.') tb0')->count();
		$online_total = $M->table('(select * from ('.$strsql.') tb1 where alert_dt>0) tb0')->count();
		$tpagetotal = ceil($count_total/$psize);
		
		$datainfo = $M->table('('.$strsql.') tb0')->order($sorder)->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
		
		$model = M('Protocol_data');
		foreach($datainfo as $k=>$v){
			if($v['alert_dt']){
				$datainfo[$k]['cmd_name']=$model->where("device_sn='".$v['device_sn']."' and cmd_name='DEV_PWR_OFF_RPT' and dt='".$v['alert_dt']."'")->getField('cmd_name');
			}else{
				$datainfo[$k]['cmd_name']='';
			}
		}
		
		$this->assign('aged_name',$aged_name);				
		$this->assign('count_total',(int)$count_total);
		$this->assign('online_total',(int)$online_total);
		$this->assign('currpage',(int)$currpage);
		$this->assign('tpagetotal',(int)$tpagetotal);
		$this->assign('datainfo',$datainfo);
		$this->assign('device_sn',$datainfo[0][device_sn]);

		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		if($varinfo){
			$varinfo['value1']=unserialize($varinfo['value1']);
			$varinfo['value2']=unserialize($varinfo['value2']);
			$varinfo['value3']=unserialize($varinfo['value3']);
			$varinfo['default_map']=$varinfo['value1']['default_map'];
			$varinfo['service_price']=$varinfo['value1']['service_price'];
			$varinfo['device_set1']=$varinfo['value1']['device_set1'];
			$varinfo['device_set2']=$varinfo['value1']['device_set2'];
			$varinfo['device_set3']=$varinfo['value1']['device_set3'];
			$varinfo['device_set4']=$varinfo['value1']['device_set4'];
			$varinfo['device_set7']=$varinfo['value1']['device_set7'];
			$varinfo['device_set8']=$varinfo['value1']['device_set8'];
			$varinfo['device_set9']=$varinfo['value1']['device_set9'];
			$varinfo['device_set10']=$varinfo['value1']['device_set10'];
			$varinfo['device_set14']=$varinfo['value1']['device_set14'];
			$varinfo['device_set15']=$varinfo['value1']['device_set15'];

			if ( $varinfo['default_map']=='baidu' )
				$default_map_dj = '1';
			else if ( $varinfo['default_map']=='google' )
				$default_map_dj = '2';
			else
				$default_map_dj = '9';

			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		$this->assign('default_map_dj',$default_map_dj);

		$this->display();
	}
	public function get_last_trace(){
		$device_sn = I('post.devicesn');
		$returnData['agedid']=0;
		$returnData['gps']=0;
		$returnData['alt_dt']='0';
		$returnData['gps_dt']='0';
		
		if($device_sn){
			$datainfo=M('Elderly_info')->field("id,elderly_code,elderly_name,nick_name,photo_src,type,sex,barthday,home_tel,phone,sim,monitor_id,address,device_sn")->where("device_sn='$device_sn'")->find();
			$returnData['agedid']=1;
			$returnData['elderly_info']['elderly_name']=$datainfo['elderly_name'];
			$returnData['elderly_info']['nick_name']=$datainfo['nick_name'];
			$returnData['elderly_info']['photo_src']=$datainfo['photo_src'];
			$returnData['elderly_info']['type']=(int)$datainfo['type'];
			$returnData['elderly_info']['sex']=(int)$datainfo['sex'];
			$returnData['elderly_info']['age']=(int)date('Y')-(int)date('Y',$datainfo['barthday']);
			$returnData['elderly_info']['monitor_tel']=$datainfo['home_tel'];
			$returnData['elderly_info']['address']=$datainfo['address'];
			$returnData['elderly_info']['ESN']=$datainfo['device_sn'];

			$protocol_info = M('Protocol_data')->where("payload like 'a%LOC_INFO%' and device_sn='".$device_sn."'")->order("dt desc")->find();
			$dev_info=M('Protocol_data')->where("cmd_name='DEV_INFO_RPT' and device_sn='".$device_sn."'")->order("id desc")->find();			
			$dev_info=unserialize($dev_info['payload']);
			if($protocol_info){
				$protocol_data=unserialize($protocol_info['payload']);
				$returnData['gps']=1;

				$returnData['protocol_data']['LOC_INFO']['TIME_STAMP']=toDate((int)$protocol_data['LOC_INFO']['TIME_STAMP']);
                            $returnData['protocol_data']['LOC_INFO']['LATITUDE']=(float)$protocol_data['LOC_INFO']['LATITUDE'];
                            $returnData['protocol_data']['LOC_INFO']['LONGITUDE']=(float)$protocol_data['LOC_INFO']['LONGITUDE'];
                            $returnData['protocol_data']['LOC_INFO']['FIX_TYPE']=$protocol_data['LOC_INFO']['FIX_TYPE'];
			    $returnData['protocol_data']['LOC_INFO']['TEMPERATURE']=$protocol_data['LOC_INFO']['TEMPERATURE'];

				$returnData['protocol_data']['STEP']=(int)$dev_info['STEP_COUNT'];
				$returnData['protocol_data']['BATT']=(int)$dev_info['BATT'];
				
				$returnData['alt_dt']=toDate((int)$protocol_info['dt']);
				$returnData['gps_dt']=toDate((int)$protocol_data['LOC_INFO']['TIME_STAMP']);
			}
		}
		$this->ajaxReturn($returnData,'JSON');
	}
	public function showOperator(){
        //C('PAGE_TRACE_SHOW',true);
		$operator_id = I('get.operator_id');
		$strwhere="service_no='".$operator_id."'";
		$userinfo = cookie('userinfo');
		if((int)$userinfo['loc_id']>1){
			$strwhere.=" and loc_id=".(int)$userinfo['loc_id'];
		}
		$model = M('User_mas');
		$operator_id_check = $model->where($strwhere)->order('service_status_flg desc,service_heart_dt desc')->getField('id');
		//echo $model->getLastSql();
		$this->assign('operator_id',(int)$operator_id_check);
		$this->display();
	}
	//获取报警列表
	public function getAlertList(){
		$userinfo = cookie('userinfo');		
		//$operator_id = M('User_mas')->where("service_no='".I('post.operator_id')."'")->order('serivce_status_flg desc,service_heart_dt desc')->getField('id');
		$operator_id = (int)I('post.operator_id');
		//获取当前客服分配到的任务
		$db_prefix  = C('DB_PREFIX');
		$M = new \Think\Model();
		$alertlist = $M->table($db_prefix."alert_list alert_list")->field("alert_list.id,alert_list.alert_type,alert_list.alert_dt,elderly_info.elderly_name,elderly_info.device_sn")->join($db_prefix.'elderly_info elderly_info ON alert_list.device_sn=elderly_info.device_sn')->where("alert_list.operator=".$operator_id." and alert_list.status_flg in('W','P')")->select();
		//var_dump($M->getLastSql());
		foreach($alertlist as $k=>$v){
			switch($v['alert_type']){
				case 'EMERGENCY_CALL':				
					$alertlist[$k]['alert_level']=1;
					break;
				case 'EMERGENCY_ALERT_RPT':
				case 'GEOFENCE_ALERT_RPT':
				case 'SHOCK_ALERT_RPT':
					$alertlist[$k]['alert_level']=2;
					break;
				case 'REMIND_ALERT':
					$alertlist[$k]['alert_level']=4;
					break;
				default:
					$alertlist[$k]['alert_level']=3;
					break;
			}
			$alertlist[$k]['alert_date']=toDate($alertlist[$k]['alert_dt'],'Ymd/H:i');
		}
		$this->ajaxReturn($alertlist,'JSON');
	}
	public function getCallList(){
		$operator_id = M('User_mas')->where("id=".I('post.operator_id')."")->getField('service_no');
		//$operator_id = explode('@',I('param.operator_id'));
		$device_sn = explode(',',I('param.device_sn'));
		//获取当前客服分配到的任务
		$db_prefix  = C('DB_PREFIX');
		$M = new \Think\Model();
		$alertlist = $M->table($db_prefix."call_list call_list")->field("call_list.QueueTime as alert_dt,elderly_info.elderly_name,elderly_info.device_sn")->join($db_prefix.'elderly_info elderly_info ON call_list.CallNo=elderly_info.sim')->where("call_list.Agent like '".$operator_id."' and call_list.CallState in('Ringing') and call_list.Ring>'".toDate(strtotime('-3 minute',getCurrTime()),'Y-m-d H:i:s')."' and elderly_info.device_sn not in ('".implode("','",$device_sn)."') and call_list.CallSheetID not in (select voice_src from ".$db_prefix."alert_list)")->select();
		foreach($alertlist as $k=>$v){
			$alertlist[$k]['alert_date']=toDate(toTime($alertlist[$k]['alert_dt']),'Ymd/H:i');
			$alertlist[$k]['alert_level']=0;
		}
		if(I('param.echo')=='yes')echo ($M->getLastSql());
		$this->ajaxReturn($alertlist,'JSON');
	}
	//获取单个报警的详细信息
	public function getAlertOne(){
		//$userinfo = cookie('userinfo');
		//$operator_id = M('User_mas')->where("service_no='".I('post.operator_id')."'")->getField('id');
		$operator_id = (int)I('post.operator_id');
		$alertid=(int)I('post.alertid');
		if($alertid){
			$swhere="id=".$alertid;
		}else{
			$swhere="operator=".$operator_id." and status_flg in('P') and alert_type like '%'";
			$db_prefix  = C('DB_PREFIX');
			$swhere.=" and device_sn in (select device_sn from ".$db_prefix."elderly_info where status_flg='Y')";
		}
		$oData = M('Alert_list')->where($swhere)->order('status_flg asc, id asc')->find();
		//var_dump($swhere);
		if($oData){
			switch($oData['alert_type']){
				case 'EMERGENCY_CALL':				
					$oData['alert_level']=1;
					break;
				case 'EMERGENCY_ALERT_RPT':
				case 'GEOFENCE_ALERT_RPT':
				case 'SHOCK_ALERT_RPT':
					$oData['alert_level']=2;
					break;
				case 'REMIND_ALERT':
					$oData['alert_level']=4;
					break;
				default:
					$oData['alert_level']=3;
					break;
			}
			$oData['alert_date']=toDate($oData['alert_dt'],'Ymd/H:i:s');
			$oData['alert_type2']=getAlertType($oData['alert_type']);
			$oData['elderly_info']=M('Elderly_info')->field("id,elderly_code,elderly_name,nick_name,photo_src,type,sex,barthday,home_tel,phone,sim,monitor_id,address,monitor_info")->where("device_sn='".$oData['device_sn']."'")->find();
			$oData['protocol_data']=M('Protocol_data')->where("cmd_name='".($oData['alert_type']=='EMERGENCY_CALL'?'POSITION_TRACKING_ALERT_RPT':$oData['alert_type'])."' and device_sn='".$oData['device_sn']."'")->order("id desc")->getField('payload');
			//$oData['monitor_tel']=M('User_mas')->where("id=".$oData['elderly_info']['monitor_id'])->getField('phone');
			
			//$elderly_id = (int)$oData['elderly_info']['id'];
			//$monitor_id_list = M('Elderly_monitor')->where("elderly_id=".$elderly_id)->getField('user_id',true);
			//$oData['monitor_list']=M('User_mas')->field('true_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$monitor_id_list).")")->select();
			$oData['monitor_list']=unserialize($oData['elderly_info']['monitor_info']);
			
			if($oData['elderly_info']){
				$oData['elderly_info']['age']=(int)date("Y",getCurrTime()) - (int)date("Y",(int)$oData['elderly_info']['barthday']);
			}else{
				$oData['elderly_info']['id']=0;
				$oData['elderly_info']['elderly_code']='0';
				$oData['elderly_info']['elderly_name']='0';
				$oData['elderly_info']['nick_name']='0';
				$oData['elderly_info']['photo_src']='0';
				$oData['elderly_info']['type']=0;
				$oData['elderly_info']['sex']=0;
				$oData['elderly_info']['barthday']='0';
				$oData['elderly_info']['age']='0';
				$oData['elderly_info']['home_tel']='0';
				$oData['elderly_info']['phone']='0';
				$oData['elderly_info']['monitor_id']='0';
				$oData['elderly_info']['address']='Device SN:'.$oData['device_sn'];
				$oData['monitor_list']='0';
				$oData['monitor_list']=array();
			}
			if($oData['protocol_data']){
				$oData['protocol_data']=unserialize($oData['protocol_data']);				
			}
			$oData['protocol_data']['STEP']=(int)$oData['protocol_data']['STEP_COUNT'];
			$oData['protocol_data']['LOC_INFO']['TEMPERATURE']=(int)$oData['protocol_data']['LOC_INFO']['TEMPERATURE'];
			$oData['protocol_data']['LOC_INFO']['LONGITUDE']=(float)$oData['protocol_data']['LOC_INFO']['LONGITUDE'];
			$oData['protocol_data']['LOC_INFO']['LATITUDE']=(float)$oData['protocol_data']['LOC_INFO']['LATITUDE'];
			$oData['protocol_data']['LOC_INFO']['FIX_TYPE']=(float)$oData['protocol_data']['LOC_INFO']['FIX_TYPE'];		
			
		}
		//var_dump($oData);
		$this->ajaxReturn($oData,'JSON');
	}
	//获取短信模板
	public function getSmsTemplate(){
		$oData=M('Sms_template')->where("status_flg='Y' and sms_type='operator_finish'")->select();
		//var_dump($oData);
		$this->ajaxReturn($oData,'JSON');
	}
	//设置客服已操作步骤
	public function setCallStep(){
		$oData = null;		
		$this->ajaxReturn($oData,'JSON');
	}
	
	public function datalist(){
		
		$model = M('Alert_list');
		$strwhere="status_flg in('A','W','P')";
		
		$userinfo = cookie('userinfo');
		$db_prefix = C('DB_PREFIX');
		if((int)$userinfo['loc_id']>1){
			$strwhere.=" and device_sn in (select device_sn from ".$db_prefix."elderly_info where use_area_id=".(int)$userinfo['loc_id'].")";
		}		
		
		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE')); 
		$show       = $Page->show();
		
		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			if((int)$v['operator']>0){
				$datalist[$k]['operator_name']=M('User_mas')->where("id=".$v['operator'])->getField('true_name');
			}else{
				$datalist[$k]['operator_name']='';
			}
		}
		
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);
		
		//var_dump($datalist);
		
		$this->display();
	}
	
	public function historyList(){
		$model = M('Alert_list');
		$strwhere="status_flg in('C','F')";

		$id=I('get.id');

		$userinfo = cookie('userinfo');
		$db_prefix = C('DB_PREFIX');
		if(((int)$userinfo['loc_id']>1) && ($id=='')){
			$strwhere.=" and device_sn in (select device_sn from ".$db_prefix."elderly_info where use_area_id=".(int)$userinfo['loc_id'].")";
		}
		if($id != ''){	
			$strwhere.=" and device_sn='$id' ";
		}
		
		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE')); 
		$show       = $Page->show();
		
		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			if((int)$v['operator']>0){
				$datalist[$k]['operator_name']=M('User_mas')->where("id=".$v['operator'])->getField('true_name');
			}else{
				$datalist[$k]['operator_name']='';
			}
		}
		
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);
		
		//var_dump($datalist);
		
		$this->display();
	}

	
}
