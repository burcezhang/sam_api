<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class OperatorController extends CommonController {
    public function index(){
        $model = M('Sys_var');
		$audio_set_run = $model->where("var_group='audio_set' and var_name='runing'")->getField('value1');
		if($audio_set_run){
			$audio_set_run = unserialize($audio_set_run);
			$player['ringout']=$audio_set_run['ringout']['kfile'];
			$player['sms']=$audio_set_run['sms']['kfile'];
			$this->assign("player",$player);
		}

                $default_map_dj = '0';
                $varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
                if($varinfo){
                        $varinfo['value1']=unserialize($varinfo['value1']);
                        $varinfo['value2']=unserialize($varinfo['value2']);
                        $varinfo['value3']=unserialize($varinfo['value3']);
                        $varinfo['default_map']=$varinfo['value1']['default_map'];

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
	//获取报警列表
	public function getAlertList(){
		$userinfo = cookie('userinfo');
		$data['id']=$userinfo['id'];
		$data['service_heart_dt']=time();
		if((int)I('post.userstate')){}else{
			$data['service_status_flg']='1';
		}
		M('User_mas')->data($data)->save();
		
		autoPassAlert(); //系统自动处理相应告警
		
		//unset($data);
		//超过5分钟没有心跳的客服强制下线
		//$now_time = toDate(getCurrTime());
		//$now_time = strtotime("$now_time -5 minute");
		//$data['service_heart_dt']=0;
		//$data['service_status_flg']='0';
		//M('User_mas')->data($data)->where("service_status_flg='1' and service_heart_dt>0 and service_heart_dt<".$now_time)->save();
		unset($data);
		$model = M('Alert_list');
		$db_prefix  = C('DB_PREFIX');
				
		//有客服座席时分配任务
		unset($active_alert_list);
		$tCurrtime = getCurrTime();
		$active_alert_list = $model->where("status_flg='A' and alert_dt<=$tCurrtime and device_sn in (select device_sn from ".$db_prefix."elderly_info where use_area_id=".(int)$userinfo['loc_id'].")")->order("id asc")->select();
		if($active_alert_list){
			$user_online = M('User_mas')->where("is_service='Y' and service_no!='' and service_status_flg='1' and service_heart_dt>0 and loc_id=".(int)$userinfo['loc_id'])->select();
			if($user_online){
				unset($user_ids);
				foreach($user_online as $row){
					$user_ids[$row['id']]=$row['id'];
				}
				foreach($active_alert_list as $row_active){
					$strsql = "select operator,count(id) as cnt from __TABLE__ where operator in(".implode(',',$user_ids).") and status_flg in('W','P') and alert_type='".$row_active['alert_type']."' group by operator order by cnt asc";
					$operator_list = $model->query($strsql);
					unset($user_ids2);
					foreach($user_online as $row){
						$user_ids2[$row['id']]=1;
					}
					foreach($operator_list as $row){
						$user_ids2[$row['id']]=(int)$row['cnt']+1;
					}
					asort($user_ids2);
					foreach($user_ids2 as $k=>$v){
						$data['id']=$row_active['id'];
						$data['operator']=$k;
						$data['status_flg']='W';
						$model->data($data)->save();
						break;
					}
					//var_dump($user_ids2[0]);exit;					
				}
				
			}
		}
		//获取当前客服分配到的任务
		$db_prefix  = C('DB_PREFIX');
		$M = new \Think\Model();
		$alertlist = $M->table($db_prefix."alert_list alert_list")->field("alert_list.id,alert_list.alert_type,alert_list.alert_dt,elderly_info.elderly_name,elderly_info.device_sn")->join($db_prefix.'elderly_info elderly_info ON alert_list.device_sn=elderly_info.device_sn')->where("alert_list.operator=".$userinfo['id']." and alert_list.status_flg in('W','P')")->select();
		//var_dump($M->getLastSql());
		foreach($alertlist as $k=>$v){
			switch($v['alert_type']){
				case 'EMERGENCY_CALL'://紧急来电				
					$alertlist[$k]['alert_level']=1;
					break;
				case 'EMERGENCY_ALERT_RPT'://紧急告警
				case 'GEOFENCE_ALERT_RPT': //围栏
				case 'SHOCK_ALERT_RPT'://跌倒告警
					$alertlist[$k]['alert_level']=2;
					break;
				case 'REMIND_ALERT'://预约
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
		$userinfo = cookie('userinfo');
		$operator_id = explode('@',$userinfo['service_no']);
		$device_sn = explode(',',I('post.device_sn'));
		//获取当前客服分配到的任务
		$db_prefix  = C('DB_PREFIX');
		$M = new \Think\Model();
		$alertlist = $M->table($db_prefix."call_list call_list")->field("call_list.QueueTime as alert_dt,elderly_info.elderly_name,elderly_info.device_sn")->join($db_prefix.'elderly_info elderly_info ON call_list.CallNo=elderly_info.sim')->where("call_list.Agent like '".$operator_id[0]."' and call_list.CallState in('Ringing') and call_list.Ring>'".toDate(strtotime('-3 minute',getCurrTime()),'Y-m-d H:i:s')."' and elderly_info.device_sn not in ('".implode("','",$device_sn)."') and call_list.CallSheetID not in (select voice_src from ".$db_prefix."alert_list)")->select();
		foreach($alertlist as $k=>$v){
			$alertlist[$k]['alert_date']=toDate(toTime($alertlist[$k]['alert_dt']),'Ymd/H:i');
			$alertlist[$k]['alert_level']=0;
		}
		if(I('param.echo')=='yes')echo ($M->getLastSql());
		$this->ajaxReturn($alertlist,'JSON');
	}
	//获取单个报警的详细信息
	public function getAlertOne(){
		
		autoPassAlert(); //系统自动处理相应告警
		
		$userinfo = cookie('userinfo');
		$alertid=(int)I('post.alertid');
		if($alertid){
			$swhere="id=".$alertid;
		}else{
			$swhere="operator=".$userinfo['id']." and status_flg in('W','P') and alert_type like '%'";
			$db_prefix  = C('DB_PREFIX');
			$swhere.=" and device_sn in (select device_sn from ".$db_prefix."elderly_info where status_flg='Y')";
		}		
		
		$oData = M('Alert_list')->where($swhere)->order('id asc')->find();
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
			$oData['protocol_data']=M('Protocol_data')->where("payload like 'a%LOC_INFO%' and device_sn='".$oData['device_sn']."'")->order("dt desc")->getField('payload');
			$oData['dev_info']=M('Protocol_data')->where("cmd_name='DEV_INFO_RPT' and device_sn='".$oData['device_sn']."'")->order("dt desc")->getField('payload');
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
				$oData['protocol_data']=unserialize($oData['protocol_data'].payload);				
			}
			if($oData['dev_info']){
				$oData['dev_info']=unserialize($oData['dev_info']);
			}
			$oData['protocol_data']['STEP']=(int)$oData['dev_info']['STEP_COUNT'];
			$oData['protocol_data']['BATT']=(int)$oData['dev_info']['BATT'];

			$oData['protocol_data']['LOC_INFO']['LONGITUDE']=(float)$oData['protocol_data']['LOC_INFO']['LONGITUDE'];
			$oData['protocol_data']['LOC_INFO']['LATITUDE']=(float)$oData['protocol_data']['LOC_INFO']['LATITUDE'];  			
			$oData['protocol_data']['LOC_INFO']['FIX_TYPE']=(float)$oData['protocol_data']['LOC_INFO']['FIX_TYPE'];

			$data['id']=(int)$oData['id'];
			$data['start_time']=getCurrTime();
			$data['status_flg']='P';
			M('Alert_list')->data($data)->save();
			
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
		$alertid=(int)I('post.alertid');
		$step   =(int)I('post.step');
		if($alertid===0 || $step===0){
			$oData = null;
		}else{
			$data['id']=$alertid;
			$data['step']=$step;
			cookie('alertcallout',$data); //给后面的Call使用
			//if($step==1){$data['start_time']=getCurrTime();}
			if($step>=5){
				$data['status_flg']='F';
				$data['end_time']=getCurrTime();
			}else{
				$data['status_flg']='P';
			}
			$oData = M('Alert_list')->data($data)->save();
			
			//send sms
			if( $step>=5 && (int)I('post.smsid')>0 ){
				$db_prefix  = C('DB_PREFIX');
				$sms_template_id=(int)I('post.smsid');
				$sms_template = M('Sms_template')->where("id=".$sms_template_id)->getField('content');
				
				$elderly_info = M('Elderly_info')->where("device_sn in (select device_sn from ".$db_prefix."alert_list where id=$alertid)")->find();
				//$elderly_monitor = M('elderly_monitor')->where("elderly_id=".$elderly_info['id'])->getField('user_id',true);
				$monitor_phone = M('User_mas')->where("id in (".implode(',',$elderly_monitor).")")->getField('phone',true);
				
				$monitor_info = unserialize($elderly_info['monitor_info']);
				for($imonitor=0;$imonitor<count($monitor_info);$imonitor++){
					if($monitor_info[$imonitor]['phone']) $monitor_phone[] = $monitor_info[$imonitor]['phone'];
				}
				
				$sms_template = str_replace('***',$elderly_info['elderly_name'],$sms_template);
				$sms = false;

				$alert_type = M('Alert_list')->where("id=$alertid")->getField('alert_type');
				$device_sn = M('Alert_list')->where("id=$alertid")->getField('device_sn');
				switch($alert_type){
					case 'SHOCK_ALERT_RPT':
					case 'EMERGENCY_CALL': //紧急来电
					case 'SNOOZE_ALERT_RPT':
					$sms=true;
					break;
					default:
					$sms=false;
				}
				
				$pushData['topic']=$alert_type;
				$pushData['target']=$device_sn;
				$pushData['body'] =$sms_template;
				$pushData['act_type']='operator_finish';
				$pushData['act_obj']=$alertid;
			echo "push 전";
		        $elderAddress = M('Elderly_info')->where("device_sn=".$oData['device_sn'])->getField('phone');
		        sendCallPush($elderAddress,'20170227_01');
			echo "jcall 후";
				pushMessage($pushData);
				if($sms){
					$smsData['phone']=$monitor_phone;
					$smsData['body'] =$sms_template;
					$smsData['act_type']='operator_finish';
					$smsData['act_obj']=$alertid;
					sendSMS($smsData);
				}
			}
		}
		$this->ajaxReturn($oData,'JSON');
	}

	//获取监护人列表
	public function getGuardianList(){
		$elderly_id = (int)I('post.elderly_id');
		$monitor_list = M('Elderly_monitor')->where("elderly_id=".$elderly_id)->getField('user_id',true);
		$oData=M('User_mas')->field('true_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$monitor_list).")")->select();
		//var_dump($oData);
		$this->ajaxReturn($oData,'JSON');
	}
	
	public function datalist(){
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
	
	public function aged_show(){	
		$userinfo = cookie('userinfo');
		$this->assign('disease',explode(',',C('DEVICE_INFO_DISEASE')));
		/*
		$this->assign('timespc',explode(',',C('DEVICE_INFO_TIMESPC')));
		$this->assign('tempspc',explode(',',C('DEVICE_INFO_TEMPSPC')));
		$this->assign('traceday',C('DEVICE_INFO_TRACEDAY'));
		$this->assign('cycspc',explode(',',C('DEVICE_INFO_CYCSPC')));
		*/
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
			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		
		
		
		$id = (int)I('get.id');
		
		$model = M('Elderly_info');
		$agedinfo = $model->find($id);
		$agedinfo['disease2']=explode('-',$agedinfo['disease']);
		$agedinfo['disease']=explode(',',$agedinfo['disease']);
		$agedinfo['address']=explode(',',$agedinfo['address']);
		if(count($agedinfo['address'])==1){
			$agedinfo['address'][3]=$agedinfo['address'][0];
			$agedinfo['address'][2]='';
			$agedinfo['address'][1]='';
			$agedinfo['address'][0]='';
		}
		$agedinfo['use_area_str']=explode(',',$agedinfo['use_area_str']);
		$agedinfo['monitor_info']=unserialize($agedinfo['monitor_info']);
		$agedinfo['device_set1']=unserialize($agedinfo['device_set1']);
		$agedinfo['device_set2']=unserialize($agedinfo['device_set2']);
		$agedinfo['device_set3']=unserialize($agedinfo['device_set3']);
		$agedinfo['device_set4']=unserialize($agedinfo['device_set4']);
		$agedinfo['device_set5']=unserialize($agedinfo['device_set5']);
		$agedinfo['device_set6']=unserialize($agedinfo['device_set6']);
		$agedinfo['device_set7']=unserialize($agedinfo['device_set7']);
		$agedinfo['device_set8']=unserialize($agedinfo['device_set8']);
		$agedinfo['device_set9']=unserialize($agedinfo['device_set9']);
		$agedinfo['device_set10']=unserialize($agedinfo['device_set10']);
		$agedinfo['device_set11']=unserialize($agedinfo['device_set11']);
		$agedinfo['device_set12']=unserialize($agedinfo['device_set12']);
		$agedinfo['device_set13']=unserialize($agedinfo['device_set13']);
		$agedinfo['device_set14']=unserialize($agedinfo['device_set14']);
		$agedinfo['device_set15']=unserialize($agedinfo['device_set15']);
		if($agedinfo['beacons']){
			$agedinfo['beacons']=unserialize($agedinfo['beacons']);
			unset($agedinfo['beacons']['FLG']);
			$agedinfo['count'] = count($agedinfo['beacons'])+1;
		}else{
			$agedinfo['beacons'] = array();
			$agedinfo['count'] = 0;
		}
		$this->assign('beacons',$agedinfo['beacons']);
		unset($agedinfo['beacons']);
		$agedinfo['service_period']=date('Y',$agedinfo['termination_date'])-date('Y',$agedinfo['register_date']);
		
		$this->assign('agedinfo',$agedinfo);

		
		//$this->assign('device_auth_code',strtoupper(base_convert(crc32($userinfo['id'].getCurrTime()),10,36)) );
		//if((int)$userinfo['loc_id']>1){
		//	$locwhere = " and loc_id=".(int)$userinfo['loc_id'];
		//}else{
		//	$locwhere = " ";
		//}
		$locwhere = " and id=".(int)$agedinfo['sales_id'];
		$sales_list = M('User_mas')->where("status_flg='Y'".$locwhere)->select();
		$this->assign('sales_list',$sales_list);
		
		//var_dump($agedinfo['device_set7']);
		
		$this->display();
	}

	
}
