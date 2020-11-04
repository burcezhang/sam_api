<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;
include('ThinkPHP/Library/Plugins/Menu.class.php');

class BeaconController extends CommonController {
    public function index(){
		$userinfo = cookie('userinfo');
		$model = M('Beacon');

		if($userinfo['loc_id'] == 1){
			$beacon=$model->select();
		}else{
			$beacon=$model->where('loc_id=\''.$userinfo['loc_id'].'\'')->select();
		}
		if(!$beacon){
			$beacon=array(array('beaconID'=>1));
		}
		
		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
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

			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		$this->assign('default_map_dj',$default_map_dj);

		$this->assign('beacon',$beacon);
		$this->assign('beacon_num',count($beacon)+1);

		$this->display();
	}
    public function save(){
		$userinfo = cookie('userinfo');
		$model = M('Beacon');
		$model->where('loc_id=\''.$userinfo['loc_id'].'\'')->Delete();
		$beacons = array_values(I('post.beacons'));
		$oData=array();
		foreach($beacons as $key => $beacon){
			$beacon['beaconID']=$beacon['beaconID'];
			$beacon['loc_id']=intval($userinfo['loc_id']);
			$flg = $model->data($beacon)->add();
			if((int)$flg==0 ){
				$model->rollback();
				$oData['error']=1;
				$oData['info']='Fail';
				break;
			}else{
				$model->commit();
			}
		}
		$oData['error']=0;
		$oData['info']='Success';	
		$this->ajaxReturn($oData,'JSON');
	}
    public function postion(){
		$userinfo = cookie('userinfo');
		$model = M('Beacon');
		$datalist=$model->where('loc_id=\''.$userinfo['loc_id'].'\'')->select();
		
		if($datalist){
			//通过经纬度定位Beacon
			$bname=array();
			$beaconlist=array();
			foreach($datalist as $k=>$Rs){
				$beaconlist[$Rs['beaconID']]=$Rs;
			}
			//最后一次订位信息
			
			$M = M();
			$arr = $M->query("SELECT * FROM (SELECT a.id,FROM_UNIXTIME(a.dt,'%H:%i %d/%m') AS dt,a.cmd1,a.device_sn,a.payload,a.cmd_name
				FROM any_protocol_data a left JOIN any_device_mas b ON a.device_sn = b.device_sn 
				WHERE a.payload like 'a%LOC_INFO%' ORDER BY a.dt DESC ) as inv GROUP BY device_sn");
 
			$location=array();
			foreach($arr as $k=>$Rs){
				$tmp=unserialize($Rs['payload']);
				$location[$Rs['device_sn']]['dt']=$Rs['dt'];
				$location[$Rs['device_sn']]['cmd']=$Rs['cmd_name'];
				$location[$Rs['device_sn']]['LATITUDE']=round($tmp['LOC_INFO']['LATITUDE'],8);
				$location[$Rs['device_sn']]['LONGITUDE']=round($tmp['LOC_INFO']['LONGITUDE'],8);
				if($Rs['cmd_name'] =='IN_HOME_ALERT_RPT'){
					$location[$Rs['device_sn']]['BNAME']=$tmp['BEACON_NAME'];
				}

				unset($logData);
				$logData['notes']['dt']=$Rs['dt'];
				$logData['notes']['bname']=$tmp['BEACON_NAME'];
				$logData['act_type']=$Rs['cmd_name'];
				$logData['module']=$Rs['device_sn'];
				$logData['user_id']=$Rs['id'];
				$logData['notes']=serialize($logData['notes']);
				saveLog($logData);
				
			}
			
			$arr= M('Elderly_info')->alias('a')->field('a.roomid,a.elderly_name,a.photo_src,a.sex,a.address,FROM_UNIXTIME(a.barthday,\'%Y\') AS barthday,a.home_tel,a.phone,a.device_sn')
					->join(C('DB_PREFIX').'device_mas b ON a.device_sn = b.device_sn','left')
					->where('b.loc_id=\''.$userinfo['loc_id'].'\' and b.status_flg=\'S\'')
					->select();
			$agedlist=array();
			
			foreach($arr as $k=>$Rs){
				$Rs['barthday']=!empty($Rs['barthday'])?(date("Y")-$Rs['barthday']):'NULL';
				$Rs['LOC_INFO']=$location[$Rs['device_sn']]?$location[$Rs['device_sn']]:NULL;
				$Rs['lastinfo']='outside';
				if($Rs['LOC_INFO']['cmd']=='IN_HOME_ALERT_RPT'){//在家
					$Rs['lastinfo']=$location[$Rs['device_sn']]['BNAME'];
				}
				$agedlist[$Rs['roomid']][]=$Rs;
			}
			
			//计算每个Beacon的人员数量
			foreach($datalist as $k=>$Rs){
				$datalist[$k]['count']=count($agedlist[$Rs['beaconID']]);
			}
			
			$beacon = $datalist[0]['beaconID'];
			//$name = $datalist[0]['name'];
			$lon = $datalist[0]['lon'];
			$lat = $datalist[0]['lat'];
			
			
			$this->assign('lon',$lon);
			$this->assign('lat',$lat);
			$this->assign('beacon',$beacon);
			$this->assign('agedlist',$agedlist[$beacon]);
			$this->assign('agedjs',json_encode($agedlist));
			$this->assign('beaconlist',json_encode($beaconlist));
			$this->assign('datalist',$datalist);
		}

		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
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

			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		$this->assign('default_map_dj',$default_map_dj);

		$this->display();
	}
    public function outside(){
		$userinfo = cookie('userinfo');
		$M = M();
		$arr = $M->query("SELECT * FROM (SELECT a.id,FROM_UNIXTIME(a.dt,'%H:%i %d/%m') AS dt,a.cmd1,a.device_sn,a.payload,a.cmd_name
				FROM any_protocol_data a left JOIN any_device_mas b ON a.device_sn = b.device_sn 
				WHERE a.payload like 'a%LOC_INFO%' ORDER BY a.dt DESC ) as inv GROUP BY device_sn");
				
		$location=array();
		$elderly=array();
		foreach($arr as $k=>$Rs){
			$tmp=unserialize($Rs['payload']);
			if($Rs['cmd_name']=='POSITION_TRACKING_ALERT_RPT'){
				$location[$Rs['device_sn']]['dt']=$Rs['dt'];
				$location[$Rs['device_sn']]['cmd']=$Rs['cmd_name'];
				$location[$Rs['device_sn']]['LATITUDE']=round($tmp['LOC_INFO']['LATITUDE'],8); 
				$location[$Rs['device_sn']]['LONGITUDE']=round($tmp['LOC_INFO']['LONGITUDE'],8);
				$location[$Rs['device_sn']]['FIX_TYPE']=$tmp['LOC_INFO']['FIX_TYPE'];
			}
		}
		$elderly=array_keys($location);
		//老人信息
		$arr= M('Elderly_info')->field('roomid,elderly_name,photo_src,sex,address,FROM_UNIXTIME(barthday,\'%Y\') AS barthday,home_tel,phone,device_sn')
				->where('device_sn in(\''.implode('\',\'',$elderly).'\')')->select();
		$LON=C('BAIDU_INIT_LON');
		$LAT=C('BAIDU_INIT_LAT');
		$agedlist=array();
		foreach($arr as $Rs){
			$Rs['barthday']=!empty($Rs['barthday'])?(date("Y")-$Rs['barthday']):'NULL';
			$Rs['LATITUDE']=$location[$Rs['device_sn']]['LATITUDE'];
			$Rs['LONGITUDE']=$location[$Rs['device_sn']]['LONGITUDE'];
			$Rs['fix_type']=$location[$Rs['device_sn']]['FIX_TYPE'];
			$Rs['dt']=$location[$Rs['device_sn']]['dt'];
			$LAT=!empty($Rs['LATITUDE'])?$Rs['LATITUDE']:$LAT;
			$LON=!empty($Rs['LONGITUDE'])?$Rs['LONGITUDE']:$LON;
			$agedlist[]=$Rs;
		}
		$this->assign('agedlist',$agedlist);
		$this->assign('agedjs',json_encode($agedlist));
		$this->assign('LON',$LON);
		$this->assign('LAT',$LAT);

		$default_map_dj = '0';
		$varinfo = M('Sys_var')->where("var_group='defvalue' and var_name='defvalue'")->find();
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

			unset($varinfo['value1']);
			unset($varinfo['value2']);
			unset($varinfo['value3']);
		}
		//var_dump($varinfo);
		$this->assign('varinfo',$varinfo);
		$this->assign('default_map_dj',$default_map_dj);

		$this->display();
	}


}
