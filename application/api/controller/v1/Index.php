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
use app\api\model\Fishot_sharemember;
use app\api\validate\PatternValidate;
use app\api\controller\v1\User as user;
use app\api\controller\v1\Story as Story;
use app\api\controller\v1\Image as Image;
use app\api\controller\v1\Token as Token;
use think\Db;
use think\Request;

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
            //邀请好友进入相册
            $result = $Story->invite_friend($data);
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
            //倒数关闭激活

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
            //展示签名
            $result = $User->show_sign();
        }elseif ($type == 'A015'){
            //展示背景图片
            $result = $User->show_background();
        }elseif ($type == 'A016'){
            //用户背景图片
            $result = $User->change_background();
        }elseif ($type == 'A017'){
            //创建相册id
            $result = $Story->create_album();
        }elseif ($type == 'A018'){
            //删除相册id
            $result = $Story->destroy($data);
        }elseif ($type == 'A019'){
            //相册封面
            $result = $Story->change_album_background();
        }elseif ($type == 'A020'){
            //修改名字和描述
            $result = $Story->change_album_info($data);
        }elseif ($type == 'A021'){
            //展示相册封面
            $result = $Story->show_album_background($data);
        }elseif ($type == 'A022'){
            //展示相册名字和描述
            $result = $Story->show_name($data);
        }elseif ($type == 'A023'){
            //创建时添加故事接口
            $result = $Story->real_add_pic();
        }elseif ($type == 'A024'){
            //拿到用户的相册信息
            $result = $Story->back_user_id($data);
        }elseif ($type == 'A025'){
            //获取用户的拥有的相册数目
            $result = $Story->get_album_count();
        }elseif ($type == 'A026'){
            //根据相册的id获取相册信息
            $result = $Story->id_get_info($data);
        }elseif ($type == 'A027'){
            //id获取故事信息
            $result = $Story->show_single_story($data);
        }elseif ($type == 'A028'){
            //分页获取故事
            $result = $Story->show_album_story($data);
        }elseif ($type == 'A029'){
            //获取相册拥有的故事数目
            $result = $Story->get_album_story_count($data);
        }elseif ($type == 'A030'){
            //切换故事的顺序
            $result = $Story->change_rank();
        }elseif ($type == 'A031'){
            //添加颜色
            $result = $Story->add_color($data);
        }elseif ($type == 'A032'){
            //添加颜色
            $result = $Story->change_state($data);
        }elseif ($type == 'A033'){
            //上传头像
            $result = $Story->upload_head($data);
        }elseif ($type == 'A034'){
            //更新故事
            $result = $Story->final_update($token_value);
        }elseif ($type == 'A035'){
            //新增编辑者状态
            $result = $Story->change_edit_state($token_value,$data);
        }elseif ($type == 'A036'){
            //新增编辑者状态
            $result = $Story->exit_edit_state($token_value,$data);
        }elseif ($type == 'A037'){
            //新增编辑者状态
            $result = $Story->get_head();
        }elseif ($type == 'A038'){
            //切后台
            $result = $Story->back_close($token_value,$data);
        }elseif ($type == 'A039'){
            //日签
            $result = $Story->get_diary();
        }elseif ($type == 'A040'){
            //获取所有日签
            $result = $Story->get_all_diary($data);
        }elseif ($type == 'A041'){
            //意见反馈
            $result = $Story->feedback();
        }elseif ($type == 'A042'){
            //获取
            $result = $Story->get_all_diary_number();
        }elseif ($type == 'A043'){
            //展示成员信息
            $result = $Story->show_member_info($data);
        }elseif ($type == 'A044'){
            //删除成员
            $result = $Story->delete_member($data);
        }else{
            throw new ParameterException([
                'msg' => '输入类型有误！'
            ]);
        }
        return $result;
    }
}