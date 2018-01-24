<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 14:11
 */

namespace app\api\validate;


class TokenGet extends BaseValidate
{
    protected $rule = [
        'code' => 'require|isNotEmpty'
    ];

    protected $message = [
        'code' => '没有code不能获取Token'
    ];
}