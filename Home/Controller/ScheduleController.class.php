<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class ScheduleController extends CommonController {
    public function index(){
		$userinfo = cookie('userinfo');
		$model = M('Schedule');
		$data=$model->where('var_group=\''.$userinfo['loc_id'].'\'')->find();
		if($data){
			$data['week0']=unserialize($data['week0']);
			$data['week1']=unserialize($data['week1']);
			$data['week2']=unserialize($data['week2']);
			$data['week3']=unserialize($data['week3']);
			$data['week4']=unserialize($data['week4']);
			$data['week5']=unserialize($data['week5']);
			$data['week6']=unserialize($data['week6']);
			$count=array('0'=>count($data['week0']),'1'=>count($data['week1']),'2'=>count($data['week2']),'3'=>count($data['week3']),'4'=>count($data['week4']),'5'=>count($data['week5']),'6'=>count($data['week6']));
		}else{
			$count=array('0'=>1,'1'=>1,'2'=>1,'3'=>1,'4'=>1,'5'=>1,'6'=>1);
		}
		$this->assign('count',$count);
		$this->assign('data',$data);
		$this->display();
	}

	public function edit(){
		$userinfo = cookie('userinfo');
		if(IS_POST){
			$model = M('Schedule');
			$news=$model->where('var_group=\''.$userinfo['loc_id'].'\'')->find();
			if($news){
				$mArr=array();
				$mArr['week0']=serialize(I('post.week0'));
				$mArr['week1']=serialize(I('post.week1'));
				$mArr['week2']=serialize(I('post.week2'));
				$mArr['week3']=serialize(I('post.week3'));
				$mArr['week4']=serialize(I('post.week4'));
				$mArr['week5']=serialize(I('post.week5'));
				$mArr['week6']=serialize(I('post.week6'));
				$setting_flg = $model->data($mArr)->where('var_group=\''.$userinfo['loc_id'].'\'')->save();
			}else{
				$mArr=array();
				$mArr['var_group']=$userinfo['loc_id'];
				$mArr['var_name']=$userinfo['true_name'];
				$mArr['week0']=serialize(I('post.week0'));
				$mArr['week1']=serialize(I('post.week1'));
				$mArr['week2']=serialize(I('post.week2'));
				$mArr['week3']=serialize(I('post.week3'));
				$mArr['week4']=serialize(I('post.week4'));
				$mArr['week5']=serialize(I('post.week5'));
				$mArr['week6']=serialize(I('post.week6'));
				$setting_flg = $model->add($mArr);
			}
			if($setting_flg){
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
		$this->ajaxReturn($returnData,'JSON');
	}
}