<?php
namespace app\api\validate;
use think\Validate;

class TestValidate extends Validate
{
//    验证器需要继承validate
    protected $rule = [
        'name' => 'require',
        'email' => 'email',
        'phone' => 'length:4',
        'area' => 'require|number',
        'price' => 'require'
    ];
}