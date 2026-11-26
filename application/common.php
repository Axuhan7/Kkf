<?php

namespace app;

use think\Db;
use think\Exception;

class Common{

     /**
      * url参数加密类.
      * [encrypt description]
      * @param  [type] $string    [description]
      * @param  [type] $operation [description]
      * @param  string $key       [description]
      * @return [type]            [description]
      */
     public function encrypt($string,$operation,$key='')
     {
        /* if($operation=='D'){
             $string=urldecode($string);
         }*/
         $key=md5($key);
         $key_length=strlen($key);
         $string=$operation=='D'?base64_decode($string):substr(md5($string.$key),0,8).$string;
         $string_length=strlen($string);
         $rndkey=$box=array();
         $result='';
         for($i=0;$i<=255;$i++)
         {
           $rndkey[$i]=ord($key[$i%$key_length]);
           $box[$i]=$i;
         }
         for($j=$i=0;$i<256;$i++)
         {
           $j=($j+$box[$i]+$rndkey[$i])%256;
           $tmp=$box[$i];
           $box[$i]=$box[$j];
           $box[$j]=$tmp;
         }
         for($a=$j=$i=0;$i<$string_length;$i++)
         {
           $a=($a+1)%256;
           $j=($j+$box[$a])%256;
           $tmp=$box[$a];
           $box[$a]=$box[$j];
           $box[$j]=$tmp;
           $result.=chr(ord($string[$i])^($box[($box[$a]+$box[$j])%256]));
         }
         if($operation=='D')
         {
           if(substr($result,0,8)==substr(md5(substr($result,8).$key),0,8))
           {
             return substr($result,8);
           }
           else
           {
             return'';
           }
         }
         else
         {
           return urlencode(str_replace('=','',base64_encode($result)));
         }
     }


