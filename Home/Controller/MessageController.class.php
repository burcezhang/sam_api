<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class MessageController extends CommonController {
	public function smssend(){
		$model = M('Sms_log');
		$strwhere=" status_flg ='Y'";

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

	       $model = M('Sys_var');
		$audio_set_run = $model->where("var_group='audio_set' and var_name='runing'")->getField('value1');
		if($audio_set_run){
			$audio_set_run = unserialize($audio_set_run);
			$player['sms']=$audio_set_run['sms']['kfile'];
			$this->assign("player",$player);
		}
		
		$this->display();
	}
	public function smssend_show(){
		$model = M('Sms_log');
		$smsinfo = $model->find((int)I('post.id'));
		$this->assign("smsinfo",$smsinfo);
		$this->ajaxReturn($smsinfo,'JSON');
	}
	public function smssend_del(){
		$userinfo = cookie('userinfo');
		$model = M('Sms_log');
		$result = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		if($result === false){
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
			$logData['act_obj']='0';
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

		if(I('post.id')==''){
			$model->create();
			$model->title = I('post.title');
			$model->phone = I('post.phone');
			$model->num = count($phone);
			$model->content = I('post.content');
			$model->sendtime = getCurrTime();
			$sms_flg = $model->add();
		}else{
			$sms_flg = I('post.id');
		}
		

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
				$username=array_slice($phone,($p-1)*$ext, $size-($p-1)*$ext);
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
			$logData['notes']['note']='Save SMS'.I('post.title');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='smssend_save';
			$logData['module']='Admin';
			$logData['act_obj']=$sms_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}
		$this->ajaxReturn($oData,'JSON');
	}
	
	
	public function pushsend(){
		$model = M('Push_log');
		$strwhere=" status_flg ='Y'";

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

	       $model = M('Sys_var');
		$audio_set_run = $model->where("var_group='audio_set' and var_name='runing'")->getField('value1');
		if($audio_set_run){
			$audio_set_run = unserialize($audio_set_run);
			$player['sms']=$audio_set_run['sms']['kfile'];
			$this->assign("player",$player);
		}
		
		$this->display();
	}
	public function pushsend_show(){
		$model = M('Push_log');
		$pushinfo = $model->find((int)I('post.id'));
		$this->assign("pushinfo",$pushinfo);
		$this->ajaxReturn($pushinfo,'JSON');
	}
	
	
	public function pushsend_del(){
		$userinfo = cookie('userinfo');
		$model = M('Push_log');
		$result = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		if($result === false){
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
			$logData['act_obj']='0';
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);

		}

		$this->ajaxReturn($oData,'JSON');
	}

	public function pushsend_save(){
		$userinfo = cookie('userinfo');
		$phone = I('post.phone');
		if(empty($phone)){
			$oData['error']=1;
			$oData['info']='No Receiver Number';
			return;
		}
		$model = M('Push_log');
		$phone = str_replace(' ', '', $phone);
		$phone = str_replace('��', ',', $phone);
		$phone=explode(',', $phone);
		$phone=array_unique($phone);

		if(I('post.id')==''){
			$model->create();
			$model->title = I('post.title');
			$model->phone = I('post.phone');
			$model->num = count($phone);
			$model->content = I('post.content');
			$model->sendtime = getCurrTime();
			$push_flg = $model->add();
		}else{
			$push_flg = I('post.id');
		}
		if((int)$push_flg==0){
			$oData['error']=1;
			$oData['info']='Cannot Add Push DB';

		}else{
			if(count($phone)>1){
				$size = count($phone);
				foreach($phone as $username){
					$pushData=array();//Notification
					$pushData['topic']='Notification';
					$pushData['title']=I('post.title');
					$pushData['target']=$username;
					$pushData['body']=I('post.content');
					$pushData['act_type']='Send Push';
					$pushData['act_obj']='0';
					pushMessage($pushData);
				}
			}else{
				$pushData=array();
				$pushData['topic']='Notification';
				$pushData['title']=I('post.title');
				$pushData['target']=$phone[0];
				$pushData['body']=I('post.content');
				$pushData['act_type']='Send Push';
				$pushData['act_obj']='0';
				pushMessage($pushData);
			}
			$oData['error']=0;
			$oData['info']='Success';

			//***Log***
			$logData['notes']['userinfo']['account']=$userinfo['account'];
			$logData['notes']['userinfo']['true_name']=$userinfo['true_name'];
			$logData['notes']['agedinfo']['elderly_name']='';
			$logData['notes']['agedinfo']['nick_name']='';
			$logData['notes']['result']='Success';
			$logData['notes']['note']='Save SMS'.I('post.title');
			$logData['user_id']=(int)$userinfo['id'];
			$logData['act_type']='pushsend_save';
			$logData['module']='Admin';
			$logData['act_obj']=$push_flg;
			$logData['notes']=serialize($logData['notes']);
			saveLog($logData);
		}
		$this->ajaxReturn($oData,'JSON');
	}

}
