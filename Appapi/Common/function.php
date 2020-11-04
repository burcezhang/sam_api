<?php

function toDate($time, $format = 'Y-m-d H:i:s') {
	if (empty ( $time )) {
		return '';
	}
	//date_default_timezone_set('Asia/Seoul');
	date_default_timezone_set('Asia/Shanghai');
	$format = str_replace ( '#', ':', $format );
	return date ($format, $time );
}

function toTime($sDatetime) {
	if (empty ( $sDatetime )) {
		return '';
	}
	//date_default_timezone_set('Asia/Seoul');
	date_default_timezone_set('Asia/Shanghai');
	return strtotime($sDatetime);
}

function getCurrTime() {
	//date_default_timezone_set('Asia/Seoul');
	date_default_timezone_set('Asia/Shanghai');
	return time();
}

function checkLogin($account,$pwd) {
	$model = M('User_mas');
	$pwd = strtolower($pwd);
	$datainfo = $model->where("status_flg='Y' and account='$account' and pwd='$pwd'")->find();
	//var_dump($datainfo);
	if($datainfo)
	{
		return $datainfo;
	}else{
		$returnData['data']['error']=1;
		$returnData['data']['reason']='ID or Password  was incorrect';
		$result=json_encode($returnData);
		echo "($result)";
		exit;
	}
}

function getWeekString($repeat){
	$reStr = ',';
	$week = '0000000'.base_convert($repeat,10,2);
	$week = substr($week,-7);
	if( (int)substr($week,0,1) == 1 ){$reStr .= '0,';}
	if( (int)substr($week,1,1) == 1 ){$reStr .= '6,';}
	if( (int)substr($week,2,1) == 1 ){$reStr .= '5,';}
	if( (int)substr($week,3,1) == 1 ){$reStr .= '4,';}
	if( (int)substr($week,4,1) == 1 ){$reStr .= '3,';}
	if( (int)substr($week,5,1) == 1 ){$reStr .= '2,';}
	if( (int)substr($week,6,1) == 1 ){$reStr .= '1,';}
	return $reStr;
}
function timestamp() {
	list($s1, $s2) = explode(' ', microtime());
	return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
}
function sendSMS1($smsData)
{
	$timestamp = timestamp();
	$data = array();
	$data['cmd'] = 'send';
	$data['eprId'] = '355';
	$data['userId'] = 'szanydata';
	$data['key'] = Md5($data['eprId'] . $data['userId'] . 'Anydata355' . $timestamp);;
	$data['timestamp'] = $timestamp;
	$data['format'] = 'json';
	$data['mobile'] = is_array($smsData['phone']) ? implode(',', $smsData['phone']) : $smsData['phone'];
	$data['msgId'] = $timestamp;
	$data['content'] = ($smsData['body']);
	$http = new HttpClient('client.sms10000.com');
	$ret = $http->post('/api/webservice', $data);
	return $ret;
}

function sendSMS2($smsData)
{
	$sms_url = "https://apis.aligo.in/send/";
	$sms = [];
	$sms['user_id'] = "dranycare"; // SMS ID
	$sms['key'] = "3ry10qgwaj4ufeppf3n7r7tq6p4xcfm3";//authentication key
	$sms['msg'] = stripslashes($smsData['body']);
	$sms['receiver'] = is_array($smsData['phone']) ? implode(',', $smsData['phone']) : $smsData['phone'];//收件人号码
	$sms['destination'] = $sms['receiver'];
	$sms['sender'] = '01052051478';//发送人号码
	$sms['rdate'] = date('Ymd');//发送日期
	$sms['rtime'] = date('Hi');//发送时间
	//
	$sms['testmode_yn'] = empty($smsData['testmode_yn']) ? '' : $smsData['testmode_yn'];
	//LMS, MMS 消息标题
	$sms['title'] = $smsData['act_obj'];
	$sms['msg_type'] = 'SMS';

	$host_info = explode("/", $sms_url);
	$port = $host_info[0] == 'https:' ? 443 : 80;
	$oCurl = curl_init();
	curl_setopt($oCurl, CURLOPT_PORT, $port);
	curl_setopt($oCurl, CURLOPT_URL, $sms_url);
	curl_setopt($oCurl, CURLOPT_POST, 1);
	curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($oCurl, CURLOPT_POSTFIELDS, $sms);
	curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
	$ret = curl_exec($oCurl);
	curl_close($oCurl);
	$ret=urldecode($ret);
	$retArr = json_decode($ret);
	return $retArr;
}

function sendSMS($smsData){
	header('Content-Type: text/html; charset=UTF-8');
	if($smsData['phone']){
		require_once('./ThinkPHP/Library/Org/Net/HttpClient.class.php');
		$ret = sendSMS2($smsData);

		$notes['phone']=is_array($smsData['phone'])?implode(',',$smsData['phone']):$smsData['phone'];;
		$notes['inData']=$smsData;
		$notes['reData']=$ret;

		$logData['user_id']=0;
		$logData['act_type']=$smsData['act_type'];
		$logData['module']='SMS';
		$logData['act_obj']=$smsData['act_obj'];
		$logData['notes']=serialize($notes);
		saveLog($logData);
		return true;
	}else{
		$notes['reData']='No phone';
		$logData['user_id']=0;
		$logData['act_type']=$smsData['act_type'];
		$logData['module']='SMS';
		$logData['act_obj']=$smsData['obj_id'];
		$logData['notes']=serialize($notes);
		saveLog($logData);
		return false;
	}
}
//存日志
function saveLog($logData){
	if(!$logData['user_id'])$logData['user_id']=0;
	if(!$logData['act_type'])$logData['act_type']='';
	if(!$logData['module'])$logData['module']='';
	if(!$logData['act_obj'])$logData['act_obj']='';
	if(!$logData['notes'])$logData['notes']='';
	$logData['act_time']=getCurrTime();
	return M('Sys_log')->data($logData)->add();
}




?>
