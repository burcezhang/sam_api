<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class BeaconController extends AppapiController {
    public function index()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{
			case 1://新增
				$model = M('Elderly_info');
				$device_sn = I('get.device_sn');
				$datainfo = $model->where("status_flg='Y' and device_sn='$device_sn'")->find();
				if($datainfo){
					$data = $datainfo['beacons']? unserialize($datainfo['beacons']) : array();
					$p=count($data);
					foreach($data as $beacon){
						if(I('get.beaconID')==$beacon['beaconID']){
							$returnData['error']=1;
							$returnData['reason']='Failed';
							$returnData['info']='beaconID already exists!';
							$jsonData['data']=$returnData;
							//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

							return;	
						}
					}
					$data[$p]['beaconID'] = I('get.beaconID');
					$data[$p]['name'] = I('get.name');
					$data[$p]['address'] = I('get.address');
					$data[$p]['lat'] = I('get.lat');
					$data[$p]['lon'] = I('get.lon');
					$data['FLG']='wait_update';
					$model->data( array('beacons'=>serialize($data)) )->where("status_flg='Y' and device_sn='$device_sn'")->save();
					$returnData['error']=0;
					$returnData['reason']='Success';
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
			case 2://查询
				$model = M('Elderly_info');
				$device_sn = I('get.device_sn');
				$datainfo = $model->where("status_flg='Y' and device_sn='$device_sn'")->find();
				if($datainfo){
					$data = $datainfo['beacons']? unserialize($datainfo['beacons']) : array();
					unset($data['FLG']);
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']=array_values($data);
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
			case 3://删除
				$model = M('Elderly_info');
				$device_sn = I('get.device_sn');
				$datainfo = $model->where("status_flg='Y' and device_sn='$device_sn'")->find();
				if($datainfo){
					$data = $datainfo['beacons']? unserialize($datainfo['beacons']) : array();
					if($data){
						foreach($data as $k => $Rs){
							if($Rs['beaconID']==I('get.beaconID')){
								unset($data[$k]);
								break;
							}
						}
					}
					$data['FLG']='wait_update';
					$model->data( array('beacons'=>serialize($data)) )->where("status_flg='Y' and device_sn='$device_sn'")->save();
					$returnData['error']=0;
					$returnData['reason']='Success';
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
			case 4://修改
				//http://cc.anycare-cn.com/Appapi/beacon.aspx?fun=1&name=office&beaconID=0001&address=&lat=22.546242&lon=113.956&device_sn=3572040400000330
				$model = M('Elderly_info');
				$device_sn = I('get.device_sn');
				$datainfo = $model->where("status_flg='Y' and device_sn='$device_sn'")->find();
				if($datainfo){
					$flag=false;
					$data = $datainfo['beacons']? unserialize($datainfo['beacons']) : array();
					if($data){
						foreach($data as $k => $Rs){
							if($Rs['beaconID']==I('get.beaconID')){
								$data[$k]['name']=I('get.name');
								$data[$k]['address']=I('get.address');
								$data[$k]['lat']=I('get.lat');
								$data[$k]['lon']=I('get.lon');
								$flag=true;
								break;
							}
						}
					}
					if($flag){
						$data['FLG']='wait_update';
						$model->data( array('beacons'=>serialize($data)) )->where("status_flg='Y' and device_sn='$device_sn'")->save();
						$returnData['error']=0;
						$returnData['reason']='Success';
					}else{
						$returnData['error']=1;
						$returnData['reason']='Failed';
					}
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
		}

	}
}