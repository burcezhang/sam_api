<?php
namespace Home\Controller;

include('ThinkPHP/Library/Org/Net/ExcelReader.class.php');
use Common\Controller\CommonController;

class InventoryController extends CommonController {
    public function index(){
        //var_dump(cookie('userinfo'));
		$this->display();
	}

	public function import(){

		$userinfo = cookie('userinfo');
		if((int)$userinfo['loc_id']>1){
			$oData['error']=2;
			$oData['info']='Non Access';
			$this->ajaxReturn($oData,'JSON');
		}

		$file_src=I('post.file_src');
		if(file_exists(C('BASE_DIR').$file_src)) {
			$excl = new \ExcelReader();
			$excl->setOutputEncoding('utf-8');
			$excl->read(C('BASE_DIR').$file_src);

			
			$data=array();
			//$file = file(C('BASE_DIR').$file_src);
			$icount = 0;
			$iexists = 0;
			$device_sn = array();
			$create_date = getCurrTime();
			for($i = 2; $i <= $excl->sheets[0]['numRows']; $i++) {
			//foreach($file as &$line){
				//echo $line.'<br />';
				//$row = explode(',',trim($line));
				$row=$excl->sheets[0]['cells'][$i];
				//if(count($row)<=0) continue;
				//$data=array();
				$device_sn[] = trim($row[1]);
				$data[$row[1]]['device_sn'] = trim($row[1]);
				$data[$row[1]]['sim'] = strlen(trim($row[1]))>8?'1':'0';
				$data[$row[1]]['wh'] = count($row)>1&&!empty($row[2])?$row[2]:'总部';
				$data[$row[1]]['create_date'] = $create_date;
				$data[$row[1]]['status_flg'] = 'Y';
				$data[$row[1]]['loc_id'] = '0';
				$icount++;
			}
			$model = M('Device_mas');
			$check_double = $model->where("device_sn in ('".implode('\',\'',$device_sn)."')")->select();
			foreach($check_double as $k=>$v){
				if($data[$v['device_sn']]){
					unset($data[$v['device_sn']]);
					$iexists++;
				}
			}
			$data2_index=0;
			foreach($data as $k=>$v){
				$data2[$data2_index]['device_sn'] = $v['device_sn'];
				$data2[$data2_index]['sim'] = $v['sim'];
				$data2[$data2_index]['create_date'] = $v['create_date'];
				$data2[$data2_index]['wh'] = $v['wh'];
				//$data2[$data2_index]['other'] = $v['other'];
				$data2[$data2_index]['status_flg'] = $v['status_flg'];
				$data2[$data2_index]['loc_id'] = $v['loc_id'];
				$data2_index++;
			}
			$addflg = $model->addAll($data2);

			$oData['error']=0;
			$oData['info']='Success';
			$oData['icount']=$icount;
			$oData['iexists']=$iexists;
			$oData['iimport']=($icount - $iexists);
			$oData['addflg'] = $addflg;
		}else{
			$oData['error']=1;
			$oData['info']='File not exists';
		}
		$this->ajaxReturn($oData,'JSON');
	}

	public function deliver(){
		if(IS_POST){
			$userinfo = cookie('userinfo');
			if((int)$userinfo['loc_id']>1){
				$oData['error']=2;
				$oData['info']='Non Access';
				$this->ajaxReturn($oData,'JSON');
			}

			$file_src=I('post.file_src');
			$loc_id=I('post.loc_id');
			if(file_exists(C('BASE_DIR').$file_src)) {
				$excl = new \ExcelReader();
				$excl->setOutputEncoding('utf-8');
				$excl->read(C('BASE_DIR').$file_src);
				
				//$file = file(C('BASE_DIR').$file_src);
				$icount = 0;
				$model = M('Device_mas');
				
				for($i = 2; $i <= $excl->sheets[0]['numRows']; $i++) {
					$row=$excl->sheets[0]['cells'][$i];
					//if(count($row)<=0) continue;
				//foreach($file as &$line){
					//$row = explode(',',trim($line));
					//if(count($row)<=0) continue;
					$data=array();
					$data['loc_id'] = trim($loc_id);
					//$data['status_flg'] = 'S';
					$model->where("device_sn='".trim($row[1])."'")->save($data);
					$icount++;
				}
				$oData['error']=0;
				$oData['info']='Success';
				$oData['icount']=$icount;
			}else{
				$oData['error']=2;
				$oData['info']='File not exists';
			}
			$this->ajaxReturn($oData,'JSON');
		}else{
			$strwhere="status_flg in ('Y')";
			$loclist = M('Loc_mas')->field("id,loc_name,parent_id,parent_str,concat(parent_str,',',id) as bpath")->where($strwhere)->order('bpath')->select();
			$tmp=current($loclist);
			$this->assign('loclist',$loclist);
			$this->assign('locid',$tmp['id']);
			$this->assign('locname',$tmp['loc_name']);
			$this->display();
		}
	}

