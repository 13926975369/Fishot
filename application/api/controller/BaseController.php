<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/19
 * Time: 18:33
 */

namespace app\api\controller;

use app\api\exception\UserException;
use app\api\service\UserToken as TokenService;
use app\api\model\Fishot_album as AlbumService;
use think\Controller;

class BaseController extends Controller
{
    //基类统一封装前置方法
    protected function checkPrimaryScope(){
        TokenService::needPrimaryScope();
    }

    protected function checkExclusiveScope(){
        TokenService::needExclusiveScope();
    }

    protected function checkShareAlbumScope(){
        AlbumService::needShareAlbumScope();
    }
}