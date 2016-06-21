<?php
namespace Home\Controller;
use Think\Controller;
class WxController extends Controller {
    public function index()
    {
        //接口验证过程
        //1、获得参数，对值按字典排序
        $timestamp = $_GET['timestamp'];
        $nonce = $_GET['nonce'];
        $token = 'weixin';
        $signature = $_GET['signature'];
        $echostr = $_GET['echostr'];
        $array = array($timestamp,$nonce,$token);
        sort($array);
        //2、构建字符串并加密-sha1
        $str = implode('',$array);
        $str = sha1($str);

        //3、对signature进行验证
        if($str == $signature && $echostr){
            header('content-type:text');
            ob_clean();
            echo $echostr;
            exit;
        }else{
            $this->responseMsg();
        }
    }

    public function responseMsg(){
        //接收微信post过来的xml数据
        $postArr = $GLOBALS["HTTP_RAW_POST_DATA"];
        //处理消息类型，设置回复类型和内容
        $postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if(strtolower($postObj->MsgType) == 'event'){
            if(strtolower($postObj->Event) == 'subscribe'){
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = "text";
                $content = "欢迎订阅boyyb的微信公众号！我将为你提供优质的服务。";
                $template = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            </xml>";
                $info = sprintf($template,$toUser,$fromUser,$time,$msgType,$content);
                echo $info;
            }
        }elseif(strtolower($postObj->MsgType == 'text')){
            $str = trim($postObj->Content);//去除用户发来信息的前后空格
            if($str == "你是谁"){
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $time = time();
                $msgType = "text";
                $contentStr = "我是帅气的彬哥哥！！！";
                $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                    </xml>";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;

            }elseif($str == "你在哪"){
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $time = time();
                $msgType = "text";
                $contentStr = "我在重庆市南岸区！";
                $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                    </xml>";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;

            }



        }



    }

    public function send($str){

    }
}