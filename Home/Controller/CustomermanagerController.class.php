<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class CustomermanagerController extends CommonController {

    public function index(){

		$this->assign('disease',explode(',',L('DEVICE_INFO_DISEASE')));
		/*
		$this->assign('timespc',explode(',',C('DEVICE_INFO_TIMESPC')));
		$this->assign('tempspc',explode(',',C('DEVICE_INFO_TEMPSPC')));
		$this->assign('traceday',C('DEVICE_INFO_TRACEDAY'));
		$this->assign('cycspc',explode(',',C('DEVICE_INFO_CYCSPC')));
		*/
		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		//var_dump($varinfo);
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
			//print_r($varinfo);

			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		$this->assign('default_map_dj',$default_map_dj);

		$userinfo = cookie('userinfo');
		$this->assign('device_auth_code',strtoupper(base_convert(crc32($userinfo['id'].getCurrTime()),10,36)) );

		if((int)$userinfo['loc_id']>1){
			$locwhere = " and loc_id=".(int)$userinfo['loc_id'];
		}else{
			$locwhere = " ";
		}
		$sales_list = M('User_mas')->where("status_flg='Y'".$locwhere)->select();
		$this->assign('sales_list',$sales_list);

		$group=M('Loc_mas')->where('id=\''.$userinfo['loc_id'].'\'')->getField('group',true);
		$this->assign('group',$group);

		$beacon=M('Beacon')->where('loc_id=\''.$userinfo['loc_id'].'\'')->select();
		$this->assign('beacon',$beacon);
		
		$this->display();
	}

	public function temp(){
		var_dump($_POST);
		var_dump($_GET);
	}

	public function check_device_sn(){
		$device_sn = I('post.sn');
		$model = M('Device_mas');
		$deviceInfo = $model->where("device_sn='$device_sn'")->find();
		if($deviceInfo){
			$oData['error']=0;
			$oData['info']=$deviceInfo;
		}else{
			$oData['error']=1;
			$oData['info']='Fail'; //不存在或已被使用
		}
		$this->ajaxReturn($oData,'JSON');
	}

	public function useradd_save(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');

		$device_check = $model->where("device_sn='".I('post.device_sn')."'")->find();
		if($device_check){
			$oData['error']=2;
			$oData['info']='Device SN Double';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}
		if(IS_POST){
			$photo=I('post.photo');
			if(!empty($photo)){
				$photo=uploadData($photo);
				if($photo[0]){
					$photo=$photo[1];
				}else{
					$returnData['error']=1;
					$returnData['reason']='Photo Upload Failed';
					$this->ajaxReturn($returnData,'JSON');
					return;
				}
			}
		}

		$model_device_mas = M('Device_mas');
		$model->startTrans();

		$model->create();

		if(!empty($photo)) $model->photo_src=$photo;
		$model->barthday=toTime(I('post.barthday'));
		$model->register_date=toTime(I('post.register_date'));
		$model->termination_date=toTime(I('post.termination_date'));

		$model->disease=implode(',',I('post.disease'));
		if(empty($model->disease)) $model->disease='';
		$model->address=implode(',',I('post.address'));
		$model->use_area_str=implode(',',I('post.use_area_str'));

		$model->monitor_info=serialize(I('post.monitor_info'));
		$model->device_set2=I('post.device_set2');


		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		$varinfo['value1']=unserialize($varinfo['value1']);
		if(I('post.device_set2')=='1'){
			$varinfo['value1']['device_set1']['FLG']='wait_update';
			$model->device_set1=serialize($varinfo['value1']['device_set1']);
		}else{
			$varinfo['value1']['device_set2']['FLG']='wait_update';
			$model->device_set1=serialize($varinfo['value1']['device_set2']);
		}

		$model->device_set3=serialize(I('post.device_set3'));

		$device_set4 = I('post.device_set4');;
		$device_set4['FLG']='wait_update';
		$model->device_set4=serialize($device_set4);

		$device_set5['PORT']=$varinfo['value1']['device_set5'];
		$device_set5['FLG']='wait_update';
		$model->device_set5=serialize($device_set5);

		$device_set6['URL']=$varinfo['value1']['device_set6'];
		$device_set6['FLG']='wait_update';
		$model->device_set6=serialize($device_set6);

		$device_set7 = I('post.device_set7');
		$device_set7['TIME1']=(int)$device_set7['TIME1'];
		$device_set7['TIME2']=(int)$device_set7['TIME2'];
		$device_set7['TIME3']=(int)$device_set7['TIME3'];
		$device_set7['FLG'] = (($device_set7['default_flg']=='Y')?'__updated__':'wait_update');
		$model->device_set7=serialize($device_set7);

		$device_set8 = I('post.device_set8');
		$device_set8['TIME1']=toTime($device_set8['TIME1']);
		$device_set8['TIME2']=toTime($device_set8['TIME2']);
		$device_set8['TIME3']=toTime($device_set8['TIME3']);
		$device_set8['FLG'] = (($device_set8['default_flg']=='Y')?'__updated__':'wait_update');
		$model->device_set8=serialize($device_set8);

		$device_set9 = I('post.device_set9');
		$device_set9['TIME1']=(int)$device_set9['TIME1'];
		$device_set9['TIME2']=(int)$device_set9['TIME2'];
		$device_set9['TIME3']=(int)$device_set9['TIME3'];
		$device_set9['FLG'] = (($device_set9['default_flg']=='Y')?'__updated__':'wait_update');
		$model->device_set9=serialize($device_set9);

		$device_set10 = I('post.device_set10');
		$device_set10['LOW_T']=(int)$device_set10['LOW_T'];
		$device_set10['HIGHT_T']=(int)$device_set10['HIGHT_T'];
		$device_set10['METHOD']=(int)$device_set10['METHOD'];
		$device_set10['FLG'] = (($device_set10['default_flg']=='Y')?'__updated__':'wait_update');
		$model->device_set10=serialize($device_set10);

		$device_set11['EN']='1';
		$device_set11['FLG']='__updated__';
		$model->device_set11=serialize($device_set11);

		$device_set12['SEX']=(int)I('post.sex');
		$device_set12['HIGHT']=(int)I('post.height');
		$device_set12['WEIGHT']=(int)I('post.weight');
		$device_set12['FLG']='wait_update';
		$model->device_set12=serialize($device_set12);

		$device_set13_tmp = explode(',',C('DEVICE_INFO_TRACESPC'));
		$device_set13['TIME1']=$device_set13_tmp[0];
		$device_set13['TIME2']=$device_set13_tmp[1];
		$device_set13['TIME3']=$device_set13_tmp[2];
		$device_set13['FLG']='__updated__';
		$model->device_set13=serialize($device_set13);

		$device_set14 = I('post.device_set14');
        $device_set14['TIME1']=(int)$device_set14['TIME1'];
        $device_set14['TIME2']=(int)$device_set14['TIME2'];
        $device_set14['TIME3']=(int)$device_set14['TIME3'];
		$device_set14['FLG'] = (($device_set14['default_flg']=='Y')?'__updated__':'wait_update');
		$model->device_set14=serialize($device_set14);

        $device_set15 = I('post.device_set15');
        $device_set15['arg1']=$device_set15['arg1'];
		$device_set15['FLG'] = (($device_set15['default_flg']=='Y')?'__updated__':'wait_update');
        $model->device_set15=serialize($device_set15);

		$model->status_flg='Y';

		//var_dump($model);exit;

		$aged_flg = $model->add();

		$data['status_flg']='S';
		$device_mas_flg = $model_device_mas->where("device_sn='".I('post.device_sn')."'")->save($data);

		if((int)$aged_flg==0 || $device_mas_flg===false){
			$model->rollback();
			$oData['error']=1;
			$oData['info']='Fail';

			$logData['notes']['aged_flg']=$aged_flg;
			$logData['notes']['$device_mas_flg']=$device_mas_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);			

		}else{
			$model->commit();
			$oData['error']=0;
			$oData['info']='Success';

			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=I('post.elderly_name');
			$logData['notes']['agedinfo']['nick_name']=I('post.nick_name');
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Add to';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='useradd_save';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)$aged_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function useredit(){
		//C('SHOW_PAGE_TRACE',true);
		$model = M('Elderly_info');
		$userinfo = cookie('userinfo');
		$strwhere="status_flg='Y'";
		if((int)$userinfo['loc_id']>1){
			$strwhere.=" and use_area_id=".$userinfo['loc_id'];
		}

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (elderly_name like '%$keyword%' or device_sn like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->field('id,elderly_name,barthday,address,phone,register_date,termination_date,device_sn')->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['age']=(int)date('Y')-(int)date('Y',$v['barthday']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		$group=M('Loc_mas')->where('id=\''.$userinfo['loc_id'].'\'')->getField('group',true);
		$this->assign('group',$group);

		$this->display();
	}

	public function useredit_show(){

		$this->assign('disease',explode(',',L('DEVICE_INFO_DISEASE')));
		/*
		$this->assign('timespc',explode(',',C('DEVICE_INFO_TIMESPC')));
		$this->assign('tempspc',explode(',',C('DEVICE_INFO_TEMPSPC')));
		$this->assign('traceday',C('DEVICE_INFO_TRACEDAY'));
		$this->assign('cycspc',explode(',',C('DEVICE_INFO_CYCSPC')));
		*/

		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
		//var_dump($varinfo);
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
		$this->assign('default_map_dj',$default_map_dj);

		$userinfo = cookie('userinfo');
		//$this->assign('device_auth_code',strtoupper(base_convert(crc32($userinfo['id'].getCurrTime()),10,36)) );
		if((int)$userinfo['loc_id']>1){
			$locwhere = " and loc_id=".(int)$userinfo['loc_id'];
		}else{
			$locwhere = " ";
		}
		$sales_list = M('User_mas')->where("status_flg='Y'".$locwhere)->select();
		$this->assign('sales_list',$sales_list);

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
		//$agedinfo['device_set2']=$agedinfo['device_set2'];
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

		$agedinfo['service_period']=date('Y',$agedinfo['termination_date'])-date('Y',$agedinfo['register_date']);
                $this->assign('agedinfo',$agedinfo);

		//var_dump($datalist);
		$beacon=M('Beacon')->where('loc_id=\''.$userinfo['loc_id'].'\'')->select();
		$this->assign('beacon',$beacon);

                //if(isset($beacon)){ echo '$beacons is true'; }

		$group=M('Loc_mas')->where('id=\''.$userinfo['loc_id'].'\'')->getField('group',true);
		$this->assign('group',$group);
		
		$this->display();
	}

	public function useredit_save(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');

		$device_check = $model->where("device_sn='".I('post.device_sn')."' and id!=".(int)I('post.id'))->find();
		if($device_check){
			$oData['error']=2;
			$oData['info']='Device SN Double';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}

		$device_check = $model->where("device_sn='".I('post.device_sn')."'")->find();
		if(IS_POST){
			$photo=I('post.photo');
			if(!empty($photo)) {
				$photo=uploadData($photo);
				if($photo[0]){
					$photo=$photo[1];
				}else{
					$returnData['error']=1;
					$returnData['reason']='Photo Upload Failed';
					$this->ajaxReturn($returnData,'JSON');
					return;
				}
			}
		}
		$model_device_mas = M('Device_mas');
		$model->startTrans();

		$model->create();

		if(!empty($photo)) $model->photo_src=$photo;
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
		$old_device_set4=unserialize($device_check['device_set4']);
		$old_device_set7=unserialize($device_check['device_set7']);
		$old_device_set8=unserialize($device_check['device_set8']);
		$old_device_set9=unserialize($device_check['device_set9']);
		$old_device_set10=unserialize($device_check['device_set10']);
		$old_device_set12=unserialize($device_check['device_set12']);
		$old_device_set14=unserialize($device_check['device_set14']);
		$old_device_set15=unserialize($device_check['device_set15']);
		$old_beacon=unserialize($device_check['beacons']);


		$device_set4 = I('post.device_set4');
		$device_set4['NO1']=$device_set4['NO1'];
		$device_set4['NO2']=$device_set4['NO2'];
		if($device_set4['NO1']!=$old_device_set4['NO1']||$device_set4['NO2']!=$old_device_set4['NO2']){
			$device_set4['FLG'] = 'wait_update';
		}else{
			$device_set4['FLG'] = $old_device_set4['FLG']=='__updated__'?'__updated__':'wait_update';
		}
		$model->device_set4=serialize($device_set4);

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

		$model->status_flg='Y';
		if(!I('post.sales_id'))unset($model->sales_id);
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
			$logData['notes']['note']='Modify';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='useredit_save';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function userdelete(){
		//C('SHOW_PAGE_TRACE',true);
		$model = M('Elderly_info');
		$userinfo = cookie('userinfo');
		$strwhere="status_flg='Y'";
		if((int)$userinfo['loc_id'] > 1)$strwhere.=" and use_area_id=".$userinfo['loc_id'];

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (elderly_name like '%$keyword%' or phone like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->field('id,elderly_name,barthday,address,phone,register_date,termination_date')->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['age']=(int)date('Y')-(int)date('Y',$v['barthday']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//var_dump($datalist);

		$this->display();
	}

	public function userdelete_todo(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');
		$aged_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();

		if((int)$aged_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';


		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$agedinfo = $model->field('id,elderly_name,nick_name')->find((int)I('post.id'));
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Delete';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='userdelete_todo';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function deactive(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');
		$data['id']=(int)I('post.id');
		$data['status_flg']='D';
		$aged_flg = $model->data($data)->save();

		if((int)$aged_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';


		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$agedinfo = $model->field('id,elderly_name,nick_name')->find((int)I('post.id'));
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Delete';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='deactive';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}

		$this->ajaxReturn($oData,'JSON');
	}
	

	public function recycle(){
		//C('SHOW_PAGE_TRACE',true);
		$model = M('Elderly_info');
		$userinfo = cookie('userinfo');
		$strwhere="status_flg='D'";
		if((int)$userinfo['loc_id'] > 1)$strwhere.=" and use_area_id=".$userinfo['loc_id'];

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (elderly_name like '%$keyword%' or phone like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->field('id,elderly_name,barthday,address,phone,register_date,termination_date')->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['age']=(int)date('Y')-(int)date('Y',$v['barthday']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//var_dump($datalist);

		$this->display();
	}

	public function recycle_todo(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');
		$data['id']=(int)I('post.id');
		$data['status_flg']='Y';
		$aged_flg = $model->data($data)->save();

		if((int)$aged_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$agedinfo = $model->field('id,elderly_name,nick_name')->find((int)I('post.id'));
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Restore';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='recycle_todo';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function usertime(){
		//C('SHOW_PAGE_TRACE',true);
		$model = M('Elderly_info');
		$userinfo = cookie('userinfo');
		$strwhere="status_flg='Y'";
		if((int)$userinfo['loc_id'] > 1)$strwhere.=" and use_area_id=".$userinfo['loc_id'];

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (elderly_name like '%$keyword%' or phone like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$query_flg = (int)I('get.query');
		if($query_flg==1){
			$start_date = I('post.start_date');
			$end_date = I('post.end_date');
			if($start_date)$strwhere.=" and termination_date>=".toTime($start_date);
			if($end_date)$strwhere.=" and termination_date<=".toTime($end_date);
			$this->assign('start_date',$start_date);
			$this->assign('end_date',$end_date);
		}else if($query_flg==2){
			$strwhere.=" and termination_date<".getCurrTime();
		}else if($query_flg==3){

		}else if($query_flg==4){

		}

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->field('id,elderly_name,barthday,address,phone,register_date,termination_date,monitor_info,device_sn,device_set1,device_set2')->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['age']=(int)date('Y')-(int)date('Y',$v['barthday']);
			$datalist[$k]['monitor_info']=unserialize($v['monitor_info']);
			$datalist[$k]['device_set1']=unserialize($v['device_set1']);
			$datalist[$k]['device_set2']=unserialize($v['device_set2']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//var_dump($datalist);
		//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Due inquiry';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='usertime';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		$this->display();
	}
	//到期
	public function usertime_sms(){
		$userinfo = cookie('userinfo');
		$model = M('Elderly_info');
		$uid=(int)I('post.id');
		$agedinfo = $model->field('id,elderly_name,device_sn,nick_name,monitor_info')->find($uid);

		$sms_template = M('Sms_template')->where("status_flg='Y' and sms_type='service_expire'")->order('id desc')->getField('content');

		$monitor_info = unserialize($agedinfo['monitor_info']);
		for($imonitor=0;$imonitor<count($monitor_info);$imonitor++){
			if($monitor_info[$imonitor]['phone']) $monitor_phone[] = $monitor_info[$imonitor]['phone'];
		}

		$sms_template = str_replace('***',$agedinfo['elderly_name'],$sms_template);

		$pushData['topic']='SERVICE_EXPIRE';
		$pushData['tager']=$alert_info['device_sn'];
		$pushData['body'] =$sms_template;
		$pushData['act_type']='service_expire';
		$pushData['act_obj']=$alertid;
		pushMessage($pushData);


		$smsData['phone']=$monitor_phone;
		$smsData['body'] =$sms_template;
		$smsData['act_type']='service_expire';
		$smsData['act_obj']=$alertid;
		$sms_flg = sendSMS($smsData);

		if(!$sms_flg){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']=$agedinfo['elderly_name'];
			$logData['notes']['agedinfo']['nick_name']=$agedinfo['nick_name'];
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Texting';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='usertime_sms';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function history(){
		$userinfo = cookie('userinfo');
		$model = M('Sys_log');

		$strwhere="module='Customermanager'";
		if((int)$userinfo['loc_id'] > 1){
			$user_ids = M('User_mas')->where("loc_id=".$userinfo['loc_id'])->getField('id',true);
			$strwhere.=" and user_id in (".implode(',',$user_ids).")";
		}

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (act_obj = '$keyword' or user_id = '$keyword')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['notes']=unserialize($v['notes']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//var_dump($datalist);
		//***Log***
		/*
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success操作成功';
			$logData['notes']['note']='查看历史';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='history';
			$logData['module']='Customermanager';
			$logData['act_obj']=(int)I('post.id');
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		*/
		$this->display();
	}

	public function smssetting(){
		$userinfo = cookie('userinfo');
		$model = M('Sms_template');

		$strwhere="status_flg in ('Y','N')";

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (title like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("sms_type desc,status_flg desc")->limit($Page->firstRow.','.$Page->listRows)->select();

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		$this->display();
	}

	public function smssetting_show(){
		$model = M('Sms_template');
		$smsinfo = $model->find((int)I('get.id'));
		$this->assign("smsinfo",$smsinfo);
		$this->display();
	}
	public function smssetting_add(){
		$this->display();
	}

	public function smssetting_save(){
		$userinfo = cookie('userinfo');

		if( (int)$userinfo['loc_id'] > 1){
			$oData['error']=2;
			$oData['info']='Access Fail';
			$this->ajaxReturn($oData,'JSON');
		}

		$model = M('Sms_template');

		$model->create();
		$model->sms_type = strtolower(I('post.sms_type'));

		if((int)I('post.id')){
			$sms_flg = $model->save();
			if($sms_flg)$sms_flg=(int)I('post.id');
		}else{
			$sms_flg = $model->add();
		}

		if((int)$sms_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Setting SMS templates-'.I('post.title');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssetting_save';
			$logData['module']='Customermanager';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}


}
