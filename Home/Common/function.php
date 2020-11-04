<?php
function toDate($time, $format = 'Y-m-d H:i:s')
{
    if (empty ($time)) {
        return '';
    }
    //date_default_timezone_set('Asia/Seoul');
    date_default_timezone_set('Asia/Shanghai');
    $format = str_replace('#', ':', $format);
    return date($format, $time);
}

function toTime($sDatetime)
{
    if (empty ($sDatetime)) {
        return '';
    }
    //date_default_timezone_set('Asia/Seoul');
    date_default_timezone_set('Asia/Shanghai');
    return strtotime($sDatetime);
}

function getCurrTime()
{
    //date_default_timezone_set('Asia/Seoul');
    date_default_timezone_set('Asia/Shanghai');
    return time();
}

/*
$user_type:
ADMIN=管理员,
WEBUSER=临护人,
OPERATOR=客服,
OPERMANAGER=客服经理,
CUSTOMERMANAGER=客户经理,
INVENTORY=进销存管理
*/
function checkRole($req_role)
{
    $userinfo = cookie('userinfo');
    if ((int)$userinfo['id']) {
        $user_type = $userinfo['user_type'];
        if (strtoupper($user_type) == 'ADMINS' && strtoupper($req_role) != 'OPERATOR') {
            return true;
        } else if (strtoupper($req_role) == 'OPERATOR') { //只要有客服属性的都可以进入
            if ($userinfo['is_service'] == 'Y' && $userinfo['service_no'] != '') {
                return true;
            } else {
                return false;
            }
        } else if (strtoupper($user_type) == strtoupper($req_role) && strtoupper($req_role) == 'WEBUSER') {
            return true;
        } else if (stripos(',,,' . strtoupper($userinfo['role']) . ',', ',' . strtoupper($req_role)) > 0) {
            return true;
        } else {
            return false;
        }
    } else {
        echo '<script type="text/javascript">alert("' . L('common_alert_login_timeout') . '");window.location.href="' . U('Index/index') . '";</script>';
        return false;
    }
}

//获取告警状态
function getAlertState($state)
{
    switch (strtoupper($state)) {
        case 'A':
            return L('common_alert_list_state_a');
            break;
        case 'W':
            return L('common_alert_list_state_w');
            break;
        case 'P':
            return L('common_alert_list_state_p');
            break;
        case 'F':
            return L('common_alert_list_state_f');
            break;
        case 'C':
            return L('common_alert_list_state_c');
            break;
        default:
            return $state;
            break;
    }
}

// 获取权限语言包
function getRoleTitle($role_key)
{
    $role_detail = L('lb_role_list');
    $strout = '';
    foreach ($role_detail as $k => $v) {
        if (stripos(',,,' . $role_key . ',', ',' . $v['krole'] . ',') > 0) {
            $strout .= ',' . $v['title'];
        }
    }
    return substr($strout, 1);
}

function getDeviceState($state)
{
    switch (strtoupper($state)) {
        case 'Y':
            return 'Unused';
            break;
        case 'S':
            return 'Used';
            break;
        default:
            return $state;
            break;
    }
}

//获取告警类型
function getAlertType($stype)
{
    if (L('common_alert_type_' . $stype)) {
        return L('common_alert_type_' . $stype);
    } else {
        return $stype;
    }
}

/*
global $eol;
$eol = "\n";
if (isset($_SERVER['REQUEST_URI'])) {
    $eol = '<br>';
}
function _e($s) {global $eol;echo '-->'.$s."$eol";}
function _t($s) {global $eol;echo '   '.$s."$eol";}
function _x($s) {global $eol;echo '<--'.$s."$eol";}
*/
function error_output($str)
{
    echo "\033[1;40;31m" . $str . "\033[0m" . "\n";
}

function right_output($str)
{
    echo "\033[1;40;32m" . $str . "\033[0m" . "\n";
}

