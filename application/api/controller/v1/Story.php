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
use app\api\exception\UpdateException;
use app\api\model\Fishot_story as StoryModel;
use app\api\model\Fishot_user as UserModel;
use app\api\model\Fishot_sharealbum;
use app\api\model\Image;
use app\api\service\Token;
use app\api\validate\AlbumName;
use app\api\validate\IDMustBePostINT;
use app\api\validate\StoryIdTest;


class Story extends BaseController
{
    //前置验证
    protected $beforeActionList = [
        'checkShareAlbumScope' => ['only' => 'showstory,delstory,create_album,destroy,change_album_background'],
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

    //创建相册
    public function create_album(){
//        $post = input('post.');
//        //数据
//        $data = [];
//        //名字和描述
//        if (array_key_exists('name',$post)){
//            //防XSS
//            $name = strip_tags($post['name']);
//            $name = htmlspecialchars($name);
//            $data['name'] = $name;
//        }
//        if (array_key_exists('statement',$post)){
//            //防XSS
//            $statement = strip_tags($post['statement']);
//            $statement = htmlspecialchars($statement);
//            $data['statement'] = $statement;
//        }
//        if (!array_key_exists('color',$post)){
//            throw new BaseException([
//                'msg' => '无颜色传入！'
//            ]);
//        }
//
//        (new CreateAlbum())->goToCheck($data);
//
//
//        //传图片
//        $photo = Request::instance()->file('photo');
//        if ($photo){
//            //创建相册
//            $Image = new Image();
//            $url = $Image->upload_image('photo');
//            $data['background'] = $url;
//        }
//
//
        //拿用户id
        $uid = Token::getCurrentUid();


        $sharealbum = new Fishot_sharealbum();
        $info = $sharealbum->insertGetId([
            'main_speaker' => $uid
        ]);

        return json_encode([
            'code' => 200,
            'msg' => $info
        ]);
    }

    //销毁相册
    public function destroy($data){
        if (!array_key_exists('id',$data)){
            throw new ParameterException([
                'msg' => '无标识'
            ]);
        }
        //检验
        (new IDMustBePostINT())->goToCheck($data);
        $sharealbum = new Fishot_sharealbum();
        $result = $sharealbum->where([
            'id' => $data['id']
        ])->delete();
        if (!$result){
            throw new ParameterException();
        }
        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    //修改相册的封面
    public function change_album_background(){
        $id = input('post.data');
        (new IDMustBePostINT())->goToCheck([
            'id' => $id
        ]);

        $image = new Image();
        $url = $image->upload_image('photo');
        $sharealbum = new Fishot_sharealbum();
        $u = $sharealbum->where('id','=',$id)
            ->field('background')
            ->find();
        $result = $sharealbum->where([
            'id' => $id
        ])->update([
                'background' => $url
        ]);

        if (!$result){
            if (is_file(COMMON_PATH."/".$url)){
                unlink(COMMON_PATH."/".$url);
            }
            throw new UpdateException([
                'msg' => '更新出错，身份不正确！'
            ]);
        }
        if ($u['background'] != 'upload/album.png'){
            if (is_file(COMMON_PATH."/".$u['background'])){
                unlink(COMMON_PATH."/".$u['background']);
            }
        }
        $new_url = config('setting.image_root').$url;
        return json_encode([
            'code' => 200,
            'msg' => $new_url
        ]);
    }

    //修改相册的名字和描述
    public function change_album_info($data){
        if (!array_key_exists('id',$data)){
            throw new ParameterException([
                'msg' => '无标识'
            ]);
        }
        if (!array_key_exists('name',$data)){
            throw new ParameterException([
                'msg' => '无名字'
            ]);
        }
        if (!array_key_exists('statement',$data)){
            throw new ParameterException([
                'msg' => '无描述'
            ]);
        }

        (new IDMustBePostINT())->goToCheck($data);
        (new AlbumName())->goToCheck($data);

        $name = xss($data['name']);
        $statement = xss($data['statement']);

        $sharealbum = new Fishot_sharealbum();
        $info = $sharealbum->where([
            'id' => $data['id']
        ])->field('group_name,statement')->find();
        $d['group_name'] = $name;
        $d['statement'] = $statement;
        if ($name == $info['group_name']){
            unset($d['group_name']);
        }
        if ($statement == $info['statement']){
            unset($d['statement']);
        };
        if ($d){
            $result = $sharealbum->where([
                'id' => $data['id']
            ])->update($d);
            if (!$result){
                throw new UpdateException();
            }
        }
        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function  show_album_background($data){
        if (!array_key_exists('id',$data)){
            throw new ParameterException([
                'msg' => '无标识'
            ]);
        }

        (new IDMustBePostINT())->goToCheck($data);

        $sharealbum = new Fishot_sharealbum();
        $url = $sharealbum->where([
            'id' => $data['id']
        ])->field('background')->find();
        $new_url = config('setting.image_root').$url['background'];
        return json_encode([
            'code' => 200,
            'msg' => $new_url
        ]);
    }
}