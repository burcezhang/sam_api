<?php
return array(
	//'配置项'=>'配置值'
	'WEB_DOMAIN' => 'http://161.117.12.93/',
	'DB_DSN' => 'mysql://root:321cc97c46@localhost:3306/anycarecc',
	'DB_PREFIX' => 'any_',
	'URL_CASE_INSENSITIVE' => true,
	'URL_PATHINFO_DEPR' =>'_',
	'URL_MODEL'=>2,
    'URL_HTML_SUFFIX'=>'aspx',
	'VCODE_VALIDITY'=>30,//day
	//'SHOW_PAGE_TRACE'=>1,
	'UPLOAD_DIR'=>'/Upload/Appapi/',
	'NEWS_LINK_DIR'=>'/Upload/Home/News/link',		
	'BASE_DIR' => dirname(dirname(dirname(dirname(__FILE__)))),
);
