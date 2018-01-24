<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/17
 * Time: 23:58
 */

namespace app\api\exception;


class UserException extends BaseException
{
    public $code = 404;
    public $msg = '用户不存在';
    public $errorCode = 60000;
}