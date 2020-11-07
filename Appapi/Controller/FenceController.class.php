<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class FenceController extends AppapiController {
    public function index()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 1:		//get list		
				$model = M('Geofence');				
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$objectid = I('get.objectId');
				$swhere = "status_flg='Y' and device_sn='$objectid'";
				$count = $model->where($swhere)->count();				
				$tpagetotal = ceil($count/$psize);				
				$datainfo = $model->where($swhere)->order("create_date desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				//var_dump($model->getLastSql());
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['areaid']=$row['id'];
						$returnData['info'][$inx]['areaname']=$row['geo_name'];
						$returnData['info'][$inx]['alarmtype']=$row['geo_type'];
						$returnData['info'][$inx]['arealat']=$row['area_lat'];
						$returnData['info'][$inx]['arealon']=$row['area_lon'];
						$returnData['info'][$inx]['radius']=$row['radius'];
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
				echo $result;   

			break;
			case 2: //update
				$model = M('Geofence');
				//$geofence_info=$model->where("status_flg='Y' and geo_no=".(int)I('get.id')." and device_sn='".('get.objectId')."'")->find();
				//$data['id']=$geofence_info['id'];
				$data['id']=(int)I('get.id');
				$data['geo_name']=I('get.name');
				$data['area_lat']=(float)I('get.latitude');
				$data['area_lon']=(float)I('get.longitude');
				$data['radius']=(float)I('get.radius');
				$data['device_sn']=I('get.objectId');
				if(I('get.alarmType'))$data['alert_type']=I('get.alarmType');
				$data['sync_flg']="2";
				$setting_flg = $model->data($data)->save();
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
				echo $result;   

			break;
			case 3: //delete
				$model = M('Geofence');
				//$swhere = "geo_no=".(int)I('get.id')." and device_sn='".('get.objectId')."'";
				$data['id']=(int)I('get.id');
				$data['sync_flg']='3';
				$data['status_flg']='D';
				$setting_flg = $model->data($data)->save();
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
				echo $result;   

			break;
			case 4: //addnew
				$model = M('Geofence');
				//$geofence_info=$model->where("status_flg='Y' and geo_no=".(int)I('get.id')." and device_sn='".('get.objectId')."'")->find();
				//if($geofence_info){
				//	$returnData['error']=1;
				//	$returnData['reason']='GeoID already exists';
				//}else{
				$fence_ids = $model->where("status_flg='Y' and device_sn='".I('get.objectId')."'")->getField('geo_no',true);
				//var_dump($fence_ids);exit;
				for($i=1;$i<100;$i++){
					if(!in_array($i,$fence_ids)){
						$data['geo_no']=$i;
						break;
					}
				}
				$data['geo_name']=I('get.name');
				$data['area_lat']=(float)I('get.latitude');
				$data['area_lon']=(float)I('get.longitude');
				$data['radius']=(float)I('get.radius');
				$data['device_sn']=I('get.objectId');
				$data['alert_type']=(int)I('get.alarmType');
				
				$data['geo_type']="0";
				$data['geo_attr']="1";
				$data['address']="";
				$data['create_date']=getCurrTime();
				$data['sync_flg']="1";
				$data['status_flg']="Y";
				
				$setting_flg = $model->data($data)->add();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}else{
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
								
				//}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $result;   

			break;
			
		}
		
	}
}