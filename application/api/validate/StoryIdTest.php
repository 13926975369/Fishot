<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/12/12
 * Time: 9:12
 */

namespace app\api\validate;


class StoryIdTest extends BaseValidate
{
    protected $rule = [
        's_id' => 'require|isPositiveInteger|number',
    ];
}