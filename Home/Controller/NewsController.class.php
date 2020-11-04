<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class NewsController extends CommonController {
    public function index(){
		$userinfo = cookie('userinfo');
		$model = M('News');
       	 $news=$model->where('var_group=\''.$userinfo['loc_id'].'\'')->select();
		
		$count  = $model->where('var_group=\''.$userinfo['loc_id'].'\'')->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show  = $Page->show();

		$datalist2 = $model->where('var_group=\''.$userinfo['loc_id'].'\'') -> order("id asc")->limit($Page->firstRow.','.$Page->listRows)->select();
		foreach($datalist2 as $k=>$v){
			$datalist[$k]=$v;
		}
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		$this->display();
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
	
	public function edit(){
		$userinfo = cookie('userinfo');
		//echo $data=substr($data,strpos($data,',')+1);
		if(IS_POST){
			$id=I('post.id');
			$photo=I('post.photo');
			if(!empty($photo)){
				$photo=uploadData($photo,'News/');
				if($photo[0]){
					$photo=$photo[1];
				}else{
					$returnData['error']=1;
					$returnData['reason']='Photo Upload Failed. '.$photo[1];
					$this->ajaxReturn($returnData,'JSON');
					return;
				}
			}
			if($id) {
				$model = M('News');
				$news=$model->where('id=\''.$id.'\' and var_group=\''.$userinfo['loc_id'].'\'')->find();
				if($news){
					$mArr=array();
					$mArr['title']=I('post.title');
					$mArr['var_name']=I('post.var_name');
					$mArr['info']=I('post.info');
					if(!empty($photo)) $mArr['photo']=$photo;
					$mArr['stime']=toTime(I('post.stime'));
					$mArr['etime']=toTime(I('post.etime'));
					$mArr['text']=htmlspecialchars(I('post.text'));
					$ascno=(int)I('post.ascno');
					$ascno++;
					$mArr['ascno']=$ascno;
					
					$tmpFilename = C('NEWS_LINK_DIR').'/'.toDate(getCurrTime(),'YmdHis').'_'.rand(10000,99999).'.html';
					$content=I('post.text');
					$head='<!doctype html><html><head><meta charset="UTF-8"></head><title>';
					file_put_contents(C('BASE_DIR').$tmpFilename, $head);
					$title=$mArr['title'].'</title><body>';
					file_put_contents(C('BASE_DIR').$tmpFilename, $title,FILE_APPEND);					
					file_put_contents(C('BASE_DIR').$tmpFilename, $content,FILE_APPEND);
					$end="</body></html>";
					file_put_contents(C('BASE_DIR').$tmpFilename, $end,FILE_APPEND);					
					$mArr['link']=C('WEB_DOMAIN').$tmpFilename;
					
					$setting_flg = $model->data($mArr)->where('id=\''.$id.'\' and var_group=\''.$userinfo['loc_id'].'\'')->save();
				}
			}else{
				$model = M('News');
				$mArr=array();
				$mArr['var_group']=$userinfo['loc_id'];
				$mArr['var_name']=$userinfo['true_name'];
				$mArr['title']=I('post.title');
				$mArr['info']=I('post.info');
				if(!empty($photo)) $mArr['photo']=$photo;
				$mArr['stime']=toTime(I('post.stime'));
				$mArr['etime']=toTime(I('post.etime'));
				$mArr['text']=htmlspecialchars(I('post.text'));

				$tmpFilename = C('NEWS_LINK_DIR').'/'.toDate(getCurrTime(),'YmdHis').'_'.rand(10000,99999).'.html';
				$content=I('post.text');
				$head='<!doctype html><html><head><meta charset="UTF-8"></head><title>';
				file_put_contents(C('BASE_DIR').$tmpFilename, $head);
				$title=$mArr['title'].'</title><body>';
				file_put_contents(C('BASE_DIR').$tmpFilename, $title,FILE_APPEND);					
				file_put_contents(C('BASE_DIR').$tmpFilename, $content,FILE_APPEND);
				$end="</body></html>";
				file_put_contents(C('BASE_DIR').$tmpFilename, $end,FILE_APPEND);					
				$mArr['link']=C('WEB_DOMAIN').$tmpFilename;
					
				$setting_flg = $model->add($mArr);
			}
			if($setting_flg){
				$returnData['error']=0;
				$returnData['reason']='Success';
			}else{
				$returnData['error']=1;
				$returnData['reason']='Failed';
			}
			$this->ajaxReturn($returnData,'JSON');
		}else{
			$id=I('get.id');
			if($id) {
				$model = M('News');
				$news=$model->where('id=\''.$id.'\' and var_group=\''.$userinfo['loc_id'].'\'')->find();
				$this->assign('news',$news);
			}
			$this->assign('var_name',$userinfo['true_name']);
			$this->display();
		}
	}
}
