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
        $type = $post['type'];
        $data = $post['data'];
        $token_value = $post['token'];

        //实例化控制器
        $User = new user();
        $token = new Token();
        $Image = new Image();
        $Story = new Story();

        //不必要携带token的接口
        if ($type == 'A001'){
            //判断是否传码和判断token的值
            if (!array_key_exists('code',$data) || $token_value != 'gettoken'){
                throw new ParameterException();
            }
            $result = $token->getToken($data['code']);
            return $result;
        }elseif($type == 'A002'){
            //判断是否传码和判断token的值
            if (!array_key_exists('id',$data) || $token_value != 'getRelatedMsg'){
                throw new ParameterException();
            }
            $result = $User->getRelatedInformation($data['id']);
            return $result;
        }

        //验证层
        (new PatternValidate())->goCheck();

        $result = json_encode([]);
        if ($type == 'A003'){
            $result = $User->getUserInfo();
        }elseif ($type == 'A004'){
            $identity = $data['identity'];
            $result = $User->searchUser($identity);
        }elseif ($type == 'A005') {
            $friend_id = $data['id'];
            $result = $User->addFriend($friend_id);
        }elseif ($type == 'A006'){
            $size = $data['size'];
            $page = $data['page'];
            $result = $User->showFriend($size,$page);
        }elseif ($type =='A007'){
            $result = $User->buildGroup($data);
        }elseif ($type == 'A008') {
            if ($post['data']==1) $result = $Image->uploadStory($post['a_id'],$post['time'],$post['story'],$post['position'],$post['data']);
            elseif ($post['data']=='2') $result = $Image->uploadStory($post['a_id'],$post['time'],'',$post['position'],$post['data']);
            elseif ($post['data']=='3') $result = $Image->uploadStory($post['a_id'],'null',$post['story'],'',$post['data']);
        }elseif ($type == 'A009'){
            if ($data['flag'] == '1'){
                $result = $Story->ShowStory($data['flag'],'',$data['s_id']);
            }else{
                $result = $Story->ShowStory($data['flag'],$data['a_id'],'',$data['page'],$data['size'],$data['order']);
            }
        }elseif ($type == 'A010'){
            $result = $Story->DelStory($data['s_id']);
        }elseif ($type == 'A011'){
            $result = $Image->ShowAlbumPhoto($data['a_id'],$data['page'],$data['size']);
        }elseif ($type == 'A012'){
            $result = $Image->ShowAlbumPhotoAll($data['a_id']);
        }
        return $result;
    }
}