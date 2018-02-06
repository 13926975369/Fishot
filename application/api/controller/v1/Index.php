<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2018/1/6
 * Time: 2:51
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\exception\ParameterException;
use app\api\validate\PatternValidate;
use app\api\controller\v1\User as user;
use app\api\controller\v1\Story as Story;
use app\api\controller\v1\Image as Image;
use app\api\controller\v1\Token as Token;

class Index extends BaseController
{
    public function index(){
        $post = input('post.');
        if (!array_key_exists('type',$post)){
            throw new ParameterException([
                'msg' => '无接口类型！'
            ]);
        }
        $type = $post['type'];
        if (!array_key_exists('data',$post)){
            throw new ParameterException([
                'msg' => '没有data参数！'
            ]);
        }
        $data = $post['data'];
        if (!array_key_exists('token',$post)){
            throw new ParameterException([
                'msg' => '无身份标识！'
            ]);
        }
        $token_value = $post['token'];

        //实例化控制器
        $User = new user();
        $token = new Token();
        $Image = new Image();
        $Story = new Story();

        //不必要携带token的接口
        if ($type == 'A001'){
            //登录注册
            //判断是否传码和判断token的值
            if ($token_value != 'gettoken'){
                throw new ParameterException([
                    'msg' => '传入的身份验证不正确！'
                ]);
            }
            if (!array_key_exists('code',$data)){
                throw new ParameterException([
                    'msg' => '无code！'
                ]);
            }
            if ($data['code'] == ''){
                throw new ParameterException([
                    'msg' => 'code不能为空！'
                ]);
            }
            $result = $token->getToken($data['code']);
            return $result;
        }elseif($type == 'A002'){
            //获取相关信息
            //判断是否传码和判断token的值
            if ($token_value != 'getRelatedMsg'){
                throw new ParameterException([
                    'msg' => '传入的身份验证不正确！'
                ]);
            }
            if (!array_key_exists('id',$data)){
                throw new ParameterException([
                    'msg' => '无唯一标识id！'
                ]);
            }
            $result = $User->getRelatedInformation($data['id']);
            return $result;
        }

        //验证层  主要是专门验证一下token的格式是否正确
        (new PatternValidate())->goCheck();

        $result = json_encode([]);
        if ($type == 'A003'){
            //获取相片数和相册数
            $result = $User->getUserInfo();
        }elseif ($type == 'A004'){
            //返回id，用户名和头像
            $identity = $data['identity'];
            $result = $User->searchUser($identity);
        }elseif ($type == 'A005') {
            //传两个id进去添加好友
            $friend_id = $data['id'];
            $result = $User->addFriend($friend_id);
        }elseif ($type == 'A006'){
            //分页展示好友
            $size = $data['size'];
            $page = $data['page'];
            $result = $User->showFriend($size,$page);
        }elseif ($type =='A007'){
            //拉一个相册群
            $result = $User->buildGroup($data);
        }elseif ($type == 'A008') {
            //发布故事
            if ($post['data']==1) $result = $Image->uploadStory($post['a_id'],$post['time'],$post['story'],$post['position'],$post['data']);
            elseif ($post['data']=='2') $result = $Image->uploadStory($post['a_id'],$post['time'],'',$post['position'],$post['data']);
            elseif ($post['data']=='3') $result = $Image->uploadStory($post['a_id'],'null',$post['story'],'',$post['data']);
        }elseif ($type == 'A009'){
            //展示故事
            if (!array_key_exists('flag',$data)){
                throw new ParameterException([
                    'msg' => 'data中某参数未传递！'
                ]);
            }
            if ($data['flag'] == '1'){
                if (!array_key_exists('s_id',$data)){
                    throw new ParameterException([
                        'msg' => 'data中某参数未传递！'
                    ]);
                }
                $result = $Story->ShowStory($data['flag'],'',$data['s_id']);
            }else{
                if (!array_key_exists('a_id',$data)||!array_key_exists('size',$data)||!array_key_exists('page',$data)
                    ||!array_key_exists('order',$data)){
                    throw new ParameterException([
                        'msg' => 'data中某参数未传递！'
                    ]);
                }
                $result = $Story->ShowStory($data['flag'],$data['a_id'],'',$data['page'],$data['size'],$data['order']);
            }
        }elseif ($type == 'A010'){
            //删除任务
            if (!array_key_exists('s_id',$data)){
                throw new ParameterException([
                    'msg' => 'data中某参数未传递！'
                ]);
            }
            $result = $Story->DelStory($data['s_id']);
        }elseif ($type == 'A011'){
            //展示相册的照片
            if (!array_key_exists('a_id',$data)||!array_key_exists('size',$data)||!array_key_exists('page',$data)){
                throw new ParameterException([
                    'msg' => 'data中某参数未传递！'
                ]);
            }
            $result = $Image->ShowAlbumPhoto($data['a_id'],$data['page'],$data['size']);
        }elseif ($type == 'A012'){
            //展示相册所有相片
            if (!array_key_exists('a_id',$data)){
                throw new ParameterException([
                    'msg' => '未传入a_id！'
                ]);
            }
            $result = $Image->ShowAlbumPhotoAll($data['a_id']);
        }elseif ($type == 'A013'){
            //修改个性签名
            if (!array_key_exists('personality_signature',$data)){
                throw new ParameterException([
                    'msg' => '未传入个性签名！'
                ]);
            }
            $sign = $data['personality_signature'];
            $result = $User->change_sign($sign);
        }elseif ($type == 'A014'){
            $result = $User->show_sign();
        }elseif ($type == 'A015'){
            $result = $User->show_background();
        }elseif ($type == 'A016'){
            $result = $User->change_background();
        }elseif ($type == 'A017'){
            //创建相册时编辑相册封面图
            $result = $Story->Change_album_background();
        }elseif ($type == 'A018'){
            var_dump('lalal');
            var_dump(input('post.'));
            $result = input('post.');
        }else{
            throw new ParameterException([
                'msg' => '输入类型有误！'
            ]);
        }
        return $result;
    }
}