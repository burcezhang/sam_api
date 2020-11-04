<?php
$alipay_config=array();
//合作身份者id，以2088开头的16位纯数字
$alipay_config['partner']		= '0';
//安全检验码，以数字和字母组成的32位字符
$alipay_config['key']			= '0';
$alipay_config['seller_email']	= 'admin@admin.com';
//签名方式 不需修改
$alipay_config['sign_type']    = strtoupper('MD5');
//字符编码格式 目前支持 gbk 或 utf-8
$alipay_config['input_charset']= strtolower('utf-8');
//ca证书路径地址，用于curl中ssl校验
//请保证cacert.pem文件在当前文件夹目录中
$alipay_config['cacert']    = getcwd().'/Application/Home/Conf/cacert.pem';
//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$alipay_config['transport']    = 'http';
//页面跳转同步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
//return_url的域名不能写成http://localhost/create_direct_pay_by_user_php_utf8/return_url.php ，否则会导致return_url执行无效
$alipay_config['return_url']   = 'http://161.117.12.93/alipay~cancel.html';
//服务器异步通知页面路径，要用 http://格式的完整路径，不允许加?id=123这类自定义参数
$alipay_config['notify_url']   = 'http://161.117.12.93/alipay~notify.html';
//订单列表页面
$alipay_config['show_url']   = 'http://161.117.12.93/alipay~order.html';

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////
//支付接口代码
$alipay_config['code']	=	'alipay';
return array(
	//'配置项'=>'配置值'
	'WEB_DOMAIN' => 'http://161.117.12.93/',
	'DB_DSN' => 'mysql://root:321cc97c46@localhost:3306/anycarecc',
	'DB_PREFIX' => 'any_',
	'URL_CASE_INSENSITIVE' => true,
	'URL_PATHINFO_DEPR' =>'~',
	'URL_MODEL'=>2,
    'URL_HTML_SUFFIX'=>'html',	
	'SHOW_PAGE_TRACE'=>false,
	'PAGE_SIZE'=>10,
	'BAIDU_AK'=>'Uym6ARP3daPKQOgDCCOcskBHm7bNwuGt',
	//'BAIDU_INIT_LON'=>'113.944557', //地图初始化时的经度
	//'BAIDU_INIT_LAT'=>'22.544165', //地较初始化时的纬度
	//'BAIDU_INIT_LON'=>'113.955624', //地图初始化时的经度
	//'BAIDU_INIT_LAT'=>'22.546535', //地较初始化时的纬度

    //113.943163,22.54926;
	'BAIDU_INIT_LON'=>'22.549261', //地图初始化时的经度
	'BAIDU_INIT_LAT'=>'113.943163', //地较初始化时的纬度

	'GOOGLE_AK'=>'AIzaSyAvpKiPgu8wFTb27UANGbm93IncC2Ver98',
	'GOOGLE_INIT_LON'=>'126.969847',  // djkim_20160907
	'GOOGLE_INIT_LAT'=>'37.392207', 
	
        //22.546850/113.955980
	
	'BAIDU_OFFSET_LON'=>0.01199,
	'BAIDU_OFFSET_LAT'=>0.003055,
	
	'UPLOAD_DIR'=>'/Upload/Home/',
	'NEWS_LINK_DIR'=>'/Upload/Home/News/link',	
	'BASE_DIR' => dirname(dirname(dirname(dirname(__FILE__)))),
	
	'CALL7X24_DOMAIN' => '@csgyy', //@csgyy  @szzlh
	'CALL7X24_TYPE' => 'sip', //sip;gateway;Local;
	
	'DEVICE_INFO_TIMESPC' => '5,10,30',
	'DEVICE_INFO_TEMPSPC' => '-10,30,60',
	'DEVICE_INFO_TRACESPC' => '10,30,60',
	'DEVICE_INFO_TRACEDAY' => '5',
	'DEVICE_INFO_CYCSPC' => '07:00,18:00,22:00',
	'DEVICE_INFO_SERVICE_NO' => '075536991860,075536991860',
	'DEVICE_INFO_TCP_PORT' => '2233',
	'DEVICE_INFO_TCP_URL' => 'http://161.117.12.93',
	
	'AUTO_PASS_ALERT_USERID' => '1',
	'ALIPAY_CONFIG' => $alipay_config,

);
