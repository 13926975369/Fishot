<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/1/6
 * Time: 2:57
 */

namespace app\api\validate;


class PatternValidate extends BaseValidate
{
    protected $rule = [
        'token' => 'require|isToken',
        'type' => 'require|isType',
    ];

    //判断Token格式
    protected function isToken($value, $rule = '', $data = '', $field = ''){
        $pattern = '/[a-z0-9]{32}/';
        if (preg_match($pattern,$value)){
            return true;
        }else{
            return $field.'格式不对';
        }
    }

    //判断type格式
    protected function isType($value, $rule = '', $data = '', $field = ''){
        $pattern = '/^A0[0-9]{2}$/';
        if(preg_match($pattern,$value)){
            return true;
        }else{
            return $field.'格式不对';
        }
    }
}