function pushAndroid($topic, $target, $alert, $body)
{
    require_once('./ThinkPHP/Library/Baidu/RequestCore.class.php');
    require_once('./ThinkPHP/Library/Baidu/ChannelException.class.php');
    require_once('./ThinkPHP/Library/Baidu/BaeBase.class.php');
    require_once('./ThinkPHP/Library/Baidu/Channel.class.php');
    $apiKey = "LFeyLysc49t6EeSgAAVc1GvBUM0ksFai";
    $secretKey = "89NLMeiWizPWq3TlnbxDoHSTFUqYGYTH";
    $channel = new Channel ($apiKey, $secretKey);
    $push_type = 2; //推送消息
    $optional[Channel::TAG_NAME] = $topic . '_' . $target;
    $optional[Channel::DEVICE_TYPE] = 3;
    $optional[Channel::MESSAGE_TYPE] = 0;//0:消息,1:通知
    //http://developer.baidu.com/wiki/index.php?title=docs/cplat/push/api/list
    //http://developer.baidu.com/wiki/index.php?title=docs/cplat/push/faq#.E4.B8.BA.E4.BD.95.E9.80.9A.E8.BF.87Server_SDK.E6.8E.A8.E9.80.81.E6.88.90.E5.8A.9F.EF.BC.8CAndroid.E7.AB.AF.E5.8D.B4.E6.94.B6.E4.B8.8D.E5.88.B0.E9.80.9A.E7.9F.A5.EF.BC.9F
    $message = array('title' => $alert['title'], 'description' => $body, 'alert_id' => $alert['id'], 'alert_type' => $topic, 'target' => $target);
    $message = json_encode($message);
    $message_key = "msg_key";
    $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);
    if (false === $ret) {
        error_output('WRONG, ' . __FUNCTION__ . ' ERROR!!!!!');
        error_output('ERROR NUMBER: ' . $channel->errno());
        error_output('ERROR MESSAGE: ' . $channel->errmsg());
        error_output('REQUEST ID: ' . $channel->getRequestId());
    }
    return $ret;
}

function pushIOS($topic, $target, $alert, $body)
{
    $apiKey = "LFeyLysc49t6EeSgAAVc1GvBUM0ksFai";
    $secretKey = "89NLMeiWizPWq3TlnbxDoHSTFUqYGYTH";
    $channel = new Channel ($apiKey, $secretKey);
    $push_type = 2; //推送消息
    $optional[Channel::TAG_NAME] = $topic . '_' . $target;
    $optional[Channel::DEVICE_TYPE] = 4;
    $optional[Channel::MESSAGE_TYPE] = 0;
    $optional[Channel::DEPLOY_STATUS] = 1;
    //通知类型的内容必须按指定内容发送，示例如下：
    $message = '{
		"aps":{
			"alert":"' . $body . '",
			"sound":"",
			"badge":0,
			"open_type":2,
			"custom_content":{\'alert_type\':\'' . $topic . '\',\'target\':\'' . $target . '\'}
		}
 	}';
    return $channel->pushMessage($push_type, $message, $optional);
}

function pushMessage($smsData)
{
    header('Content-Type: text/html; charset=UTF-8');
    if (!empty($smsData['topic']) && !empty($smsData['target'])) {
        $topic = $smsData['topic'];
        $target = $smsData['target'];
        $body = $smsData['body'];

        $Alert = array();
        $Alert['DEV_PWR_OFF_RPT'] = array('id' => 1, 'title' => '关机告警');
        $Alert['DEV_PWR_ON_RPT'] = array('id' => 2, 'title' => '开机告警');
        $Alert['EMERGENCY_ALERT_RPT'] = array('id' => 3, 'title' => '紧急告警');
        $Alert['EMERGENCY_CALL'] = array('id' => 4, 'title' => '紧急来电');
        $Alert['FULL_BATTERY_ALERT_RPT'] = array('id' => 5, 'title' => '满电告警');
        $Alert['GEOFENCE_ALERT_RPT'] = array('id' => 6, 'title' => '围栏告警');
        $Alert['HAZARDOUS_MOVEMENT_ALERT_RPT'] = array('id' => 7, 'title' => 'HAZARDOUS_MOVEMENT_ALERT_RPT');
        $Alert['LOW_BATTERY_ALERT_RPT'] = array('id' => 8, 'title' => '低电告警');
        $Alert['LOW_MOVMENT_ALERT_PRT'] = array('id' => 9, 'title' => 'LOW_MOVMENT_ALERT_PRT');
        $Alert['SHOCK_ALERT_RPT'] = array('id' => 10, 'title' => '跌倒报警');
        $Alert['SNOOZE_ALERT_RPT'] = array('id' => 11, 'title' => '静止报警');
        $Alert['TEMPERATURE_ALERT_RPT'] = array('id' => 12, 'title' => '温度告警');
        $Alert['SERVICE_EXPIRE'] = array('id' => 13, 'title' => '服务即将到期时提醒');
        $Alert['IN_HOME_ALERT_RPT'] = array('id' => 14, 'title' => '到家提醒');
        $Alert['OUT_OF_HOME_ALERT_RPT'] = array('id' => 15, 'title' => '离家提醒');
        $Alert['Notification'] = array('id' => 16, 'title' => $smsData['title']);
        //print_r($smsData);
        $ret = pushAndroid($topic, $target, $Alert[$topic], $body);
        //var_dump($ret);
        $notes['target'] = $topic . '/' . $target;
        $notes['inData'] = $body;
        $notes['reData'] = 1;

        $logData['user_id'] = 0;
        $logData['act_type'] = $smsData['act_type'];
        $logData['module'] = 'PUSH';
        $logData['act_obj'] = $smsData['act_obj'];
        $logData['notes'] = serialize($notes);
        saveLog($logData);
        return true;
    } else {
        $notes['topic'] = $smsData['topic'];
        $notes['target'] = $smsData['target'];
        $logData['user_id'] = 0;
        $logData['act_type'] = $smsData['act_type'];
        $logData['module'] = 'PUSH';
        $logData['act_obj'] = $smsData['obj_id'];
        $logData['notes'] = serialize($notes);
        saveLog($logData);
        return false;
    }
}

