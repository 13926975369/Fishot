<?php
/**
 * Created by PhpStorm.
 * User: 63254
 * Date: 2017/11/18
 * Time: 21:03
 */

namespace app\api\controller\v1;
use app\api\controller\BaseController;
use app\api\exception\ForbiddenException;
use app\api\exception\TokenException;
use app\api\lib\enum\ScopeEnum;
use app\api\service\Token as TokenService;

class Order extends BaseController
{
    // 用户在选择商品后，像API提交包含它所选商品的相关信息
    // API在接收到信息后，需要检查订单相关商品的库存
    // 有库存，把订单数据存入数据库中= 下单成功了，返回客户端信息，告诉客户可以支付了
    // 调用我们的支付接口，进行支付
    // 还需要再次进行库存量的检测
    // 服务器这边就可以调用微信的支付接口进行支付
    // 微信会返回给我们一个支付的结果
    // 成功：也需要在进行库存量的检测
    // 成功：库存扣除  失败：返回一个支付失败的结果
    protected $beforeActionList = [
        'checkExclusiveScope' => ['only' => 'placeOrder'],
    ];

    public function placeOrder(){

    }
}