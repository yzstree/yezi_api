<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// return [
//     '__pattern__' => [
//         'name' => '\w+',
//     ],
//     '[hello]'     => [
//         ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
//         ':name' => ['index/hello', ['method' => 'post']],
//     ],

// ];
use think\Route;
// 用户登入
Route::post('user/login','api/User/login');
// 注册
Route::post('user/register','api/User/register');
// 头像上传
Route::post('user/icon','api/User/upload_head_img');
// 修改密码
Route::post('user/change_pwd','api/User/change_pwd');
// 找回密码
Route::post('user/find_pwd','api/User/find_pwd');
// // 绑定手机号
// Route::post('user/bind_phone','api/User/bind_phone');
// //绑定邮箱
// Route::post('user/bind_email','api/User/bind_email');
// 绑定手机号|邮箱
Route::post('user/bind_username','api/User/bind_username');
// 修改用户昵称
Route::post('user/set_nickname','api/User/set_nickname');
// 验证码
Route::get('code/:time/:token/:username/:is_exist','api/Code/getCode');
/******article******/
// 文章
Route::post('article','api/Article/add_article');
// 查看文章
Route::get('article/article_list/:time/:token/:user_id/[:num]/[:page]','api/Article/article_list');
// 单个文章详情
Route::get('article/article_detail/:time/:token/:article_id','api/Article/article_detail');
// 修改保存文章
Route::post('article/update_article','api/Article/update_article');
// 删除文章
Route::get('article/delete_article/:time/:token/:article_id','api/Article/delete_article');