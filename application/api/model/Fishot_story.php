<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/28
 * Time: 1:11
 */

namespace app\api\model;

use app\api\service\UserToken as TokenService;
use app\api\exception\ParameterException;
use app\api\model\Fishot_album as AlbumModel;
use think\Request;

class Fishot_story extends BaseModel
{
    //存储故事条
    public function save_message($album_id,$time,$story,$position)
    {
        $uid = TokenService::getCurrentUid();
        $photo = Request::instance()->file('photo');
        $published_time = date("Y-m-d H:i:s", time());

        //给定一个目录
        $info = $photo->move('upload');
        if ($info && $info->getPathname()) {
            $url = $info->getPathname();
        } else {
            throw new ParameterException();
        }
        //存入数据
        $result_story = self::data([
            'group_id' => $album_id,
            'user_id' => $uid,
            'story' => $story,
            'shooting_time' => $time,
            'photo_position' => $position,
            'photo_url' => $url,
            'published_time' => $published_time
        ])->save();

        //存一份到总的相册里
        $result_album = (new AlbumModel())->data([
            'user_id' => $uid,
            'photo_url' => $url,
            'date' => $published_time,
            'sharealbum_id' => $album_id,
        ])->save();

        if(!$result_story || !$result_album){
            return false;
        }else{
            return true;
        }
    }
    //只存故事叙述
    public function save_msg($album_id,$story)
    {
        $uid = TokenService::getCurrentUid();
        $published_time = date("Y-m-d H:i:s", time());

        //存入数据
        $result_story = self::data([
            'group_id' => $album_id,
            'user_id' => $uid,
            'story' => $story,
            'published_time' => $published_time
        ])->save();
        if(!$result_story){
            return false;
        }else{
            return true;
        }
    }
    //只存故事图片
    public function save_images($album_id,$time,$position)
    {
        $uid = TokenService::getCurrentUid();
        $photo = Request::instance()->file('photo');
        $published_time = date("Y-m-d H:i:s", time());

        //给定一个目录
        $info = $photo->move('upload');
        if ($info && $info->getPathname()) {
            $url = $info->getPathname();
        } else {
            throw new ParameterException();
        }
        //存入数据
        $result_story = self::data([
            'group_id' => $album_id,
            'user_id' => $uid,
            'shooting_time' => $time,
            'photo_position' => $position,
            'photo_url' => $url,
            'published_time' => $published_time
        ])->save();

        //存一份到总的相册里
        $result_album = (new AlbumModel())->data([
            'user_id' => $uid,
            'photo_url' => $url,
            'date' => $published_time,
            'sharealbum_id' => $album_id,
        ])->save();

        if(!$result_story || !$result_album){
            return false;
        }else{
            return true;
        }
    }

}