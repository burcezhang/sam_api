<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class LoginController extends AppapiController {
    public function index()
	{
		switch((int)I('get.fun'))
		{
			case 1:
				$model = M('User_mas');
				$account = I('get.account');
				$pwd = strtolower(I('get.pwd'));
				$datainfo = $model->where("status_flg='Y' and account='$account' and pwd='$pwd'")->find();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Login Success';
					$returnData['info']['name']=$datainfo['true_name'];
					$returnData['info']['login_count']=(int)$datainfo['login_count'];
					$returnData['info']['userId']=$datainfo['id'];
					$returnData['info']['holdId']=$datainfo['group_id'];
					//登录后返回绑定的deviceID供PushMessage使用
					$model = M('Elderly_monitor');
					$datalist = $model->alias('a')->field('b.device_sn')->join(C('DB_PREFIX').'elderly_info b ON a.elderly_id = b.id','inner')->where('a.user_id = '.$datainfo['id'].' and b.status_flg=\'Y\'')->order('a.id desc')->select();
					$device='';
					$locid='';
					foreach($datalist as $v){
						$device.=$v['device_sn'].',';
						$model = M('Device_mas');
						$loc_id=$model->where("device_sn='{$v['device_sn']}'")->getField('loc_id',true);
						$locid.=$loc_id[0].',';
					}
					$returnData['info']['deviceID']=rtrim($device,',');
					$returnData['info']['loc_id']=rtrim($locid,',');
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

				break;
			case 2:
				checkLogin(I('get.account'),I('get.pwd'));
				$model = M('User_mas');
				$data['pwd']=strtolower(md5(I('get.new_pwd')));
				$data['vcode']=I('get.new_pwd');
				$data['vcode_validity'] = time()+C('VCODE_VALIDITY')*86400;
				$account = I('get.account');
				$setting_flg = $model->data($data)->where("account='$account'")->save();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				//var_dump($setting_flg);
				//var_dump($model->getLastSql());
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

				break;
			case 3:
				$lang_vcode_sms='Your current Anycare Password:% s (valid for 30 days), for your information secure, do not tell them the information to others.';
				$account = I('get.account');
				$returnData['error']=0;
				if( preg_match('/^1[3,4,5,8][0-9]{9}/', $account) ){
					$model = M('User_mas');
					$datainfo = $model->where("account='$account'")->find();
					if($datainfo){
						$returnData['isnew']=0;
						if( time() > (int)$datainfo['vcode_validity']){
							$data=array();
							$data['vcode']=rand(1000,9999);
							$data['vcode_validity'] = time()+C('VCODE_VALIDITY')*86400;
							$data['pwd']=md5($data['vcode']);
							$model->data($data)->where("account='$account'")->save();
						}else{
							$data['vcode']=$datainfo['vcode'];
						}
						$smsData['phone']=$account;
						$smsData['body'] = sprintf($lang_vcode_sms,$data['vcode']);//$mArr['vcode'];
						$smsData['act_type']='';
						$smsData['act_obj']='Send login code!';
						sendSMS($smsData);
					}else{
						$mArr=array();
						$mArr['user_type']='WEBUSER';
						$mArr['account']=$account;
						$mArr['vcode']=rand(100000,999999);
						$mArr['vcode_validity'] = time()+C('VCODE_VALIDITY')*86400;
						$mArr['pwd']=md5($mArr['vcode']);
						$mArr['loc_id']='1';
						$mArr['group_id']='0';
						$mArr['create_time']=time();
						$mArr['login_count']='0';
						$mArr['login_last_dt']=time();
						$mArr['phone']=$account;
						$mArr['status_flg']='Y';
						$mArr['area_id']='1';
						$mArr['area_str']='中国';
						$mArr['is_service']='N';
						$mArr['service_status_flg']='0';
						$mArr['service_heart_dt']='0';
						$setting_flg = $model->data($mArr)->add();
						if($setting_flg){
							$returnData['isnew']=1;
							$smsData['phone']=$account;
							$smsData['body'] = sprintf($lang_vcode_sms,$mArr['vcode']);//$mArr['vcode'];
							$smsData['act_type']='';
							$smsData['act_obj']='Send login code!';
							sendSMS($smsData);
						}else{
							$returnData['error']=1;
							$returnData['reason']='Error';
						}
					}
				}else{
					$returnData['error']=1;
					$returnData['reason']='Error';
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

				break;
		}

	}
}
