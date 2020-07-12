<?php
namespace app\api\controller;
use think\Controller;
use think\Request;//获取参数
use think\Validate;//验证数据/参数
use think\DB;
use think\Image;

/**
 * 定义接口的基类
 */
class Common extends Controller
{
	protected $request;//获取api传过来的数据（参数）
	protected $validater;//用来验证数据、参数
	protected $params;//过滤后符合要求的参数
	protected $rules = array(//验证的规则
			'User' => array(
				'login' => array(
					'user_name'=>'require|max:20',
					'user_pwd'=>'require|length:32'
				),
				'register' => array(
					'user_name'=>'require|max:20',
					'user_pwd'=>'require|length:32',
					'code'=>'require|number|length:6'
				),
				'upload_head_img'=>array(
					'user_id' =>'require|number',
					'user_icon' => 'require|image|fileSize:2000000|fileExt:jpg,png,bmp,jpeg'
				),
				'change_pwd'=>array(
					'user_pwd'=>'require|length:32',
					'user_name'=>'require|max:20',
					'user_ini_pwd'=>'require|length:32',
				),
				'find_pwd' => array(
					'user_name'=>'require|max:20',
					'user_pwd'=>'require|length:32',
					'code'=>'require|number|length:6'
				),
				'bind_username' => array(
					'user_id'=>'require|number',
					'user_name'=>'require',
					'code'=>'require|number|length:6'
				),
				'set_nickname' => array(
					'user_id'=>'require|number',
					'user_nickname'=>'require|chsDash',
				),
			),
			'Code' => array(
				'getcode' => array(
					'username' => 'require',
					'is_exist' =>'require|number|length:1'
				),
			),
			'Article' => array(
				'add_article' => array(
					'article_uid' => 'require|number',
					'article_title' =>'require|chsDash'
				),
				'article_list' => array(
					'user_id' => 'require|number',
					'num' =>'number',
					'page'=>'number'
				),
				'article_detail' => array(
					'article_id' => 'require|number',
				),
				'update_article' => array(
					'article_id' => 'require|number',
					'article_title' =>'chsDash'
				),
				'delete_article' => array(
					'article_id' => 'require|number',
				),
			),
		);
	protected function _initialize(){
		parent::_initialize();//调用父类的构造函数
		$this->request = Request::instance();//调用request
		// $this->check_time($this->request->only(['time']));//仅获取时间的参数
		// $this->check_token($this->request->param());//验证token
		// $this->params = $this->check_params($this->request->except(['time','token']));//验证除了time，token之外的数据
		$this->params = $this->check_params($this->request->param(true));//获取包含文件的参数
	}

	/**
	 * [验证请求是否超时]
	 * @Author    Yezi
	 * @DateTime  2020-07-08
	 * @param     [array]      $arr [包含时间戳的参数数组]
	 * @return    [json]           [检测结果]
	 */
	public function check_time($arr){
		if (!isset($arr['time']) || intval($arr['time']<=1)) {
			$this->return_msg(400,'时间戳不正确!');
		}
		if (time()-intval($arr['time'])>60) {
			$this->return_msg(400,'请求超时!');
		}
	}

	/**
	 * [api的数据返回]
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [int]     $code [结果码 200：正常|4**：数据问题|5**：服务器问题]
	 * @param    string     $msg  [接口返回的提示信息]
	 * @param    array      $data [接口返回的数据]
	 * @return   [string]           [d最终的json数据]
	 */
	public function return_msg($code,$msg='',$data=[]){
		$return_data['code'] = $code;
		$return_data['msg'] = $msg;
		$return_data['data'] = $data;
		/********返回信息并且终止脚本**********/
		echo json_encode($return_data);die;//错误直接die掉，不能return，return会继续往下执行
	}

	/**
	 * 验证token值是否正确【防止数据被篡改】
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [array]     $arr [api接口传过来的数据]
	 * @return   [string]          [返回json数据]
	 */
	public function check_token($arr){
		// api接口传过来的数据
		if (!isset($arr['token'])||empty($arr['token'])) {
			$this->return_msg(400,'token不能为空！');
		}
		$api_token = $arr['token'];//api接口传过来的token
		// 服务端生成token,并对token进行加密
		unset($arr['token']);
		$service_token = '';
		foreach ($arr as $key => $value) {
			$service_token .= md5($value);
		}
		$service_token = md5('api_'.$service_token.'api_');//服务器端即时生成的token
		// 对比token，返回结果
		if ($api_token != $service_token) {
			$this->return_msg(400,'token值不正确！');
		}
	}

