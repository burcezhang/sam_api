<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class HistoryController extends AppapiController {
    public function index()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 1:				
				//C('SHOW_PAGE_TRACE',true);
				$model = M('Protocol_data');
				$elderly_id = (int)I('get.id');
				$start_dt = toTime(str_replace('_',' ',I('get.start')));
				$end_dt = toTime(str_replace('_',' ',I('get.end')));
				$device_sn = M('Elderly_info')->where('id='.$elderly_id)->getField('device_sn');
				$datainfo = $model->where("device_sn='$device_sn' and cmd_name='POSITION_TRACKING_ALERT_RPT' and dt>$start_dt and dt<$end_dt")->order('id asc')->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx=0;
					foreach($datainfo as $row){
						$payload = unserialize($row['payload']);
						$returnData['info'][$inx]['id']=(int)$row['id'];
						$returnData['info'][$inx]['Lat']=$payload['LOC_INFO']['LATITUDE'];
						$returnData['info'][$inx]['Lon']=$payload['LOC_INFO']['LONGITUDE'];
						$returnData['info'][$inx]['GPSTime']=toDate($payload['LOC_INFO']['TIME_STAMP']);
						$inx++;
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