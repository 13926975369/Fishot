<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/27
 * Time: 19:28
 */

namespace app\api\controller\v1;

use app\api\controller\BaseController;
use app\api\exception\ParameterException;
use app\api\validate\IDMustBePostINT;
use app\api\validate\ShowAlbumValidate;
use app\api\validate\UploadStoryValidate;
use app\api\model\Fishot_story as StoryModel;
use think\Request;

class Image extends BaseController
{
    //前置验证
    protected $beforeActionList = [
        'checkShareAlbumScope' => ['only' => 'uploadstory,showalbumphoto,showalbumphotoall,showphoto'],
    ];
    /*
     * 上传故事
     * @param    story故事的文字  photo代表照片的name  time时间：格式 xxxx/xx/xx/xx(年/月/日/时)
     * @param    position地点
     * */
    public function uploadStory($album_id,$time='1',$story_value,$position,$flag)
    {
        (new UploadStoryValidate())->goToCheck([
            'id' => $album_id,
            'time' => $time
        ]);
        $story = new StoryModel();
        if ($flag == '1') $sql = $story->save_message($album_id,$time,$story_value,$position);
        elseif ($flag == '2') $sql = $story->save_images($album_id,$time,$position);
        elseif ($flag == '3') $sql = $story->save_msg($album_id,$story_value);
        else throw new ParameterException();
        if (!$sql) {
            throw new ParameterException();
        } else {
            return json_encode([
                'msg' => '上传成功',
                'code' => 200
            ]);
        }
    }

    /*
     * 上传故事
     * @param    story故事的文字  photo代表照片的name  time时间：格式 xxxx/xx/xx/xx(年/月/日/时)
     * */

    public function uploadStoryImage($a_id,$time,$story_value,$position)
    {
        (new UploadStoryValidate())->goToCheck([
            'id' => $a_id,
            'time' => $time
        ]);
        $story = new StoryModel();
        $sql = $story->save_message($a_id,$time,$story_value,$position);
        if (!$sql) {
            throw new ParameterException();
        } else {
            $result = [
                'msg' => '上传成功',
                'code' => 200
            ];
            return json_encode($result);
        }
    }


    /*
     * 展示相册图片
     * @param    id 相册id    number  想要的相片数，按照时间先看近期的   page页数
     * */
    public function ShowAlbumPhoto($albumId,$page,$number)
    {
        (new ShowAlbumValidate())->goToCheck([
            'id' => $albumId,
            'number' => $number,
            'page' => $page
        ]);
        $story = new StoryModel();
        $result_photo = $story
            ->where('group_id', '=', $albumId)
            ->limit((int)$page-1,(int)$number)
            ->field('photo_url')
            ->order('shooting_time', 'desc')
            ->select();
        if (!$result_photo) {
            throw new ParameterException();
        } else {
            return json_encode([
                'code' => 200,
                'msg' => $result_photo
            ]);
        }
    }

    /*
     * 展示所有的相册图片
     * @param    id 相册id   按照时间先看近期的
     * */
    public function ShowAlbumPhotoAll($albumId)
    {
        (new IDMustBePostINT())->goToCheck(['id' => $albumId]);
        //验证数据
        $story = new StoryModel();
        $result_photo = $story
            ->where('group_id', '=', $albumId)
            ->field('photo_url')
            ->order('shooting_time', 'desc')
            ->select();
        if (!$result_photo) {
            throw new ParameterException();
        } else {
            return json_encode([
                'code' => 200,
                'msg' => $result_photo
            ]);
        }
    }

    /*
     * 时间轴展示照片
     * @param    id 相册id   按照时间
     * */
    public function ShowPhoto()
    {
        (new IDMustBePostINT())->goCheck();
        $albumId = Request::instance()->get('id');
        //验证数据
        $story = new StoryModel();
        $result_photo = $story
            ->where('group_id', '=', $albumId)
            ->field('photo_url,story')
            ->order('shooting_time', 'asc')
            ->select();
        if (!$result_photo) {
            throw new ParameterException();
        } else {
            $result_photo['code'] = 200;
            return json_encode($result_photo);
        }
    }
}