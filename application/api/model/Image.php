<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/2/5
 * Time: 21:24
 */

namespace app\api\model;

use app\api\exception\ParameterException;
use app\api\exception\UserExistException;
use think\Request;

class Image extends BaseModel
{
    public function upload_image($parameter){
        $photo = Request::instance()->file($parameter);
        if (!$photo){
            throw new UserExistException([
                'msg' => '请上传图片！'
            ]);
        }
        //给定一个目录
        $info = $photo->validate(['size'=> 5242880,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
        if ($info && $info->getPathname()) {
            $url = $info->getPathname();
        } else {
            throw new ParameterException([
                'msg' => '请检验上传图片格式（jpg,jpeg,png,bmp,gif）！'
            ]);
        }

        return $url;
    }
}