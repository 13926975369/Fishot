<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 16:49
 */

namespace app\api\exception;


class TokenException extends BaseException
{
    public $code = 401;
    public $msg = 'Token已经过期或无效Token';
    public $errorCode = 10001;
}