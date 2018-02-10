<?php
namespace app\api\validate;

class IDMustBePostINT extends BaseValidate
{
    protected $rule = [
        'id' => 'require|number'
    ];

    protected $message = [
        'id.require' => '参数不能为空！',
        'id.number' => '参数必须为整数！',
    ];

    protected $field = [
        'id' => '参数',
    ];

}