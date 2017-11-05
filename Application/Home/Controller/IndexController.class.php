<?php
namespace Home\Controller;
use Think\Controller;
class IndexController extends Controller {
    
    //显示订单
    public function index(){
        $this ->display();
    }
    
    //确认订单，发起支付
    public function pay(){
        
        vendor("alipay.wappay.buildermodel.AlipayTradeWapPayContentBuilder");
        vendor("alipay.wappay.service.AlipayTradeService");
       
        if (!empty($_POST['WIDout_trade_no'])&& trim($_POST['WIDout_trade_no'])!=""){
            
            //商户订单号，商户网站订单系统中唯一订单号，必填
            $out_trade_no = $_POST['WIDout_trade_no'];

            //订单名称，必填
            $subject = $_POST['WIDsubject'];

            //付款金额，必填
            $total_amount = $_POST['WIDtotal_amount'];

            //商品描述，可空
            $body = $_POST['WIDbody'];

            //超时时间
            $timeout_express="1m";

            $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
            //$payRequestBuilder ->test();die;
            
            $payRequestBuilder->setBody($body);
            $payRequestBuilder->setSubject($subject);
            $payRequestBuilder->setOutTradeNo($out_trade_no);
            $payRequestBuilder->setTotalAmount($total_amount);
            $payRequestBuilder->setTimeExpress($timeout_express);

            $conf = array(
                "app_id" => C("app_id"),
                "merchant_private_key" => C("merchant_private_key"),
                "notify_url" => C("notify_url"),
                "return_url" => C("return_url"),
                "charset" => C("charset"),
                "sign_type" => C("sign_type"),
                "gatewayUrl" => C("gatewayUrl"),
                "alipay_public_key" => C("alipay_public_key")
            );
            $payResponse = new \AlipayTradeService($conf);
            //$payResponse ->test();die;
            $result=$payResponse->wapPay($payRequestBuilder,$conf['return_url'],$conf['notify_url']);
        }
        
    }
    
    
    //同步通知(支付成功或者是失败后的处理,对应的跳转)
    public function return_url(){
        $conf = array(
                "app_id" => C("app_id"),
                "merchant_private_key" => C("merchant_private_key"),
                "notify_url" => C("notify_url"),
                "return_url" => C("return_url"),
                "charset" => C("charset"),
                "sign_type" => C("sign_type"),
                "gatewayUrl" => C("gatewayUrl"),
                "alipay_public_key" => C("alipay_public_key")
            );
        vendor("alipay.wappay.service.AlipayTradeService");
        $alipayService = new \AlipayTradeService($conf);
        $alipayService->writeLog(var_export($_POST,true));
        $result = $alipayService->check($_GET);
        
        if($result){
            redirect('success');
        }else{
            redirect('fail');
        }
        
    }
    
    public function success(){
        echo 'pay success';
    }
    
    public function fail(){
        echo 'pay fail';
    }
    
    //异步通知（对服务器进行推送，数据上报、日志记录等）
    public function notify_url(){
        $conf = array(
                "app_id" => C("app_id"),
                "merchant_private_key" => C("merchant_private_key"),
                "notify_url" => C("notify_url"),
                "return_url" => C("return_url"),
                "charset" => C("charset"),
                "sign_type" => C("sign_type"),
                "gatewayUrl" => C("gatewayUrl"),
                "alipay_public_key" => C("alipay_public_key")
            );
        vendor("alipay.wappay.service.AlipayTradeService");
        $alipayService = new \AlipayTradeService($conf);
        $alipayService->writeLog(var_export($_POST,true));
        $result = $alipayService->check($_POST);
        if($result){ //验证成功
            
            //通知时间
            $notify_time = $_POST['notify_time'];
            
            //通知类型
            $notify_type = $_POST['notify_type'];
            
            //校验id
            $notify_id = $_POST['notify_id'];
            
            //开发者app_id
            $app_id = $_POST['app_id'];
            
            //编码格式
            $charset = $_POST['charset'];
            
            //接口版本
            $version = $POST['version'];
            
            //签名类型
            $sign_type = $_POST['sign_type'];
            
            //签名
            $sign = $_POST['sign'];
            
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            
            //商户业务号
            $out_biz_no = $_POST["out_biz_no"];
            
            //买家支付宝用户号
            $buyer_id = $_POST["buyer_id"];
            
            //买家支付宝账号
            $buyer_logon_id = $_POST["buyer_logon_id"];
            
            //卖家支付宝用户号
            $seller_id = $_POST["seller_id"];
            
            //卖家支付宝账号
            $seller_email = $_POST["seller_email"];
            
            //交易状态
            $trade_status = $_POST['trade_status'];
            
            //订单金额
            $total_amount = $_POST["total_amount"];
            
            //实收金额
            $receipt_amount = $_POST["receipt_amount"];
            
            //开票金额
            $invoice_amount = $_POST["invoice_amount"];
            
            //付款金额
            $buyer_pay_amount = $_POST["buyer_pay_amount"];
            
            if($_POST['trade_status'] == 'TRADE_FINISHED') { //过了退款日期发送的通知
                $str = "交易完成#通知时间：".$notify_time.",商户订单号：".$trade_no.",买家支付宝账号:".$buyer_logon_id."卖家支付宝账号".$seller_email."订单金额:".$total_amount."实收金额:".$invoice_amount."开票金额:".$receipt_amount."付款金额:".$buyer_pay_amount;
                file_put_contents('./paylog/alipay/trade_finished/alipayfinishedlog.txt'.date('Y-m-d'), date('Y-m-d H:i:s').$str.PHP_EOL,FILE_APPEND);
            }else if($_POST['trade_status'] == 'TRADE_SUCCESS'){ //交易成功时候发送的通知
                //1.数据上报
                
                //2.日志文件
                $str = "交易成功#通知时间：".$notify_time.",商户订单号：".$trade_no.",买家支付宝账号:".$buyer_logon_id."卖家支付宝账号".$seller_email."订单金额:".$total_amount."实收金额:".$invoice_amount."开票金额:".$receipt_amount."付款金额:".$buyer_pay_amount;
                file_put_contents('./paylog/alipay/trade_success/alipaysuccesslog.txt'.date('Y-m-d'), date('Y-m-d H:i:s').$str.PHP_EOL,FILE_APPEND);
            }
            
            echo "success";
            
        }
        
        echo "fail";
    }
    
    public function test(){
        
        vendor("alipay.wappay.buildermodel.AlipayTradeWapPayContentBuilder");
        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder ->test();
       
        $conf = array(
            "app_id" => C("app_id"),
            "merchant_private_key" => C("merchant_private_key"),
            "notify_url" => C("notify_url"),
            "return_url" => C("return_url"),
            "charset" => C("charset"),
            "sign_type" => C("sign_type"),
            "gatewayUrl" => C("gatewayUrl"),
            "alipay_public_key" => C("alipay_public_key")
        );
        vendor("alipay.wappay.service.AlipayTradeService");
        $payResponse = new \AlipayTradeService($conf);
        $payResponse ->test();
        
    }
    
    
}