<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class DeviceController extends AppapiController {
    public function index()
	{
		$userinfo = checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{
			case 1:	//注册设备
				$device_sn=I('get.device_sn');
				$device_auth_code=I('get.device_auth_code');

				$model = M('Elderly_info');
				$agedinfo = $model->field('id,monitor_id,elderly_name,nick_name')->where("device_auth_code='$device_auth_code' and device_sn='$device_sn' and status_flg='Y'")->find();
				$check_double = M('Elderly_monitor')->where("user_id='".$userinfo['id']."' and elderly_id='".$agedinfo['id']."'")->find();
				if($check_double){//已经绑定
					$returnData['error']=2;
					$returnData['reason']='Data Double';
				}else{
					if($agedinfo['id'] && $userinfo['id']){//绑定
						$model->startTrans();
						$data['user_id']=$userinfo['id'];
						$data['elderly_id']=$agedinfo['id'];
						$auth_code = M('Elderly_monitor')->data($data)->add();
					}
					if((int)$agedinfo['monitor_id']==0){//如果没有默认监护人设置默认监护人
						unset($data);
						$data['id']=$agedinfo['id'];
						$data['monitor_id']=$userinfo['id'];
						$auth_code = M('Elderly_info')->data($data)->save();
					}
					if($auth_code){
						$model->commit();
						$returnData['error']=0;
						$returnData['reason']='Success';
						$returnData['info']['userid']=$agedinfo;
					}else{
						$model->rollback();
						$returnData['error']=1;
						$returnData['reason']='Fail';
					}
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

				break;
			case 2://获取VCode
				$device_sn=I('get.device_sn');
				$model = M('Elderly_info');
				$agedinfo = $model->field('id,device_auth_code')->where("device_sn='$device_sn' and status_flg='Y'")->find();
				if($agedinfo){//已经绑定
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']['regist']='0';
					$returnData['info']['device_auth_code']=$agedinfo['device_auth_code'];
					if(empty($agedinfo['device_auth_code'])){
						$agedinfo['device_auth_code']=strtoupper(base_convert(crc32($userinfo['id'].getCurrTime()),10,36));
						$returnData['info']['regist']='1';
						$returnData['info']['device_auth_code']=$agedinfo['device_auth_code'];
						$mData=array();
						$mData['device_auth_code']=$agedinfo['device_auth_code'];
						$model->data($mData)->where("device_sn='$device_sn' and status_flg='Y'")->save();
					}
				}else{
					$returnData['error']=1;
					$returnData['reason']='Fail';
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