<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/2/7
 * Time: 10:41
 */

namespace app\api\validate;


class CreateAlbum extends BaseValidate
{
    protected $rule = [
        'color' => 'require|isColor',
    ];

    protected $message = [
        'color.require' => '传入颜色不能为空！',
    ];

    protected $field = [
        'color' => '传入颜色',
    ];

    protected function isColor($value, $rule = '', $data = '', $field = ''){
//        value+0的意思是字符串转化数字
        if ($value == 'black' || $value == 'blue' || $value == 'brown' || $value == 'purple' || $value == 'green' || $value == 'blue' || $value == 'red'){
            return true;
        }else{
            return '传入颜色未在颜色列表中！';
        }
    }
}