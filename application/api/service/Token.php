<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 16:23
 */

namespace app\api\service;


use app\api\exception\ForbiddenException;
use app\api\exception\ParameterException;
use app\api\exception\TokenException;
use app\api\lib\enum\ScopeEnum;
use think\Cache;
use think\Exception;
use app\api\service\UserToken as TokenService;
use think\Request;

class Token
{
    /*
     * 生成一个Token令牌
     * @return   token
     * */
    public static function generateToken(){
        //用三组字符串md5加密
        //32个字符组成一组随机字符串
        $randChars = getRandChars(32);
        //时间戳
        $timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
        //salt 盐
        $salt = config('secure.token_salt');

        return md5($randChars.$timestamp.$salt);
    }

    /*
     * 根据Token令牌获取用户数据库中$key字段的信息（如这里为id）
     * @http     get: key   post: token
     * @param    $key（string）：数据库字段名称  $token
     * @return   $vars[$key]：字段的值
     * */
    public static function getCurrentTokenVar($key){
//        $token = Request::instance()->header('token');
        $token = $post = input('post.token');
        $vars = Cache::get($token);
        if (!$vars){
            throw new TokenException();
        }else{
            if (!is_array($vars)){
                $vars = json_decode($vars,true);
            }
            if (array_key_exists($key,$vars)) {
                return $vars[$key];
            }else{
                throw new Exception('尝试获取的Token变量并不存在');
            }
        }
    }

    /*
     * 获取用户id
     * @return   uid
     * */
    public static function getCurrentUid(){
        $uid = self::getCurrentTokenVar('uid');
        return $uid;
    }


    /*
     * @ 权限前置方法：用户和CMS可以访问
     */
    public static function needPrimaryScope(){
        $scope = self::getCurrentTokenVar('scope');
        if ($scope){
            if ($scope >= ScopeEnum::User){
                return true;
            }else{
                throw new ForbiddenException();
            }
        }else{
            throw new TokenException();
        }
    }

    /*
     * @ 订单支付权限前置方法：专用户可以访问
     */
    public static function needExclusiveScope(){
        $scope = self::getCurrentTokenVar('scope');
        if ($scope){
            if ($scope == ScopeEnum::User){
                return true;
            }else{
                throw new ForbiddenException();
            }
        }else{
            throw new TokenException();
        }
    }

    /*
     * 验证token是否有效
     * */
    public function verifyToken($token){
        $exist = Cache::get($token);
        if ($exist){
            return true;
        }else{
            return false;
        }
    }
}