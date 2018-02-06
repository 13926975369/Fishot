<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/30
 * Time: 19:24
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\exception\ParameterException;
use app\api\validate\IDMustBePostINT;
use app\api\model\Fishot_story as StoryModel;
use app\api\model\Fishot_user as UserModel;
use app\api\model\Image;
use app\api\validate\StoryIdTest;
use think\Request;

class Story extends BaseController
{
    //前置验证
    protected $beforeActionList = [
        'checkShareAlbumScope' => ['only' => 'showstory,delstory,change_album_background'],
    ];

    /*
     * 展示故事
     * @param    s_id 故事id  a_id 相册id username：上传此故事的用户名
     * */
    public function ShowStory($flag,$a_id = '',$s_id = '',$page = '',$size = '',$order = ''){
        $story = new StoryModel();
        if ($flag == 1){
            if(!preg_match("/^[0-9]+$/",$s_id)){
                throw new ParameterException();
            }
            $result_story = $story
                ->field('story,photo_url,photo_position,user_id')
                ->where('id' ,'=',(int)$s_id)
                ->select();
        }elseif ($flag == 2){
            if(!preg_match("/^[0-9]+$/",$a_id) || !preg_match("/^[0-9]+$/",$page) || !preg_match("/^[0-9]+$/",$size)){
                throw new ParameterException();
            }
            if ($order == '1'){
                //从近期到早期
                $or['shooting_time'] = 'desc';
            }elseif ($order == '2'){
                //从早期到近期
                $or['shooting_time'] = 'asc';
            }else{
                throw new ParameterException();
            }
            $result_story = $story
                ->field('story,photo_url,photo_position,user_id')
                ->where('group_id' ,'=',(int)$a_id)
                ->order($or)
                ->limit((int)$page-1,(int)$size)
                ->select();
            var_dump($result_story);
        }else{
            throw new ParameterException();
        }

        if (!$result_story){
            throw new ParameterException();
        }else{
            foreach ($result_story as $k => $v){
                //查到该用户的用户名并且返回
                $user_id = $result_story[$k]['user_id'];
                $user = new UserModel();
                $nickname = $user->field('username,portrait')->where('id','=',$user_id)->select();
                if (!$nickname){
                    throw new ParameterException();
                }else{
                    $result_story[$k]['username'] = $nickname[0]['username'];
                    $result_story[$k]['portrait'] = $nickname[0]['portrait'];
                }
            }
            return json_encode([
                'code' => 200,
                'msg' => $result_story
            ]);
        }
    }

    /*
     * 删除故事
     * @param    id 故事id  a_id 相册id username：上传此故事的用户名
     * */
    public function DelStory($story_id){
        (new StoryIdTest())->goToCheck(['s_id'=>$story_id]);
        $story = new StoryModel();
        //根据id删除数据
        $result = $story->where('id','=',$story_id)->delete();
        if (!$result){
            throw new ParameterException();
        }else{
            $result = [
                'code' => 200,
                'msg' => 0
            ];
            return json_encode($result);
        }
    }

    //编辑相册的背景图片
    public function Change_album_background(){
        //上传背景
        $Image = new Image();
        $url = $Image->upload_image('photo');


    }
}