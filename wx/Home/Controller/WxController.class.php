<?php
namespace Home\Controller;
use Think\Controller;
class WxController extends Controller {
    public $toUserName = '';
    public $fromUserName = '';
    public $time = '';
    public $msgType = "text";
    public $postObj = '';
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
        $this -> postObj = simplexml_load_string($postArr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if(strtolower($this->postObj->MsgType) == 'event' && !$this->postObj->Event){ //排除菜单点击事件
            if(strtolower($this->postObj->Event) == 'subscribe'){
                $content = "欢迎订阅boyyb的微信公众号！我将为你提供优质的服务。";
                $this->sendText($content);
            }
        }elseif(strtolower($this->postObj->MsgType == 'text')){
            $str = trim($this->postObj->Content);//去除用户发来信息的前后空格
            if($str == '图文'){
                $this->sendNews();die;
            }
            if(strpos($str,"天气") !== false){
                $city = mb_substr($str,0,2,'UTF-8');
                if($city == "天气"){$city = "重庆";}
                $citycode = urlencode(iconv('utf-8', 'gb2312', $city));
                $url = "http://php.weather.sina.com.cn/xml.php?city=$citycode&password=DJOYnieT8234jlsK&day=0";
                $weatherXml = file_get_contents($url);//获取的是xml数据
                $obj = simplexml_load_string($weatherXml, 'SimpleXMLElement', LIBXML_NOCDATA);//转为对象
                $weather = json_decode(json_encode($obj),TRUE);//转为数组
                if($weather['Weather']){
                    $contentStr = $weather['Weather']['city']."天气情况:\n";
                    $contentStr .= $weather['Weather']['status1']."\n";
                    $contentStr .= $weather['Weather']['direction1']."\n";
                    $contentStr .= '最低温度：'.$weather['Weather']['temperature2']."℃\n";
                    $contentStr .= '最高温度：'.$weather['Weather']['temperature1']."℃\n";
                    $contentStr .= '更新时间：'.$weather['Weather']['udatetime']."\n";
                    $contentStr .= '回复“城市+天气”查询其他城市天气';
                }else{
                    $contentStr = "不存在该地点的天气，你的输入有误！";
                }
                $this -> sendText($contentStr);
                die;
            }
            switch($str){//根据关键字回复内容
                case "你是谁":$content = "我是帅气的彬彬哥！";break;
                case "你在哪":$content = "我在重庆南坪呢！";break;
                case "你好":$content = "你好，有什么可以为你服务的吗？";break;
                case "bingo":$content = "that's so cool！";break;
                case "看片":$content = "<a href='http://www.youku.com'>点我就可以看</a>";break;
                default:$content = "不知道你在说什么鬼？";
            }

            $this->sendText($content);
        }elseif(strtolower($this->postObj->MsgType == 'voice')){
            $content = "你说什么呀？我什么都听不到！！！";
            $this->sendText($content);

        }elseif(strtolower($this->postObj->Event) == "click"){
            //自定义菜单中的点击事件
            $key = $this->postObj->EventKey;
            switch($key){
                case 'item1':$content = "你点击了菜单一";break;
                case 'item21':$content = "你点击了二级菜单一";break;
                case 'item22':$content = "你点击了二级菜单二";break;
            }
            $this -> sendText($content);
        }

    }

