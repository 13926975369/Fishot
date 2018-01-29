<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//Route::post('gettoken','api/v1.Token/getToken');
Route::post('index','api/v1.Index/index');
Route::get('indexx','api/v1.Banner/ttt');
Route::get('index','api/v1.Banner/tttt');
//Route::get('indexxx','api/v1.Index/test');
//User
Route::rule('relatedinformation/:id','api/v1.User/getRelatedInformation','GET',['https' => false]);
Route::rule('albuminfo','api/v1.User/getUserInfo','GET|POST',['https' => false]);

Route::rule('addfriend/:id','api/v1.User/addFriend','GET|POST',['https' => false]);
Route::rule('showfriend/:id','api/v1.User/showFriend','GET|POST',['https' => false]);
Route::rule('searchuser/:identity','api/v1.User/searchUser','GET|POST',['https' => false]);
Route::rule('buildgroup/:id','api/v1.User/buildGroup','GET|POST',['https' => false]);

//Story
Route::rule('showstory','api/v1.Story/ShowStory','GET|POST',['https' => false]);
Route::rule('delstory','api/v1.Story/DelStory','GET|POST',['https' => false]);
//Image
Route::rule('uploadstory','api/v1.Image/uploadStory','POST',['https' => false]);
Route::rule('showalbumphoto/:id','api/v1.Image/ShowAlbumPhoto','GET|POST',['https' => false]);
Route::rule('showalbumphotoall/:id','api/v1.Image/ShowAlbumPhotoAll','GET|POST',['https' => false]);
Route::rule('showphoto/:id','api/v1.Image/ShowPhoto','GET|POST',['https' => false]);
//Token
Route::rule('gettoken','api/v1.Token/getToken','POST',['https' => false]);
//Route::rule('relatedinformation/:id','','GET|POST',['https' => false]);
