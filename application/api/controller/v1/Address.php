<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/18
 * Time: 20:13
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\exception\ForbiddenException;
use app\api\exception\TokenException;
use app\api\exception\UserException;
use app\api\lib\enum\ScopeEnum;
use app\api\service\UserToken as TokenService;
use app\api\model\Fishot_user as UserModel;
use think\Controller;

class Address extends BaseController
{
    protected $beforeActionList = [
        //意思是first是second的前置方法
        'first' => ['only' => 'second'],
        //这样写也可以
//        'first' => ['only' => 'second,third']
        'checkPrimaryScope' => ['only' => 'createOrUpdateAddress'],
    ];

    private function first(){
        echo 'first';
    }

    //接口
    public function second(){
        echo 'second';
    }

//    protected function checkPrimaryScope(){
//        $scope = TokenService::getCurrentTokenVar('scope');
//        if ($scope){
//            if ($scope >= SecretEnum::User){
//                return true;
//            }else{
//                throw new ForbiddenException();
//            }
//        }else{
//            throw new TokenException();
//        }
//    }

    protected function checkPrimaryScope(){
        TokenService::needPrimaryScope();
    }

    /*
     * 获取用户相片数和相册数
     * @token    用户的token令牌
     * @url      /banner/:id
     * @http     post
     * @param    token
     * @return   json格式用户信息
     * @throws   UserException
     * */
    public function createOrUpdateAddress(){
        $uid = TokenService::getCurrentUid();
        $user = UserModel::get($uid);
        if (!$user){
            throw new UserException();
        }

        $userAddress = $user->address;
        if (!$userAddress){
//            $user->address()->save($dataArray);
        }
    }
}