    //发送消息给用户
    public function sendText($str){
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $msgType = "text";
        $contentStr = $str;
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

    //发送图文消息
    public function sendNews(){
        $fromUsername = $this->postObj->FromUserName;
        $toUsername = $this->postObj->ToUserName;
        $time = time();
        $title = "努力！";
        $description = "bingo is on the way！";
        $picurl = "http://img0.imgtn.bdimg.com/it/u=2481620578,3431696814&fm=21&gp=0.jpg";
        $url = "http://www.baidu.com";
        $newsTplHead = "<xml>
                <ToUserName><![CDATA[$fromUsername]]></ToUserName>
                <FromUserName><![CDATA[$toUsername]]></FromUserName>
                <CreateTime>$time</CreateTime>
                <MsgType><![CDATA[news]]></MsgType>
                <ArticleCount>3</ArticleCount>
                <Articles>";
        //多个item就是多图文，一个item就是单图文，注意上面的articlecount
        $newsTplBody = "<item>
                <Title><![CDATA[$title]]></Title>
                <Description><![CDATA[$description]]></Description>
                <PicUrl><![CDATA[$picurl]]></PicUrl>
                <Url><![CDATA[$url]]></Url>
                </item>
                <item>
                <Title><![CDATA[$title]]></Title>
                <Description><![CDATA[$description]]></Description>
                <PicUrl><![CDATA[$picurl]]></PicUrl>
                <Url><![CDATA[$url]]></Url>
                </item>
                <item>
                <Title><![CDATA[$title]]></Title>
                <Description><![CDATA[$description]]></Description>
                <PicUrl><![CDATA[$picurl]]></PicUrl>
                <Url><![CDATA[$url]]></Url>
                </item>";
        $newsTplFoot = "</Articles>
                <FuncFlag>0</FuncFlag>
                </xml>";

        $str = $newsTplHead.$newsTplBody.$newsTplFoot;
        echo $str;
    }

    /*
     * $url 接口url
     * $type 请求方法
     * $res 返回数据类型
     * $arr post请求参数
     */
    public function http_curl($url,$type='get',$res='json',$arr){
        //初始化curl
        $ch = curl_init();
        // 设置URL和相应的选项
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        if($type == "post"){
            curl_setopt ($ch, CURLOPT_POST, 1);
            curl_setopt ($ch, CURLOPT_POSTFIELDS, $arr);
        }
        //跳过SSL证书检查,否则结果为null
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //采集数据，返回json格式数据
        $data = curl_exec($ch);
        //关闭cURL资源，并且释放系统资源
        curl_close($ch);
        if($res == 'json'){
            if(curl_errno($ch)){
                return curl_error($ch);
            }else{
                return json_decode($data,true);//json转为数组并返回,不加true转为对象
            }
        }
    }
    //access_token是公众号的全局唯一接口调用凭据，公众号调用各接口时都需使用access_token
    public function getAccessToken(){
        if($_SESSION['accessToken'] && $_SESSION['expireTime']>time()){ //accesstoken没过期直接调用
            return $_SESSION['accessToken'];
        }else{ //accesstoken过期后或不存在，则重新生成
            //开发者号
            //$appid = "wx72deeb26411bd991";
            //$appsecret = "44573d9d684cb0570c9bb46a39ffe28d";
            //测试号,用于自定义菜单
            $appid = 'wxda2fb7a546a427bb';
            $appsecret = 'b1e4c71c5370487b74527e11d40819f2';
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$appsecret}";
            $res = $this -> http_curl($url,'get','json');
            $accessToken = $res['access_token'];
            //将accesstoken存到session
            $_SESSION['accessToken'] = $accessToken;
            $_SESSION['expireTime'] = time()+7200;
            return $accessToken;
        }
    }

    //获取微信服务器ip用于检测，杜绝假冒ip
    public function getServerIp(){
        $accessToken= 'xzP-lOpFhctdmYOSmu9u9YRwEv528oPfYA2l7kJAONmFLbJKACfhxz_Ap9fXXn5GBWmyVFF0watOQ2NN8nvoFnq9t1AtvaVaS_tg7oK_JdFtYIzPed5pdBX3SpbFBh76GZBiAEALEG';
        $url = "https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token={$accessToken}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = curl_exec($ch);
        curl_close($ch);
        if(curl_errno($ch)){
            var_dump(curl_error($ch));
        }
        $arr = json_decode($data,true);
        var_dump($arr);
    }

    //创建微信菜单
    public function menu(){
        //微信接口调用都是通过curl方式 post/get
        $accessToken = $this -> getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=$accessToken";
        //菜单数组
        $postArr = array(
            'button'=>array(
                array(
                    'name'=>urlencode('菜单一'),
                    'type'=>'click',
                    'key'=>'item1'
                ),
                array(
                    'name'=>urlencode('菜单二'),
                    'sub_button'=>array(
                        array(
                            'name'=>urlencode('二级菜单一'),
                            'type'=>'click',
                            'key'=>'item21'
                        ),
                        array(
                            'name'=>urlencode('二级菜单二'),
                            'type'=>'click',
                            'key'=>'item22'
                        ),
                        array(
                            'name'=>urlencode('跳转百度'),
                            'type'=>'view',
                            'url'=>'http://www.baidu.com'
                        )
                    )
                ),
                array(
                    'name'=>urlencode('腾讯主页'),
                    'type'=>'view',
                    'url'=>'http://www.qq.com'
                )
            )
        );
        //汉字转json时会被转码，所以事先对汉字转码，转json时不会被转码，最后再解码恢复汉字
        $postJson = urldecode(json_encode($postArr));
        $res = $this -> http_curl($url,'post','json',$postJson);
        var_dump($res);

    }

}