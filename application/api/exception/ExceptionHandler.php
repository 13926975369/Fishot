<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/19
 * Time: 18:48
 */

namespace app\api\exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\Request;

class ExceptionHandler extends Handle
{
    private $code;
    private $msg;
    private $errorCode;
    // 需要返回客户端当前请求的URL路径

    public function render(\Exception $e){
        if ($e instanceof BaseException){
            //如果是自定义的异常
            $this->code = $e->code;
            $this->msg = $e->msg;
            $this->errorCode = $e->errorCode;
        }else{
            $this->code = 500;
            $this->msg = '服务器内部错误';
            $this->errorCode = 999;
            //调试
            if ($e instanceof HttpException) {
                return $this->renderHttpException($e);
            } else {
                return $this->convertExceptionToResponse($e);
            }
        }
        $request = Request::instance();

        $result = [
            'code' => $this->code,
            'msg' => $this->msg,
//            'error_code' => $this->errorCode,
//            'request_url' => $request->url()
        ];
        return json($result,$this->code);
    }
}
