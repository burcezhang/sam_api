<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class AgedController extends AppapiController {
    public function info()
	{
		checkLogin(I('param.account'),I('param.pwd'));
		switch((int)I('param.fun'))
		{
			case 1:  //get list
				$user_id = M('User_mas')->where("account='".I('get.account')."'")->getField('id');
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');

				$model = M('Elderly_monitor');
				$count = $model->alias('a')->field('b.id,b.elderly_name,b.nick_name,b.photo_src,b.device_sn')
				                ->join(C('DB_PREFIX').'elderly_info b ON a.elderly_id = b.id','inner')
				                ->where('a.user_id = '.$user_id.' and b.status_flg=\'Y\'')
				                ->count();
				$tpagetotal = ceil($count/$psize);

				$datainfo = $model->alias('a')->field('b.id,b.elderly_name,b.nick_name,b.photo_src,b.device_sn')
				                ->join(C('DB_PREFIX').'elderly_info b ON a.elderly_id = b.id','inner')
				                ->where('a.user_id = '.$user_id.' and b.status_flg=\'Y\'')
				                ->order('a.id desc')->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['id']=$row['id'];
						$returnData['info'][$inx]['name']=$row['elderly_name'];
						$returnData['info'][$inx]['nickName']=$row['nick_name'];
						$returnData['info'][$inx]['photo_src']=$row['photo_src'];
						$returnData['info'][$inx]['objectId']=$row['device_sn'];
						$inx++;
					}
					$returnData['PageInfo'][0]['Records']=$count;
					$returnData['PageInfo'][0]['PCount']=$tpagetotal;
					$returnData['PageInfo'][0]['PSize']=$psize;
					$returnData['PageInfo'][0]['P']=$currpage;
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 2:  //get detail
				$model = M('Elderly_info');
				$id = I('get.id');
				$datainfo = $model->where("id=".$id)->find();
				//if(I('get.debug')) echo $model->getLastsql();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']['name']=$datainfo['elderly_name'];
					$returnData['info']['nickName']=$datainfo['nick_name'];
					$returnData['info']['photo_src']=$datainfo['photo_src'];
					$returnData['info']['bg_src']=$datainfo['bg_src'];					
					$returnData['info']['type']=(int)$datainfo['type'];
					$returnData['info']['sex']=(int)$datainfo['sex'];
					$returnData['info']['age']=(int)date('Y')-(int)date('Y',$datainfo['barthday']);
					$returnData['info']['birthday']=date('Ymd',$datainfo['barthday']);					
					$returnData['info']['weight']=(int)$datainfo['weight'];
					$returnData['info']['height']=(int)$datainfo['height'];
					$returnData['info']['disease']=$datainfo['disease'];
					$returnData['info']['phone']=$datainfo['phone'];
					$returnData['info']['sim']=$datainfo['sim'];
					//$returnData['info']['monitor_tel']=M('User_mas')->where("id=".$datainfo['monitor_id'])->getField('phone');
					$returnData['info']['monitor_tel']=$datainfo['home_tel'];
					$returnData['info']['care_star']=(int)$datainfo['care_star'];
					$returnData['info']['address']=$datainfo['address'];
					$returnData['info']['ESN']=$datainfo['device_sn'];
					$returnData['info']['device_model']=$datainfo['device_model'];
					$returnData['info']['IDCard']=$datainfo['idcard'];
					
					$STEP=0;
					$Batt=0;
					$start_date= toTime(date('Y-m-d'));
					$end_date = strtotime('+1 day',$start_date);
					
					$devInfo = M('Protocol_data')->where("cmd_name='DEV_INFO_RPT' and device_sn='".$datainfo['device_sn']."' and dt>=$start_date and dt<=$end_date")->order("dt desc")->select();

					$i=0;
					foreach($devInfo as $Rs){
						$Rs=unserialize($Rs['payload']);
						$STEP+=$Rs['STEP_COUNT']?(int)$Rs['STEP_COUNT']:0;
						if($i==0) $Batt=$Rs['BATT'];
						$i++;
					}

					$data = M('Protocol_data')->where("payload like 'a%LOC_INFO%' and device_sn='".$datainfo['device_sn']."' ")->order("id desc")->limit('1')->select();
					$protocol_data=unserialize($data[0]['payload']);

					$returnData['info']['gps_time']=$protocol_data['LOC_INFO']['TIME_STAMP']? date('Y-m-d H:i:s',$protocol_data['LOC_INFO']['TIME_STAMP']):'';
					$returnData['info']['device_time']=$protocol_datainfo['dt']? date('Y-m-d H:i:s',$protocol_datainfo['dt']):'';
					$returnData['info']['latitude']=$protocol_data['LOC_INFO']['LATITUDE']? $protocol_data['LOC_INFO']['LATITUDE']:'';
					$returnData['info']['longitude']=$protocol_data['LOC_INFO']['LONGITUDE']? $protocol_data['LOC_INFO']['LONGITUDE']:'';
					$returnData['info']['uncert_hor']=$protocol_data['LOC_INFO']['UNCERT_HORI']? $protocol_data['LOC_INFO']['UNCERT_HORI']:'';
					$returnData['info']['uncert_perp']=$protocol_data['LOC_INFO']['UNCERT_PERP']? $protocol_data['LOC_INFO']['UNCERT_PERP']:'';
					$returnData['info']['GPSFlag']=$protocol_data['LOC_INFO']['FIX_TYPE']? $protocol_data['LOC_INFO']['FIX_TYPE']:'';
					$returnData['info']['battery']=$Batt;
					$returnData['info']['ph_state']=1;
					$returnData['info']['temperature']=$protocol_data['LOC_INFO']['TEMPERATURE']? $protocol_data['LOC_INFO']['TEMPERATURE']:'';
					$returnData['info']['step']=$STEP?(int)$STEP:'';
					$returnData['info']['tstep']=$datainfo['tstep'];
					$returnData['info']['beacon_name']='';

					if((int)$returnData['info']['GPSFlag']==4){
						$returnData['info']['beacon_name']=$protocol_data['BEACON_NAME'];
					}
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 3:  //update info
				$model = M('Elderly_info');
				//$elderly_info=$model->find((int)I('param.id'));
				$data['id']=(int)I('param.id');
				$data['elderly_name']=I('param.name');
				$data['type']=(int)I('param.type');
				$data['sex']=(int)I('param.sex');

				if(I('param.nickName'))$data['nick_name']=I('param.nickName');
				if(I('param.birthday'))$data['barthday']=strtotime(I('param.birthday'));
				//if(I('param.age'))$data['barthday']=strtotime(((int)date('Y')-(int)I('param.age')).'-'.date('m-d',$elderly_info['barthday']));
				if(I('param.weight'))$data['weight']=(int)I('param.weight');
				if(I('param.height'))$data['height']=(int)I('param.height');

				if(I('param.imgByte')){
					$tmpImgByte  = base64_decode(I('param.imgByte'));
					$tmpFilename = C('UPLOAD_DIR').'aged_photo_'.toDate(getCurrTime(),'YmdHis').'_'.rand(10000,99999).'.jpg';
					file_put_contents(C('BASE_DIR').$tmpFilename, $tmpImgByte);
					$data['photo_src']=C('WEB_DOMAIN').$tmpFilename;
				}

				if(I('param.disease'))$data['disease']=I('param.disease');
				if(I('param.phone'))$data['phone']=I('param.phone');
				if(I('param.monitor_tel'))$data['home_tel']=I('param.monitor_tel');
//				if(I('param.monitor_tel')){
//					$data['home_tel']=I('param.monitor_tel');
//					$tels=explode(',',$data['home_tel']);
//					if($tels[0] != '0'){
//						$mArr=array();
//						$mArr['NO']=$tels[0];
//						if($tels[0] == '1'){ $mArr['GN1']=$tels[1]; $mArr['GN2']='0'; $mArr['GN3']='0'; $mArr['GN4']='0';}
//						if($tels[0] == '2'){ $mArr['GN1']=$tels[1]; $mArr['GN2']=$tels[2]; $mArr['GN3']='0'; $mArr['GN4']='0';}
//						if($tels[0] == '3'){ $mArr['GN1']=$tels[1]; $mArr['GN2']=$tels[2]; $mArr['GN3']=$tels[3]; $mArr['GN4']='0';}
//						if($tels[0] == '4'){ $mArr['GN1']=$tels[1]; $mArr['GN2']=$tels[2]; $mArr['GN3']=$tels[3]; $mArr['GN4']=$tels[4];}
//						$mArr['FLG']='wait_update';
//						$data['device_set16']=serialize($mArr);
//					}	
//				}
				if(I('param.address'))$data['address']=I('param.address');
				if(I('param.IDCard'))$data['idcard']=I('param.IDCard');
				if(I('param.tstep'))$data['tstep']=I('param.tstep');
				
				$setting_flg = $model->data($data)->save();

				if($setting_flg===false){
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}else{
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}
				/*
				if(I('param.monitor_tel')){
						unset($data);
						$model_user = M('User_mas');
						$data['id']=(int)($model->where("id=".(int)I('param.id'))->getField('monitor_id'));
						$data['phone']=I('param.monitor_tel');
						$model_user->data($data)->save();
				}
				*/
				$jsonData['data']=$returnData;
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 4: //delete info
				$model = M('Elderly_info');
				$data['id']=(int)I('get.id');
				$data['status_flg']='D';
				$setting_flg = $model->data($data)->save();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Success';
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 5: //Updata background image
				$model = M('Elderly_info');
				$data['id']=(int)I('param.id');
				if(I('param.imgByte')){
					$tmpImgByte  = base64_decode(I('param.imgByte'));
					$tmpFilename = C('UPLOAD_DIR').'aged_bg_'.toDate(getCurrTime(),'YmdHis').'_'.rand(10000,99999).'.jpg';
					file_put_contents(C('BASE_DIR').$tmpFilename, $tmpImgByte);
					$data['bg_src']=C('WEB_DOMAIN').$tmpFilename;
				}

				$setting_flg = $model->data($data)->save();

				if($setting_flg===false){
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}else{
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}
				$jsonData['data']=$returnData;
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";  
			break;
		}

	}
}
