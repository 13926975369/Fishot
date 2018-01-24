<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 17:05
 */

namespace app\api\exception;


class ParameterException extends BaseException
{
    public $code = 400;
    public $msg = '参数错误';
    public $errorCode = 10000;
}