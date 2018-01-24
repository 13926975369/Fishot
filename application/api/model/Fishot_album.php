<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/30
 * Time: 7:43
 */

namespace app\api\model;
use app\api\exception\ForbiddenException;
use app\api\exception\TokenException;
use app\api\lib\enum\ScopeEnum;
use app\api\service\Token as TokenServer;
use app\api\model\Fishot_sharemember as MemberModel;
use think\Request;

class Fishot_album extends BaseModel
{
    public static function needShareAlbumScope(){
        $scope = TokenServer::getCurrentTokenVar('scope');
        $uid = TokenServer::getCurrentUid();
        $data = Request::instance()->post('data');
        $album_id = $data['a_id'];
        $where['group_id'] = $album_id;
        $where['user_id'] = $uid;
        $member = new MemberModel();
        $result = $member->where($where)->find();

        if ($scope){
            if ($scope == ScopeEnum::User && $result){
                return true;
            }else{
                throw new ForbiddenException();
            }
        }else{
            throw new TokenException();
        }
    }
}