<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class AdminController extends CommonController {

	public function index(){
        //C('SHOW_PAGE_TRACE',true);
		$userinfo = cookie('userinfo');
		$model = M('Loc_mas');
		$db_prefix = C('DB_PREFIX');
		$curr_level = 0;

		$locId = (int)I('get.loc');
		if(!$locId) $locId=(int)$userinfo['loc_id'];

		$count      = $model->where("parent_id=$locId ")->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist2 = $model->where("parent_id=$locId ") -> order("id asc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist2 as $k=>$v){
			$datalist2[$k]['notes']=unserialize($v['notes']);
		}
		//var_dump($datalist);
		$this->assign("loc",$locId);
		$this->assign("parent_str",I('get.parent_str'));
		$this->assign("datalist2",$datalist2);
		$this->assign('page',$show);

		$locinfo = $model->find($locId);
		$this->assign('locinfo',$locinfo);

		$this->display();
	}

	public function get_loc_info(){
		$ac = (int)I('get.ac');
		$loc = (int)I('get.loc');
		$model = M('Loc_mas');
		$parment = $model->find($loc);

		if($ac == 2){
			$locinfo = $model->find((int)I('get.locid'));
			if($locinfo['notes']){
				$locinfo['notes']=unserialize($locinfo['notes']);
			}else{
				$locinfo['notes']['address'][3]=' ';
				$locinfo['notes']['address'][2]=' ';
				$locinfo['notes']['address'][1]=' ';
				$locinfo['notes']['address'][0]=' ';
			}
			$locinfo['create_date']=toDate($locinfo['create_date']);
		}else{
			$locinf=array();
			$locinfo['notes']['address'][3]=' ';
			$locinfo['notes']['address'][2]=' ';
			$locinfo['notes']['address'][1]=' ';
			$locinfo['notes']['address'][0]=' ';

		}
		$this->assign('parent_str',$parment['parent_str']);
		$this->assign('parent_name',$parment['loc_name']);
		$this->assign('group_name',$locinfo['group']);		
				
		$this->assign('locId',$loc);

		$this->assign('Dat',$locinfo);

		$this->display();
	}

	public function locadd(){
		$userinfo = cookie('userinfo');
		$model = M('Loc_mas');
		
		if(IS_POST){
			$photo=I('post.photo');
			if(!empty($photo)){
				$photo=uploadData($photo);
				if($photo[0]){
					$photo=$photo[1];
				}else{
					$returnData['error']=1;
					$returnData['reason']='Photo Upload Failed'.$photo[1];
					$this->ajaxReturn($returnData,'JSON');
					return;
				}
			}
		}
		if((int)I('post.id')){
			$model->Create();
			unset($model->parent_id);
			unset($model->parent_str);
			unset($model->status_flg);
			$data=I('post.notes');
			if(!empty($photo)) $data['photo_src']=$photo;
			$model->group=I('post.group_name');
			$model->create_date=(I('post.create_date')==''?getCurrTime():toTime(I('post.create_date')));
			$model->notes=serialize($data);
			
			$locid = $model->save();
			if($locid){
				$oData['error']=0;
				$oData['info']='Success';
				
				//***Log***
				$logData['notes']['userinfo']['account']=$userinfo['account'];
				$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
				$logData['notes']['agedinfo']['elderly_name']='';
				$logData['notes']['agedinfo']['nick_name']='';
				$logData['notes']['agedinfo']['target_name']=I('post.loc_name');
				$logData['notes']['result']='Saved Successfully';
				$logData['notes']['note']='Change Local Information-'.I('post.loc_name');
				$logData['user_id']=(int)$userinfo['id'];
				$logData['act_type']='locadd';
				$logData['module']='Admin';
				$logData['act_obj']=(int)I('post.id');
				$logData['notes']=serialize($logData['notes']);
				saveLog($logData);

			}else{
				$oData['error']=1;
				$oData['info']='Fail';
			}
			$this->ajaxReturn($oData,'JSON');
		}else{
			$check_double = $model->where("loc_name='".I('post.loc_name')."")->find();
			if($check_double){
				$oData['error']=2;
				$oData['info']='Data Double';
				$this->ajaxReturn($oData,'JSON');
				exit;
			}

			$model->Create();
			$model->parent_str=(substr(I('post.parent_str'),0,1)==',')?substr(I('post.parent_str'),1):I('post.parent_str');
			$model->create_date=(I('post.create_date')==''?getCurrTime():toTime(I('post.create_date')));
			$model->group=I('post.group_name');
			$data=I('post.notes');
			if(!empty($photo)) $data['photo_src']=$photo;
			$model->notes=serialize($data);
			$locid = $model->add();

			if($locid){
				$oData['error']=0;
				$oData['info']='Success';

				//***Log***
				$logData['notes']['userinfo']['account']=$userinfo['account'];
				$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
				$logData['notes']['agedinfo']['elderly_name']='';
				$logData['notes']['agedinfo']['nick_name']='';
				$logData['notes']['agedinfo']['target_name']=I('post.loc_name');
				$logData['notes']['result']='Saved Successfully';
				$logData['notes']['note']='Change Local Information';
				$logData['user_id']=(int)$userinfo['id'];
				$logData['act_type']='locadd';
				$logData['module']='Admin';
				$logData['act_obj']=$locid;
				$logData['notes']=serialize($logData['notes']);
				saveLog($logData);

			}else{
				$oData['error']=1;
				$oData['info']='Fail';
			}
			$this->ajaxReturn($oData,'JSON');
		}
	}

	public function defvalue(){
		$userinfo = cookie('userinfo');
		$model = M('Sys_var');
		if(IS_POST){
			$old_var = array();
			if((int)I('post.id')){
				$old=$model->where('id='.I('post.id'))->find();
				$old=unserialize($old['value1']);
			}

			//$elderly = M('Elderly_info');
			//$elderly->startTrans();

			$basedata['default_map']=(I('post.default_map'));
			$basedata['service_price']=(I('post.service_price'));

			$basedata['device_set1']=(I('post.device_set1'));
			$basedata['device_set2']=(I('post.device_set2'));
			$basedata['device_set3']=(I('post.device_set3'));

			$device_set4 = I('post.device_set4');
			$basedata['device_set4']=$device_set4;
			if($old['NO1']!=$device_set4['NO1']||$old['NO2']!=$device_set4['NO2']){
				$mArr=array();
				$mArr['NO1']=$device_set4['NO1'];
				$mArr['NO2']=$device_set4['NO2'];
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set4']=serialize($mArr);
				M('Elderly_info')->where('1=1')->save($elderly_data);
			}
			$device_set5 = I('post.device_set5');
			$basedata['device_set5']=$device_set5;
			if($old['device_set5']!=$device_set5){
				$mArr=array();
				$mArr['PORT']=$device_set5;
				$mArr['FLG']='wait_update';
				$elderly_data=array();
				$elderly_data['device_set5']=serialize($mArr);
				M('Elderly_info')->where('1=1')->save($elderly_data);
			}
			$device_set6 = I('post.device_set6');
			$basedata['device_set6']=($device_set6);
			if($old['device_set6']!=$device_set6){
				$mArr=array();
				$mArr['URL']=$device_set6;
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set6']=serialize($mArr);
				M('Elderly_info')->where('1=1')->save($elderly_data);
			}

			$device_set7 = I('post.device_set7');
			$device_set7['TIME1']=(int)$device_set7['TIME1'];
			$device_set7['TIME2']=(int)$device_set7['TIME2'];
			$device_set7['TIME3']=(int)$device_set7['TIME3'];
			$device_set7['FLG'] = 'wait_update';
			$basedata['device_set7']=($device_set7);

			if(serialize($basedata['device_set1'])!=serialize($old['device_set1'])){
				$basedata['device_set1']['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set1']=serialize($basedata['device_set1']);
				M('Elderly_info')->where('device_set2 = 1')->save($elderly_data);
			}
			if(serialize($basedata['device_set2'])!=serialize($old['device_set2'])){
				$basedata['device_set2']['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set1']=serialize($basedata['device_set2']);
				M('Elderly_info')->where('device_set2 = 0')->save($elderly_data);
			}

			$save_elderly_flg7=$save_elderly_flg8=$save_elderly_flg9=$save_elderly_flg10=$save_elderly_flg14=$save_elderly_flg15=$save_elderly_flg16=true;
			//更新用户默认数据设置
			if($old['device_set7']['TIME1']!=$device_set7['TIME1']
				||$old['device_set7']['TIME2']!=$device_set7['TIME2']
				||$old['device_set7']['TIME3']!=$device_set7['TIME3']){
				$mArr=array();
				$mArr['TIME1']=(int)$device_set7['TIME1'];
				$mArr['TIME2']=(int)$device_set7['TIME2'];
				$mArr['TIME3']=(int)$device_set7['TIME3'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set7']=serialize($mArr);
				M('Elderly_info')->where('device_set7 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$device_set8 = I('post.device_set8');
			$device_set8['TIME1']=toTime($device_set8['TIME1']);
			$device_set8['TIME2']=toTime($device_set8['TIME2']);
			$device_set8['TIME3']=toTime($device_set8['TIME3']);
			$device_set8['FLG'] = 'wait_update';
			$basedata['device_set8']=($device_set8);

			//更新用户默认数据设置
			if($old['device_set8']['TIME1']!=$device_set8['TIME1']
				||$old['device_set8']['TIME2']!=$device_set8['TIME2']
				||$old['device_set8']['TIME3']!=$device_set8['TIME3']){
				$mArr=array();
				$mArr['TIME1']=(int)$device_set8['TIME1'];
				$mArr['TIME2']=(int)$device_set8['TIME2'];
				$mArr['TIME3']=(int)$device_set8['TIME3'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set8']=serialize($mArr);
				M('Elderly_info')->where('device_set8 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$device_set9 = I('post.device_set9');
			$device_set9['TIME1']=(int)$device_set9['TIME1'];
			$device_set9['TIME2']=(int)$device_set9['TIME2'];
			$device_set9['TIME3']=(int)$device_set9['TIME3'];
			$device_set9['FLG'] = 'wait_update';
			$basedata['device_set9']=($device_set9);

			//更新用户默认数据设置
			if($old['device_set9']['TIME1']!=$device_set9['TIME1']
				||$old['device_set9']['TIME2']!=$device_set9['TIME2']
				||$old['device_set9']['TIME3']!=$device_set9['TIME3']){
				$mArr=array();
				$mArr['TIME1']=(int)$device_set9['TIME1'];
				$mArr['TIME2']=(int)$device_set9['TIME2'];
				$mArr['TIME3']=(int)$device_set9['TIME3'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set9']=serialize($mArr);
				M('Elderly_info')->where('device_set9 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$device_set10 = I('post.device_set10');
			$device_set10['LOW_T']=(int)$device_set10['LOW_T'];
			$device_set10['HIGHT_T']=(int)$device_set10['HIGHT_T'];
			$device_set10['METHOD']=(int)$device_set10['METHOD'];
			$device_set10['FLG'] = 'wait_update';
			$basedata['device_set10']=($device_set10);

			//更新用户默认数据设置
			if($old['device_set10']['LOW_T']!=$device_set10['LOW_T']
				||$old['device_set10']['HIGHT_T']!=$device_set10['HIGHT_T']
				||$old['device_set10']['METHOD']!=$device_set10['METHOD']){
				$mArr=array();
				$mArr['LOW_T']=(int)$device_set10['LOW_T'];
				$mArr['HIGHT_T']=(int)$device_set10['HIGHT_T'];
				$mArr['METHOD']=(int)$device_set10['METHOD'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set10']=serialize($mArr);
				M('Elderly_info')->where('device_set10 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$device_set14 = I('post.device_set14');
			$device_set14['TIME1']=(int)$device_set14['TIME1'];
			$device_set14['TIME2']=(int)$device_set14['TIME2'];
			$device_set14['TIME3']=(int)$device_set14['TIME3'];
			$device_set14['FLG'] = 'wait_update';
			$basedata['device_set14']=($device_set14);
			//更新用户默认数据设置
			if($old['device_set14']['TIME1']!=$device_set14['TIME1']
				||$old['device_set14']['TIME2']!=$device_set14['TIME2']
				||$old['device_set14']['TIME3']!=$device_set14['TIME3']){
				$mArr=array();
				$mArr['TIME1']=(int)$device_set14['TIME1'];
				$mArr['TIME2']=(int)$device_set14['TIME2'];
				$mArr['TIME3']=(int)$device_set14['TIME3'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set14']=serialize($mArr);
				M('Elderly_info')->where('device_set14 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$device_set15 = I('post.device_set15');
			$device_set15['arg1']= $device_set15['arg1'];
			$device_set15['FLG'] = 'wait_update';
			$basedata['device_set15']=($device_set15);
			//更新用户默认数据设置
			if($old['device_set15']['arg1']!=$device_set15['arg1']){
				$mArr=array();
				$mArr['arg1']=$device_set15['arg1'];
				$mArr['default_flg']='Y';
				$mArr['FLG'] = 'wait_update';
				$elderly_data=array();
				$elderly_data['device_set15']=serialize($mArr);
				M('Elderly_info')->where('device_set15 like\'%default_flg";s:1:"Y%\'')->save($elderly_data);
			}

			$data['id']=(int)I('post.id');
			$data['var_group']='defvalue';
			$data['var_name']='defvalue';
			$data['title']='Basic Values Setting';
			$data['value1']=serialize($basedata);
			$data['value2']='';
			$data['value3']='';
			$data['status_flg']='Y';
			//var_dump($data);exit;
			if((int)I('post.id')){
				$save_flg = $model->data($data)->save();
			}else{
				$save_flg = $model->data($data)->add();
			}

			if($save_flg){
				$oData['error']=0;
				$oData['info']='Success';

				//***Log***
				$logData['notes']['userinfo']['account']=$userinfo['account'];
				$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
				$logData['notes']['agedinfo']['elderly_name']='';
				$logData['notes']['agedinfo']['nick_name']='';
				$logData['notes']['agedinfo']['target_name']='';
				$logData['notes']['result']='Success';
				$logData['notes']['note']='default parameter settings';
				$logData['user_id']=(int)$userinfo['id'];
				$logData['act_type']='defvalue';
				$logData['module']='Admin';
				$logData['act_obj']=(int)I('post.id');
				$logData['notes']=serialize($logData['notes']);
				saveLog($logData);

			}else{
				$oData['error']=1;
				$oData['info']='Fail';
			}
			$this->ajaxReturn($oData,'JSON');


		}else{
			$varinfo = $model->where("var_group='defvalue' and var_name='defvalue'")->find();
			//var_dump($varinfo);
			if($varinfo){
				$varinfo['value1']=unserialize($varinfo['value1']);
				$varinfo['value2']=unserialize($varinfo['value2']);
				$varinfo['value3']=unserialize($varinfo['value3']);

				$varinfo['default_map']=$varinfo['value1']['default_map'];
				$varinfo['service_price']=$varinfo['value1']['service_price'];
				$varinfo['device_set1']=$varinfo['value1']['device_set1'];
				$varinfo['device_set2']=$varinfo['value1']['device_set2'];
				$varinfo['device_set3']=$varinfo['value1']['device_set3'];
				$varinfo['device_set7']=$varinfo['value1']['device_set7'];
				$varinfo['device_set8']=$varinfo['value1']['device_set8'];
				$varinfo['device_set9']=$varinfo['value1']['device_set9'];
				$varinfo['device_set10']=$varinfo['value1']['device_set10'];
				$varinfo['device_set14']=$varinfo['value1']['device_set14'];
				$varinfo['device_set15']=$varinfo['value1']['device_set15'];
				$varinfo['device_set4']=$varinfo['value1']['device_set4'];
				$varinfo['device_set5']=$varinfo['value1']['device_set5'];
				$varinfo['device_set6']=$varinfo['value1']['device_set6'];
				//print_r($varinfo['device_set1']);
				unset($varinfo['value1']);
				unset($varinfo['value2']);
				unset($varinfo['value3']);
			}
			//var_dump($varinfo);
			$this->assign('varinfo',$varinfo);
			$this->display();
		}
	}

	public function history(){
		$userinfo = cookie('userinfo');
		$model = M('Sys_log');

		$strwhere="module not in ('SMS','7X24CALL')";
		if((int)$userinfo['loc_id'] > 1){
			$user_ids = M('User_mas')->where("loc_id=".(int)$userinfo['loc_id'])->getField('id',true);
			$strwhere.=" and user_id in (".implode(',',$user_ids).")";
		}else{
			//$strwhere.=" ";
		}

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (act_obj = '$keyword' or user_id = '$keyword')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist as $k=>$v){
			$datalist[$k]['notes']=unserialize($v['notes']);
		}

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		$this->display();
	}

	public function gup(){
		$userinfo = cookie('userinfo');
		$model = M('User_mas');

		$strwhere = "";
		if((int)$userinfo['loc_id'] > 1){
			$strwhere .= " and (id=".(int)$userinfo['loc_id']." or parent_id=".(int)$userinfo['loc_id']." or parent_str like '%,".(int)$userinfo['loc_id'].",%')";
		}

		$loctree = M('Loc_mas')->field("id,loc_name,parent_id,parent_str,concat(parent_str,',',id) as bpath")->where($strwhere)->order('bpath')->select();
		$space_num = 0;
		foreach($loctree as $k=>$v){
			if((int)$v['id']<=1){
				$loctree[$k]['group_count']=M('User_mas')->where("user_type in ('WORKMAN','ADMINS') ")->count();
			}else{
				$loctree[$k]['group_count']=M('User_mas')->where("user_type in ('WORKMAN','ADMINS') and loc_id={$v['id']}")->count();
			}
			if(count($loctree)<=1){
				$loctree[$k]['count']=0;
			}else{
				$loctree[$k]['count']=count(explode(',',$v['bpath']));
			}

			if($space_num==0){
				$space_num = (int)$datalist[$k]['count']-1;
			}else if( (int)$datalist[$k]['count'] < $space_num && $space_num>0 ){
				$space_num = (int)$datalist[$k]['count']-1;
			}
		}
		$this->assign("loctree",$loctree);

		if($space_num<1) $space_num=1;
		$this->assign("space_num",$space_num);


		$locid = (int)I('get.loc');
		if(!$locid) $locid=(int)$userinfo['loc_id'];
		$this->assign("locid",$locid);
		$gid = (int)I('get.gid');
		if(!$gid) $gid=0;
		$this->assign("gid",$gid);
		if($locid<=1) $strwhere="user_type in ('WORKMAN','ADMINS') ";
		else $strwhere="user_type in ('WORKMAN','ADMINS') and loc_id=$locid";

		$locname =I('get.name');
		if(empty($locname)){
			reset($loctree);
			$vo=current($loctree);
			$locname=$vo['loc_name'].'('.$vo['group_count'].')';
		}else $locname.='('.I('get.count').')';
		$this->assign("locname",$locname);

		//$grouptree = M('Group_mas')->where($strwhere)->select();
		//$this->assign("grouptree",$grouptree);

		$gid = (int)I('get.gid');
		if($gid) $strwhere.=" and group_id=$gid";
		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (account like '%$keyword%' or true_name like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//$loclist = M('Loc_mas')->where('parent_id=0')->select();
		//$this->assign("loclist",$loclist);

		$strwhere = "";
		if((int)$userinfo['loc_id'] > 1){
			$strwhere .= "loc_id=".(int)$userinfo['loc_id'];
		}
		$group_list = M('Group_mas')->where($strwhere)->order("id asc")->select();
		$group=array();
		foreach($group_list as $dt){
			$group[$dt['id']]=$dt['group_name'];
		}
		$this->assign("group",$group);


		$this->display();

	}
	public function Guardian(){
		$userinfo = cookie('userinfo');
		$model = M('User_mas');

		$strwhere = "";
		if((int)$userinfo['loc_id'] > 1){
			$strwhere .= " and (id=".(int)$userinfo['loc_id']." or parent_id=".(int)$userinfo['loc_id']." or parent_str like '%,".(int)$userinfo['loc_id'].",%')";
		}

		$loctree = M('Loc_mas')->field("id,loc_name,parent_id,parent_str,concat(parent_str,',',id) as bpath")->where($strwhere)->order('bpath')->select();
		$space_num = 0;
		foreach($loctree as $k=>$v){
			if((int)$v['id']<=1){
				$loctree[$k]['group_count']=M('User_mas')->where("user_type = 'WEBUSER' ")->count();
			}else{
				$loctree[$k]['group_count']=M('User_mas')->where("user_type = 'WEBUSER' and loc_id in(".(int)$v['id'].")")->count();
			}
			if(count($loctree)<=1){
				$loctree[$k]['count']=0;
			}else{
				$loctree[$k]['count']=count(explode(',',$v['bpath']));
			}

			if($space_num==0){
				$space_num = (int)$datalist[$k]['count']-1;
			}else if( (int)$datalist[$k]['count'] < $space_num && $space_num>0 ){
				$space_num = (int)$datalist[$k]['count']-1;
			}
		}
		$this->assign("loctree",$loctree);

		if($space_num<1) $space_num=1;
		$this->assign("space_num",$space_num);


		$locid = (int)I('get.loc');
		if(!$locid) $locid=(int)$userinfo['loc_id'];
		$this->assign("locid",$locid);
		$gid = (int)I('get.gid');
		if(!$gid) $gid=0;
		$this->assign("gid",$gid);
		if($locid<=1) $strwhere="user_type = 'WEBUSER' ";
		else $strwhere="user_type = 'WEBUSER' and loc_id=$locid";
		$locname =I('get.name');
		if(empty($locname)){
			reset($loctree);
			$vo=current($loctree);
			$locname=$vo['loc_name'].'('.$vo['group_count'].')';
		}else $locname.='('.I('get.count').')';
		$this->assign("locname",$locname);

		//$grouptree = M('Group_mas')->where($strwhere)->select();
		//$this->assign("grouptree",$grouptree);

		$gid = (int)I('get.gid');
		if($gid) $strwhere.=" and group_id=$gid";
		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (account like '%$keyword%' or true_name like '%$keyword%')";
		$this->assign('keyword',$keyword);
		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();
		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		//$loclist = M('Loc_mas')->where('parent_id=0')->select();
		//$this->assign("loclist",$loclist);

		$strwhere = "";
		if((int)$userinfo['loc_id'] > 1){
			$strwhere .= " and loc_id=".(int)$userinfo['loc_id'];
		}
		$group_list = M('Group_mas')->where($strwhere)->order("id asc")->select();
		$group=array();
		foreach($group_list as $dt){
			$group[$dt['id']]=$dt['group_name'];
		}
		$this->assign("group",$group);


		$this->display();

	}

	public function get_loc_path(){
		$locid = (int)I('post.locid');
		$loclist = M('Loc_mas')->where("parent_id=$locid")->select();
		$oData['error']=0;
		$oData['loc']=count($loclist);
		$oData['info']=$loclist;

		$this->ajaxReturn($oData,'JSON');
	}

	public function check_account_double(){
		$account = I('post.account');
		$userid = (int)I('post.userid');
		$model = M('User_mas');
		$oData['error'] = (int)($model->where("account='$account' and id!=$userid ")->getField('id'));
		$this->ajaxReturn($oData,'JSON');
	}
	public function check_service_no_double(){
		$service_no = I('post.service_no');
		if($service_no){
			$userid = (int)I('post.userid');
			$model = M('User_mas');
			$oData['error'] = (int)($model->where("service_no='$service_no' and id!=$userid ")->getField('id'));
		}else{
			$oData['error'] = 0;
		}
		$this->ajaxReturn($oData,'JSON');
	}

	public function get_editinfo(){
		$userinfo = cookie('userinfo');
		$userid = (int)I('get.userid');
		$model = M('User_mas');
		if((int)$userinfo['loc_id'] > 1){
			$editinfo = $model->where("id=$userid and loc_id=".(int)$userinfo['loc_id'])->find();
		}else{
			$editinfo = $model->where("id=$userid")->find();
		}
		if($editinfo){
			$editinfo['home_address']=explode(',',$editinfo['home_address']);
			if(count($editinfo['home_address'])==1){
				$editinfo['home_address'][3]=$editinfo['home_address'][0];
				$editinfo['home_address'][2]=' ';
				$editinfo['home_address'][1]=' ';
				$editinfo['home_address'][0]=' ';
			}
			$sLocFull = getLocFullPathUp($editinfo['loc_id']);
			$editinfo['loc_full']=implode(',',(M('Loc_mas')->where("id in($sLocFull)")->getField('loc_name',true)));
		}else{
			$editinfo=array();
			$editinfo['home_address'][3]=' ';
			$editinfo['home_address'][2]=' ';
			$editinfo['home_address'][1]=' ';
			$editinfo['home_address'][0]=' ';
		}


		$loc = (int)I('get.loc');
		$parment = M('Loc_mas')->find($loc);
		$this->assign('parent_str',$parment['parent_str']);
		$this->assign('parent_name',$parment['loc_name']);
		$this->assign('locId',$loc);
			//$oData['error'] = 0;
		$groups = M('Group_mas')->where("loc_id=".(int)$loc)->select();
		$this->assign('group_list',$groups);
			//$oData['info'] = $editinfo;
		//}else{
			//$oData['error'] = 1;
			//$oData['info'] = 'Fail';
		//}
		$this->assign('userinfo',$userinfo);
		$this->assign('Dat',$editinfo);
		$this->display();
	}

	public function reg(){
		$userinfo = cookie('userinfo');
		$model = M('User_mas');

		$double_check = $model->where("account='".I('post.account')."' and id!=".(int)I('post.id'))->find();
		if($double_check){
			$oData['error']=2;
			$oData['info']='Account Double';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}
		if(I('post.service_no')){
			$double_check = $model->where("service_no='".I('post.service_no')."' and id!=".(int)I('post.id'))->find();
			if($double_check){
				$oData['error']=3;
				$oData['info']='Service No Double';
				$this->ajaxReturn($oData,'JSON');
				exit;
			}
		}
		$model->create();
		$model->home_address=implode(',',I('post.home_address'));
		if(I('post.is_service')){
			$model->is_service='Y';
		}else{
			$model->is_service='N';
		}

		//var_dump($model);exit;
		if((int)$userinfo['loc_id'] > 1){
			$model->loc_id=(int)$userinfo['loc_id']; //非总部仅能修改同区域账号信息
		}else{
			if((int)I('post.loc_id')){}else{$model->loc_id=(int)$userinfo['loc_id'];}
		}

		if(I('post.user_type')=='WEBUSER'){
			$model->group_id=0;
		}

		if((int)I('post.id')){
			//modify
			if(I('post.pwd')){
				$model->pwd=md5(I('post.pwd'));
			}else{
				unset($model->pwd);
			}

			//unset($model->group_id);
			unset($model->create_time);
			unset($model->login_count);
			unset($model->login_last_dt);
			unset($model->area_id);
			unset($model->area_str);
			unset($model->service_status_flg);
			unset($model->service_heart_dt);
			$user_flg = $model->save();

		}else{
			//new
			if(I('post.pwd')){
				$model->pwd=md5(I('post.pwd'));
			}else{
				$model->pwd=md5('000000');
			}
			//$model->group_id='1';
			$model->create_time=getCurrTime();
			$model->login_count='0';
			$model->login_last_dt='0';
			$model->area_id='1';
			$model->area_str='中国';
			$model->service_status_flg='0';
			$model->service_heart_dt='0';
			$user_flg = $model->add();
		}

		if((int)$user_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
				$logData['notes']['userinfo']['account']=$userinfo['account'];
				$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
				$logData['notes']['agedinfo']['elderly_name']='';
				$logData['notes']['agedinfo']['nick_name']='';
				$logData['notes']['agedinfo']['target_name']='';
				$logData['notes']['result']='Saved Successfully';
				$logData['notes']['note']=((int)I('post.id'))==0?'New users-'.I('post.account'):'Modify User-'.I('post.account');
				$logData['user_id']=(int)$userinfo['id'];
				$logData['act_type']='defvalue';
				$logData['module']='Admin';
				$logData['act_obj']=((int)I('post.id'))==0?(int)$user_flg:(int)I('post.id');
				$logData['notes']=serialize($logData['notes']);
				saveLog($logData);
		}

		$this->ajaxReturn($oData,'JSON');
	}


	public function smssetting(){
		$userinfo = cookie('userinfo');
		$model = M('Sms_template');

		$strwhere="(status_flg = 'Y')";

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (title like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$kname = I('get.kname');
		$ktitle = I('get.ktitle');

		if(!$kname) $kname = 'operator_finish';
		if($kname) $strwhere.=" and (sms_type = '$kname')";
		$this->assign('kname',$kname);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("sms_type desc,status_flg desc")->limit($Page->firstRow.','.$Page->listRows)->select();

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		if(!$ktitle){
			$vo=current(L('lb_sms_type_list'));
			$ktitle = $vo['title'];
		}
		$this->assign('ktitle',$ktitle);

		$this->assign('sms_type_list',L('lb_sms_type_list'));

		$this->display();
	}

	public function smssetting_show(){
		$model = M('Sms_template');
		$smsinfo = $model->find((int)I('post.id'));
		$smsinfo['error'] = 0;
		$this->ajaxReturn($smsinfo,'JSON');
		//$this->assign("smsinfo",$smsinfo);
		//$this->display();
	}
	public function smssetting_add(){
		$smsinfo["sms_type"]=I('post.kname');
		$smsinfo['error'] = 0;
		$this->ajaxReturn($smsinfo,'JSON');
		//$this->assign("smsinfo",$smsinfo);
	}

	public function smssetting_save(){
		$userinfo = cookie('userinfo');
		/*
		if( (int)$userinfo['loc_id'] > 1){
			$oData['error']=2;
			$oData['info']='Access Fail';
			$this->ajaxReturn($oData,'JSON');
		}
		*/
		$model = M('Sms_template');

		$model->create();
		$model->sms_type = strtolower(I('post.sms_type'));

		if((int)I('post.id')){
			$sms_flg = $model->save();
			if($sms_flg)$sms_flg=(int)I('post.id');
		}else{
			$sms_flg = $model->add();
		}

		if((int)$sms_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Saved Successfully';
			$logData['notes']['note']='Setting SMS templates-'.I('post.title');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssetting_save';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function smssetting_del(){
		$userinfo = cookie('userinfo');
		/*
		if( (int)$userinfo['loc_id'] > 1){
			$oData['error']=2;
			$oData['info']='Access Fail';
			$this->ajaxReturn($oData,'JSON');
		}
		*/
		$model = M('Sms_template');

		$data['id']=(int)I('post.sms_id');
		$data['status_flg']='N';
		$sms_flg = $model->save($data);

		$model->create();
		$model->sms_type = strtolower(I('post.sms_type'));

		if((int)$sms_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Saved Successfully';
			$logData['notes']['note']='Delete SMS templates-'.I('post.sms_id');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssetting_del';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}
	public function smssend(){
		$model = M('Sms_log');
		$strwhere="";

		$keyword = urldecode(I('get.keyword'));
		if($keyword)$strwhere.=" and (`title` like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$kname = I('get.kname');
		if(!$kname) $kname = 'history';
		$this->assign('kname',$kname);

		$count      = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,6);
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("sendtime desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);


		$this->assign('keyword',$keyword);
		$this->assign('sms_type_list',L('lb_sms_type_list'));

		$this->display();
	}
	public function smssend_show(){
		$model = M('Sms_template');
		$smsinfo = $model->find((int)I('get.id'));
		$this->assign("smsinfo",$smsinfo);
		$this->display();

		$this->ajaxReturn($smsinfo,'JSON');
	}
	public function smssend_del(){
		$userinfo = cookie('userinfo');
		$model = M('Sms_log');
		$sms_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		if($sms_flg === false){
			$oData['error']=1;
			$oData['info']='Fail';
		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Delete SMS-'.I('post.sms_id');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssend_del';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}
	//群发短信
	public function smssend_save(){
		$userinfo = cookie('userinfo');
		$phone = I('post.phone');
		if(empty($phone)){
			$oData['error']=1;
			$oData['info']='Fail';
			return;
		}
		$model = M('Sms_log');
		$phone = str_replace(' ', '', $phone);
		$phone = str_replace('，', ',', $phone);
		$phone=explode(',', $phone);
		$phone=array_unique($phone);//数组去重

		$model->create();
		$model->title = I('post.title');
		$model->phone = I('post.phone');
		$model->num = count($phone);
		$model->content = I('post.content');
		$model->sendtime = getCurrTime();
		$sms_flg = $model->add();

		if((int)$sms_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$ext=500;//每次取500
			if(count($phone)>$ext){
				$size = count($phone);
				$p=ceil($size/$ext);
				for($i=0;$i<$p-1;$i++){
					$username=array_slice($phone,$i*$ext,$ext);
					$smsData=array();
					$smsData['phone']=$username;
					$smsData['body']=I('post.content');
					$smsData['act_type']='Send SMS';
					$smsData['act_obj']='0';
					sendSMS($smsData);
				}
				$username=array_slice($info['username'],($p-1)*$ext, $size-($p-1)*$ext);
				$smsData=array();
				$smsData['phone']=$username;
				$smsData['body']=I('post.content');
				$smsData['act_type']='Send SMS';
				$smsData['act_obj']='0';
				sendSMS($smsData);
			}else{
				$smsData=array();
				$smsData['phone']=$phone;
				$smsData['body']=I('post.content');
				$smsData['act_type']='Send SMS';
				$smsData['act_obj']='0';
				sendSMS($smsData);
			}
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='send messages'.I('post.title');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssend_save';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}
		$this->ajaxReturn($oData,'JSON');
	}

	public function syssetting(){
		$setflg = I('get.set');
		if(!$setflg) $setflg = 'audio';
		$this->assign("set",$setflg);

		if($setflg == 'audio'){
			$this->assign('audio_list1',L('lb_audio_list'));
			$this->assign('audio_list2',L('lb_audio_list'));
			$this->assign('audio_list3',L('lb_audio_list'));
			$this->assign('audio_list4',L('lb_audio_list'));

			$model = M('Sys_var');
			$audio_set_run = $model->where("var_group='audio_set' and var_name='runing'")->getField('value1'); //echo $model->getLastSql();
			$audio_set_def = $model->where("var_group='audio_set' and var_name='default'")->getField('value1'); //echo $model->getLastSql();
			if($audio_set_def){
				$audio_set_def = unserialize($audio_set_def);
			}
			if($audio_set_run){
				$audio_set_run = unserialize($audio_set_run);
			}
			//var_dump($audio_set_run);var_dump($audio_set_def);exit;
			$this->assign("audio_set_def",$audio_set_def);
			$this->assign("audio_set_run",$audio_set_run);
		}

		$this->display();
	}

	public function audio_set(){
		$userinfo = cookie('userinfo');
		$model = M('Sys_var');

		$data['value1']=serialize(I('post.audio_set'));
		$set_flg = $model->where("var_group='audio_set' and var_name='runing'")->data($data)->save();

		if((int)$set_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Sound Setting';
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='audio_set';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function position(){
		$userinfo = cookie('userinfo');
		$this->assign('role_list',L('lb_role_list'));
		$model = M('Group_mas');
		$strwhere="";

		if((int)$userinfo['loc_id'] > 1){
			$strwhere .= " and (id=".(int)$userinfo['loc_id']." or parent_id=".(int)$userinfo['loc_id']." or parent_str like '%,".(int)$userinfo['loc_id'].",%')";
		}

		$loclist = M('Loc_mas')->field("id,loc_name,parent_id,parent_str,concat(parent_str,',',id) as bpath")->where($strwhere)->order('bpath')->select();
		$space_num = 0;

		foreach($loclist as $k=>$v){

			if((int)$v['id']<=1){
				$loclist[$k]['group_count']=M('Group_mas')->where("loc_id=1")->count();
			}else{
				$loclist[$k]['group_count']=M('Group_mas')->where("loc_id in(".(int)$v['id'].")")->count();
			}
			if(count($loclist)<=1){
				$loclist[$k]['count']=0;
			}else{
				$loclist[$k]['count']=count(explode(',',$v['bpath']));
			}

			if($space_num==0){
				$space_num = (int)$datalist[$k]['count']-1;
			}else if( (int)$datalist[$k]['count'] < $space_num && $space_num>0 ){
				$space_num = (int)$datalist[$k]['count']-1;
			}

		}

		$this->assign("loclist",$loclist);

		if($space_num<1) $space_num=1;
		$this->assign("space_num",$space_num);


		$locid = (int)I('get.loc');
		if(!$locid) $locid=(int)$userinfo['loc_id'];
		$this->assign("locid",$locid);
		$this->assign("userinfo",$userinfo);
		
		$strwhere="loc_id=$locid";

		$locname =I('get.name');
		if(empty($locname)){
			reset($loclist);
			$vo=current($loclist);
			$locname=$vo['loc_name'].'('.$vo['group_count'].')';
		}else $locname.='('.I('get.count').')';
		$this->assign("locname",$locname);

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (group_name like '%$keyword%')";
		$this->assign('keyword',$keyword);

		$count = $model->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->where($strwhere) -> order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();

		$this->assign("datalist",$datalist);
		$this->assign('page',$show);
		$this->assign('count',$count);

		$this->display();
	}
	public function position_check_double(){
		$gname = I('post.gname');
		$gid = (int)I('post.gid');
		$locid = (int)I('post.locid');
		$model = M('Group_mas');
		$position_info = $model->where("group_name='$gname' and id!=$gid and loc_id=$locid")->find();
		if($position_info){
			$oData['error']=1;
			$oData['info']='Double';
		}else{
			$oData['error']=0;
			$oData['info']='Success';
		}
		$this->ajaxReturn($oData,'JSON');
	}
	public function get_position_info(){
		$gid = (int)I('post.gid');
		$model = M('Group_mas');
		$position_info = $model->where("id=$gid")->find();
		if($position_info){
			$oData['error']=0;
			$oData['info']=$position_info;
		}else{
			$oData['error']=1;
			$oData['info']='Fail';
		}
		$this->ajaxReturn($oData,'JSON');
	}
	public function position_save(){
		$model = M('Group_mas');

		$gid = (int)I('post.id');
		$locid = (int)I('post.loc_id');
		$gname = I('post.group_name');
		$model = M('Group_mas');
		$position_info = $model->where("group_name='$gname' and id!=$gid and loc_id=$locid")->find();
		if($position_info){
			$oData['error']=2;
			$oData['info']='Double';
			$this->ajaxReturn($oData,'JSON');
			exit;
		}

		$data['group_name']=I('post.group_name');
		$data['content'] = implode(',',I('post.role'));
		$data['loc_id'] = $locid;

		if((int)I('post.id')){
			$data['id']=I('post.id');
			$act_flg = $model->data($data)->save();
		}else{
			$data['status_flg']='Y';
			$act_flg = $model->data($data)->add();
		}
		if((int)$act_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';
		}
		$this->ajaxReturn($oData,'JSON');
	}

	public function position_delete(){
        $userinfo = cookie('userinfo');        
		$model = M('Group_mas');

		$act_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();

		if((int)$act_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';

            //***Log***
            $logData['notes']['userinfo']['account']=$userinfo['account'];
            $logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
            $logData['notes']['agedinfo']['elderly_name']='';
            $logData['notes']['agedinfo']['nick_name']='';
            $logData['notes']['agedinfo']['target_name']=I('post.name');
            $logData['notes']['result']='Success';
            $logData['notes']['note']='Delete permissions posts-'.I('post.name');
            $logData['user_id']=(int)$userinfo['post.id'];
            $logData['act_type']='position_delete';
            $logData['module']='Admin';
            $logData['act_obj']=$locid;
            $logData['notes']=serialize($logData['notes']);
            saveLog($logData);               
		}
		$this->ajaxReturn($oData,'JSON');

	}
	public function loc_delete(){
        $userinfo = cookie('userinfo');
		$model = M('Loc_mas');

		$act_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		
		if((int)$act_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';
            
			//***Log***
            $logData['notes']['userinfo']['account']=$userinfo['account'];
            $logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
            $logData['notes']['agedinfo']['elderly_name']='';
            $logData['notes']['agedinfo']['nick_name']='';
            $logData['notes']['agedinfo']['target_name']=I('post.name');
            $logData['notes']['result']='Success';
            $logData['notes']['note']='Delete-'.I('post.name');
            $logData['user_id']=(int)$userinfo['post.id'];
            $logData['act_type']='loc_delete';
            $logData['module']='Admin';
            $logData['act_obj']=$locid;
            $logData['notes']=serialize($logData['notes']);
            saveLog($logData);                 
		}
		$this->ajaxReturn($oData,'JSON');

	}
	public function user_delete(){
        $userinfo = cookie('userinfo');
		$model = M('User_mas');

		$act_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		
		if((int)$act_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';
            
			//***Log***
            $logData['notes']['userinfo']['account']=$userinfo['account'];
            $logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
            $logData['notes']['agedinfo']['elderly_name']='';
            $logData['notes']['agedinfo']['nick_name']='';
            $logData['notes']['agedinfo']['target_name']=I('post.name');
            $logData['notes']['result']='Success';
            $logData['notes']['note']='Delete-'.I('post.name');
            $logData['user_id']=(int)$userinfo['post.id'];
            $logData['act_type']='user_delete';
            $logData['module']='Admin';
            $logData['act_obj']=$locid;
            $logData['notes']=serialize($logData['notes']);
            saveLog($logData);             
		}
		$this->ajaxReturn($oData,'JSON');

	}

    public function worker_delete(){
        $userinfo = cookie('userinfo');
		$model = M('User_mas');

		$act_flg = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		
		if((int)$act_flg==0){
			$oData['error']=1;
			$oData['info']='Fail';

		}else{
			$oData['error']=0;
			$oData['info']='Success';
            
			//***Log***
            $logData['notes']['userinfo']['account']=$userinfo['account'];
            $logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
            $logData['notes']['agedinfo']['elderly_name']='';
            $logData['notes']['agedinfo']['nick_name']='';
            $logData['notes']['agedinfo']['target_name']=I('post.name');
            $logData['notes']['result']='Success';
            $logData['notes']['note']='Delete-'.I('post.name');
            $logData['user_id']=(int)$userinfo['post.id'];
            $logData['act_type']='user_delete';
            $logData['module']='Admin';
            $logData['act_obj']=$locid;
            $logData['notes']=serialize($logData['notes']);
            saveLog($logData);             
		}
		$this->ajaxReturn($oData,'JSON');

	}
	public function analyse(){
		$userinfo = cookie('userinfo');
		$model = M();
		$db_prefix = C('DB_PREFIX');
		$cyear = (int)toDate(getCurrTime(),'Y');
		$this->assign("cyear",$cyear + 3);

		$strwhere='';
		$echart_subtitle='';
		$iyear = (int)I('post.iyear');
		$iquarter = (int)I('post.iquarter');
		$imonth = (int)I('post.imonth');

		if($iyear){
			$strwhere.=" and aged.register_date>=".toTime($iyear.'-01-01')." and aged.register_date<=".toTime($iyear.'-12-31')."";
			$this->assign("iyear",$iyear);
			$echart_subtitle=$iyear;
		}
		if($iquarter){
			if(!$iyear){$iyear=$cyear;}
			$strwhere.=" and aged.register_date>=".toTime($iyear.'-'.(($iquarter-1)*3+1).'-01')." and aged.register_date<".strtotime('+3month',toTime($iyear.'-'.(($iquarter-1)*3+1).'-01'))."";
			$this->assign("iyear",$iyear);
			$this->assign("iquarter",$iquarter);
			$echart_subtitle=$iyear.' / Q'.$iquarter;
		}else if($imonth){
			if(!$iyear){$iyear=$cyear;}
			$strwhere.=" and aged.register_date>=".toTime($iyear.'-'.$imonth.'-01')." and aged.register_date<".strtotime('+1month',toTime($iyear.'-'.$imonth.'-01'))."";
			$this->assign("iyear",$iyear);
			$this->assign("imonth",$imonth);
			$echart_subtitle=$iyear.' / '.$imonth;
		}

		if((int)$userinfo['loc_id'] > 1){
			$user_ids = M('User_mas')->where("loc_id=".$userinfo['loc_id'])->getField('id',true);
			$strwhere.=" and aged.sales_id in (".implode(',',$user_ids).")";
		}

		$user_ids = M('User_mas')->where("loc_id=".$userinfo['loc_id'])->getField('id',true);
		$strsql = "select usr.true_name,aged.sales_id,aged.use_area_str,count(aged.id) as aged_qty from ".$db_prefix."elderly_info aged inner join ".$db_prefix."user_mas usr on aged.sales_id=usr.id";
		$strsql.= " where 1=1";
		$strsql.= $strwhere;
		$strsql.= " group by usr.true_name,aged.sales_id,aged.use_area_str";
		$strsql.= " order by aged.sales_id asc";

		$datalist = $model->query($strsql);
		foreach($datalist as $k=>$v){
			$datalist[$k]['use_area_str']=explode(',',$v['use_area_str']);
		}

		$this->assign("datalist",$datalist);

		unset($strsql);
		$strsql = "select usr.true_name,aged.sales_id,count(aged.id) as aged_qty from ".$db_prefix."elderly_info aged inner join ".$db_prefix."user_mas usr on aged.sales_id=usr.id";
		$strsql.= " where 1=1";
		$strsql.= $strwhere;
		$strsql.= " group by usr.true_name,aged.sales_id";
		$strsql.= " order by aged.sales_id asc";

		$chartlist = $model->query($strsql);
		foreach($chartlist as $k=>$v){
			$chartlabel[]=$v['true_name'];
			$chartdata[]="{value:".$v['aged_qty'].", name:'".$v['true_name']."'}";
		}
		$this->assign("chartlabel",implode("','",$chartlabel));
		$this->assign("chartdata",implode(",",$chartdata));

		$this->assign("echart_subtitle",$echart_subtitle);

		$this->display();
	}


}
