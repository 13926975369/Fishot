<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/30
 * Time: 19:08
 */

namespace app\api\validate;


class ShowAlbumValidate extends BaseValidate
{
    protected $rule = [
        'id' => 'require|isPositiveInteger',
        'number' => 'require|number',
        'page' => 'require|number'
    ];
}