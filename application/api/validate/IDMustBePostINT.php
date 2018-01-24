<?php
namespace app\api\validate;

class IDMustBePostINT extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger'
    ];
//    data是个数组，field是说明我们现在的参数名字是id，rule一般来说我们是用不到的，value就是id参数的值
    protected function isPostiveInteger($value, $rule = '', $data = '', $field = ''){
//        value+0的意思是字符串转化数字
        if (is_numeric($value) && is_int($value+0) && ($value+0) > 0){
            return true;
        }else{
            return $field.'必须是正整数';
        }
    }
}