      /**
  * 判断访问地址是PC和移动方法
  * [is_mobile description]
  * @return boolean [description]
  */
  public function isMobile()
   {
      // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
      if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
      }
      // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
      if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
      }
      // 脑残法，判断手机发送的客户端标志,兼容性有待提高。其中'MicroMessenger'是电脑微信
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia','sony','ericsson','mot','samsung','htc','sgh','lg','sharp','sie-','philips','panasonic','alcatel','lenovo','iphone','ipod','blackberry','meizu','android','netfront','symbian','ucweb','windowsce','palm','operamini','operamobi','openwave','nexusone','cldc','midp','wap','mobile','MicroMessenger');
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
          return true;
        }
      }
      // 协议法，因为有可能不准确，放到最后判断
      if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
          return true;
        }
      }
      return false;
   }

   /**
    * 生成唯一访客ID
    * @return string
    */
   public function getvid()
   {
       return md5(uniqid(mt_rand(), true));
   }

   /**
    * 移除emoji表情
    * @param string $string
    * @return string
    */
   public function remove_emoji($string)
   {
       $clean_text = "";
       $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
       $clean_text = preg_replace($regexEmoticons, '', $string);
       $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
       $clean_text = preg_replace($regexSymbols, '', $clean_text);
       $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
       $clean_text = preg_replace($regexTransport, '', $clean_text);
       $regexMisc = '/[\x{2600}-\x{26FF}]/u';
       $clean_text = preg_replace($regexMisc, '', $clean_text);
       $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
       $clean_text = preg_replace($regexDingbats, '', $clean_text);
       return $clean_text;
   }

   /**
    * 加密编码
    * @param string $str
    * @param string $key
    * @param int $expire
    * @return string
    */
   public function cpEncode($str, $key = '', $expire = 0)
   {
       $key = md5($key);
       $key_length = strlen($key);
       $str = substr(md5($str . $key), 0, 8) . $str;
       $string_length = strlen($str);
       $rndkey = $box = array();
       $result = '';
       for ($i = 0; $i <= 255; $i++) {
           $rndkey[$i] = ord($key[$i % $key_length]);
           $box[$i] = $i;
       }
       for ($j = $i = 0; $i < 256; $i++) {
           $j = ($j + $box[$i] + $rndkey[$i]) % 256;
           $tmp = $box[$i];
           $box[$i] = $box[$j];
           $box[$j] = $tmp;
       }
       for ($a = $j = $i = 0; $i < $string_length; $i++) {
           $a = ($a + 1) % 256;
           $j = ($j + $box[$a]) % 256;
           $tmp = $box[$a];
           $box[$a] = $box[$j];
           $box[$j] = $tmp;
           $result .= chr(ord($str[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
       }
       if ($expire) {
           $result = sprintf('%010d', $expire ? $expire + time() : 0) . substr(md5($result . $key), 0, 16) . $result;
       } else {
           $result = '0000000000' . substr(md5($result . $key), 0, 16) . $result;
       }
       return str_replace('=', '', base64_encode($result));
   }

   /**
    * 解密编码
    * @param string $str
    * @param string $key
    * @return string
    */
   public function cpDecode($str, $key = '')
   {
       $key = md5($key);
       $key_length = strlen($key);
       $str = base64_decode($str);
       $string_length = strlen($str);
       $rndkey = $box = array();
       $result = '';
       for ($i = 0; $i <= 255; $i++) {
           $rndkey[$i] = ord($key[$i % $key_length]);
           $box[$i] = $i;
       }
       for ($j = $i = 0; $i < 256; $i++) {
           $j = ($j + $box[$i] + $rndkey[$i]) % 256;
           $tmp = $box[$i];
           $box[$i] = $box[$j];
           $box[$j] = $tmp;
       }
       for ($a = $j = $i = 0; $i < $string_length; $i++) {
           $a = ($a + 1) % 256;
           $j = ($j + $box[$a]) % 256;
           $tmp = $box[$a];
           $box[$a] = $box[$j];
           $box[$j] = $tmp;
           $result .= chr(ord($str[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
       }
       if (substr($result, 10, 16) == substr(md5(substr($result, 26) . $key), 0, 16)) {
           return substr($result, 26);
       } else {
           return '';
       }
   }

   /**
    * 递归数组trim
    * @param mixed $data
    * @return mixed
    */
   public function deep_array_map_trim($data)
   {
       if (is_array($data)) {
           foreach ($data as $key => $value) {
               $data[$key] = $this->deep_array_map_trim($value);
           }
       } elseif (is_string($data)) {
           $data = trim($data);
       }
       return $data;
   }

   /**
    * 获取点亮起来访问域名
    * @return string
    */
   public function dianqilai_access_domain()
   {
       $option = Db::name('option')->where('title', 'dianqilai_access_domain')->find();
       return $option ? $option['value'] : '';
   }

   /**
    * 获取点亮起来版本
    * @return string
    */
   public function dianqilai_version()
   {
       $option = Db::name('option')->where('title', 'dianqilai_version')->find();
       return $option ? $option['value'] : '';
   }

   /**
    * 清除XSS攻击代码（基于 HTMLPurifier 白名单过滤）
    * @param string $content
    * @return string
    */
   public static function clearXSS($content)
   {
       if (empty($content)) {
           return $content;
       }
       // 优先使用 HTMLPurifier 进行白名单过滤
       $purifier = self::getHTMLPurifier();
       if ($purifier !== null) {
           return $purifier->purify($content);
       }
       // HTMLPurifier 不可用时降级为严格转义（仅保留 img/a/audio 标签的白名单）
       return htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
   }

   /**
    * 获取 HTMLPurifier 实例（单例）
    * @return \HTMLPurifier|null
    */
   private static function getHTMLPurifier()
   {
       static $purifier = null;
       if ($purifier !== null) {
           return $purifier;
       }
       $autoloader = VENDOR_PATH . 'ezyang/htmlpurifier/library/HTMLPurifier.auto.php';
       if (!is_file($autoloader)) {
           return null;
       }
       require_once $autoloader;
       $config = \HTMLPurifier_Config::createDefault();
        $cacheDir = RUNTIME_PATH . 'htmlpurifier';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Core.EscapeNonASCIICharacters', false);
        $config->set('HTML.Allowed', 'a[href|target],img[src|alt|title|width|height],b,i,em,strong,br,span,div,p,ul,ol,li');
       $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
       $config->set('HTML.TargetBlank', true);
       $config->set('HTML.DefinitionID', 'custom_html5');
       $config->set('HTML.DefinitionRev', 1);
       if ($def = $config->maybeGetRawHTMLDefinition()) {
           $def->addElement('audio', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', ['src' => 'URI', 'controls' => 'Bool', 'autoplay' => 'Bool', 'loop' => 'Bool', 'preload' => 'Enum#auto,metadata,none']);
           $def->addElement('source', 'Block', 'Empty', 'Common', ['src' => 'URI', 'type' => 'Text']);
       }
       $purifier = new \HTMLPurifier($config);
       return $purifier;
   }

   /**
    * 构建商品卡片 HTML（所有字段经 htmlspecialchars 转义）
    * @param array $content json_decode 后的商品数组
    * @return string 安全的 HTML 字符串
    */
   public static function buildProductHtml($content)
   {
       if (!is_array($content)) {
           return '';
       }
       $url   = isset($content['url'])   ? htmlspecialchars($content['url'],   ENT_QUOTES, 'UTF-8') : '';
       $img   = isset($content['img'])   ? htmlspecialchars($content['img'],   ENT_QUOTES, 'UTF-8') : '';
       $title = isset($content['title']) ? htmlspecialchars($content['title'], ENT_QUOTES, 'UTF-8') : '';
       $price = isset($content['price']) ? htmlspecialchars($content['price'], ENT_QUOTES, 'UTF-8') : '';
       $info  = isset($content['info'])  ? htmlspecialchars($content['info'],  ENT_QUOTES, 'UTF-8') : '';
       $str  = '<a href="' . $url . '" target="_blank" class="wolive_product">';
       $str .= '<div class="wolive_img"><img src="' . $img . '" width="100px"></div>';
       $str .= '<div class="wolive_head"><p class="wolive_info">' . $title . '</p><p class="wolive_price">' . $price . '</p>';
       $str .= '<p class="wolive_info">' . $info . '</p>';
       $str .= '</div></a>';
       return $str;
   }

}
