<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class TempController extends CommonController {
    public function index(){
        echo ('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>[ 您现在访问的是Home模块的Temp控制器 ]</div>');

	}
	
	public function show(){
        echo ('<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} body{ background: #fff; font-family: "微软雅黑"; color: #333;font-size:24px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.8em; font-size: 36px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p>欢迎使用 <b>ThinkPHP</b>！</p><br/>[ 您现在访问的是Home模块的Temp/show控制器 ]</div>');
		echo APP_NAME.'<br/>';	//当前项目名
		echo GROUP_NAME.'<br/>';	//	当前分组名
		echo MODULE_NAME.'<br/>';	//	当前模块名
		echo ACTION_NAME.'<br/>';	//	当前操作名
		echo APP_DEBUG.'<br/>';	//	是否开启调试模式
		echo MODE_NAME.'<br/>';	//	当前模式名称
		echo APP_PATH.'<br/>';	//	当前项目路径
		echo THINK_PATH.'<br/>';	//	系统框架路径
		echo MEMORY_LIMIT_ON.'<br/>';	//	系统内存统计支持
		echo RUNTIME_FILE.'<br/>';	//	项目编译缓存文件名
		echo THEME_NAME.'<br/>';	//	当前主题名称
		echo THEME_PATH.'<br/>';	//	当前模板主题路径
		echo APP_TMPL_PATH.'<br/>';	//
	}

	
}