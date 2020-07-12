<?php
namespace app\api\controller;
/**
 * 
 */
class User extends Common
{
	/**
	 * 用户登入
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function login(){
		$data = $this->params;
		$user_name_type = $this->check_username($data['user_name']);
		switch ($user_name_type) {
			case 'phone':
				$this->check_exist($data['user_name'],'phone',1);
				$db_res = db('user')
				->field('user_id,user_phone,user_email,user_rtime,user_pwd')
				->where('user_phone',$data['user_name'])
				->find();
				break;
			case 'email':
				$this->check_exist($data['user_name'],'email',1);
				$db_res = db('user')
				->field('user_id,user_phone,user_email,user_rtime,user_pwd')
				->where('user_email',$data['user_name'])
				->find();
				break;
		}
		if ($db_res['user_pwd']!==$data['user_pwd']) {
			$this->return_msg(400,'用户名或密码错误,请重新输入！');
		}else{
			$this->return_msg(200,$db_res);
		}
	}

	/**
	 * 用户注册
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function register(){
		$data = $this->params;
		/******检测验证码******/
		$this->check_code($data['user_name'],$data['code']);
		/******检测用户名******/
		$user_name_type = $this->check_username($data['user_name']);
		switch ($user_name_type) {
			case 'phone':
				$this->check_exist($data['user_name'],'phone',0);
				$data['user_phone'] = $data['user_name'];
				break;
			case 'email':
				$this->check_exist($data['user_name'],'email',0);
				$data['user_email'] = $data['user_name'];
				break;
		}
		/******讲用户信息写入数据表******/
		unset($data['user_name']);
		$data['user_rtime'] = time();
		$res = db('user')->insertGetId($data);
		if (!$res) {
			$this->return_msg(400,'用户注册失败！');
		}else{
			$this->return_msg(200,'用户注册成功！',$res);
		}
	}

	/**
	 * 用户已头像上传
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function upload_head_img(){
		$data = $this->params;
		$head_img_path = $this->upload_file($data['user_icon'],'head_img');
		$res = db('user')->where('user_id',$data['user_id'])->setField('user_icon',$head_img_path);
		if ($res) {
			$this->return_msg(200,'头像上传成功！',$head_img_path);
		}else{
			$this->return_msg(400,'头像上传失败！');
		}
	}

	/**
	 * 修改密码
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [api返回json的数据]
	 */
	public function change_pwd(){
		$data = $this->params;
		$user_name_type = $this->check_username($data['user_name']);
		switch ($user_name_type) {
			case 'phone':
				$this->check_exist($data['user_name'],'phone',1);
				$where['user_phone'] = $data['user_name'];
				break;
			case 'email':
				$this->check_exist($data['user_name'],'email',1);
				$where['user_email'] = $data['user_name'];
				break;
		}
		$db_ini_pwd = db('user')->where($where)->value('user_pwd');
		if ($db_ini_pwd !==$data['user_ini_pwd']) {
			$this->return_msg(400,'原密码错误！');
		}
		$res = db('user')->where($where)->setField('user_pwd',$data['user_pwd']);
		if ($res !== false) {
			$this->return_msg(200,'密码修改成功！');
		}else{
			$this->return_msg(400,'密码修改失败！');
		}
	}

	/**
	 * 找回密码
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function find_pwd(){
		$data = $this->params;
		// 检测验证码
		$this->check_code($data['user_name'],$data['code']);
		$user_name_type = $this->check_username($data['user_name']);
		switch ($user_name_type) {
			case 'phone':
				$this->check_exist($data['user_name'],'phone',1);
				$where['user_phone'] = $data['user_name'];
				break;
			case 'email':
				$this->check_exist($data['user_name'],'email',1);
				$where['user_email'] = $data['user_name'];
				break;
		}
		$res = db('user')->where($where)->setField('user_pwd',$data['user_pwd']);
		if ($res !== false) {
			$this->return_msg(200,'密码修改成功！');
		}else{
			$this->return_msg(400,'密码修改失败！');
		}
	}

	// public function bind_phone(){
	// 	$data = $this->params;
	// 	$this->check_code($data['phone'],$data['code']);
	// 	$res = db('user')->where('user_id',$data['user_id'])->setField('user_phone',$data['phone']);
	// 	if ($res !== false) {
	// 		$this->return_msg(200,'手机绑定成功！');
	// 	}else{
	// 		$this->return_msg(400,'手机绑定失败！');
	// 	}
	// }

	// public function bind_email(){
	// 	$data = $this->params;
	// 	$this->check_code($data['email'],$data['code']);
	// 	$res = db('user')->where('user_id',$data['user_id'])->serField('user_email',$data['email']);
	// 	if ($res !== false) {
	// 		$this->return_msg(200,'邮箱绑定成功！');
	// 	}else{
	// 		$this->return_msg(400,'邮箱绑定失败！');
	// 	}
	// }

	/**
	 * 把那个顶手机号或邮箱
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function bind_username(){
		$data = $this->params;
		$this->check_code($data['user_name'],$data['code']);
		$user_name_type = $this->check_username($data['user_name']);
		switch ($user_name_type) {
			case 'phone':
				$type = '手机号';
				$update_data['user_phone'] = $data['user_name'];
				break;
			case 'email':
				$type = '邮箱';
				$update_data['user_email'] = $data['user_name'];
				break;
		}
		$res = db('user')->where('user_id',$data['user_id'])->update($update_data);
		if ($res !== false) {
			$this->return_msg(200,$type.'绑定成功！');
		}else{
			$this->return_msg(400,$type.'绑定失败！');
		}
	}

	/**
	 * 修改用户昵称
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @return   [json]     [返回json数据]
	 */
	public function set_nickname(){
		$data = $this->params;
		$res = db('user')->where('user_nickname',$data['user_nickname'])->find();
		if ($res) {
			$this->return_msg(400,'该昵称已被占用！');
		}
		$result = db('user')->where('user_id',$data['user_id'])->setField('user_nickname',$data['user_nickname']);
		if ($result) {
			$this->return_msg(200,'昵称修改成功！');
		}else{
			$this->return_msg(400,'昵称修改失败！');
		}
	}
}
