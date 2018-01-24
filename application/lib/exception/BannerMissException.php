<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/20
 * Time: 17:59
 */

namespace app\lib\exception;


class BannerMissException extends BaseException
{
    public $code = 400;
    public $msg = '参数错误';
    public $errorCode = 10000;
}