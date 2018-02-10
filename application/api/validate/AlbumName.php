<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/2/10
 * Time: 10:15
 */

namespace app\api\validate;


class AlbumName extends BaseValidate
{
    protected $rule = [
        'name' => 'require',
        'statement' => 'require'
    ];

    protected $message = [
        'name.require' => '名字不能为空！',
        'statement.require' => '描述不能为空！',
    ];

    protected $field = [
        'name' => '名字',
        'statement' => '名字'
    ];
}