	/**
	 * 验证数据的必要性
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [array  $arr [api接口传过来的数据
	 * @return   [array       [返回过滤后合格的参数数据]
	 */
	public function check_params($arr){
		$rule = $this->rules[$this->request->controller()][$this->request->action()];
		$this->validater = new Validate($rule);
		if (!$this->validater->check($arr)) {
			$this->return_msg(400,$this->validater->getError());
		}
		return $arr;
	}

	/**
	 * 检测用户名
	 * @Author   Yezi
	 * @DateTime 2020-07-08
	 * @param    [string]     $username [用户名]
	 * @return   [string]               [返回结果]
	 */
	public function check_username($username){
		$is_email = Validate::is($username,'email')?1:0;
		$is_phone = preg_match('/^1[34578]\d{9}$/',$username)?4:2;
		$flag = $is_email+$is_phone;
		switch ($flag) {
			// 既不是邮箱也不是手机
			case '2':
				$this->return_msg(400,'邮箱或手机号不正确！');
				break;
			// 是邮箱不是手机
			case '3':
				return 'email';
				break;
			// 是手机不是邮箱
			case '4':
				return 'phone';
				break;	
		}
	}

	/**
	 * 判断手机号|邮箱是否存在
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [type]     $value [description]
	 * @param    [type]     $type  [description]
	 * @param    [type]     $exist [description]
	 * @return   [type]            [description]
	 */
	public function check_exist($value,$type,$exist){
		$type_num = $type == 'phone' ? 2 : 4;
		$flag = $type_num+$exist;//$exist的值是0或1
		$phone_res = db('user')->where('user_phone',$value)->find();
		$email_res = db('user')->where('user_email',$value)->find();
		// dump($email_res);die;
		switch ($flag) {
			// 2+0，是手机号需要不存在
			case '2':
				if ($phone_res) {
					$this->return_msg(400,'手机号已被占用！');
				}
				break;
			// 2+1，是手机号需要存在
			case '3':
				if (!$phone_res) {
					$this->return_msg(400,'手机号不存在！');
				}
				break;
			// 4+0，是邮箱需要不存在
			case '4':
				if ($email_res) {
					$this->return_msg(400,'邮箱已被占用！');
				}
				break;
			// 4+1，是邮箱需要存在
			case '5':
				if (!$email_res) {
					$this->return_msg(400,'邮箱不存在！');
				}
				break;
		}
	}

	/**
	 * 检测验证码
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [string]     $user_name [用户名]
	 * @param    [Int]     $code      [验证码]
	 * @return   [json]                [api接口返回的json数据]
	 */
	public function check_code($user_name,$code){
		//检测是否超时
		$last_time = session($user_name.'_last_send_time');
		if (time()-$last_time>600) {
			$this->return_msg(400,'验证码超时，请在一分钟之内验证！');
		}
		//检测验证码是否正确
		$md5_code = md5($user_name.'_'.md5($code));
		if (session($user_name.'_code')!==$md5_code) {
			$this->return_msg(400,'验证码不正确！');
		}
		// 不管正确与否，验证码值验证一次
		session($user_name.'_code',null);
	}

	/**
	 * 获取文件上传的地址
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [string]     $file [上传的文件]
	 * @param    string     $type [图片类型]
	 * @return   [string]           [图片地址]
	 */
	public function upload_file($file,$type=''){
		$info = $file->move(ROOT_PATH.'public'.DS.'uploads');
		if ($info) {
			$path = '/uploads/'.$info->getSaveName();
			// 裁剪图片
			if (!empty($type)) {
				$this->image_edit($path,$type);
			}
			return str_replace('\\', '/', $path);
		}else{
			$this->return_msg(400,$file->getError());
		}
	}

	/**
	 * 裁剪图片
	 * @Author   Yezi
	 * @DateTime 2020-07-09
	 * @param    [string]     $path [图片路径]
	 * @param    [string]     $type [图片类型]
	 * @return   [string]           [裁剪后的图片]
	 */
	public function image_edit($path,$type){
		$image = Image::open(ROOT_PATH.'public'.$path);
		switch ($type) {
			case 'head_img':
				$image->thumb(150, 150,Image::THUMB_CENTER)->save(ROOT_PATH.'public'.$path);
				break;
		}
	}
}

