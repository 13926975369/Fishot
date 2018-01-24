<?php
namespace app\api\controller\v1;
use app\api\exception\BannerMissException;
use app\api\exception\UserException;
use app\api\validate\IDMustBePostINT;
use think\console\command\make\Controller;
use app\api\validate\BaseValidate;
use think\Request;

class Banner extends \app\api\controller\BaseController
{


//    protected $beforeActionList = [
////        'bcbcbc',
////        'bcbcbc' => ['only' => 'ttt']
//    ];


    /*
     * 获取Banner信息
     * @id       banner的id号
     * @url      /banner/:id
     * @http     get
     * @param    int $id banner id
     * @return   array of banner item , code 200
     * @throws   MissException
     * */
    public function getBanner(){
        $banner = \app\api\model\Banner::getBannerByID();
        if (!$banner){
            throw new BannerMissException();
        }
        return $banner;
    }

    public function test(){
        echo "lal";
        $file = Request::instance()->file('file');
        //给定一个目录
        $info = $file->move('upload');
        if ($info && $info->getPathname()) {
            return var_dump('success'.'/'.$info->getPathname());
        }else{
            return var_dump('upload error');
        }
    }

    public function delate(){
        echo COMMON_PATH;
//        echo "<img src='/Fishot/public/upload/20171125/5deaa980522dff414782d9be012822eb.jpg'>";
        unlink(COMMON_PATH."/upload/20171125/5deaa980522dff414782d9be012822eb.jpg");
    }

    public function ttt(){
        $photo = Request::instance()->file('file');
        var_dump($photo);
        $info = $photo->move('upload');
        var_dump(Request::instance()->post('user'));
    }
}