<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class WebuserController extends CommonController {
	public function index(){
		$userinfo = cookie('userinfo');
		$aged_list = M('Elderly_monitor')->where("user_id=".$userinfo['id'])->getField('elderly_id',true);
		$datalist=M('Elderly_info')->where("status_flg='Y' and id in(".implode(',',$aged_list).")")->select();
		
		$this->assign('datalist',$datalist);
		
		$this->display();
	}
	
	public function check_auth_code(){
		$sn=I('post.sn');
		$auth_code = M('Elderly_info')->where("monitor_id=0 and device_sn='$sn' and status_flg='Y'")->getField('device_auth_code');
		if($auth_code){
			$oData['error']=0;
			$oData['info']=$auth_code;
		}else{
			$oData['error']=1;
			$oData['info']='Error';
		}
		$this->ajaxReturn($oData,'JSON');		
	}
	
	public function reg(){
		$userinfo = cookie('userinfo');
		$device_sn=I('post.device_sn');
		$device_auth_code=I('post.device_auth_code');
		$model = M('Elderly_info');
		
		$agedinfo = $model->field('id,monitor_id,elderly_name,nick_name')->where("device_auth_code='$device_auth_code' and device_sn='$device_sn' and status_flg='Y'")->find();
		
		$check_double = M('Elderly_monitor')->where("user_id='".$userinfo['id']."' and elderly_id='".$agedinfo['id']."'")->find();
		if($check_double){
			$oData['error']=2;
			$oData['info']='Data Double';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}
		
		if($agedinfo['id'] && $userinfo['id']){
			$model->startTrans();
			$data['user_id']=$userinfo['id'];
			$data['elderly_id']=$agedinfo['id'];
			$auth_code = M('Elderly_monitor')->data($data)->add();				
		}
		if((int)$agedinfo['monitor_id']==0){
			unset($data);
			$data['id']=$agedinfo['id'];
			$data['monitor_id']=$userinfo['id'];
			$auth_code = M('Elderly_info')->data($data)->save();
		}
		
		if($auth_code){
			$model->commit();
			$oData['error']=0;
			$oData['info']='Success';			
			
			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Get Information';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='reg';
			$logData['module']='Webuser';
			$logData['act_obj']=(int)$agedinfo['id'];
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
			
		}else{
			$model->rollback();
			$oData['error']=1;
			$oData['info']='Fail';			
		}
		$this->ajaxReturn($oData,'JSON');		
	}
	
	public function gps(){
        	$userinfo = cookie('userinfo');
		$aged_list = M('Elderly_monitor')->where("user_id=".$userinfo['id'])->getField('elderly_id',true);
		$datalist=M('Elderly_info')->field('elderly_name,nick_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$aged_list).")")->select();
		$this->assign('datalist',$datalist);

		$agedid = (int)I('get.id');

		if($agedid==0) $agedid=$datalist[0][id];
		
		
		$returnData['agedid']=$agedid;
		$returnData['gps']=0;
		if($agedid){
			$datainfo=M('Elderly_info')->field("id,elderly_code,elderly_name,nick_name,photo_src,type,sex,barthday,home_tel,phone,sim,monitor_id,address,device_sn")->where("id='$agedid'")->find();
			$returnData['elderly_info']['elderly_name']=$datainfo['elderly_name'];
			$returnData['elderly_info']['nick_name']=$datainfo['nick_name'];
			$returnData['elderly_info']['photo_src']=$datainfo['photo_src'];
			$returnData['elderly_info']['type']=(int)$datainfo['type'];
			$returnData['elderly_info']['sex']=(int)$datainfo['sex'];
			$returnData['elderly_info']['age']=(int)date('Y')-(int)date('Y',$datainfo['barthday']);
			$returnData['elderly_info']['monitor_tel']=$datainfo['home_tel'];
			$returnData['elderly_info']['address']=$datainfo['address'];
			$returnData['elderly_info']['ESN']=$datainfo['device_sn'];

			$dev_info=M('Protocol_data')->where("cmd_name='DEV_INFO_RPT' and device_sn='".$datainfo['device_sn']."'")->order("dt desc")->find();			
			$dev_info=unserialize($dev_info['payload']);
			$protocol_data = M('Protocol_data')->where("payload like 'a%LOC_INFO%' and device_sn='".$datainfo['device_sn']."'")->order("dt desc")->getField('payload');
			
			if($protocol_data){
				$protocol_data=unserialize($protocol_data);

				$returnData['gps']=1;
				$returnData['protocol_data']['LOC_INFO']['TIME_STAMP']=date('Y-m-d H:i:s',$protocol_data['LOC_INFO']['TIME_STAMP']);
				$returnData['protocol_data']['LOC_INFO']['LATITUDE']=(float)$protocol_data['LOC_INFO']['LATITUDE'];
				$returnData['protocol_data']['LOC_INFO']['LONGITUDE']=(float)$protocol_data['LOC_INFO']['LONGITUDE'];
				$returnData['protocol_data']['LOC_INFO']['FIX_TYPE']=$protocol_data['LOC_INFO']['FIX_TYPE'];
				$returnData['protocol_data']['LOC_INFO']['TEMPERATURE']=$protocol_data['LOC_INFO']['TEMPERATURE'];
				$returnData['protocol_data']['STEP']=(int)$dev_info['STEP_COUNT'];
				$returnData['protocol_data']['BATT']=(int)$dev_info['BATT'];	
	
				//echo($protocol_data['LOC_INFO']['TEMPERATURE']);	
			}
			
			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$datainfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$datainfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Location Fixed';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='gps';
			$logData['module']='Webuser';
			$logData['act_obj']=(int)I('get.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}
		
		$this->assign('agedinfo',json_encode($returnData));

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
	
	public function geo(){
	       $userinfo = cookie('userinfo');
		$aged_list = M('Elderly_monitor')->where("user_id=".$userinfo['id'])->getField('elderly_id',true);
		$datalist=M('Elderly_info')->field('elderly_name,nick_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$aged_list).")")->select();
		$this->assign('datalist',$datalist);
		
		$agedid = (int)I('get.id');

		if($agedid==0) $agedid=$datalist[0][id];
		
		$returnData['agedid']=$agedid;

		$device_sn = M('Elderly_info')->where('id='.$agedid)->getField('device_sn');
		$geolist = M('Geofence')->where("status_flg='Y' and device_sn='$device_sn'")->order('id desc')->select();
		
		$returnData['geo']=count($geolist);
		$returnData['geolist']=$geolist;
		
		$this->assign('currdate',date('Y-m-d'));
		$this->assign('agedinfo',json_encode($returnData));

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
	
	public function geoadd(){
		$userinfo = cookie('userinfo');
		$model = M('Geofence');
		$agedinfo = M('Elderly_info')->field('id,device_sn,elderly_name,nick_name')->where("id=".I('post.agedid')."")->find();
		$device_sn = $agedinfo['device_sn'];
		$fence_ids = $model->where("status_flg='Y' and device_sn='$device_sn'")->getField('geo_no',true);
		//var_dump($fence_ids);exit;
		for($i=1;$i<100;$i++){
			if(!in_array($i,$fence_ids)){
				$data['geo_no']=$i;
				break;
			}
		}
		if($data['geo_no']>10){
			$returnData['error']=2;
			$returnData['info']='Limit';
			$this->ajaxReturn($returnData,'JSON');
		}
		$data['geo_name']=I('post.geo_name');
		$data['area_lat']=(float)I('post.area_lat');
		$data['area_lon']=(float)I('post.area_lon');
		$data['radius']=(int)I('post.radius');
		if($data['radius']<100)$data['radius']=500;
		$data['device_sn']=$device_sn;
		$data['alert_type']=1;
		
		$data['geo_type']="0";
		$data['geo_attr']="1";
		$data['address']="";
		$data['create_date']=getCurrTime();
		$data['sync_flg']="1";
		$data['status_flg']="Y";
		
		$setting_flg = $model->data($data)->add();
		if($setting_flg){
			$returnData['error']=0;
			$returnData['info']='Success';
			
			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Create Geofence';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='geoadd';
			$logData['module']='Webuser';
			$logData['act_obj']=(int)I('post.agedid');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
			
		}else{
			$returnData['error']=1;
			$returnData['info']='Failed';
		}
						
		$this->ajaxReturn($returnData,'JSON');
	}
	
	public function geodelete(){
		$userinfo = cookie('userinfo');
		$agedinfo = M('Elderly_info')->field('id,device_sn,elderly_name,nick_name')->where("id=".I('post.id')."")->find();
		$model = M('Geofence');
		$data['id']=(int)I('post.id');
		$data['sync_flg']="3";
		$data['status_flg']="D";
		
		$setting_flg = $model->data($data)->save();
		if($setting_flg){
			$returnData['error']=0;
			$returnData['info']='Success';
			
			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Delete Geofence';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='geodelete';
			$logData['module']='Webuser';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
			
		}else{
			$returnData['error']=1;
			$returnData['info']='Failed';
		}
						
		$this->ajaxReturn($returnData,'JSON');
	}
	
	public function get_aged_trace(){
		$model = M('Protocol_data');
		$elderly_id = (int)I('post.agedid');
		$curr_date = toTime(I('post.currdate'));
		$date_flg = I('post.dateflg');
		$device_sn = M('Elderly_info')->where('id='.$elderly_id)->getField('device_sn');
		
		if(!$curr_date)$curr_date=getCurrTime();
		if($date_flg)  $curr_date=strtotime($date_flg,$curr_date);
		$end_dt = strtotime('-1 day',$curr_date);

		$datainfo = $model->where("device_sn='$device_sn' and payload like 'a%LOC_INFO%' and dt<=$curr_date and dt>$end_dt")->order('dt desc')->select();
		echo("datainfo: $datainfo");

		if($datainfo){
			$returnData['error']=0;
			//$returnData['trace']=count($datainfo);
			$returnData['reason']='Success';
			$returnData['currdate']=toDate($curr_date,'Y-m-d');
			$inx=0;
			
			$arrayGPS = array();
			
			foreach($datainfo as $row){
				$payload = unserialize($row['payload']);
				if(in_array($payload['LOC_INFO']['LATITUDE'].','.$payload['LOC_INFO']['LONGITUDE'],$arrayGPS)){
					continue;
				}
				$returnData['info'][$inx]['id']=(int)$row['id'];
				$returnData['info'][$inx]['Lat']=(float)$payload['LOC_INFO']['LATITUDE'];
				$returnData['info'][$inx]['Lon']=(float)$payload['LOC_INFO']['LONGITUDE'];
				$returnData['info'][$inx]['GPSTime']=toDate($payload['LOC_INFO']['TIME_STAMP']);
				$returnData['info'][$inx]['FixType']=$payload['LOC_INFO']['FIX_TYPE'];
				$arrayGPS[] = $payload['LOC_INFO']['LATITUDE'].','.$payload['LOC_INFO']['LONGITUDE'];
				$inx++;
			}
			$returnData['trace']=$inx;
		}else{
			$returnData['error']=1;
			$returnData['trace']=0;
			$returnData['info']='Failed';
			$returnData['reason']='Failed';
			$returnData['currdate']=toDate($curr_date,'Y-m-d');
		}
		$this->ajaxReturn($returnData,'JSON');
	}
	
	public function edit(){
        $userinfo = cookie('userinfo');
		$aged_list = M('Elderly_monitor')->where("user_id=".$userinfo['id'])->getField('elderly_id',true);
		$datalist=M('Elderly_info')->field('elderly_name,nick_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$aged_list).")")->select();
		
		$this->assign('datalist',$datalist);
		
		$this->display();
	}
	
	public function edit_show(){
		
		$this->assign('disease',explode(',',C('DEVICE_INFO_DISEASE')));
		/*
		$this->assign('timespc',explode(',',C('DEVICE_INFO_TIMESPC')));
		$this->assign('tempspc',explode(',',C('DEVICE_INFO_TEMPSPC')));
		$this->assign('traceday',C('DEVICE_INFO_TRACEDAY'));
		$this->assign('cycspc',explode(',',C('DEVICE_INFO_CYCSPC')));
		*/
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		//var_dump($varinfo);
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
		
		$userinfo = cookie('userinfo');
		//$this->assign('device_auth_code',strtoupper(base_convert(crc32($userinfo['id'].getCurrTime()),10,36)) );
		
		$id = (int)I('get.id');
		
		$model = M('Elderly_info');
		$agedinfo = $model->find($id);
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
		//$agedinfo['device_set2']=unserialize($agedinfo['device_set2']);
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
		
		//var_dump($agedinfo['device_set7']);
		
		
		$sales_list = M('User_mas')->where("status_flg='Y' and loc_id in (0,1,".(int)$agedinfo['use_area_id'].") and user_type in('ADMIN','COSTOMERMANAGER')")->select();
		$this->assign('sales_list',$sales_list);
		
		
		$this->display();
	}
	
	public function edit_save(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');
		
		$device_check = $model->where("id=".(int)I('post.id'))->find();
		if(!$device_check){
			$oData['error']=2;
			$oData['info']='Device cannot find!';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}
		
		$model_device_mas = M('Device_mas');
		$model->startTrans();
		
		$model->create();
		
		$model->barthday=toTime(I('post.barthday'));
		$model->register_date=toTime(I('post.register_date'));
		$model->termination_date=toTime(I('post.termination_date'));
		
		$model->disease=implode(',',I('post.disease'));
		$model->address=implode(',',I('post.address'));
		$model->use_area_str=implode(',',I('post.use_area_str'));
		
		$model->monitor_info=serialize(I('post.monitor_info'));
		
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		$varinfo['value1']=unserialize($varinfo['value1']);
		
		$model->device_set2=I('post.device_set2');
		if(I('post.device_set2')=='1'){
			$varinfo['value1']['device_set1']['FLG']='wait_update';
			$model->device_set1=serialize($varinfo['value1']['device_set1']);
		}else{
			$varinfo['value1']['device_set2']['FLG']='wait_update';
			$model->device_set1=serialize($varinfo['value1']['device_set2']);
		}
		//$model->device_set1=serialize(I('post.device_set1'));
		//$model->device_set2=serialize(I('post.device_set2'));
		$model->device_set3=serialize(I('post.device_set3'));
		/*
		$device_set4_tmp = explode(',',C('DEVICE_INFO_SERVICE_NO'));
		$device_set4['NO1']=$device_set4_tmp[0];
		$device_set4['NO2']=$device_set4_tmp[1];
		$device_set4['FLG']='__updated__';
		$model->device_set4=serialize($device_set4);
		
		$device_set5['PORT']=C('DEVICE_INFO_TCP_PORT');
		$device_set5['FLG']='__updated__';
		$model->device_set5=serialize($device_set5);
		
		$device_set6['URL']=C('DEVICE_INFO_TCP_URL');
		$device_set6['FLG']='__updated__';
		$model->device_set6=serialize($device_set6);
		*/
		$old_device_set7=unserialize($device_check['device_set7']);
		$old_device_set8=unserialize($device_check['device_set8']);
		$old_device_set9=unserialize($device_check['device_set9']);
		$old_device_set10=unserialize($device_check['device_set10']);
		$old_device_set12=unserialize($device_check['device_set12']);
		$old_device_set14=unserialize($device_check['device_set14']);
		$old_device_set15=unserialize($device_check['device_set15']);
		$old_beacon=unserialize($device_check['beacons']);
		
		$device_set7 = I('post.device_set7');
		$device_set7['TIME1']=(int)$device_set7['TIME1'];
		$device_set7['TIME2']=(int)$device_set7['TIME2'];
		$device_set7['TIME3']=(int)$device_set7['TIME3'];
		if($device_set7['TIME1']!=(int)$old_device_set7['TIME1']||$device_set7['TIME2']!=(int)$old_device_set7['TIME2']
			||$device_set7['TIME3']!=(int)$old_device_set7['TIME3']){
			$device_set7['FLG'] = 'wait_update';
		}else{
			$device_set7['FLG'] = $old_device_set7['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set7=serialize($device_set7);
		
		$device_set8 = I('post.device_set8');
		$device_set8['TIME1']=toTime($device_set8['TIME1']);
		$device_set8['TIME2']=toTime($device_set8['TIME2']);
		$device_set8['TIME3']=toTime($device_set8['TIME3']);
		if($device_set8['TIME1']!=$old_device_set8['TIME1']||$device_set8['TIME2']!=$old_device_set8['TIME2']
			||$device_set8['TIME3']!=$old_device_set8['TIME3']){
			$device_set8['FLG'] = 'wait_update';
		}else{
			$device_set8['FLG'] = $old_device_set8['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set8=serialize($device_set8);
		
		$device_set9 = I('post.device_set9');
		$device_set9['TIME1']=(int)$device_set9['TIME1'];
		$device_set9['TIME2']=(int)$device_set9['TIME2'];
		$device_set9['TIME3']=(int)$device_set9['TIME3'];
		if($device_set9['TIME1']!=(int)$old_device_set9['TIME1']||$device_set9['TIME2']!=(int)$old_device_set9['TIME2']
			||$device_set9['TIME3']!=(int)$old_device_set9['TIME3']){
			$device_set9['FLG'] = 'wait_update';
		}else{
			$device_set9['FLG'] = $old_device_set9['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set9=serialize($device_set9);
		
		$device_set10 = I('post.device_set10');
		$device_set10['LOW_T']=(int)$device_set10['LOW_T'];
		$device_set10['HIGHT_T']=(int)$device_set10['HIGHT_T'];
		$device_set10['METHOD']=(int)$device_set10['METHOD'];
		if($device_set10['LOW_T']!=(int)$old_device_set10['LOW_T']||$device_set10['HIGHT_T']!=(int)$old_device_set10['HIGHT_T']
			||$device_set10['METHOD']!=(int)$old_device_set10['METHOD']){
			$device_set10['FLG'] = 'wait_update';
		}else{
			$device_set10['FLG'] = $old_device_set10['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set10=serialize($device_set10);
		/*
		$device_set11['EN']='1';
		$device_set11['FLG']='__updated__';
		$model->device_set11=serialize($device_set11);
		*/
		$device_set12['SEX']=(int)I('post.sex');
		$device_set12['HIGHT']=(int)I('post.height');
		$device_set12['WEIGHT']=(int)I('post.weight');
		if((int)$old_device_set12['SEX']!=$device_set12['SEX']||(int)$old_device_set12['HIGHT']!=$device_set12['HIGHT']
			||(int)$old_device_set12['WEIGHT']!=$device_set12['WEIGHT']){
			$device_set12['FLG'] = 'wait_update';
		}else{
			$device_set12['FLG'] = $old_device_set12['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set12=serialize($device_set12);
		/*
		$device_set13_tmp = explode(',',C('DEVICE_INFO_TRACESPC'));
		$device_set13['TIME1']=$device_set13_tmp[0];
		$device_set13['TIME2']=$device_set13_tmp[1];
		$device_set13['TIME3']=$device_set13_tmp[2];
		$device_set13['FLG']='__updated__';
		$model->device_set13=serialize($device_set13);
		*/
		 $device_set14 = I('post.device_set14');
        $device_set14['TIME1']=(int)$device_set14['TIME1'];
        $device_set14['TIME2']=(int)$device_set14['TIME2'];
        $device_set14['TIME3']=(int)$device_set14['TIME3'];
        if($device_set14['TIME1']!=(int)$old_device_set14['TIME1']||$device_set14['TIME2']!=(int)$old_device_set14['TIME2']
			||$device_set14['TIME3']!=(int)$old_device_set14['TIME3']){
            $device_set14['FLG'] = 'wait_update';
        }else{
            $device_set14['FLG'] = $old_device_set14['FLG']=='__updated__'?'__updated__':'wait_update';
        }
        $model->device_set14=serialize($device_set14);
		
        
		$device_set15 = I('post.device_set15');
        $device_set15['arg1']=$device_set15['arg1']?$device_set15['arg1']:'off';
		if($device_set15['arg1']!=$old_device_set15['arg1']){
            $device_set15['FLG'] = 'wait_update';
        }else{
            $device_set15['FLG'] = $old_device_set15['FLG']=='__updated__'?'__updated__':'wait_update';
        }
        $model->device_set15=serialize($device_set15);
		
		$beacons = array_values(I('post.beacons'));
		$beacons['FLG'] = 'wait_update';
		if(!empty($old_beacons['sn'])){
			foreach($beacons['sn'] as $sn){
				if(!in_array($sn,$old_beacons['sn'])){
					$beacons['FLG'] = 'wait_update';
				}
			}
		}else{
			$beacons['FLG'] = $old_beacons['FLG']=='__updated__'?'__updated__':'wait_update';
		}
        $model->beacons=serialize($beacons);
		
		$model->status_flg='Y';
		
		//var_dump($model);exit;
		unset($model->sales_id);
		unset($model->sim);
		unset($model->device_sn);
		unset($model->device_auth_code);
		unset($model->device_model);
		unset($model->register_date);
		unset($model->device_set4);
		unset($model->device_set5);
		unset($model->device_set6);
		unset($model->device_set11);
		unset($model->device_set13);
		
		$aged_flg = $model->save();
		
		$data['status_flg']='S';
		$device_mas_flg = $model_device_mas->where("device_sn='".I('post.device_sn')."'")->save($data);
		
		if((int)$aged_flg==0 || $device_mas_flg===false){
			$model->rollback();
			$oData['error']=1;
			$oData['info']='Fail';
			
		}else{
			$model->commit();
			$oData['error']=0;
			$oData['info']='Success';
			
			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=I('post.elderly_name');
			$logData['notes']['agedinfo']['nick_name']=I('post.nick_name');
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Modify Elderly Info.';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='edit_save';
			$logData['module']='Webuser';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
			
		}
		
		$this->ajaxReturn($oData,'JSON');
	}
	
	public function history(){
		$userinfo = cookie('userinfo');
		$model = M('Sys_log');
		
		$strwhere="module in('Public','Webuser') and user_id in (".(int)$userinfo['id'].")";
		
		$keyword = I('get.keyword');
		
		if($keyword)$strwhere.=" and (act_obj = '$keyword' or user_id = '$keyword')";
		$this->assign('keyword',$keyword);
		
		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("act_time desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['notes']=unserialize($v['notes']);
		}
		
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);
		
		$this->display();
	}
	public function alertlist(){
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
		
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);
		
		//var_dump($datalist);
		
		$this->display();
	}

	
	public function setting(){
        $userinfo = cookie('userinfo');
		$aged_list = M('Elderly_monitor')->where("user_id=".$userinfo['id'])->getField('elderly_id',true);
		$datalist=M('Elderly_info')->field('elderly_name,nick_name,phone,id')->where("status_flg='Y' and id in(".implode(',',$aged_list).")")->select();
		
		$this->assign('datalist',$datalist);
		
		$this->display();
	}
}
