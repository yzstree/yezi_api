<?php
namespace app\api\controller;
use phpmailer\phpmailer;
use submail\meaasgexsend;//SDK发送短信
/**
 * 
 */
class Code extends Common
{
	public function getCode(){
		$username = $this->params['username'];
		$exist = $this->params['is_exist'];
		$username_type = $this->check_username($username);
		switch ($username_type) {
			case 'phone':
				$this->get_code_by_username($username,'phone',$exist);
				break;
			case 'email':
				$this->get_code_by_username($username,'email',$exist);
				break;
		}
	}

	/**
	 * 手机号发送验证码
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [string]     $phone [手机号/邮箱]
	 * @param    [string]      [手机号/邮箱]
	 * @param    [int]     $exist [手机号/邮箱是否存在数据库中，1：是，0：不是]
	 * @return   [json]            [api返回的接口数据]
	 */
	public function get_code_by_username($username,$type,$exist){
		if ($type == 'phone') {
			$type_name = "手机";
		}else{
			$type_name = "邮箱";
		}
		/******检测手机号/邮箱是否存在******/
		$this->check_exist($username,$type,$exist);
		/******检查验证码请求频率，60s请求一次******/
		if (session("?".$username.'_last_send_time')) {
			if (time()-session($username.'_last_send_time')<60) {
				$this->return_msg(400,$type_name.'验证码请求频率过快！');
			}
		}
		/******生成验证码*****/
		$code = $this->make_code(6);
		/******使用session存储验证码，方便比对，MD5加密******/
		$md5_code = md5($username.'_'.md5($code));
		session($username.'_code',$md5_code);
		/******使用session存储发送验证码的时间******/
		session($username.'_last_send_time',time());                              
		/******发送验证码*****/
		if ($type == 'phone') {
			$this->send_code_to_phone($username,$code);
		}else{
			$this->send_code_to_email($username,$code);
		}	
	}

	/**
	 * 生成验证码
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [int]     $num [验证码位数]
	 * @return   [int]          [验证码]
	 */
	public function make_code($num){
		$max = pow(10, $num)-1;
		$min = pow(10, $num-1);
		return mt_rand($min,$max);
	}

	/**
	 * 向手机号发送短信验证码
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [string]     $phone [手机号]
	 * @param    [int]     $code  [验证码]
	 * @return   [json]            [api返回的json数据]
	 */
	public function send_code_to_phone($phone,$code){
		$url='https://api.mysubmail.com/message/xsend';//赛迪云通信
		$ch = curl_init();//初始化CURL句柄
		curl_setopt($ch, CURLOPT_URL, $url);//设置请求的url
		curl_setopt($ch, CURLOPT_HEADER, 0);//设置头文件
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设为TRUE把curl_exec()结果转化为字串，而不是直接输出
		curl_setopt($ch, CURLOPT_POST, 1);//设置请求方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置提交的数据
		$data=[
			'appId' => '',//
			'to' => '$phone',//目标手机号
			'project' => '',//项目的id
			'vars' =>'{"code":$code,"time":"60"}',//模板中的变量
			'signture' => '',//项目的key
		];
		$res = curl_exec($ch);//执行curl
		curl_close($ch);//关闭curl
		$res = json_encode($res);
		if ($res->status != 'success') {
			$this->return_msg(400,$res->msg);
		}else{
			$thsi->return_msg(200,'手机验证码已发送，请在一分钟之内验证！');
		}
	}

	/**
	 * 向手机号发送验证码，运用的是SDK方式
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [string]     $phone [目标手机号]
	 * @param    [int]     $code  [发送的验证码]
	 * @return   [json]            [api返回的json数据]
	 */
	// public function send_code_to_phone($phone,$code){
	// 	$submail = new MESSAGEXsend();
	// 	$submail->SetTo($phone);
	// 	$submail->SetProject();//项目id
	// 	$submail->AddVar('code',$code);//模板中的变量
	// 	$submail->AddVar('time',60);
	// 	$xsend= $sunmail->xsend();
	// 	if ($xsend['status'] != 'success') {
	// 		$this->return_msg(400,$xsend['msg']);
	// 	}else{
	// 		$thsi->return_msg(200,'手机验证码已发送，请在一分钟之内验证！');
	// 	}
	// }

	/**
	 *向邮箱发送验证码
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [string]     $email [目标邮箱]
	 * @param    [int]     $code  [验证码]
	 * @return   [json]            [返回的json数据]
	 */
	public function send_code_to_email($email,$code){
		$toemail = $email;
		//实例化PHPMailer核心类
		$mail = new PHPMailer();
		$mail->isSMTP();// 启用SMTP
		$mail->CharSet = 'utf8';//设置发送的邮件的编码
		$mail->Host = 'smtp.163.com';//smtp服务器的名称
		$mail->SMTPAuth=true;//启用smtp认证
		$mail->Username ='yzstree@163.com';//你的邮箱名
		$mail->Password = 'DEIAETKDPMEDAEUO';
		$mail->STMPSecure = 'ssl';//设置使用ssl加密方式登录鉴权
		$mail->Port = 25;//设置ssl连接smtp服务器的远程服务器端口号
		$mail->setFrom('yzstree@163.com','接口测试');//发件人地址()
		$mail->addAddress($toemail);
		$mail->addReplyTo('yzstree@163.com','Reply');//回复地址(可填可不填)
		$mail->Subject = '您有新的验证码！';//邮件主题
		$mail->Body = "这是一个测试邮件，您的验证码是$code,验证码的有效期是1分钟，本邮件请勿回复！";//邮件内容
		if (!$mail->send()) {
			$this->return_msg(400,$mail->ErrorInfo);
		}else{
			$this->return_msg(200,'您的验证码已经发送成功，请注意查收！');
		}
	}
}