function timestamp()
{
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
    $sms['destination'] = $sms['receiver'] = is_array($smsData['phone']) ? implode(',', $smsData['phone']) : $smsData['phone'];//收件人号码
    $sms['sender'] = '01052051478';//发送人号码
    //$sms['rdate'] = date('Ymd');//发送日期
    //$sms['rtime'] = date('Hi');//发送时间
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

function sendSMS($smsData)
{
    header('Content-Type: text/html; charset=UTF-8');
    if ($smsData['phone']) {
        require_once('./ThinkPHP/Library/Org/Net/HttpClient.class.php');
        $ret = sendSMS2($smsData);
        $notes['phone'] = is_array($smsData['phone']) ? implode(',', $smsData['phone']) : $smsData['phone'];;
        $notes['inData'] = $smsData['body'];
        $notes['reData'] = $ret;

        $logData['user_id'] = 0;
        $logData['act_type'] = $smsData['act_type'];
        $logData['module'] = 'SMS';
        $logData['act_obj'] = $smsData['act_obj'];
        $logData['notes'] = serialize($notes);
        saveLog($logData);
        return true;
    } else {
        $notes['reData'] = 'No phone';
        $logData['user_id'] = 0;
        $logData['act_type'] = $smsData['act_type'];
        $logData['module'] = 'SMS';
        $logData['act_obj'] = $smsData['obj_id'];
        $logData['notes'] = serialize($notes);
        saveLog($logData);
        return false;
    }
}

/*
function sendSMS2($smsData){
	header('Content-Type: text/html; charset=UTF-8');
	$wsdl = "http://58.249.48.146:8080/ocservice/service/msgNormWebService?wsdl";
	if(is_array($smsData['phone'])){
		$phone = implode(',',$smsData['phone']);
	}else{
		$phone = $smsData['phone'];
	}
	if($phone){
		$client = new SoapClient($wsdl);
		$param = array('in0'=>'anycare123',
				'in1'=>'123anycare',
				'in2'=>'075500730948',
				'in3'=>''.$phone,
				'in4'=>$smsData['body'],
				'in5'=>'',
				'in6'=>'ANY'.rand(10000,99999));
		$ret = $client->sendNormMsg($param);
		$ret=get_object_vars($ret);
		//var_dump($ret);
		$notes['phone']=$phone;
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
*/
//存日志
function saveLog($logData)
{
    if (!$logData['user_id']) $logData['user_id'] = 0;
    if (!$logData['act_type']) $logData['act_type'] = '';
    if (!$logData['module']) $logData['module'] = '';
    if (!$logData['act_obj']) $logData['act_obj'] = '';
    if (!$logData['notes']) $logData['notes'] = '';
    $logData['act_time'] = getCurrTime();
    return M('Sys_log')->data($logData)->add();
}

//获取指定分类的所有子分类ID号
function getLocFullPathDown($categoryID)
{
    $array[] = $categoryID;
    do {
        $ids = '';
        $where = "parent_id in ($categoryID) and status_flg in ('Y')";
        $cate = M('Loc_mas')->where($where)->select();
        foreach ($cate as $k => $v) {
            $array[] = $v['id'];
            $ids .= ',' . $v['id'];
        }
        $ids = substr($ids, 1, strlen($ids));
        $categoryID = $ids;
    } while (!empty($cate));
    $ids = implode(',', $array);
    return $ids;
}

function getLocFullPathUp($categoryID)
{
    //初始化ID数组
    $array[] = $categoryID;
    do {
        $ids = '';
        $where = "id in ($categoryID) and status_flg in ('Y')";
        $cate = M('Loc_mas')->where($where)->select();
        foreach ($cate as $v) {
            $array[] = $v['parent_id'];
            $ids .= ',' . $v['parent_id'];
        }
        $ids = substr($ids, 1, strlen($ids));
        $categoryID = $ids;
    } while (!empty($cate));
    $ids = implode(',', $array);
    return $ids;
}

function getDeviceSet($device_sn)
{
    //只取SET1,SET2 (SMS,CALL)
    $device_set = M('Elderly_info')->field('device_set1,device_set2')->where("device_sn='$device_sn'")->find();
    $device_set['sms'] = unserialize($device_set['device_set1']);
    $device_set['call'] = unserialize($device_set['device_set2']);
    return $device_set;
}

function autoSMS($device_sn, $alert_type, $alertid)
{
    $sms_template = M('Sms_template')->where("sms_type='$alert_type' and status_flg='Y'")->order('id desc')->getField('content');
    $elderly_info = M('Elderly_info')->field('elderly_name,monitor_info')->where("device_sn in ('$device_sn')")->find();
    $monitor_info = unserialize($elderly_info['monitor_info']);
    for ($imonitor = 0; $imonitor < count($monitor_info); $imonitor++) {
        if ($monitor_info[$imonitor]['phone']) $monitor_phone[] = $monitor_info[$imonitor]['phone'];
    }
    $sms_template = str_replace('***', $elderly_info['elderly_name'], $sms_template);
    $smsData['phone'] = $monitor_phone;
    $smsData['body'] = $sms_template;
    $smsData['act_type'] = $alert_type;
    $smsData['act_obj'] = $alertid;
    sendSMS($smsData);
}

/*
function autoPushMessage($device_sn,$alert_type,$alertid){
	$sms_template = M('Sms_template')->where("sms_type='$alert_type' and status_flg='Y'")->order('id desc')->getField('content');
	$elderly_info = M('Elderly_info')->field('elderly_name')->where("device_sn in ('$device_sn')")->find();
	//$monitor_info = unserialize($elderly_info['monitor_info']);
	//for($imonitor=0;$imonitor<count($monitor_info);$imonitor++){
		//if($monitor_info[$imonitor]['phone']) $monitor_phone[] = $monitor_info[$imonitor]['phone'];
	//}
	$sms_template = str_replace('***',$elderly_info['elderly_name'],$sms_template);
	$smsData['topic']=$alert_type;
	$smsData['target']=$device_sn;
	$smsData['body'] =$sms_template;
	$smsData['act_type']=$alert_type;
	$smsData['act_obj']=$alertid;
	pushMessage($smsData);
}
*/
function autoPassAlert()
{
    //超过30分钟没有心跳的客服强制下线
    $now_time = strtotime("-30minute", getCurrTime());
    $data['service_heart_dt'] = 0;
    $data['service_status_flg'] = '0';
    M('User_mas')->data($data)->where("service_status_flg='1' and service_heart_dt>0 and service_heart_dt<" . $now_time)->save();

    $model = M('Alert_list');
    $tCurrtime = getCurrTime();
    $lock_pass_alert = F('lockpassalert'); //自动锁
    if ((int)$lock_pass_alert['look'] >= 1 && strtotime('-1hours', $tCurrtime) < (int)$lock_pass_alert['ptime']) {
        //有进程处理中
    } else {
        $lock_pass_alert['look'] = 1;
        F('lockpassalert', $lock_pass_alert); //上锁

        //生成预约告警
        $dCurrdate = toDate($tCurrtime, 'Ymd');
        $wCurrweek = toDate($tCurrtime, 'w');
        $remind_alert_list = M('Remind_alert')->where("start_date<=$tCurrtime and end_date2>=$tCurrtime and last_gen_alert<$dCurrdate and (repeat='0' or repeat_week like '%,$wCurrweek,%')")->select();

        foreach ($remind_alert_list as $k => $v) {
            unset($data);
            $data['alert_type'] = 'REMIND_ALERT';
            $data['alert_dt'] = strtotime('-' . (int)$v['befor_remind'] . 'minute', toTime(toDate($tCurrtime, 'Y-m-d') . ' ' . toDate($v['remind_time'], 'H:i:s')));
            $data['device_sn'] = $v['device_sn'];
            $data['operator'] = 0;
            $data['start_time'] = 0;
            $data['end_time'] = 0;
            $data['voice_src'] = $v['id'];
            $data['step'] = 0;
            $data['status_flg'] = 'A';
            $model->data($data)->add();

            unset($data);
            $data['id'] = $v['id'];
            $data['last_gen_alert'] = $dCurrdate;
            M('Remind_alert')->data($data)->save();
        }
        /*
        //检查交费服务项目，未交费则由系统自动处理，不弹窗
        unset($active_alert_list);
        $active_alert_list = $model->where("status_flg in('A','W') and alert_dt<=$tCurrtime and id>=".(int)$lock_pass_alert['altid'])->order("id asc")->select();
        foreach($active_alert_list as $k=>$v){
            switch($v['alert_type']){
                case 'SHOCK_ALERT_RPT': //跌倒告警
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']= 'on'; //$device_set['sms']['arg1'];
                    $checkAction['sms_st'] = toTime('2000-01-01');
                    $checkAction['sms_et'] = toTime('9999-01-01');
                    $checkAction['call_flg']=$device_set['call']['arg1'];
                    $checkAction['call_st'] = toTime('2000-01-01');
                    $checkAction['call_et'] = toTime('9999-01-01');
                    $checkAction['auto_pass'] = false;
                break;
                case 'EMERGENCY_CALL': //紧急来电
                case 'EMERGENCY_ALERT_RPT': //紧急告警
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']= 'on'; //$device_set['sms']['arg2'];
                    $checkAction['sms_st'] = toTime('2000-01-01');
                    $checkAction['sms_et'] = toTime('9999-01-01');
                    $checkAction['call_flg']=$device_set['call']['arg2'];
                    $checkAction['call_st'] = toTime('2000-01-01');
                    $checkAction['call_et'] = toTime('9999-01-01');
                    $checkAction['auto_pass'] = false;
                break;
                case 'SNOOZE_ALERT_RPT': //小睡告警
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']= 'on'; //$device_set['sms']['arg4'];
                    $checkAction['sms_st'] = toTime('2000-01-01');
                    $checkAction['sms_et'] = toTime('9999-01-01');
                    $checkAction['call_flg']=$device_set['call']['arg4'];
                    $checkAction['call_st'] = toTime('2000-01-01');
                    $checkAction['call_et'] = toTime('9999-01-01');
                    $checkAction['auto_pass'] = false;
                break;
                case 'GEOFENCE_ALERT_RPT': //围栏
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg3']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg3']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg3']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg3']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg3']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg3']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                case 'IN_HOME_ALERT_RPT': //在Beacon附近
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg3']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg3']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg3']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg3']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg3']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg3']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                case 'OUT_OF_HOME_ALERT_RPT': //离开Beacon
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg3']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg3']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg3']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg3']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg3']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg3']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                case 'TEMPERATURE_ALERT_RPT': //温度
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg5']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg5']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg5']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg5']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg5']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg5']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                case 'LOW_BATTERY_ALERT_RPT': //低电
                case 'FULL_BATTERY_ALERT_RPT': //满电
                case 'DEV_PWR_OFF_RPT': //关机
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg6']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg6']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg6']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg6']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg6']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg6']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                case 'DEV_PWR_ON_RPT': //开机
                    unset($device_set);unset($checkAction);
                    //$device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=false;
                    $checkAction['sms_st'] = 0;
                    $checkAction['sms_et'] = 0;
                    $checkAction['call_flg']=false;
                    $checkAction['call_st'] = 0;
                    $checkAction['call_et'] = 0;
                    $checkAction['auto_pass'] = false;
                break;
                case 'REMIND_ALERT': //预约
                    unset($device_set);unset($checkAction);
                    $device_set = getDeviceSet($v['device_sn']);
                    $checkAction['sms_flg']=$device_set['sms']['arg8']['chk_flg'];
                    $checkAction['sms_st'] = toTime($device_set['sms']['arg8']['time1']);
                    $checkAction['sms_et'] = toTime($device_set['sms']['arg8']['time2']);
                    $checkAction['call_flg']=$device_set['call']['arg8']['chk_flg'];
                    $checkAction['call_st'] = toTime($device_set['call']['arg8']['time1']);
                    $checkAction['call_et'] = toTime($device_set['call']['arg8']['time2']);
                    $checkAction['auto_pass'] = false;
                break;
                default:
                    $checkAction['auto_pass'] = true;
            }
            $save=true;//保存日志
            if($checkAction['auto_pass']){
                //PASS
                $save=false;
            }else if($checkAction['call_flg'] && getCurrTime()>$checkAction['call_st'] && getCurrTime()<$checkAction['call_et']){
                //订购了CALL服务，由后面弹窗处理
                $save=false;
            }
            //SMS和电话设置同时使用。
            if($checkAction['sms_flg'] && getCurrTime()>$checkAction['sms_st'] && getCurrTime()<$checkAction['sms_et']){
                //订购了SMS服务，系统自动处理
                autoPushMessage($v['device_sn'],$v['alert_type'],$v['id']);
                autoSMS($v['device_sn'],$v['alert_type'],$v['id']);
                unset($data);
                $data['id']         =$v['id'];
                $data['operator']   =(int)C('AUTO_PASS_ALERT_USERID');
                $data['step']       ='2';
                $data['start_time'] =getCurrTime();
                $data['end_time']   =getCurrTime();
                $data['status_flg'] ='F';
                $model->data($data)->save();
            }elseif($save){
                unset($data);
                $data['id']         =$v['id'];
                $data['operator']   =(int)C('AUTO_PASS_ALERT_USERID');
                $data['step']       ='1';
                $data['start_time'] =getCurrTime();
                $data['end_time']   =getCurrTime();
                $data['status_flg'] ='F';
                $model->data($data)->save();
            }
            $lock_pass_alert['altid']=(int)$v['id'];
        }*/
    }
    $lock_pass_alert['look'] = 0;
    $lock_pass_alert['ptime'] = getCurrTime();
    F('lockpassalert', $lock_pass_alert); //解锁
}

function uploadData($data, $fd = '')
{
    $ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
    $save_path = 'Upload/Home/' . $fd;
    //文件保存目录URL
    $save_url = 'http://161.117.12.93/Upload/Home/' . $fd;
    if (@is_dir($save_path) === false) return array(0, "Upload directory does not exist。");
    //检查目录写权限
    if (@is_writable($save_path) === false) return array(0, "Upload directory does not have write permission。");
    //data:image/jpeg;base64,
    $header = substr($data, 0, strpos($data, ','));
    $header = strtolower($header);
    if (preg_match('/^data\:([a-z]+)\/([a-z]+)\;([a-z0-9]+)/is', $header, $math)) {
        if (in_array($math[2], $ext_arr) === false) {
            return array(0, "Upload file extension is not allowed extension。\nOnly allowed" . implode(",", $ext_arr) . "format。");
        }
        if ($math[3] != 'base64') {
            return array(0, "Image data coding errors。");
        }
        if ($math[2] == 'jpeg') $math[2] = 'jpg';
        $fname = time() . '.' . $math[2];
        $data = substr($data, strpos($data, ',') + 1);
        $data = base64_decode($data);
        $fp = fopen($save_path . $fname, 'w+');

        $old = umask(0);
        chmod($save_path . $fname, 0777);
        $old = umask(0);

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $data);//写入
            flock($fp, LOCK_UN);
        }
        fclose($fp);
        return array(1, $save_url . $fname);
    }
    return array(0, "Image data error。");
}

function sendCallPush($address, $listId)
{
    echo "sendCallPush";
    header('Content-Type: Application/json; charset=UTF-8');
    require_once('./ThinkPHP/Library/Org/Net/HttpClient.class.php');
    $data = array();
    $data['address'] = $address;
    $data['listId'] = $listId;
    jcallLog($data, 8);

    $http = new HttpClient('https://ct-e1i.ct-e1.jp');
    $ret = $http->get('/AnyCare/api/Call/Connect', $data);

    $result = $ret->body->result;
    $errCode = $result['errCode'];


    switch ($errCode) {
        case 0 :
            // blank (CONNECT success）
            return true;
        case 1  :
            // OPEN error
            return false;
        case 2:
            // CONNECT error
            return false;
        case 9 :
            // Invalid parameter
            return false;
        case 429 :
            // request count overload
            return false;
        default     :
            return false;
    }

}

function jcallLog($logData, $errCode)
{
    echo "jcall log";
    if (!$logData['address']) $logData['address'] = 'null';
    if (!$logData['listId']) $logData['listId'] = 'null';
    $logData['dt'] = getCurrTime();
    $logData['resultCode'] = $errCode;
    return M('Jcall_log')->data($logData)->add();
}

?>
