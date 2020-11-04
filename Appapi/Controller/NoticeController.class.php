<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class NoticeController extends AppapiController {
    public function index()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 1:  //get list
				//C('SHOW_PAGE_TRACE',true);
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$holdId = (int)I('get.holdId');
				$agedId = (int)I('get.agedId');
				$type = (int)I('get.Type');
				if(!$type) $type = (int)I('get.type');
				$key = I('get.key');
				$swhere = "status_flg='Y' and aged_id=$agedId and notice_type='$type'";
				if($holdId) $swhere.=" and  hold_id=$holdId";
				if($key) $swhere .= $swhere." and title like '%$key%'";
				$model = M('Notice');
				$count = $model->where($swhere)->count();
				$tpagetotal = ceil($count/$psize);				
				$datainfo = $model->where($swhere)->order("id desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['NoticeID']=$row['id'];
						$returnData['info'][$inx]['Title']=$row['title'];
						$returnData['info'][$inx]['CreateTime']=toDate($row['create_time']);
						$returnData['info'][$inx]['Content']=$row['content'];
						$returnData['info'][$inx]['UserName']=$row['user_name'];
						$returnData['info'][$inx]['Type']=$row['notice_type'];
						$returnData['info'][$inx]['HoldName']='Default';
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
			case 2:  //get remind alert
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$agedId = (int)I('get.agedId');
				$key = I('get.key');
				//$device_sn = M('Elderly_info')->where("id=".$agedId)->getField('device_sn');
				//$swhere = "status_flg in('F','Y') and device_sn='$device_sn'";
				$swhere = "status_flg in('Y') and aged_id=$agedId";
				if($key)$swhere = $swhere." and remind_title  like '%$key%'";
				$model = M('Remind_alert');
				$count = $model->where($swhere)->count();
				$tpagetotal = ceil($count/$psize);				
				$datainfo = $model->where($swhere)->order("id desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['RemindID']=$row['id'];
						$returnData['info'][$inx]['Title']=$row['remind_title'];
						$returnData['info'][$inx]['RemindTimes']=toDate($row['remind_time'],'H:i:s');
						$returnData['info'][$inx]['RemindDate']=toDate($row['start_date'],'Y-m-d');
						$returnData['info'][$inx]['Repeat']=$row['repeat'];
						$returnData['info'][$inx]['BeforRemind']=$row['befor_remind'];
						$returnData['info'][$inx]['State']=($row['status_flg']=='F'?1:0);
						$returnData['info'][$inx]['Tag']=$row['tag'];
						$returnData['info'][$inx]['CreateTime']=toDate($row['create_time']);
						$returnData['info'][$inx]['Remark']=$row['note'];
						$returnData['info'][$inx]['RemindFail']=(int)$row['end_date'];
						$returnData['info'][$inx]['ReserveID']=$row['remind_no'];
						$returnData['info'][$inx]['Type']=$row['bellring'];
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
			case 3:  //add alert				
				$model = M('Remind_alert');
				$checkDouble=$model->where("status_flg='Y' and aged_id='".I('get.agedId')."' and remind_no=".(int)I('get.ReserveID'))->find();
				if($checkDouble){
					$returnData['error']=1;
					$returnData['reason']='ReserveID Double';
				}else{
				//$data['id']=(int)I('get.id');
				$data['remind_title']=I('get.title');
				$data['aged_id']=(int)I('get.agedId');
				$data['user_id']=(int)I('get.userId');
				$data['hold_id']=(int)I('get.holdId');
				$data['remind_time']=toTime(I('get.remindTimes'));
				$data['repeat']=I('get.repeat');
				$data['befor_remind']=(int)I('get.beforRemind');
				$data['tag']=I('get.tag');
				$data['note']=I('get.remark');
				$data['start_date']=toTime(I('get.remindDate'));
				$data['end_date']=(int)I('get.remindFail');
				$data['bellring']=(int)I('get.type');
				$data['device_sn']=I('get.objectId');
				$data['remind_no']=(int)I('get.ReserveID');
				$data['create_time']=getCurrTime();
				$data['sync_flg']='1';
				$data['status_flg']='Y';
				$tmpData['TIME1']=toTime(I('get.remindTimes'));
				$data['remark']=serialize($tmpData);
				
				$data['end_date2']=strtotime('+'.(int)I('get.remindFail').'day',toTime(I('get.remindDate')));
				$data['repeat_week']=getWeekString((int)I('get.repeat'));
				$data['last_gen_alert']=0;
				
				$setting_flg = $model->data($data)->add();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 4:  //update alert				
				$model = M('Remind_alert');
				$data['id']=(int)I('get.remindId');
				$data['remind_title']=I('get.title');
				//$data['aged_id']=(int)I('get.agedId');
				//$data['user_id']=(int)I('get.userId');
				//$data['hold_id']=(int)I('get.holdId');
				$data['remind_time']=toTime(I('get.remindTimes'));
				$data['repeat']=I('get.repeat');
				$data['befor_remind']=(int)I('get.beforRemind');
				if(I('get.tag'))$data['tag']=I('get.tag');
				if(I('get.remark'))$data['note']=I('get.remark');
				//$data['start_date']=getCurrTime();
				$data['start_date']=toTime(I('get.remindDate'));
				$data['end_date']=(int)I('get.remindFail');
				$data['bellring']=(int)I('get.type');
				$data['device_sn']=I('get.objectId');
				$data['remind_no']=(int)I('get.ReserveID');
				$data['create_time']=getCurrTime();
				$data['sync_flg']='2';
				//$data['status_flg']='Y';
				$tmpData['TIME1']=toTime(I('get.remindTimes'));
				$data['remark']=serialize($tmpData);
				
				$data['end_date2']=strtotime('+'.(int)I('get.remindFail').'day',toTime(I('get.remindDate')));
				$data['repeat_week']=getWeekString((int)I('get.repeat'));
				$data['last_gen_alert']=0;
				
				//把未处理的预约告警先取消，事后自动重新生成
				$dataAlert['status_flg']='C';
				M('Remind_alert')->where("status_flg in ('A','W') and alert_type='REMIND_ALERT' and voice_src='".(int)I('get.remindId')."'")->save($dataAlert);
				
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
				echo $callback."($result)";   

			break;
			case 5: //delete info
				$model = M('Remind_alert');
				$data['id']=(int)I('get.remindId');
				$data['status_flg']='D';
				$data['sync_flg']='3';
				$setting_flg = $model->data($data)->save();
				if($setting_flg){
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
				echo $callback."($result)";   

			break;
			case 6:  //get pill list
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$agedId = (int)I('get.agedId');
				$key = I('get.key');
				//$device_sn = M('Elderly_info')->where("id=".$agedId)->getField('device_sn');
				//$swhere = "status_flg in('F','Y') and device_sn='$device_sn'";
				$swhere = "status_flg in('Y') and aged_id=$agedId";
				if($key)$swhere = $swhere." and pill_name  like '%$key%'";
				$model = M('Pill_alert');
				$count = $model->where($swhere)->count();
				$tpagetotal = ceil($count/$psize);				
				$datainfo = $model->where($swhere)->order("id desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['RecID']=$row['id'];
						$returnData['info'][$inx]['Title']=$row['pill_name'];
						$returnData['info'][$inx]['Notes']=$row['notes'];
						$returnData['info'][$inx]['FoodInstructions']=$row['food_instructions'];
						$returnData['info'][$inx]['StartTime']=toDate($row['start_time']);
						$returnData['info'][$inx]['EndTime']=toDate($row['end_time']);
						$returnData['info'][$inx]['TotalTime']=floor(($row['end_time']-$row['start_time'])/(24*60*60));
						$returnData['info'][$inx]['RemainTime']=floor(($row['end_time']-getCurrTime())/(24*60*60));
						$returnData['info'][$inx]['AdvancedDate']=$row['advanced_date'];
						$returnData['info'][$inx]['FoodTime']=$row['food_time'];
						$returnData['info'][$inx]['AdvancedFoodTime']=$row['advanced_food_time'];
						$returnData['info'][$inx]['CreateTime']=toDate($row['create_time']);
						$returnData['info'][$inx]['Remark']=$row['note'];
						$returnData['info'][$inx]['FoodTimeInfo']=$row['food_time_info'];
						$returnData['info'][$inx]['Period']=$row['repeat'];
						$returnData['info'][$inx]['BellRing']=$row['bellring'];
						$returnData['info'][$inx]['PillID']=$row['pill_no'];
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
			case 7:  //add pill alert
				$model = M('Pill_alert');
				$checkDouble=$model->where("status_flg='Y' and aged_id='".I('get.agedId')."' and pill_no=".(int)I('get.PillID'))->find();
				if($checkDouble){
					$returnData['error']=1;
					$returnData['reason']='PillID Double';
				}else{
				//$data['id']=(int)I('get.id');
				$data['pill_name']=I('get.title');
				$data['aged_id']=(int)I('get.agedId');
				$data['user_id']=(int)I('get.userId');
				$data['hold_id']=(int)I('get.holdId');
				$data['food_instructions']=I('get.foodInstructions');
				$data['repeat']=I('get.period');
				$data['notes']=I('get.notes');
				$data['note']=I('get.remark');
				$data['start_time']=toTime(I('get.startTime'));
				$data['end_time']=toTime(I('get.endTime'));
				$data['advanced_date']=I('get.advancedDate');
				$data['food_time']=I('get.foodTime');
				$data['advanced_food_time']=I('get.advancedFoodTime');
				$data['food_time_info']=I('get.foodTimeInfo');				
				$data['bellring']=(int)I('get.bellRing');
				$data['device_sn']=M('Elderly_info')->where("id=".(int)I('get.agedId'))->getField('device_sn');
				$data['pill_no']=(int)I('get.PillID');
				$data['create_time']=getCurrTime();
				$data['sync_flg']='1';
				$data['status_flg']='Y';
				$tmpData=explode(';',I('get.foodTimeInfo'));
				$tmpData2=array();
				foreach($tmpData as $k=>$v){
					if($v){
						unset($tmp_v);
						$tmp_v = explode(',',$v);
						$tmpData2[] = toTime($tmp_v[0]);
					}
				}
				$data['remark']=serialize($tmpData2);
				
				$setting_flg = $model->data($data)->add();
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Setting Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				
				}
				$jsonData['data']=$returnData;
				//echo json_encode($jsonData);
				$result=json_encode($jsonData);
				$callback=I('get.callback');  
				echo $callback."($result)";   

			break;
			case 8:  //update pill alert
				$model = M('Pill_alert');
				
				$data['id']=(int)I('get.recId');
				$data['pill_name']=I('get.title');
				//$data['aged_id']=(int)I('get.agedId');
				//$data['user_id']=(int)I('get.userId');
				//$data['hold_id']=(int)I('get.holdId');
				$data['food_instructions']=I('get.foodInstructions');
				$data['repeat']=I('get.period');
				$data['notes']=I('get.notes');
				$data['note']=I('get.remark');
				$data['start_time']=toTime(I('get.startTime'));
				$data['end_time']=toTime(I('get.endTime'));
				$data['advanced_date']=I('get.advancedDate');
				$data['food_time']=I('get.foodTime');
				$data['advanced_food_time']=I('get.advancedFoodTime');
				$data['food_time_info']=I('get.foodTimeInfo');				
				$data['bellring']=(int)I('get.bellRing');
				//$data['device_sn']=M('Elderly_info')->where("id=".(int)I('get.agedId'))->getField('device_sn');
				$data['pill_no']=(int)I('get.PillID');
				//$data['create_time']=getCurrTime();
				$data['sync_flg']='2';
				//$data['status_flg']='Y';
				$tmpData=explode(';',I('get.foodTimeInfo'));
				$tmpData2=array();
				foreach($tmpData as $k=>$v){
					if($v){
						unset($tmp_v);
						$tmp_v = explode(',',$v);
						$tmpData2[] = toTime($tmp_v[0]);
					}
				}
				$data['remark']=serialize($tmpData2);
				
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
				echo $callback."($result)";   

			break;
			case 9: //delete pill alert
				$model = M('Pill_alert');
				$data['id']=(int)I('get.recId');
				$data['status_flg']='D';
				$data['sync_flg']='3';
				$setting_flg = $model->data($data)->save();
				if($setting_flg){
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
				echo $callback."($result)";   

			break;
		}
		
	}
}