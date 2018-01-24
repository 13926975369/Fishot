<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 12:49
 */

namespace app\api\controller\v1;


use app\api\exception\ParameterException;
use app\api\service\UserToken;
use app\api\controller\BaseController;
use app\api\validate\TokenGet;
use app\api\service\Token as TokenService;

class Token extends BaseController
{
    /*
     * @ 注册或者登录获取token令牌
     * @ $code  小程序获取的code码
     */
    public function getToken($code='') {
        $ut = new UserToken($code);
        $token = $ut->get();
        $msg = [
            'token' => $token
        ];
        return json_encode([
            'msg' => $msg,
            'code' => 200,
        ]);
    }

    /*
     * 验证token是否有效
     * */
    public function verifyToken($token=''){
        if (!$token){
            throw new ParameterException([
                'token不允许为空'
            ]);
        }
        $Token = new TokenService();
        $valid = $Token->verifyToken($token);
        return [
            'isValid' => $valid
        ];
    }
}