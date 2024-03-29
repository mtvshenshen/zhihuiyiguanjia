<?php

namespace Qwadmin\Controller;
use Common\Controller\BaseController;
use Think\Auth;
class LoginController extends BaseController {
    public function index(){
	
		$flag = false;
        $auth = cookie('auth');
		list($identifier, $token) = explode(',', $auth);
		if (ctype_alnum($identifier) && ctype_alnum($token)) {
			$user = M('member')->field('uid,user,identifier,token,salt')->where(array('identifier'=>$identifier))->find();
			if($user) {
				if($token == $user['token'] && $user['identifier'] == password($user['uid'].md5($user['user'].$user['salt']))){
					$flag = true;
					$this->USER = $user;
				}
			}
		}
        if ($flag) { 
           $this -> error('您已经登录,正在跳转到主页',U("index/index"));
        }
		$image = M('setting')->where("k='pcimg'")->find();
		$this->assign('image' , $image);
		
		$this -> display();
    }
    public function login(){
		$verify = isset($_POST['verify'])?trim($_POST['verify']):'';
		// if (!$this->check_verify($verify,'login')) {
		// 	$this -> error('验证码错误！',U("login/index"));
		// }

		$username = isset($_POST['user'])?trim($_POST['user']):'';
		$password = isset($_POST['password'])?password(trim($_POST['password'])):'';
		$remember = isset($_POST['remember'])?$_POST['remember']:0;
		if ($username=='') {
			$this -> error('用户名不能为空！',U("login/index"));
		} elseif ($password=='') {
			$this -> error('密码必须！',U("login/index"));
		}

		$model = M("Member");
		//$user = $model ->field('uid,user')-> where(array('user'=>$username,'password'=>'123456')) -> find();
		$user = $model ->field('uid,user')-> where(array('user'=>$username,'password'=>$password)) -> find();
		if($user) {
			$token = password(uniqid(rand(), TRUE));
			$salt = random(10);
			$identifier = password($user['uid'].md5($user['user'].$salt));
			$auth = $identifier.','.$token;
			
			M('member')->data(array('identifier'=>$identifier,'token'=>$token,'salt'=>$salt))->where(array('uid'=>$user['uid']))->save();

			if($remember){
				cookie('auth',$auth,3600*24*365);//记住我
			}else{
				cookie('auth',$auth);
			}
			addlog('登录成功。');
			$url=U('index/index');
			header("Location: $url");
			exit(0);
		}else{
			addlog('登录失败。',$username);
			$this -> error('登录失败，请重试！',U("login/index"));
		}
    }
	
	public function verify() { //刷新? &区别于URL的设置规则 --规则1 用? 规则2 用&
		$config = array(
		'fontSize' => 14, // 验证码字体大小
		'length' => 3, // 验证码位数
		'useNoise' => false, // 关闭验证码杂点
		'imageW'=>100,//图片宽度
		'imageH'=>30,//图片高
		);
		$verify = new \Think\Verify($config);
		$verify -> entry('login');
	}
	
	function check_verify($code, $id = '') {
		$verify = new \Think\Verify();
		return $verify -> check($code, $id);
	}
}
