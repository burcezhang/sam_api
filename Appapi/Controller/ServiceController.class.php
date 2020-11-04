<?php
namespace Appapi\Controller;
//use Think\Controller;
use Common\Controller\AppapiController;
class ServiceController extends AppapiController {
    public function info()
	{
		checkLogin(I('get.account'),I('get.pwd'));
		switch((int)I('get.fun'))
		{			
			case 5:	 //Get Service Type List
				$model = M('Pservice_type');				
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$agedId = I('get.agedId');
				$key = I('get.key');
				
				$swhere = "status_flg='Y' and aged_id=".$agedId;
				if($key)$swhere = $swhere." and type_name like '%$key%'";
				$count = $model->where($swhere)->count();
				$tpagetotal = ceil($count/$psize);
				$datainfo = $model->where($swhere)->order("id desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['TypeID']=(int)$row['id'];
						$returnData['info'][$inx]['TypeName']=$row['type_name'];
						$returnData['info'][$inx]['isPrivate']=(int)$row['is_private'];
						$returnData['info'][$inx]['EntryID']=(int)$row['entry_id'];
						$returnData['info'][$inx]['CreateTime']=toDate($row['create_time']);
						$returnData['info'][$inx]['Remark']=$row['remark'];
						$returnData['info'][$inx]['AgedID']=(int)$row['aged_id'];
						$returnData['info'][$inx]['UserID']=(int)$row['user_id'];
						$returnData['info'][$inx]['HoldID']=(int)$row['hold_id'];
						$inx++;
					}
					$returnData['PageInfo']['Records']=$count;
					$returnData['PageInfo']['PCount']=$tpagetotal;
					$returnData['PageInfo']['PSize']=$psize;
					$returnData['PageInfo']['P']=$currpage;
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');
			break;
			case 6: //add / update service type
				$model = M('Pservice_type');
				$data['id']=(int)I('get.typeId');
				$data['type_name']=I('get.typeName');
				$data['is_private']=I('get.isPrivate');
				$data['hold_id']=I('get.holdId');
				$data['aged_id']=I('get.agedId');
				$data['user_id']=I('get.userId');
				$data['remark']=I('get.remark');
				if($data['id']){
					$setting_flg = $model->data($data)->save();				
				}else{
					$data['status_flg']='Y';
					$data['create_time']=getCurrTime();
					$setting_flg = $model->data($data)->add();
				}
				
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');				
			break;
			case 7: //delete service type
				$model = M('Pservice_type');
				$data['id']=(int)I('get.typeId');
				$data['status_flg']='D';
				$setting_flg = $model->data($data)->save();	
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');
			break;
			case 1: //get service info list
				$model = M('Pservice_info');				
				$psize  = (int)I('get.psize');
				$currpage = (int)I('get.p');
				$agedId = I('get.agedId');
				$serviceType = (int)I('get.serviceType');
				$key = I('get.key');
				
				$elderly_ids = M('Pservice_elderly')->where("elderly_id=".$agedId)->getField('service_id',true);
				
				$swhere = "status_flg='Y' and id in (".implode(',',$elderly_ids).")";
				if($serviceType)$swhere = $swhere." and type_id='$serviceType'";
				if($key)$swhere = $swhere." and service_name like '%$key%'";
				$count = $model->where($swhere)->count();
				$tpagetotal = ceil($count/$psize);
				$datainfo = $model->where($swhere)->order("id desc")->limit(($psize*($currpage-1)).','.($psize*$currpage))->select();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					$inx = 0;
					foreach($datainfo as $row){
						$returnData['info'][$inx]['ServiceID']=(int)$row['id'];
						$returnData['info'][$inx]['Code']=$row['service_code'];
						$returnData['info'][$inx]['ServiceType']=(int)$row['type_id'];
						$returnData['info'][$inx]['CompanyName']=$row['service_name'];
						$returnData['info'][$inx]['Classificate']=$row['star_level'];
						$returnData['info'][$inx]['Business']=$row['business'];
						$returnData['info'][$inx]['Address']=$row['address'];
						$returnData['info'][$inx]['Phone']=$row['phone'];
						$returnData['info'][$inx]['isPrivate']=(int)$row['is_private'];
						$inx++;
					}
					$returnData['PageInfo']['Records']=$count;
					$returnData['PageInfo']['PCount']=$tpagetotal;
					$returnData['PageInfo']['PSize']=$psize;
					$returnData['PageInfo']['P']=$currpage;
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');
			break;
			case 2: //get service info detail
				$model = M('Pservice_info');
				$serviceId = (int)I('get.serviceId');
				
				$swhere = "status_flg='Y' and id=".$serviceId;
				$datainfo = $model->where($swhere)->find();
				if($datainfo){
					$returnData['error']=0;
					$returnData['reason']='Success';
					
					$returnData['info']['ServiceID']=(int)$row['id'];
					$returnData['info']['Code']=$row['service_code'];
					$returnData['info']['CompanyName']=$row['service_name'];
					$returnData['info']['Classificate']=$row['star_level'];
					$returnData['info']['Business']=$row['business'];
					$returnData['info']['Address']=$row['address'];
					$returnData['info']['Phone']=$row['phone'];
					$returnData['info']['isPrivate']=(int)$row['is_private'];
					
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');
			break;
			case 3: //add / update service info
				$model = M('Pservice_info');
				$data['id']=(int)I('get.serviceId');
				//$data['holdId']=I('get.holdId');
				//$data['agedId']=I('get.agedId');
				$data['service_name ']=I('get.name');
				$data['type_id']=I('get.type_id');
				//$data['imgbyte']=I('get.imgbyte');
				if(I('get.imgbyte')){
					//解码
					$tmpImgByte  = base64_decode(I('get.imgbyte'));
					//写文件
					$tmpFilename = C('UPLOAD_DIR').'service_photo_'.toDate(getCurrTime(),'YmdHis').'_'.rand(10000,99999).'.jpg';
					file_put_contents($tmpFilename, $tmpImgByte);
					$data['photo_src']=$tmpFilename;
				}else{
					$data['photo_src']='';
				}
				$data['star_level']=I('get.star');
				$data['phone']=I('get.tel');
				$data['address']=I('get.address');
				$data['remark']=I('get.remark');
				$data['is_private']=I('get.isPrivate');				
				if($data['id']){
					$setting_flg = $model->data($data)->save();				
				}else{
					$data['status_flg']='Y';
					$data['create_dt']=getCurrTime();
					$setting_flg = $model->data($data)->add();
					unset($data);
					$data['service_id']=$setting_flg;
					$data['elderly_id']=(int)I('get.agedId');
					M('Pservice_elderly')->data($data)->add();
				}
				
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');				
			break;
			case 4: //delete service info
				$model = M('Pservice_info');
				$data['id']=(int)I('get.serviceId');
				$data['status_flg']='D';
				$setting_flg = $model->data($data)->save();	
				if($setting_flg){
					$returnData['error']=0;
					$returnData['reason']='Success';
				}else{					
					$returnData['error']=1;
					$returnData['reason']='Failed';
				}
				$jsonData['data']=$returnData;
				$this->show(json_encode($jsonData),'utf-8');
			break;
		}
		
	}
}