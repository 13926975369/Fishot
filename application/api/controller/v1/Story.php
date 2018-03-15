<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/30
 * Time: 19:24
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\exception\BaseException;
use app\api\exception\ParameterException;
use app\api\exception\PasswordException;
use app\api\exception\UpdateException;
use app\api\exception\UserExistException;
use app\api\model\Fishot_sharemember;
use app\api\model\Fishot_story as StoryModel;
use app\api\model\Fishot_story;
use app\api\model\Fishot_user as UserModel;
use app\api\model\Fishot_sharealbum;
use app\api\model\Fishot_user;
use app\api\model\Image;
use app\api\service\Token;
use app\api\validate\AlbumName;
use app\api\validate\IDMustBePostINT;
use app\api\validate\StoryIdTest;
use DoctrineTest\InstantiatorTestAsset\PharExceptionAsset;
use think\Cache;
use think\Db;
use think\Request;
use think\Validate;


class Story extends BaseController
{
    //前置验证
    protected $beforeActionList = [
        'checkShareAlbumScope' => ['only' => 'showstory,delstory,create_album,destroy,change_album_background,show_name,show_statement,get_album_count,back_user_id,get_album_count
        add_story,id_get_info,show_single_story,show_album_story,get_album_story_count,change_rank,add_color,change_state,invite_friend,upload_head,add_photo,update_story,
        change_edit_state,get_head,exit_edit_state,real_add_pic,final_update'],
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
        $sharemember = new Fishot_sharemember();
        $USer = new Fishot_user();
        $rs = $USer->where([
            'id' => $uid,
        ])->field('album_count')->find();
        $sharealbum->startTrans();
        $sharemember->startTrans();
        $USer->startTrans();
        $info = $sharealbum->insertGetId([
            'main_speaker' => $uid,
            'publish_time' => time()
        ]);
        $info1 = $sharemember->data([
            'group_id' => $info,
            'user_id' => $uid
        ])->save();
        $info2 = $USer->where([
            'id' => $uid
        ])->update(['album_count' => (int)$rs['album_count']+1]);
        if ($info && $info1 && $info2){
            $sharealbum->commit();
            $sharemember->commit();
            $USer->commit();
        }else{
            $sharealbum->rollback();
            $sharemember->rollback();
            $USer->rollback();
            throw new UpdateException();
        }


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
        $member = new Fishot_sharemember();
        $story = new Fishot_story();
        $sharealbum->startTrans();
        $member->startTrans();
        $story->startTrans();
        $result = $sharealbum->where([
            'id' => $data['id']
        ])->delete();
        $re = $member->where([
            'group_id' => $data['id']
        ])->delete();
        $info = $story->where([
            'group_id' => $data['id']
        ])->find();
        //相册里有故事才能删
        if ($info){
            $rrr = $story->where([
                'group_id' => $data['id']
            ])->delete();
            if (!$rrr){
                $sharealbum->rollback();
                $member->rollback();
                $story->rollback();
                throw new ParameterException();
            }
        }

        if (!$result || !$re){
            $sharealbum->rollback();
            $member->rollback();
            $story->rollback();
            throw new ParameterException();
        }else{
            $sharealbum->commit();
            $member->commit();
            $story->commit();
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

    public function show_name($data){
        if (!array_key_exists('id',$data)){
            throw new ParameterException([
                'msg' => '无标识'
            ]);
        }

        (new IDMustBePostINT())->goToCheck($data);

        $sharealbum = new Fishot_sharealbum();
        $info = $sharealbum->where([
            'id' => $data['id']
        ])->field('group_name,statement,publish_time')->find();
        return json_encode([
            'code' => 200,
            'msg' => [
                'name' => $info['group_name'],
                'statement' => $info['statement'],
                'publish_time' => date("Y/m/d",$info['publish_time'])
            ]
        ]);
    }

    //还未按时间排序
    public function back_user_id($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('page',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!array_key_exists('size',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第二项！'
            ]);
        }
        $rule = [
            'page'  => 'require|number',
            'size'   => 'require|number'
        ];
        $msg = [
            'page.require' => '页号不能为空',
            'page.number'   => '页号必须是数字',
            'size.require' => '页面大小不能为空',
            'size.number'   => '页面大小必须是数字',
        ];
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            throw new BaseException([
                'msg' => $validate->getError()
            ]);
        }
        $page = (int)$data['page'];
        $size = (int)$data['size'];
        if ($page<0){
            throw new BaseException([
                'msg' => '数据参数中的第一项最小为0'
            ]);
        }
        if ($size<0){
            throw new BaseException([
                'msg' => '数据参数中的第二项最小为0'
            ]);
        }
        if ($page*$size == 0 && $page+$size!=0){
            throw new BaseException([
                'msg' => '为0情况只有数据参数中两项同时为零，否则最小从1开始'
            ]);
        }
        if ($page == 0 && $size == 0){
            $sharemember = new Fishot_sharemember();
            $info = $sharemember->where([
                    'user_id' => $uid
                ])
                ->field('group_id')->select();
            $result = [];
            $i = 0;
            foreach ($info as $v){
                $album_id = $v['group_id'];
                $sharealbum = new Fishot_sharealbum();
                $info2 = $sharealbum->where([
                    'id' => $album_id
                ])->field('group_name,publish_time,background,person_number,story_number,statement,state,color,edit')->find();
                $result[$i]['album_id'] = $album_id;

                $member = new Fishot_sharemember();
                $user = new Fishot_user();
                $member_info = $member->where([
                    'group_id' => $album_id
                ])->field('user_id')->select();

                if (!$member_info){
                    throw new ParameterException();
                }

                $member_array = [];
                $j = 0;
                foreach ($member_info as $vvvv){
                    $member_array[$j]['user_id'] = $vvvv['user_id'];
                    $re = $user->where([
                        'id' => $vvvv['user_id']
                    ])->field('username,portrait')->find();
                    $member_array[$j]['username'] = $re['username'];
                    $member_array[$j]['portrait'] = $re['portrait'];

                    $j++;
                }

                $result[$i]['album_name'] = $info2['group_name'];
                $result[$i]['statement'] = $info2['statement'];
                $result[$i]['background'] = config('setting.image_root').$info2['background'];
                $result[$i]['publish_time'] = $info2['publish_time'];
                $result[$i]['person_number'] = $info2['person_number'];
                $result[$i]['story_number'] = $info2['story_number'];
                $result[$i]['state'] = $info2['state'];
                $result[$i]['color'] = $info2['color'];
                //看看缓存里面有没有这个键
                $vars = Cache::get($album_id);
                if (!$vars){
                    $result[$i]['edit'] = '';
                }else{
                    $result[$i]['edit'] = $vars;
                }
                $result[$i]['member'] = $member_array;

                $i++;
            }
        }else{
            $start = ($page-1)*$size;
            $sharemember = new Fishot_sharemember();
            $info = $sharemember->limit($start,$size)
                ->where([
                'user_id' => $uid
                ])
                ->field('group_id')->select();
            $result = [];
            $i = 0;
            foreach ($info as $v){
                $album_id = $v['group_id'];
                $sharealbum = new Fishot_sharealbum();
                $info2 = $sharealbum->where([
                    'id' => $album_id
                ])->field('group_name,publish_time,background,person_number,story_number,,statement,state,color,edit')->find();
                $result[$i]['album_id'] = $album_id;

                $member = new Fishot_sharemember();
                $user = new Fishot_user();
                $member_info = $member->where([
                    'group_id' => $album_id
                ])->field('user_id')->select();

                if (!$member_info){
                    throw new ParameterException();
                }

                $member_array = [];
                $j = 0;
                foreach ($member_info as $vvvv){
                    $member_array[$j]['user_id'] = $vvvv['user_id'];
                    $re = $user->where([
                        'id' => $vvvv['user_id']
                    ])->field('username,portrait')->find();
                    $member_array[$j]['username'] = $re['username'];
                    $member_array[$j]['portrait'] = $re['portrait'];

                    $j++;
                }

                $result[$i]['album_name'] = $info2['group_name'];
                $result[$i]['statement'] = $info2['statement'];
                $result[$i]['background'] = config('setting.image_root').$info2['background'];
                $result[$i]['publish_time'] = $info2['publish_time'];
                $result[$i]['person_number'] = $info2['person_number'];
                $result[$i]['story_number'] = $info2['story_number'];
                $result[$i]['state'] = $info2['state'];
                $result[$i]['color'] = $info2['color'];
                //看看缓存里面有没有这个键
                $vars = Cache::get($album_id);
                if (!$vars){
                    $result[$i]['edit'] = '';
                }else{
                    $result[$i]['edit'] = $vars;
                }
                $result[$i]['member'] = $member_array;

                $i++;
            }
        }
        //按时间排序
        array_multisort(array_column($result,'publish_time'),SORT_DESC,$result);
        $i = 0;
        foreach ($result as $value){
            $result[$i]['publish_time'] = date("Y/m/d",$value['publish_time']);
            $i++;
        }
        return json_encode([
            'code' => 200,
            'msg' => $result
        ]);
    }

    public function get_album_count(){
        //拿用户id
        $uid = Token::getCurrentUid();
        $User = new Fishot_user();
        $info = $User->where([
            'id' => $uid
        ])->field('album_count')->find();
        return json_encode([
            'code' => 200,
            'msg' => $info['album_count']
        ]);
    }

    public function id_get_info($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        (new IDMustBePostINT())->goToCheck($data);
        $album_id = $data['id'];
        $sharealbum = new Fishot_sharealbum();
        $info = $sharealbum->where([
            'id' => $album_id
        ])->field('group_name,publish_time,background,person_number,story_number,statement,state,color,edit')->find();
        if (!$info){
            throw new ParameterException();
        }

        $member = new Fishot_sharemember();
        $user = new Fishot_user();
        $member_info = $member->where([
            'group_id' => $album_id
        ])->field('user_id')->select();

        if (!$member_info){
            throw new ParameterException();
        }

        $member_array = [];
        $j = 0;
        foreach ($member_info as $v){
            $member_array[$j]['user_id'] = $v['user_id'];
            $re = $user->where([
                'id' => $v['user_id']
            ])->field('username,portrait')->find();
            $member_array[$j]['username'] = $re['username'];
            $member_array[$j]['portrait'] = $re['portrait'];
            $j++;
        }
        //看看缓存里面有没有这个键
        $vars = Cache::get($album_id);
        if (!$vars){
            $edit = '';
        }else{
            $edit = $vars;
        }
        return json_encode([
            'code' => 200,
            'msg' => [
                'album_id' => $album_id,
                'album_name' => $info['group_name'],
                'statement' => $info['statement'],
                'background' => config('setting.image_root').$info['background'],
                'publish_time' => date("Y/m/d",$info['publish_time']),
                'person_number' => $info['person_number'],
                'story_number' => $info['story_number'],
                'state' => $info['state'],
                'color' => $info['color'],
                'edit' => $edit,
                'member' => $member_array
            ]
        ]);
    }
    public function add_story(){
        //拿用户id
        $uid = Token::getCurrentUid();
        $data = input('post.');
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第四项！'
            ]);
        }
        if (!array_key_exists('rank',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第五项！'
            ]);
        }
        if (!array_key_exists('position',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第六项！'
            ]);
        }
        if (!array_key_exists('time',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第七项！'
            ]);
        }
        $rule = [
            'album_id'  => 'require|number',
            'rank'   => 'require|number',
        ];
        $msg = [
            'album_id.require' => '相册号不能为空',
            'album_id.number'   => '相册号必须是数字',
            'rank.require' => '顺序不能为空',
            'rank.number'   => '顺序必须是数字',
        ];
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            throw new BaseException([
                'msg' => $validate->getError()
            ]);
        }

        $album_id = $data['album_id'];
        $content = xss($data['data']);
        $time = xss($data['time']);
        $position = xss($data['position']);

        $published_time = time();

        $story = new Fishot_story();
        $ablum = new Fishot_sharealbum();

        $in = $ablum->where([
            'id' => $album_id
        ])->field('story_number')->find();

        if (!$in){
            throw new ParameterException([
                'msg' => '未找到相册！'
            ]);
        }

        $photo = Request::instance()->file('photo');
        //有图片上传
        if ($photo){
            //给定一个目录
            $info = $photo->validate(['size'=> 5242880,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
            if ($info && $info->getPathname()) {
                $url = $info->getPathname();
            } else {
                throw new ParameterException([
                    'msg' => '请检验上传图片格式（jpg,jpeg,png,bmp,gif）！'
                ]);
            }
            $story->startTrans();
            $ablum->startTrans();
            //存入数据
            $result_story = $story->insertGetId([
                'group_id' => $album_id,
                'user_id' => $uid,
                'story' => $content,
                'shooting_time' => $time,
                'photo_position' => $position,
                'photo_url' => $url,
                'photo_number' => 1,
                'published_time' => $published_time
            ]);
            $result_story2 = $ablum->where([
                'id' => $album_id
            ])->update([
                'story_number' => (int)$in['story_number']+1
            ]);
            if (!$result_story|| !$result_story2){
                $story->rollback();
                $ablum->rollback();
                throw new ParameterException();
            }else{
                $story->commit();
                $ablum->commit();
            }

        }else{
            //无图片上传
            $story->startTrans();
            $ablum->startTrans();
            //存入数据
            $result_story = $story->insertGetId([
                'group_id' => $album_id,
                'user_id' => $uid,
                'story' => $content,
                'shooting_time' => $time,
                'photo_position' => $position,
                'published_time' => $published_time
            ]);
            $result_story2 = $ablum->where([
                'id' => $album_id
            ])->update([
                'story_number' => (int)$in['story_number']+1
            ]);
            if (!$result_story|| !$result_story2){
                $story->rollback();
                $ablum->rollback();
                throw new ParameterException();
            }else{
                $story->commit();
                $ablum->commit();
            }
        }

        return json([
            'code' => 200,
            'msg' => $result_story
        ]);
    }

    public function add_photo(){
        //拿用户id
        $uid = Token::getCurrentUid();
        $data = input('post.');
        if (!array_key_exists('id',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第四项！'
            ]);
        }
        if (!array_key_exists('rank',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第五项！'
            ]);
        }
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无formdata中的第六项！'
            ]);
        }
        if (!is_numeric($data['rank'])){
            throw new ParameterException([
                'msg' => '顺序需为数字'
            ]);
        }
        if (!is_numeric($data['album_id'])){
            throw new ParameterException([
                'msg' => '相册标识需为数字'
            ]);
        }
        $album = new Fishot_sharealbum();
        $check = $album->where([
            'id' => $data['album_id']
        ])->field('color,story_number')->find();
        if (!$check){
            throw new ParameterException([
                'msg' => '没有这个相册！'
            ]);
        }
        $story_id = $data['id'];
        $rank = $data['rank'];
        if ((int)$rank == 1){
            $var = 'photo_url';
        }elseif ((int)$rank == 2){
            $var = 'photo_url2';
        }elseif ((int)$rank == 3){
            $var = 'photo_url3';
        }elseif ((int)$rank == 4){
            $var = 'photo_url4';
        }else{
            throw new ParameterException([
                'msg' => 'rank只能为1、2、3、4'
            ]);
        }
        $story = new Fishot_story();
        if ($story_id == ''){
            $image = new Image();
            $url = $image->upload_image('photo');
            $album->startTrans();
            $story->startTrans();
            $re = $story->insertGetId([
                'group_id' => $data['album_id'],
                'user_id' => $uid,
                $var => $url,
                'photo_number' => 1,
                'published_time' => time()
            ]);
            $rrrr = $album->where([
                'id' => $data['album_id']
            ])->update([
                'story_number' => (int)$check['story_number'] + 1
            ]);
            if (!$re || !$rrrr){
                $album->rollback();
                $story->rollback();
                if (is_file(COMMON_PATH."/".$url)){
                    unlink(COMMON_PATH."/".$url);
                }
                throw new UpdateException();
            }
            $album->commit();
            $story->commit();
            return json_encode([
                'code' => 200,
                'msg' => [
                    'id' => $re,
                    'photo_url' => config('setting.image_root').$url
                ]
            ]);
        }else{
            if (!is_numeric($story_id)){
                throw new ParameterException([
                    'msg' => '故事标识非数字'
                ]);
            }
            $old_info = $story->where([
                'id' => $story_id
            ])->field('photo_number,'.$var)->find();
            if (!$old_info){
                throw new ParameterException();
            }
            $image = new Image();
            $url = $image->upload_image('photo');

            if ($old_info[$var] == ''){
                $re = $story->where([
                    'id' => $story_id
                ])->update([
                    $var => $url,
                    'photo_number' => (int)$old_info['photo_number'] + 1
                ]);
                if (!$re){
                    if (is_file(COMMON_PATH."/".$url)){
                        unlink(COMMON_PATH."/".$url);
                    }
                    throw new UpdateException();
                }
            }else{
                $re = $story->where([
                    'id' => $story_id
                ])->update([
                    $var => $url
                ]);
                if (!$re){
                    if (is_file(COMMON_PATH."/".$url)){
                        unlink(COMMON_PATH."/".$url);
                    }
                    throw new UpdateException();
                }else{
                    if (is_file(COMMON_PATH."/".$old_info[$var])){
                        unlink(COMMON_PATH."/".$old_info[$var]);
                    }
                }
            }
            return json_encode([
                'code' => 200,
                'msg' => [
                    'id' => $story_id,
                    'photo_url' => config('setting.image_root').$url
                ]
            ]);
        }
    }

    public function real_add_pic(){
        //拿用户id
        $uid = Token::getCurrentUid();
        $image = new Image();
        $url = $image->upload_image('photo');
        return json_encode([
            'code' => 200,
            'msg' => config('setting.image_root').$url
        ]);
    }

    public function update_story(){
        $data = input('post.data/a');
        if (!is_array($data)){
            throw new ParameterException([
                'msg' => '传入并非数组'
            ]);
        }
        Db::startTrans();
        $i = 1;
        foreach ($data as $v){
            if (!array_key_exists('story_id',$v)){
                throw new BaseException([
                    'msg' => '无故事标识！'
                ]);
            }
            if (!array_key_exists('story',$v)){
                throw new BaseException([
                    'msg' => '无故事！'
                ]);
            }
            if (!array_key_exists('photo_position',$v)){
                throw new BaseException([
                    'msg' => '无故事地点！'
                ]);
            }
            if (!array_key_exists('shooting_time',$v)){
                throw new BaseException([
                    'msg' => '无故事时间！'
                ]);
            }
            $story_id = $v['story_id'];
            if (!is_numeric($story_id)){
                throw new ParameterException([
                    'msg' => '传入的故事标识非数字'
                ]);
            }
            $content = xss($v['story']);
            $position = xss($v['photo_position']);
            $time = xss($v['shooting_time']);
            $result = Db::table('fishot_story')->where([
                'id' => $story_id
            ])->update([
                'story' => $content,
                'rank' => $i,
                'photo_position' => $position,
                'shooting_time' => $time
            ]);
            if (!$result){
                Db::rollback();
                throw new UpdateException();
            }

            $i++;
        }
        Db::commit();

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    //根据id获取故事信息
    public function show_single_story($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }

        $story_id = $data['id'];

        (new IDMustBePostINT())->goToCheck($data);

        $story = new Fishot_story();
        $info = $story->where([
            'id' => $story_id
            ])->field('user_id,photo_url,story,rank,photo_position,shooting_time,published_time,photo_number,photo_url2,photo_url3,photo_url4')
            ->find();
        if (!$info){
            throw new ParameterException([
                'msg' => '未找到！'
            ]);
        }
        $result['user_id'] = $info['user_id'];
        if ((int)$info['photo_number'] == 1){
            $result['photo_url'][0] = config('setting.image_root').$info['photo_url'];
        }elseif ((int)$info['photo_number'] == 2){
            $result['photo_url'][0] = config('setting.image_root').$info['photo_url'];
            $result['photo_url'][1] = config('setting.image_root').$info['photo_url2'];
        }elseif ((int)$info['photo_number'] == 3){
            $result['photo_url'][0] = config('setting.image_root').$info['photo_url'];
            $result['photo_url'][1] = config('setting.image_root').$info['photo_url2'];
            $result['photo_url'][2] = config('setting.image_root').$info['photo_url3'];
        }elseif ((int)$info['photo_number'] == 4){
            $result['photo_url'][0] = config('setting.image_root').$info['photo_url'];
            $result['photo_url'][1] = config('setting.image_root').$info['photo_url2'];
            $result['photo_url'][2] = config('setting.image_root').$info['photo_url3'];
            $result['photo_url'][3] = config('setting.image_root').$info['photo_url4'];
        }
        $result['photo_number'] = $info['photo_number'];
        $result['story'] = $info['story'];
        $result['rank'] = $info['rank'];
        $result['photo_position'] = $info['photo_position'];
        $result['shooting_time'] = $info['shooting_time'];
        $result['published_time'] = date("Y/m/d",$info['published_time']);
        $user = new Fishot_user();
        $re = $user->where([
            'id' => $info['user_id']
        ])->field('portrait')->find();
        $result['portrait'] = $re['portrait'];

        return json_encode([
            'code' => 200,
            'msg' => $result
        ]);
    }

    //获取相册中的故事
    public function show_album_story($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!array_key_exists('page',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第二项！'
            ]);
        }
        if (!array_key_exists('size',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第三项！'
            ]);
        }
        $rule = [
            'album_id'  => 'require|number',
            'page'  => 'require|number',
            'size'   => 'require|number'
        ];
        $msg = [
            'album_id.require' => '相册号不能为空',
            'album_id.number'   => '相册号必须是数字',
            'page.require' => '页号不能为空',
            'page.number'   => '页号必须是数字',
            'size.require' => '页面大小不能为空',
            'size.number'   => '页面大小必须是数字',
        ];
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            throw new BaseException([
                'msg' => $validate->getError()
            ]);
        }
        $page = (int)$data['page'];
        $size = (int)$data['size'];
        if ($page<0){
            throw new BaseException([
                'msg' => '数据参数中的第一项最小为0'
            ]);
        }
        if ($size<0){
            throw new BaseException([
                'msg' => '数据参数中的第二项最小为0'
            ]);
        }
        if ($page*$size == 0 && $page+$size!=0){
            throw new BaseException([
                'msg' => '为0情况只有数据参数中两项同时为零，否则最小从1开始'
            ]);
        }
        $story = new Fishot_story();
        $user = new Fishot_user();
        $album_id = $data['album_id'];
        if ($page == 0 && $size == 0){
            $info = $story->where([
                'group_id' => $album_id
                ])->order('rank', 'asc')
                ->field('id')->select();
            $result = [];
            $i = 0;
            foreach ($info as $v){
                $story_id = $v['id'];
                $info2 = $story->where([
                    'id' => $story_id
                ])->field('id,user_id,photo_url,story,rank,photo_position,shooting_time,published_time,photo_number,photo_url2,photo_url3,photo_url4')->find();
                $result[$i]['story_id'] = $info2['id'];
                $result[$i]['user_id'] = $info2['user_id'];
                $result[$i]['user_id'] = $info2['user_id'];
                $re = $user->where([
                    'id' => $info2['user_id']
                ])->field('portrait')->find();
                $result[$i]['portrait'] = $re['portrait'];
                if ((int)$info2['photo_number'] == 1){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                }elseif ((int)$info2['photo_number'] == 2){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                }elseif ((int)$info2['photo_number'] == 3){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                    $result[$i]['photo_url'][2] = config('setting.image_root').$info2['photo_url3'];
                }elseif ((int)$info2['photo_number'] == 4){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                    $result[$i]['photo_url'][2] = config('setting.image_root').$info2['photo_url3'];
                    $result[$i]['photo_url'][3] = config('setting.image_root').$info2['photo_url4'];
                }
                $result[$i]['photo_number'] = $info2['photo_number'];
                $result[$i]['story'] = $info2['story'];
                $result[$i]['rank'] = $info2['rank'];
                $result[$i]['photo_position'] = $info2['photo_position'];
                $result[$i]['shooting_time'] = $info2['shooting_time'];
                $result[$i]['published_time'] = date("Y/m/d",$info2['published_time']);

                $i++;
            }
        }else{
            $start = ($page-1)*$size;
            $info = $story->limit($start,$size)
                ->where([
                    'group_id' => $album_id
                ])->order('rank', 'asc')
                ->field('id')->select();
            $result = [];
            $i = 0;
            foreach ($info as $v){
                $story_id = $v['id'];
                $info2 = $story->where([
                    'id' => $story_id
                ])->field('id,user_id,photo_url,story,rank,photo_position,shooting_time,published_time,photo_number,photo_url2,photo_url3,photo_url4')->find();
                $result[$i]['story_id'] = $info2['id'];
                $result[$i]['user_id'] = $info2['user_id'];
                $re = $user->where([
                    'id' => $info2['user_id']
                ])->field('portrait')->find();
                $result[$i]['portrait'] = $re['portrait'];
                if ((int)$info2['photo_number'] == 1){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                }elseif ((int)$info2['photo_number'] == 2){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                }elseif ((int)$info2['photo_number'] == 3){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                    $result[$i]['photo_url'][2] = config('setting.image_root').$info2['photo_url3'];
                }elseif ((int)$info2['photo_number'] == 4){
                    $result[$i]['photo_url'][0] = config('setting.image_root').$info2['photo_url'];
                    $result[$i]['photo_url'][1] = config('setting.image_root').$info2['photo_url2'];
                    $result[$i]['photo_url'][2] = config('setting.image_root').$info2['photo_url3'];
                    $result[$i]['photo_url'][3] = config('setting.image_root').$info2['photo_url4'];
                }

                $result[$i]['photo_number'] = $info2['photo_number'];
                $result[$i]['story'] = $info2['story'];
                $result[$i]['rank'] = $info2['rank'];
                $result[$i]['photo_position'] = $info2['photo_position'];
                $result[$i]['shooting_time'] = $info2['shooting_time'];
                $result[$i]['published_time'] = date("Y/m/d",$info2['published_time']);

                $i++;
            }
        }
        return json_encode([
            'code' => 200,
            'msg' => $result
        ]);
    }

    public function get_album_story_count($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        $rule = [
            'album_id'  => 'require|number',
        ];
        $msg = [
            'album_id.require' => '相册号不能为空',
            'album_id.number'   => '相册号必须是数字',
        ];
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            throw new BaseException([
                'msg' => $validate->getError()
            ]);
        }
        $album = new Fishot_sharealbum();
        $info = $album->where([
            'id' => $data['album_id']
        ])->field('')->find();
        if (!$info){
            throw new ParameterException();
        }
        return json_encode([
            'code' => 200,
            'msg' => $info['story_number']
        ]);
    }

    public function change_rank(){
        $data = input('post.data/a');
        if (!is_array($data)){
            throw new ParameterException();
        }
        $album_id = input('post.album_id');
        if (!is_numeric($album_id)){
            throw new ParameterException([
                '传入标识非数字'
            ]);
        }
        $story = new Fishot_story();
        $story->startTrans();
        foreach ($data as $v){
            if (!is_numeric($v[0])||!is_numeric($v[1])){
                throw new ParameterException([
                    'msg' => '传入数组元素不是数字'
                ]);
            }
            $re = $story->where([
                'group_id' => $album_id,
                'rank' => $v[0]
            ])->update([
                'rank' => $v[1]
            ]);
            if (!$re){
                $story->rollback();
                throw new UpdateException();
            }
        }
        $story->commit();

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function add_color($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!array_key_exists('color',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第二项！'
            ]);
        }

        $album_id = $data['album_id'];
        $color = $data['color'];
        if (!is_numeric($album_id)){
            throw new BaseException([
                'msg' => '相册标识不是数字！'
            ]);
        }

        $album = new Fishot_sharealbum();
        $info = $album->where([
            'id' => $album_id
        ])->field('color')->find();
        if ($color!=$info['color']){
            $re = $album->where([
                'id' => $album_id
            ])->update([
                'color' => $color
            ]);

            if (!$re){
                throw new UpdateException();
            }
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function change_state($data){
        //拿用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        $album_id = $data['album_id'];
        if (!is_numeric($album_id)){
            throw new BaseException([
                'msg' => '相册标识不是数字！'
            ]);
        }
        $album = new Fishot_sharealbum();
        $info = $album->where([
            'id' => $album_id
        ])->field('state')->find();
        if(!$info){
            throw new ParameterException();
        }
        if ($info['state'] == 0){
            $re = $album->where([
                'id' => $album_id
            ])->update([
                'state' => 1
            ]);

            if (!$re){
                throw new UpdateException();
            }
        }elseif ($info['state'] == 1){
            $re = $album->where([
                'id' => $album_id
            ])->update([
                'state' => 0
            ]);

            if (!$re){
                throw new UpdateException();
            }
        }
        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function invite_friend($data){
        //拿邀请用户id
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        $album_id = $data['album_id'];
        if (!is_numeric($album_id)){
            throw new BaseException([
                'msg' => '相册标识不是数字！'
            ]);
        }
        $member = new Fishot_sharemember();
        $rrr = $member->where([
            'group_id' => $album_id,
            'user_id' => $uid
        ])->find();
        if (!$rrr){
            $album = new Fishot_sharealbum();
            $info = $album->where([
                'id' => $album_id
            ])->field('person_number')->find();
            if (!$info){
                throw new ParameterException();
            }
            $member->startTrans();
            $album->startTrans();
            $result = $member->insert([
                'group_id' => $album_id,
                'user_id' => $uid
            ]);
            $re = $album->where([
                'id' => $album_id
            ])->update([
                'person_number' => (int)$info['person_number'] + 1
            ]);
            if (!$result || !$re){
                $member->rollback();
                $album->rollback();
                throw new ParameterException();
            }else{
                $member->commit();
                $album->commit();
            }
        }
        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function upload_head($data){
        //拿邀请用户id
        $uid = Token::getCurrentUid();

        if (!array_key_exists('url',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }


        $re = Db::table('fishot_user')->where([
            'id' => $uid
        ])->field('portrait')->find();

        $url = $re['portrait'];

        if ($url!=$data['url']){
            //将头像存进去
            $result = Db::table('fishot_user')->where([
                'id' => $uid
            ])->update([
                'portrait' => $data['url']
            ]);

            if (!$result){
                throw new UpdateException();
            }
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function change_edit_state($token,$data){
        //添加编辑者
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!is_numeric($data['album_id'])){
            throw new ParameterException([
                'msg' => '相册标识非数字！'
            ]);
        }
        $album_id = $data['album_id'];
        //看看缓存里面有没有这个键
        $vars = Cache::get($album_id);
        if (!$vars){
            $re = Db::table('fishot_sharemember')->where([
                'group_id' => $album_id,
                'user_id' => $uid
            ]);
            if (!$re){
                throw new BaseException([
                    'msg' => '您并非相册成员！'
                ]);
            }
            $result = cache($album_id, $token, config('setting.editor'));
            if (!$result){
                throw new UpdateException();
            }
        }else{
            if ($vars != $token){
                throw new BaseException([
                    'msg' => '已经有编辑者了！'
                ]);
            }else{
                $result = cache($album_id, $token, config('setting.editor'));

                if (!$result){
                    throw new UpdateException();
                }
            }
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function exit_edit_state($token,$data){
        //置空编辑者
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!is_numeric($data['album_id'])){
            throw new ParameterException([
                'msg' => '相册标识非数字！'
            ]);
        }
        $album_id = $data['album_id'];
        //看看缓存里面有没有这个键
        $vars = Cache::get($album_id);
        if (!$vars){
            throw new BaseException([
                'msg' => '您未在编辑状态！'
            ]);
        }else{
            if ($vars != $token){
                throw new BaseException([
                    'msg' => '您未在编辑状态！'
                ]);
            }
        }
        $result = Cache::rm($album_id);

        if (!$result){
            throw new UpdateException();
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }
    public function get_head(){
        $uid = Token::getCurrentUid();
        $result = Db::table('fishot_user')->where([
            'id' => $uid
        ])->field('portrait')->find();
        if (!$result){
            throw new ParameterException();
        }
        return json_encode([
            'code' => 200,
            'msg' => $result['portrait']
        ]);
    }

    public function count_backward(){
        $uid = Token::getCurrentUid();
        $token = input('post.token');
        $new_token = $token.'0';
        cache($new_token, 1, config('setting.backward'));
    }

    public function final_update($token){
        $uid = Token::getCurrentUid();
        $data = input('post.data/a');
        $album_id = input('post.album_id');
        if (!$album_id){
            throw new BaseException([
                'msg' => '无相册标识！'
            ]);
        }
        if (!is_numeric($album_id)){
            throw new BaseException([
                'msg' => '相册标识不是数字！'
            ]);
        }
        $album_id = (int)$album_id;
        Db::startTrans();
        $i = 1;
        $num = 0;
        foreach ($data as $v){
            $num++;
            if (!array_key_exists('user_id',$v)){
                throw new BaseException([
                    'msg' => '无用户标识！'
                ]);
            }
            if (!array_key_exists('story',$v)){
                throw new BaseException([
                    'msg' => '无故事！'
                ]);
            }
            if (!array_key_exists('photo_position',$v)){
                throw new BaseException([
                    'msg' => '无故事地点！'
                ]);
            }
            if (!array_key_exists('shooting_time',$v)){
                throw new BaseException([
                    'msg' => '无故事时间！'
                ]);
            }
            if (!array_key_exists('published_time',$v)){
                throw new BaseException([
                    'msg' => '无发布时间！'
                ]);
            }
            if ($v['user_id'] == ''){
                $user_id = $uid;
            }else{
                if (!is_numeric($v['user_id'])){
                    throw new ParameterException([
                        'msg' => '传入的用户标识非数字'
                    ]);
                }
                $user_id = $v['user_id'];
            }
            //看看缓存里面有没有这个键
            $vars = Cache::get($album_id);
            if (!$vars){
                throw new BaseException([
                    'msg' => '相册未在编辑状态！'
                ]);
            }else{
                if ($vars != $token){
                    throw new BaseException([
                        'msg' => '您未在编辑状态！'
                    ]);
                }
            }

            if ($v['published_time'] == ''){
                $ttime = (int)time();
            }else{
                $ttime = strtotime($v['published_time']);
            }

            $content = xss($v['story']);
            $position = xss($v['photo_position']);
            $time = xss($v['shooting_time']);
            if ($i == 1){
                $ccc = Db::table('fishot_story')->where([
                    'group_id' => $album_id
                ])->find();
                if ($ccc){
                    //先删除
                    $result = Db::table('fishot_story')->where([
                        'group_id' => $album_id
                    ])->delete();
                    if (!$result){
                        Db::rollback();
                        throw new UpdateException();
                    }
                }
            }
            if (!array_key_exists('photo_url',$v)){
                $re = Db::table('fishot_story')->insert([
                    'group_id' => $album_id,
                    'user_id' => $user_id,
                    'story' => $content,
                    'rank' => $i,
                    'photo_position' => $position,
                    'shooting_time' => $time,
                    'published_time' => $ttime
                ]);
                if (!$re){
                    Db::rollback();
                    throw new UpdateException();
                }
            }else{
                $photo_number = count($v['photo_url']);
                if ((int)$photo_number == 0){
                    $re = Db::table('fishot_story')->insert([
                        'group_id' => $album_id,
                        'user_id' => $user_id,
                        'story' => $content,
                        'rank' => $i,
                        'photo_position' => $position,
                        'shooting_time' => $time,
                        'published_time' => $ttime,
                        'photo_number' => 0
                    ]);
                    if (!$re){
                        Db::rollback();
                        throw new UpdateException();
                    }
                }elseif ((int)$photo_number == 1){
                    if ($v['photo_url'][0] != ''){
                        $re = Db::table('fishot_story')->insert([
                            'group_id' => $album_id,
                            'user_id' => $user_id,
                            'story' => $content,
                            'rank' => $i,
                            'photo_position' => $position,
                            'shooting_time' => $time,
                            'published_time' => $ttime,
                            'photo_url' => str_replace(config('setting.image_root'),'',$v['photo_url'][0]),
                            'photo_number' => 1
                        ]);
                        if (!$re){
                            Db::rollback();
                            throw new UpdateException();
                        }
                    }else{
                        $re = Db::table('fishot_story')->insert([
                            'group_id' => $album_id,
                            'user_id' => $user_id,
                            'story' => $content,
                            'rank' => $i,
                            'photo_position' => $position,
                            'shooting_time' => $time,
                            'published_time' => $ttime,
                            'photo_number' => 1
                        ]);
                        if (!$re){
                            Db::rollback();
                            throw new UpdateException();
                        }
                    }

                }elseif ((int)$photo_number == 2){
                    $re = Db::table('fishot_story')->insert([
                        'group_id' => $album_id,
                        'user_id' => $user_id,
                        'story' => $content,
                        'rank' => $i,
                        'photo_position' => $position,
                        'shooting_time' => $time,
                        'published_time' => $ttime,
                        'photo_url' => str_replace(config('setting.image_root'),'',$v['photo_url'][0]),
                        'photo_url2' => str_replace(config('setting.image_root'),'',$v['photo_url'][1]),
                        'photo_number' => 2
                    ]);
                    if (!$re){
                        Db::rollback();
                        throw new UpdateException();
                    }
                }elseif ((int)$photo_number == 3){
                    $re = Db::table('fishot_story')->insert([
                        'group_id' => $album_id,
                        'user_id' => $user_id,
                        'story' => $content,
                        'rank' => $i,
                        'photo_position' => $position,
                        'shooting_time' => $time,
                        'published_time' => $ttime,
                        'photo_url' => str_replace(config('setting.image_root'),'',$v['photo_url'][0]),
                        'photo_url2' => str_replace(config('setting.image_root'),'',$v['photo_url'][1]),
                        'photo_url3' => str_replace(config('setting.image_root'),'',$v['photo_url'][2]),
                        'photo_number' => 3
                    ]);
                    if (!$re){
                        Db::rollback();
                        throw new UpdateException();
                    }
                }elseif ((int)$photo_number == 4){
                    $re = Db::table('fishot_story')->insert([
                        'group_id' => $album_id,
                        'user_id' => $user_id,
                        'story' => $content,
                        'rank' => $i,
                        'photo_position' => $position,
                        'shooting_time' => $time,
                        'published_time' => $ttime,
                        'photo_url' => str_replace(config('setting.image_root'),'',$v['photo_url'][0]),
                        'photo_url2' => str_replace(config('setting.image_root'),'',$v['photo_url'][1]),
                        'photo_url3' => str_replace(config('setting.image_root'),'',$v['photo_url'][2]),
                        'photo_url4' => str_replace(config('setting.image_root'),'',$v['photo_url'][3]),
                        'photo_number' => 4
                    ]);
                    if (!$re){
                        Db::rollback();
                        throw new UpdateException();
                    }
                }else{
                    throw new ParameterException([
                        'msg' => '上传的照片最多四张'
                    ]);
                }
            }
            $i++;
        }
        $cc = Db::table('fishot_sharealbum')
            ->where([
                'id' => $album_id
            ])->update([
            'story_number' => $num
        ]);
        if (!$cc){
            Db::rollback();
            throw new UpdateException();
        }
        Db::commit();

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function back_close($token,$data){
        //切后台倒计时
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!is_numeric($data['album_id'])){
            throw new ParameterException([
                'msg' => '相册标识非数字！'
            ]);
        }
        $album_id = $data['album_id'];
        //看看缓存里面有没有这个键
        $vars = Cache::get($album_id);
        if (!$vars){
            throw new BaseException([
                'msg' => '相册未在编辑状态！'
            ]);
        }else{
            if ($vars != $token){
                throw new BaseException([
                    'msg' => '您未在编辑状态！'
                ]);
            }
        }
        Cache::rm($album_id);
        $result = cache($album_id, $token, config('setting.backclose'));

        if (!$result){
            throw new UpdateException();
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function back_edit($token,$data){
        //从后台切回编辑者
        $uid = Token::getCurrentUid();
        if (!array_key_exists('album_id',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第一项！'
            ]);
        }
        if (!is_numeric($data['album_id'])){
            throw new ParameterException([
                'msg' => '相册标识非数字！'
            ]);
        }
        $album_id = $data['album_id'];
        //看看缓存里面有没有这个键
        $vars = Cache::get($album_id);
        if (!$vars){
            throw new BaseException([
                'msg' => '相册未在编辑状态！'
            ]);
        }else{
            if ($vars != $token){
                throw new BaseException([
                    'msg' => '您未在编辑状态！'
                ]);
            }
        }
        $result = cache($album_id, $token, config('setting.editor'));

        if (!$result){
            throw new UpdateException();
        }

        return json_encode([
            'code' => 200,
            'msg' => 'success'
        ]);
    }

    public function get_diary(){
        $uid = Token::getCurrentUid();
        $day_check = date("Y/m/d",time());
        $info = Db::table('fishot_diary')->where('day','=',$day_check)
            ->find();
        if ($info){
            $url = config('setting.image_root').$info['diary_url'];
            $msg['diary_url'] = $url;
            $msg['diary_user'] = $info['user'];
            $msg['diary_text'] = $info['text'];
            $msg['diary_time'] = date("Y/m/d",$info['time']);
        }else{
            $info2 = Db::table('fishot_diary')->where([
                'id' => 1
            ])->find();
            $url = config('setting.image_root').$info2['diary_url'];
            $msg['diary_url'] = $url;
            $msg['diary_user'] = $info2['user'];
            $msg['diary_text'] = $info2['text'];
            $msg['diary_time'] = date("Y/m/d",$info2['time']);
        }

        $info1 = Db::table('fishot_banner')->select();
        $i = 0;
        foreach ($info1 as  $v){
            $msg['banner'][$i] = config('setting.image_root').$v['banner'];
            $i++;
        }

        return json_encode([
            'code' => 200,
            'msg' => $msg
        ]);
    }

    public function get_banner(){
        $uid = Token::getCurrentUid();
        $info = Db::table('fishot_banner')->where([
            'id' => 1
        ])->select();
        $banner = [];
        $i = 0;
        foreach ($info as  $v){
            $banner[$i] = $v['banner'];
            $i++;
        }
        return json_encode([
            'code' => 200,
            'msg' => $banner
        ]);
    }

    public function get_all_diary($data){
        $uid = Token::getCurrentUid();
        if (!array_key_exists('page',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第1项！'
            ]);
        }
        if (!array_key_exists('size',$data)){
            throw new BaseException([
                'msg' => '无数据参数中的第2项！'
            ]);
        }
        $rule = [
            'page'  => 'require|number',
            'size'   => 'require|number'
        ];
        $msg = [
            'page.require' => '页号不能为空',
            'page.number'   => '页号必须是数字',
            'size.require' => '页面大小不能为空',
            'size.number'   => '页面大小必须是数字',
        ];
        $validate = new Validate($rule,$msg);
        $result   = $validate->check($data);
        if(!$result){
            throw new BaseException([
                'msg' => $validate->getError()
            ]);
        }
        $page = (int)$data['page'];
        $size = (int)$data['size'];
        if ($page<0){
            throw new BaseException([
                'msg' => '数据参数中的第一项最小为0'
            ]);
        }
        if ($size<0){
            throw new BaseException([
                'msg' => '数据参数中的第二项最小为0'
            ]);
        }
        if ($page*$size == 0 && $page+$size!=0){
            throw new BaseException([
                'msg' => '为0情况只有数据参数中两项同时为零，否则最小从1开始'
            ]);
        }
        $r = [];
        $i = 0;
        if ($page == 0 && $size == 0){
            $info = Db::table('fishot_diary')
                ->order('time', 'desc')
                ->select();
            if (!$info){
                exit([
                    'code' => 404,
                    'msg' => '暂无日签'
                ]);
            }else{
                foreach ($info as $v){
                    $r[$i]['diary_url'] = $v['diary_url'];
                    $r[$i]['diary_user'] = $v['user'];
                    $r[$i]['diary_text'] = $v['text'];
                    $r[$i]['diary_time'] = date("Y/m/d",$v['time']);
                    $i++;
                }
            }
        }else{
            $start = ($page-1)*$size;
            $info = Db::table('fishot_diary')->limit($start,$size)
                ->order('time', 'desc')
                ->select();
            if (!$info){
                exit([
                    'code' => 404,
                    'msg' => '暂无日签'
                ]);
            }else{
                foreach ($info as $v){
                    $r[$i]['diary_url'] = $v['diary_url'];
                    $r[$i]['diary_user'] = $v['user'];
                    $r[$i]['diary_text'] = $v['text'];
                    $r[$i]['diary_time'] = date("Y/m/d",$v['time']);
                    $i++;
                }
            }
        }
        return json_encode([
            'code' => 200,
            'msg' => $r
        ]);
    }

    public function get_all_diary_number(){
        $uid = Token::getCurrentUid();
        $info = Db::table('fishot_diary')
            ->field('id')
            ->select();
        if (!$info){
            return json_encode([
                'code' => 200,
                'msg' => 0
            ]);
        }else{
            return json_encode([
                'code' => 200,
                'msg' => count($info)
            ]);
        }
    }

    public function change_diary(){
        $data = input('post.');
        $user = $data['user'];
        $text = $data['text'];
        $photo = Request::instance()->file('photo');
        if (!$photo){
            return '请上传图片！！';
        }
        //给定一个目录
        $info = $photo->validate(['size'=> 1048576,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
        if ($info && $info->getPathname()) {
            $url = $info->getPathname();
        } else {
            return '请检验上传图片是否小于1M 或者 请检验上传图片格式（jpg,jpeg,png,bmp,gif）！';
        }
        $day_check = date("Y/m/d",time());
        $u = Db::table('fishot_diary')->where('day','=',$day_check)
            ->field('diary_url,day')
            ->find();
        if ($u){
            $result = Db::table('fishot_diary')->where('day','=',$day_check)
                ->update([
                    'diary_url' => $url,
                    'user' => $user,
                    'text' => $text,
                    'time' => (int)time()
                ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url)){
                    unlink(COMMON_PATH."/".$url);
                }
                return '更新出错！';
            }
            if ($u['diary_url'] != 'upload/diary.png'){
                if (is_file(COMMON_PATH."/".$u['diary_url'])){
                    unlink(COMMON_PATH."/".$u['diary_url']);
                }
            }
        }else{
            $result = Db::table('fishot_diary')
                ->insert([
                    'diary_url' => $url,
                    'user' => $user,
                    'text' => $text,
                    'time' => (int)time(),
                    'day' => $day_check
                ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url)){
                    unlink(COMMON_PATH."/".$url);
                }
                return '更新出错！';
            }
            if ($u['diary_url'] != 'upload/diary.png'){
                if (is_file(COMMON_PATH."/".$u['diary_url'])){
                    unlink(COMMON_PATH."/".$u['diary_url']);
                }
            }
        }
        return '成功';
    }

    public function change_banner(){
        Db::startTrans();
        $u = Db::table('fishot_banner')
            ->field('banner')
            ->select();
        $banner1 = $u['banner1'];
        $banner2 = $u['banner2'];
        $banner3 = $u['banner3'];
        $photo1 = Request::instance()->file('photo1');
        if ($photo1){
            //给定一个目录
            $info = $photo1->validate(['size'=> 1048576,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
            if ($info && $info->getPathname()) {
                $url1 = $info->getPathname();
            } else {
                Db::rollback();
                return '请检验上传图片是否小于1M 或者 请检验上传图片格式（jpg,jpeg,png,bmp,gif）！';
            }


            $result = Db::table('fishot_banner')->where('id','=',1)
                ->update([
                    'banner1' => $url1
                ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url1)){
                    unlink(COMMON_PATH."/".$url1);
                }
                Db::rollback();
                exit([
                    'code' => 400,
                    'msg' => '更新出错！'
                ]);
            }
        }


        $photo2 = Request::instance()->file('photo2');
        if ($photo2){
            //给定一个目录
            $info = $photo2->validate(['size'=> 1048576,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
            if ($info && $info->getPathname()) {
                $url2 = $info->getPathname();
            } else {
                Db::rollback();
                return '请检验上传图片是否小于1M 或者 请检验上传图片格式（jpg,jpeg,png,bmp,gif）！';
            }

            $result = Db::table('fishot_banner')->where('id','=',1)
                ->update([
                    'banner2' => $url2
                ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url2)){
                    unlink(COMMON_PATH."/".$url2);
                }
                Db::rollback();
                return '更新出错！';
            }
        }

        $photo3 = Request::instance()->file('photo3');
        if ($photo3){
            //给定一个目录
            $info = $photo3->validate(['size'=> 1048576,'ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
            if ($info && $info->getPathname()) {
                $url3 = $info->getPathname();
            } else {
                Db::rollback();
                return '请检验上传图片是否小于1M 或者 请检验上传图片格式（jpg,jpeg,png,bmp,gif）！';
            }

            $result = Db::table('fishot_banner')->where('id','=',1)
                ->update([
                    'banner3' => $url3
                ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url3)){
                    unlink(COMMON_PATH."/".$url3);
                }
                Db::rollback();
                return '更新出错！';
            }
        }

        Db::commit();
        if ($banner1 != 'upload/banner.jpg'){
            if (is_file(COMMON_PATH."/".$banner1)){
                unlink(COMMON_PATH."/".$banner1);
            }
        }
        if ($banner2 != 'upload/banner.jpg'){
            if (is_file(COMMON_PATH."/".$banner2)){
                unlink(COMMON_PATH."/".$banner2);
            }
        }
        if ($banner3 != 'upload/banner.jpg'){
            if (is_file(COMMON_PATH."/".$banner3)){
                unlink(COMMON_PATH."/".$banner3);
            }
        }

        return '成功';
    }

    public function feedback(){
        $uid = Token::getCurrentUid();
        $data = input('post.');
        if (!array_key_exists('feedback_type',$data)){
            throw new BaseException([
                'msg' => '无类型！'
            ]);
        }
        if (!array_key_exists('text',$data)){
            throw new BaseException([
                'msg' => '无文本内容！'
            ]);
        }
        if (!array_key_exists('phone',$data)){
            throw new BaseException([
                'msg' => '无电话！'
            ]);
        }
        $time = time();
        $type = $data['feedback_type'];
        $text = $data['text'];
        $phone = $data['phone'];

        $photo1 = Request::instance()->file('photo');
        if ($photo1){
            //给定一个目录
            $info = $photo1->validate(['ext'=>'jpg,jpeg,png,bmp,gif'])->move('upload');
            if ($info && $info->getPathname()) {
                $url1 = $info->getPathname();
            } else {
                exit([
                    'code' => 400,
                    'msg' => '请检验上传图片格式（jpg,jpeg,png,bmp,gif）！'
                ]);
            }


            $result = Db::table('fishot_feedback')->insert([
                'time' => $time,
                'user_id' => $uid,
                'type' => $type,
                'phone' => $phone,
                'text' => $text,
                'photo' => $url1
            ]);
            if (!$result){
                if (is_file(COMMON_PATH."/".$url1)){
                    unlink(COMMON_PATH."/".$url1);
                }
                exit([
                    'code' => 400,
                    'msg' => '更新出错！'
                ]);
            }
        }else{
            //无图片上传
            $result = Db::table('fishot_feedback')->insert([
                'time' => $time,
                'user_id' => $uid,
                'type' => $type,
                'phone' => $phone,
                'text' => $text
            ]);
            if (!$result){
                exit([
                    'code' => 400,
                    'msg' => '更新出错！'
                ]);
            }
        }

        return json_encode([
            'code' => 200,
            'msg' => '发表成功！'
        ]);
    }
}