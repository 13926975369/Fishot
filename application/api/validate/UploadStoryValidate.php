<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/27
 * Time: 20:48
 */

namespace app\api\validate;


class UploadStoryValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'time' => 'require|TimeValidate',
    ];

    protected function TimeValidate($value, $rule = '', $data = '', $field = ''){
        $pattern = '/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}\/[0-9]{2}$/';
        $result = preg_match($pattern,$value);
        if ($result){
            return true;
        }else{
            return $field.'时间格式不正确';
        }
    }
}