	public function stock(){
		$userinfo = cookie('userinfo');
		$model = M('Device_mas');

		$strwhere = "1=1";
		if((int)$userinfo['loc_id']>1){
			$loc_name = M('Loc_mas')->where('id='.(int)$userinfo['loc_id'])->getField('loc_name');
			$strwhere .= " and mas.wh='$loc_name'";
		}

		$keyword = I('get.keyword');
		if($keyword)$strwhere.=" and (mas.device_sn = '$keyword')";
		$this->assign('keyword',$keyword);

		$count      = $model->alias('mas')->where($strwhere)->count();
		$Page = new \Think\Page($count,(int)C('PAGE_SIZE'));
		$show       = $Page->show();

		$datalist = $model->alias('mas')->field('mas.*,aged.elderly_name,aged.use_area_str,aged.register_date')->join(C('DB_PREFIX').'elderly_info aged ON mas.device_sn=aged.device_sn','LEFT')->where($strwhere) -> order("mas.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
		//var_dump($model->getLastSql());
		$loclist = M('Loc_mas')->field("id,loc_name")->where("status_flg in ('Y')")->select();
		foreach($loclist as $Rs){
			$locname[$Rs['id']]=$Rs['loc_name'];
		}
		$this->assign('locname',$locname);
		$this->assign("datalist",$datalist);
		$this->assign('page',$show);

		$this->display();
	}

	public function analyse(){
		$userinfo = cookie('userinfo');
		$model = M();
		$db_prefix = C('DB_PREFIX');
		$cyear = (int)toDate(getCurrTime(),'Y');
		$this->assign("cyear",$cyear + 3);

		$strwhere = '1=1';
		if((int)$userinfo['loc_id']>1){
			$loc_name = M('Loc_mas')->where('id='.(int)$userinfo['loc_id'])->getField('loc_name');
			$strwhere .= " and dev.wh='$loc_name'";
		}

		$echart_subtitle='';
		$iyear = (int)I('post.iyear');
		$iquarter = (int)I('post.iquarter');
		$imonth = (int)I('post.imonth');
		$status_flg = I('post.status_flg');

		if($status_flg){
			$strwhere.=" and dev.status_flg='".$status_flg."'";
			$this->assign("status_flg",$status_flg);
		}
		if($iyear){
			$strwhere.=" and dev.create_date>=".toTime($iyear.'-01-01')." and dev.create_date<=".toTime($iyear.'-12-31')."";
			$this->assign("iyear",$iyear);
			$echart_subtitle=$iyear;
		}
		if($iquarter){
			if(!$iyear){$iyear=$cyear;}
			$strwhere.=" and dev.create_date>=".toTime($iyear.'-'.(($iquarter-1)*3+1).'-01')." and dev.create_date<".strtotime('+3month',toTime($iyear.'-'.(($iquarter-1)*3+1).'-01'))."";
			$this->assign("iyear",$iyear);
			$this->assign("iquarter",$iquarter);
			$echart_subtitle=$iyear.' / Q'.$iquarter;
		}else if($imonth){
			if(!$iyear){$iyear=$cyear;}
			$strwhere.=" and dev.create_date>=".toTime($iyear.'-'.$imonth.'-01')." and dev.create_date<".strtotime('+1month',toTime($iyear.'-'.$imonth.'-01'))."";
			$this->assign("iyear",$iyear);
			$this->assign("imonth",$imonth);
			$echart_subtitle=$iyear.' / '.$imonth;
		}

		$strsql = "select dev.wh,count(dev.id) as dev_qty from ".$db_prefix."device_mas dev where ";
		$strsql.= $strwhere;
		$strsql.= " group by dev.wh";
		$strsql.= " order by dev.wh";


		$datalist = $model->query($strsql);


		$this->assign("datalist",$datalist);

		foreach($datalist as $k=>$v){
			$chartlabel[]=$v['wh'];
			$chartdata[]="{value:".$v['dev_qty'].", name:'".$v['wh']."'}";
		}
		$this->assign("chartlabel",implode("','",$chartlabel));
		$this->assign("chartdata",implode(",",$chartdata));

		$this->assign("echart_subtitle",$echart_subtitle);

		$this->display();
	}


}