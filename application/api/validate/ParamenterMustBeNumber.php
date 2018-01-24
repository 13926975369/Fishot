<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/1/11
 * Time: 8:26
 */

namespace app\api\validate;


class ParamenterMustBeNumber extends BaseValidate
{
    protected $rule = [
        'key' => 'require|MustBeNumber'
    ];
}