<?php
namespace Appapi\Controller;
//use Think\Controller;
use Think\Model;
use Common\Controller\AppapiController;
class PedoController extends AppapiController {
    public function info(){
        
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 1:
			$aged_id   = (int)I('get.agedId');
			$start_date= toTime(I('get.startDate'));
			$end_date  = toTime(I('get.endDate'));
			$type      = (int)I('get.type');
			$device_sn = M('Elderly_info')->where("id=".$aged_id)->getField('device_sn');
			//$strsql="select FROM_UNIXTIME(tpd.dt, '%H') as hh, tpd.* from any_protocol_data as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpdDEV_INFO_RPT.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<=$end_date"; //type=1,by hour of day
			//$strsql="select FROM_UNIXTIME(tpd.dt, '%w') as hh, tpd.* from any_protocol_data as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpd.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<=$end_date"; //type=2,by day of week
			//$strsql="select FROM_UNIXTIME(tpd.dt, '%d') as hh, tpd.* from any_protocol_data as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpd.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<=$end_date"; //type=3,by day of month
			$table = C('DB_PREFIX').'protocol_data';
			$model = new Model();
			switch($type){
				case 1: //type=1,by hour of day
					$end_date = strtotime('+1 day',$start_date);
					$strsql="select FROM_UNIXTIME(tpd.dt, '%H') as hh, tpd.* from $table as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpd.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<$end_date";
					$rs = $model->query($strsql);
					//var_dump($model->getLastSql());
					//var_dump($rs);
					//$p=date('h');
					for($i=0;$i<24;$i++){
						$dt[$i]=0;
					}
					foreach($rs as $k=>$v){
						$protocol = unserialize($v['payload']);
						$dt[(int)$v['hh']]=(int)$dt[(int)$v['hh']] + (int)$protocol['STEP_COUNT'];
					}
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']['step']=''.implode(',',$dt).'';
					$returnData['info']['date']=toDate($start_date,'Y-m-d');
					$returnData['info']['type']=$type;
					$returnData['info']['count']=count($dt);
				break;
				case 2: //type=2,by day of week
					$week_day = date('w',$start_date);
					$start_date=strtotime('-'.$week_day.' day',$start_date);
					$end_date = strtotime('+7 day',$start_date);
					$strsql="select FROM_UNIXTIME(tpd.dt, '%w') as week, tpd.* from $table as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpd.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<$end_date";
					$rs = $model->query($strsql);
					//echo ($model->getLastSql());
					//var_dump($rs);
					for($i=0;$i<7;$i++){
						$dt[$i]=0;
						$dt2[$i]=toDate(strtotime('+'.$i.' day',$start_date),'Y-m-d');
					}
					foreach($rs as $k=>$v){
						$protocol = unserialize($v['payload']);
						$dt[$v['week']]=(int)$dt[$v['week']] + (int)$protocol['STEP_COUNT'];
					}
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']['step']=''.implode(',',$dt).'';
					$returnData['info']['date']=implode(',',$dt2);
					$returnData['info']['type']=$type;
					$returnData['info']['count']=count($dt);
				break;
				case 3: //type=3,by day of month
					$start_date = toTime(toDate($start_date,'Y-m').'-01');
					$end_date = strtotime('+1 month',$start_date);
					$days = ((int)$end_date - (int)$start_date)/(24*3600);
					$strsql="select FROM_UNIXTIME(tpd.dt, '%d') as hh, tpd.* from $table as tpd where tpd.cmd_name='DEV_INFO_RPT' and tpd.device_sn='$device_sn' and tpd.dt>=$start_date and tpd.dt<$end_date";
					$rs = $model->query($strsql);
					//var_dump($model->getLastSql());
					//var_dump(toDate($end_date));
					/* by day of month
					for($i=1;$i<=$days;$i++){
						$dt[$i]=0;
					}
					foreach($rs as $k=>$v){
						$protocol = unserialize($v['payload']);
						$dt[(int)$v['hh']]=(int)$dt[(int)$v['hh']] + (int)$protocol['STEP_COUNT'];
					}
					*/
					//by week of month
					//for($i=1;$i<=ceil($days/7);$i++){
					for($i=1;$i<=ceil($days/7);$i++){
						$dt[$i]=0;
						$dt2[$i]=toDate(strtotime('+'.(($i-1)*7).' day',$start_date),'Y-m-d');
					}
					foreach($rs as $k=>$v){
						$protocol = unserialize($v['payload']);
						$dt[ceil((int)$v['hh']/7)]=(int)$dt[ceil((int)$v['hh']/7)] + (int)$protocol['STEP_COUNT'];
					}
					$returnData['error']=0;
					$returnData['reason']='Success';
					$returnData['info']['step']=''.implode(',',$dt).'';
					$returnData['info']['date']=implode(',',$dt2);//toDate($start_date,'Y-m-d');
					$returnData['info']['type']=$type;
					$returnData['info']['count']=count($dt);
				break;
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
