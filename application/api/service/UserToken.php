<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/15
 * Time: 14:23
 */

namespace app\api\service;
use app\api\exception\TokenException;
use app\api\exception\WeChatException;
use app\api\lib\enum\ScopeEnum;
use think\Exception;
use app\api\model\Fishot_user as UserModel;

class UserToken extends Token
{
    protected $code;
    protected $wxAppID;
    protected $wxAppSecret;
    protected $wxLoginurl;

    /*
     * 构造方法对code、appid、appsecret、loginurl进行赋值
     * */
    function __construct($code)
    {
        $this->code = $code;
        $this->wxAppID = config('wx.app_id');
        $this->wxAppSecret = config('wx.app_secret');
        $this->wxLoginurl = sprintf(config('wx.login_url'),
            $this->wxAppID,$this->wxAppSecret,$this->code);
    }

    /*
     * 获取接口页面的用户信息并生成token
     * @return   token
     * */
    public function get(){
        $result = curl_get($this->wxLoginurl);
        //返回的字符串变成数组,true是数组，false是对象
        $wxResult = json_decode($result, true);
        if (empty($wxResult)){
            throw new Exception('获取session_key及openID时异常，微信内部错误');
        }
        else{
            $loginFail = array_key_exists('errcode',$wxResult);
            if ($loginFail){
                $this->processLoginError($wxResult);
            }else{
                //检测没有报错的话就去取token
                return $this->grantToken($wxResult);
            }
        }
    }

    /*
     * 通过wxResult生成令牌并存入缓存，并且没有改用户的话注册新用户，有该用户的话即登录
     * @param    微信小程序接口网页获取的信息wxResult
     * @return   token
     * */
    private function grantToken($wxResult){
        //拿到openID，在数据库里看一下openID存不存在
        //如果存在不处理，不存在数据库里新增一条user
        //生成令牌，准备缓存数据，写入缓存
        //key：令牌
        //value：wxResult，uid，scope（决定用户身份）
        $openid = $wxResult['openid'];
        $user = UserModel::getByOpenID($openid);
        if ($user){
            //有的话就直接查询id号
            $uid = $user->id;
        }else{
            //没有的话新建
            $uid = $this->newUser($openid);
        }
        //将token存入缓存中
        $cachedValue = $this->prepareCachedValue($wxResult,$uid);
        $token = $this->saveToCache($cachedValue);
        return $token;
    }

    /*
     * 获得缓存内容并将它们作为value存入缓存中，key为 32随机+时间戳+salt 拼接的token
     * @param    cacheValue：要存入缓存的值
     * @return   $key：token
     * */
    private function saveToCache($cachedValue){
        //这是一个拼接token的函数，32随机+时间戳+salt  在service的基类里
        //key就是token，value包含openid，uid，scope
        $key = self::generateToken();
        $value = json_encode($cachedValue);
        //设置存活时间
        $expire_in = config('setting.token_expire_in');
        //存入缓存
        $request = cache($key, $value, $expire_in);
        if (!$request){
            throw new TokenException([
                'msg' => '服务器缓存异常',
                'errorCode' => 10005
            ]);
        }
        return $key;
    }

    /*
     * 将用户的id信息和权限信息加入准备缓存的内容中
     * @param    wxResult：微信小程序接口网页获取的信息 uid：用户id
     * @return   cachedValue
     * */
    private function prepareCachedValue($wxResult,$uid){
        $cachedValue = $wxResult;
        $cachedValue['uid'] = $uid;
        //scope为权限
        $cachedValue['scope'] = ScopeEnum::User;
        return $cachedValue;
    }

    /*
     * 将openid加入数据库注册一个新用户
     * @param    openid：微信小程序唯一标示
     * @return   id
     * */
    private function newUser($openid){
        //注册，并且查到用户id号
        //时间戳 date("Y-m-d H:i:s",time())
        date_default_timezone_set("PRC");
        $user = UserModel::create([
            'openid' => $openid ,
            'register_time' => time()
        ]);
        return $user->id;
    }

    /*
     * 登录错误
     * @param    wxResult：微信小程序接口网页获取的信息
     * @return   抛出错误
     * */
    private function processLoginError($wxResult){
        throw new WeChatException([
            'msg' => $wxResult['errmsg'],
            'errorCode' => $wxResult['errcode']
        ]);
    }
}