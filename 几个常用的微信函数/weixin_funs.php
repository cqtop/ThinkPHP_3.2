<?php
#几个微信常用的函数=========================================================================================================================
function writeLog($content) {
    $content = $content ? $content : '';
    file_put_contents('./tttt.log', date('Y-m-d H:i:s ') . print_r($content, true) . "\r\n\r\n", FILE_APPEND);
}

function http_get($url) {
    if (empty($url)) {
        return [];
    }
    $result = json_decode(file_get_contents($url), true);
    return $result;
}
function http_post($url, $param, $is_file = false, $return_array = true) {
    if (!$is_file && is_array($param)) {
        $param = JSON($param);
    }
    //post文件处理
    if (is_array($param) && $is_file) {
        foreach ($param as $key => $val) {
            if (substr($val, 0, 1) == '@') {
                if (class_exists('\CURLFile')) {
                    //$val = new \CURLFile(substr($val, 1));
                    $param[$key] = curl_file_create(substr($val, 1));
                }
            }
        }
    }

    $ch = curl_init();
    if ($is_file) {
        $header [] = "content-type: multipart/form-data; charset=UTF-8";
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
    } else {
        $header [] = "content-type: application/json; charset=UTF-8";
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_POST, true); // enable posting
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param); // post images 
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
    $res = curl_exec($ch);

    $flat = curl_errno($ch);
    if ($flat) {
        $data = curl_error($ch);
    }
    curl_close($ch);
    if ($return_array) {
        $res = json_decode($res, true);
        if (isset($res['errcode']) && $res['errcode'] > 0) {
            writeLog($res);
        }
    }
    return $res;
}


function outPutCurl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}
#=========================================================================================================================

//获取accessToken
public static function getAccessToken($force = false) {
	$appid = self::$appid;
	$secret = self::$secret;
	$tokenKey = 'access:token';
	if (empty($appid) || empty($secret)) {
		return 0;
	}
	//return 'lIHuVnX891FDn4OY-X1itYnrAJi_KNCJTbNC_bCSTSThoEQiBmY1Ub8bpOecdFH3mSgkfIO9rXiX0-hoa4HlNbHyRsEkXmAVASxnW-nkTL-gvs5BMa8G3MoIQGiCiEwkDAGcAFAROU';
	if ($force === true || !Redis::exists($tokenKey)) {
		$result = http_get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret);
		if (isset($result['access_token'])) {
			$token = $result['access_token'];
			Redis::set($tokenKey, $token);
			Redis::expire($tokenKey, $result['expires_in'] - 100);
		} else {
			return 0;
		}
	} else {
		$token = Redis::get($tokenKey);
	}
	return $token;
}


//获取粉丝信息
public static function getSignUserInfo($openid) {
	$url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . self::getAccessToken() . '&openid=' . $openid . '&lang=zh_CN';
	$info = file_get_contents($url);
	return json_decode($info, true);
}


//上传素材-图片
public static function UploadImage($image) {
	$param = [];
	$param ['media'] = '@' . realpath($image);
	$param ['type'] = 'image';
	$url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' . self::getAccessToken();
	$res = http_post($url, $param, true);
	return $res;
}

//下载素材-图片 临时文件
public static function DownloadImage($media_id) {
	$url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . self::getAccessToken() . '&media_id=' . $media_id;
	$fileRelative = 'upload/weixin/' . date("Y/m/d/");
	$savePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $fileRelative;
	mkdirs($savePath);
	$fileName = Helper::randNumber18() . '.' . 'jpg';
	$imagePath = $savePath . $fileName;
	$imageContent = outPutCurl($url);
	$result = json_decode($imageContent, true);
	
	if (isset($result ['errcode']) && $result ['errcode'] != 0) {
		return Response::json($data = ['success' => false, 'msg' => '发送失败,'.$result['errmsg']]);
		die();
	}
	
	//保存到本地
	file_put_contents($imagePath, $imageContent);
	//保存到7牛
	$fileRelative = $fileRelative.$fileName;
	Qiniu::upload($fileRelative);
	
	return str_replace($_SERVER['DOCUMENT_ROOT'] . '/', '', $imagePath);
}