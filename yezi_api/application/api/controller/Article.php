<?php
namespace app\api\controller;

class Article extends Common {
	/**
	 * 添加文章
	 * @Author   Yezi
	 * @DateTime 2020-07-10
	 */
	public function add_article(){
		$data = $this->params;
		$data['article_ctime'] = time();
		$res = db('article')->insertGetId($data);
		if ($res) {
			$this->return_msg(200,'文章添加成功!',$res);
		}else{
			$this->return_msg(400,'文章添加失败！');
		}
	}

	/**
	 * 获取文章列表
	 * @Author   Yezi
	 * @DateTime 2020-07-10
	 * @return   [json]     [返回json数据]
	 */
	public function article_list(){
		$data = $this->params;
		if (!isset($data['num'])) {
			$data['num'] = 10;
		}
		if (!isset($data['page'])) {
			$data['page'] = 1;
		}
		$where['article_uid'] = $data['user_id'];
		// 总记录数
		$count = db('article')->where($where)->count();
		// 总页数
		$page_num = ceil($count/$data['num']);
		$join = [['api_user au','au.user_id=a.article_uid']];
		$res = db('article')->alias('a')->field('a.*,au.user_nickname')->join($join)->where($where)->page($data['page'],$data['num'])->select();
		if ($res === false) {
			$this->return_msg(400,'查询失败！');
		}elseif (empty($res)) {
			$this->return_msg(400,'暂无数据！');
		}else{
			$return_data['articles'] = $res;
			$return_data['page_num'] = $page_num;
			$this->return_msg(200,'查询成功！',$return_data);
		}
	}

	/**
	 * 查看文章详情
	 * @Author   Yezi
	 * @DateTime 2020-07-10
	 * @return   [json]     [返回json数据]
	 */
	public function article_detail(){
		$data = $this->params;
		$where['article_id'] = $data['article_id'];
		$field = "a.*,u.user_nickname";
		$join = [['api_user u','u.user_id=a.article_uid']];
		$res = db('article')->alias('a')->field($field)->join($join)->where($where)->find();
		$res['article_content'] = htmlspecialchars_decode($res['article_content']);
		if (!$res) {
			$this->return_msg(400,'查询失败！');
		}else{
			$this->return_msg(200,'查询成功！',$res);
		}
	}

	/**
	 * 修改文章
	 * @Author   Yezi
	 * @DateTime 2020-07-10
	 * @return   [json]     [返回json数据]
	 */
	public function update_article(){
		$data = $this->params;
		$res = db('article')->where('article_id',$data['article_id'])->update($data);
		if ($res != false) {
			$this->return_msg(200,'修改成功！');
		}else{
			$this->return_msg(400,'修改失败！');
		}
	}

	/**
	 * 删除文章
	 * @Author   Yezi
	 * @DateTime 2020-07-10
	 * @return   [json]     [返回删除后的结果]
	 */
	public function delete_article(){
		$data = $this->params;
		$res =db('article')->delete($data['article_id']);
		if ($res) {
			$this->return_msg(200,'删除成功！');
		}else{
			$this->return_msg(400,'删除失败！');
		}
	}
}
