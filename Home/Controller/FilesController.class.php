<?php
namespace Home\Controller;

//use Think\Controller;
use Common\Controller\CommonController;

class FilesController extends CommonController {
	private $order = 'name';
	private $save_path = 'Upload/Home/News/';
	//文件保存目录URL
	private $save_url ='http://cc.anycare-cn.com/Upload/Home/News/';
	private function alert($msg) {
		header('Content-type: text/html; charset=UTF-8');
		echo json_encode(array('error' => 1, 'message' => $msg));
		exit;
	}
	public function uploadify()
	{
		if (!empty($_FILES)) {
			//图片上传设置
            $config = array(   
                'maxSize'    =>    2048000, 
                'savePath'   =>    '',  
				'rootPath'   =>    $this->save_path,  
                'saveName'   =>    array('uniqid',''), 
                'exts'       =>    array('jpg', 'gif', 'png', 'jpeg', 'csv', 'txt', 'pdf'),  
                'autoSub'    =>    false,   
                'subName'    =>    array('date','Ymd'),
            );
            $upload = new \Think\Upload($config);// 实例化上传类
			$images = $upload->upload();
			
			if(!$images){
				$oData['error']=1;
				$oData['info']=$upload->getError();				
			} else {
				$oData['error']=0;
				$oData['info'] = $images;
			}
			//echo $oData['info'];
			$this->ajaxReturn($oData,'JSON');
		}
		
	}
    public function upload(){
		//文件保存目录路径
		//定义允许上传的文件扩展名
		$ext_arr = array(
			'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
			'flash' => array('swf', 'flv'),
			'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
			'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2'),
		);
		$mArr=array('1'=>'Upload size exceeded allowed。','2'=>'Over form allows the size of the。','3'=>'Picture was only partially uploaded。','4'=>'Please select an image。','6'=>'Can not find a temporary directory。','7'=>'Error writing file。','8'=>'File upload stopped by extension。','999'=>'unknown error。');
		//最大文件大小
		$max_size = 2048000;
		
		if (!empty($_FILES['imgFile']['error'])) {
			$err=$_FILES['imgFile']['error'];
			if(!$mArr[$err])$err='Unknown Error。';
			else $err=$mArr[$err];
			$this->alert($err);
		}
		//有上传文件时
		if (empty($_FILES) === false) {
			//原文件名
			$file_name = $_FILES['imgFile']['name'];
			//服务器上临时文件名
			$tmp_name = $_FILES['imgFile']['tmp_name'];
			//文件大小
			$file_size = $_FILES['imgFile']['size'];
			//检查文件名
			if (!$file_name) $this->alert("Please select a file。");
			//检查目录
			if (@is_dir($this->save_path) === false) $this->alert("Upload directory does not exist。");
			//检查目录写权限
			if (@is_writable($this->save_path) === false) $this->alert("Upload directory does not have write permission。");
			//检查是否已上传
			if (@is_uploaded_file($tmp_name) === false) $this->alert("upload failed。");
			//检查文件大小
			if ($file_size > $max_size) $this->alert("Upload file size exceeds the limit。");
			//检查目录名
			$dir_name = I('get.dir');
			$dir_name = empty($dir_name)?'image':$dir_name;
			if (empty($ext_arr[$dir_name])) $this->alert("Directory name is incorrect。");
			//获得文件扩展名
			$temp_arr = explode(".", $file_name);
			$file_ext = array_pop($temp_arr);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);
			//检查扩展名
			if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
				$this->alert("Upload file extension is not allowed extension。\n only allowed" . implode(",", $ext_arr[$dir_name]) . "format。");
			}
			//新文件名
			$new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
			//移动文件
			$file_path = $this->save_path . $new_file_name;
			if (move_uploaded_file($tmp_name, $file_path) === false) {
				$this->alert("Upload file failed。");
			}
			@chmod($file_path, 0777);
			$file_url = $this->save_url . $new_file_name;
		
			header('Content-type: text/html; charset=UTF-8');
			echo json_encode(array('error' => 0, 'url' => $file_url));
			exit;
		}
	}
	public function cmp_func($a, $b) {
		//global $order;
		if ($a['is_dir'] && !$b['is_dir']) {
			return -1;
		} else if (!$a['is_dir'] && $b['is_dir']) {
			return 1;
		} else {
			if ($this->order == 'size') {
				if ($a['filesize'] > $b['filesize']) {
					return 1;
				} else if ($a['filesize'] < $b['filesize']) {
					return -1;
				} else {
					return 0;
				}
			} else if ($this->order == 'type') {
				return strcmp($a['filetype'], $b['filetype']);
			} else {
				return strcmp($a['filename'], $b['filename']);
			}
		}
	}
	public function manager(){
		$ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
		$dir_name = I('get.dir');
		$dir_name = empty($dir_name)?'image':$dir_name;
		if (!in_array($dir_name, array('image', 'flash', 'media', 'file'))) {
			echo "Invalid Directory name.";
			exit;
		}
		if (empty($_GET['path'])) {
			$current_path = $this->save_path;
			$current_url = $this->save_url;
			$current_dir_path = '';
			$moveup_dir_path = '';
		} else {
			$current_path = $this->save_path .I('get.path');
			$current_url = $this->save_url. I('get.path');
			$current_dir_path = I('get.path');
			$moveup_dir_path = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
		}
		$this->order=I('get.order');
		$this->order = empty($this->order) ? 'name' : strtolower($this->order);
		if (preg_match('/\.\./', $current_path)) {
			echo 'Access is not allowed.';
			exit;
		}
		//最后一个字符不是/
		if (!preg_match('/\/$/', $current_path)) {
			echo 'Parameter is not valid.';
			exit;
		}
		//目录不存在或不是目录
		if (!file_exists($current_path) || !is_dir($current_path)) {
			echo 'Directory does not exist.';
			exit;
		}
		$file_list = array();
		if ($handle = opendir($current_path)) {
			$i = 0;
			while (false !== ($filename = readdir($handle))) {
				if ($filename{0} == '.') continue;
				$file = $current_path . $filename;
				if (is_dir($file)) {
					$file_list[$i]['is_dir'] = true; //是否文件夹
					$file_list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件
					$file_list[$i]['filesize'] = 0; //文件大小
					$file_list[$i]['is_photo'] = false; //是否图片
					$file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
				} else {
					$file_list[$i]['is_dir'] = false;
					$file_list[$i]['has_file'] = false;
					$file_list[$i]['filesize'] = filesize($file);
					$file_list[$i]['dir_path'] = '';
					$file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					$file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
					$file_list[$i]['filetype'] = $file_ext;
				}
				$file_list[$i]['filename'] = $filename; //文件名，包含扩展名
				$file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
				$i++;
			}
			closedir($handle);
		}
		usort($file_list, 'cmp_func');
		$result = array();
		//相对于根目录的上一级目录
		$result['moveup_dir_path'] = $moveup_dir_path;
		//相对于根目录的当前目录
		$result['current_dir_path'] = $current_dir_path;
		//当前目录的URL
		$result['current_url'] = $current_url;
		//文件数
		$result['total_count'] = count($file_list);
		//文件列表数组
		$result['file_list'] = $file_list;
		
		//输出JSON字符串
		header('Content-type: application/json; charset=UTF-8');
		echo json_encode($result);
	}
}
