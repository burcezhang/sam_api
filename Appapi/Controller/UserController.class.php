<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class UserController extends AppapiController {
    public function info()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 1:				
				$model = M('User_mas');
				$account = I('get.account');
				$pwd = I('get.pwd');
				$datainfo = $model->where("status_flg='Y' and account='$account' and pwd='$pwd'")->find();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';					
					$returnData['info']['true_name']=$datainfo['true_name'];
					$returnData['info']['account']=$datainfo['account'];
					$returnData['info']['user_name']=$datainfo['true_name'];
					$returnData['info']['sex']=(int)$datainfo['sex'];
					$returnData['info']['phone']=$datainfo['phone'];
					$returnData['info']['home_phone']=$datainfo['home_phone'];
					$returnData['info']['home_address']=$datainfo['home_address'];
					$returnData['info']['create_time']=toDate($datainfo['create_time']);
					$returnData['info']['remark']=$datainfo['remark'];
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
			case 2:
				$model = M('User_mas');
				$account = I('get.account');
				$data['true_name']=I('get.true_name');
				$data['sex']=I('get.sex');
				$data['phone']=I('get.phone');
				$data['home_phone']=I('get.home_phone');
				$data['home_address']=I('get.home_address');
				$data['remark']=I('get.remark');
				$setting_flg = $model->data($data)->where("account='$account'")->save();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
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
		}
		
	}
}