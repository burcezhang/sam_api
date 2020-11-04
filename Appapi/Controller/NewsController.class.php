<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class NewsController extends AppapiController {
    public function index(){
		checkLogin(I('get.account'),I('get.pwd'));
		$groupid=I('get.loc_id');
		$model = M('News');
		
		$count = $model->where("var_group='".$groupid."'")->count();
		$psize  = (int)I('get.psize');
		$currpage = (int)I('get.p');
		$tpagetotal = ceil($count/$psize);

		$datainfo = $model->field('id,title,photo,stime,etime,var_name,info,ascno,link')
						->where("var_group='".$groupid."'")
						->order('id desc')->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
		if($datainfo){
			$returnData['error']=0;
			$returnData['reason']='Success';
			$inx = 0;
			foreach($datainfo as $row){
				$mArr=array();
				$mArr['id']=$row['id'];
				$mArr['target']=$row['var_name'];
				$mArr['title']=$row['title'];
				$mArr['info']=$row['info'];
				$mArr['photo']=$row['photo'];
				$mArr['stime']=date("Y-m-d H:i:s",$row['stime']);
				$mArr['etime']=date("Y-m-d H:i:s",$row['etime']);
				$mArr['ascno']=$row['ascno'];
				$mArr['link']=$row['link'];				
				$returnData['info'][]=$mArr;
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
		$result=json_encode($jsonData);
		$callback=I('get.callback');  
		echo $callback."($result)";   
	}

	public function delete(){
		$userinfo = cookie('userinfo');
		$model = M('News');
		$result = $model->where('id=\''.(int)I('post.id').'\'')->delete();
		if($result === false){
			$oData['error']=1;
			$oData['info']='Fail';
		}else{
			$oData['error']=0;
			$oData['info']='Success';
		}

		$this->ajaxReturn($oData,'JSON');
	}
	
	public function view(){
		$model = M('News');
		checkLogin(I('param.account'),I('param.pwd'));
		$groupid=I('get.loc_id');
		$id=I('get.id');
		$datainfo = $model->where('id=\''.$id.'\' and var_group=\''.$groupid.'\'')->find();
		if($datainfo){
			$returnData['error']=0;
			$returnData['reason']='Success';
			$returnData['info']['id']=$datainfo['id'];
			$returnData['info']['target']=$datainfo['var_name'];
			$returnData['info']['title']=$datainfo['title'];
			$returnData['info']['photo']=$datainfo['photo'];
			$returnData['info']['text']=$datainfo['text'];
			$returnData['info']['stime']=date("Y-m-d H:i:s",$datainfo['stime']);
			$returnData['info']['etime']=date("Y-m-d H:i:s",$datainfo['etime']);
			$returnData['info']['ascno']=$datainfo['ascno'];
			$returnData['info']['link']=$datainfo['link'];			
		}else{
			$returnData['error']=1;
			$returnData['reason']='Failed';
		}
		$jsonData['data']=$returnData;
		$result=json_encode($jsonData);
		$callback=I('get.callback');  
		echo $callback."($result)";   
	}
    public function schedule(){
		checkLogin(I('param.account'),I('param.pwd'));
		$groupid=I('get.loc_id');
		$model = M('Schedule');

		$datainfo = $model->field('week0,week1,week2,week3,week4,week5,week6')
						->where("var_group='".$groupid."'")
						->find();
		if($datainfo){
			$returnData['error']=0;
			$returnData['reason']='Success';
			$datainfo['week0']=unserialize($datainfo['week0']);
			$datainfo['week1']=unserialize($datainfo['week1']);
			$datainfo['week2']=unserialize($datainfo['week2']);
			$datainfo['week3']=unserialize($datainfo['week3']);
			$datainfo['week4']=unserialize($datainfo['week4']);
			$datainfo['week5']=unserialize($datainfo['week5']);
			$datainfo['week6']=unserialize($datainfo['week6']);
			$returnData['info']=$datainfo;
		}else{
			$returnData['error']=1;
			$returnData['reason']='Failed';
		}
		$jsonData['data']=$returnData;
		$result=json_encode($jsonData);
		$callback=I('get.callback');  
		echo $callback."($result)";   
	}
    public function meal(){
		checkLogin(I('param.account'),I('param.pwd'));
		$groupid=I('get.loc_id');
		$model = M('Meal');

		$datainfo = $model->field('week0,week1,week2,week3,week4,week5,week6')
						->where("var_group='".$groupid."'")
						->find();
		if($datainfo){
			$returnData['error']=0;
			$returnData['reason']='Success';
			$datainfo['week0']=unserialize($datainfo['week0']);
			$datainfo['week1']=unserialize($datainfo['week1']);
			$datainfo['week2']=unserialize($datainfo['week2']);
			$datainfo['week3']=unserialize($datainfo['week3']);
			$datainfo['week4']=unserialize($datainfo['week4']);
			$datainfo['week5']=unserialize($datainfo['week5']);
			$datainfo['week6']=unserialize($datainfo['week6']);
			$returnData['info']=$datainfo;
		}else{
			$returnData['error']=1;
			$returnData['reason']='Failed';
		}
		$jsonData['data']=$returnData;
		$result=json_encode($jsonData);
		$callback=I('get.callback');  
		echo $callback."($result)";   
	}
}
