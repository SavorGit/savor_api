<?php
namespace Common\Lib;

class WechatMessage{
    const MSGTYPE_TEXT = 'text';
    const MSGTYPE_IMAGE = 'image';
    const MSGTYPE_LOCATION = 'location';
    const MSGTYPE_LINK = 'link';
    const MSGTYPE_EVENT = 'event';
    const MSGTYPE_MUSIC = 'music';
    const MSGTYPE_NEWS = 'news';
    const MSGTYPE_VOICE = 'voice';
    const MSGTYPE_VIDEO = 'video';
    const MSGTYPE_SHORTVIDEO = 'shortvideo';
    const EVENT_SUBSCRIBE = 'subscribe';       //订阅
    const EVENT_UNSUBSCRIBE = 'unsubscribe';   //取消订阅
    const EVENT_SCAN = 'SCAN';                 //扫描带参数二维码
    const EVENT_LOCATION = 'LOCATION';         //上报地理位置
    const EVENT_MENU_VIEW = 'VIEW';                     //菜单 - 点击菜单跳转链接
    const EVENT_MENU_CLICK = 'CLICK';                   //菜单 - 点击菜单拉取消息
    const EVENT_MENU_SCAN_PUSH = 'scancode_push';       //菜单 - 扫码推事件(客户端跳URL)
    const EVENT_MENU_SCAN_WAITMSG = 'scancode_waitmsg'; //菜单 - 扫码推事件(客户端不跳URL)
    const EVENT_MENU_PIC_SYS = 'pic_sysphoto';          //菜单 - 弹出系统拍照发图
    const EVENT_MENU_PIC_PHOTO = 'pic_photo_or_album';  //菜单 - 弹出拍照或者相册发图
    const EVENT_MENU_PIC_WEIXIN = 'pic_weixin';         //菜单 - 弹出微信相册发图器
    const EVENT_MENU_LOCATION = 'location_select';      //菜单 - 弹出地理位置选择器

    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
    const MENU_CREATE_URL = '/menu/create?';
    const MENU_GET_URL = '/menu/get?';
    const SELFMENU_GET_URL = '/get_current_selfmenu_info?';
    const MENU_DELETE_URL = '/menu/delete?';
    const CALLBACKSERVER_GET_URL = '/getcallbackip?';
    const SHORT_URL='/shorturl?';
    const AUTH_URL = '/token?grant_type=client_credential&';

    private $token;
    private $encodingAesKey;
    private $encrypt_type;
    private $appid;
    private $appsecret;
    private $access_token;
    private $postxml;
    private $_msg;
    private $_funcflag = false;
    private $_receive;
    private $_text_filter = true;
    private $cache_db;
    public $debug =  false;
    public $errCode = 40001;
    public $errMsg = "no access";
    public $logcallback;

    public function __construct($options){
        $this->token = isset($options['token'])?$options['token']:'';
        $this->encodingAesKey = isset($options['encodingaeskey'])?$options['encodingaeskey']:'';
        $this->appid = isset($options['appid'])?$options['appid']:'';
        $this->appsecret = isset($options['appsecret'])?$options['appsecret']:'';
        $this->debug = isset($options['debug'])?$options['debug']:false;
        $this->logcallback = isset($options['logcallback'])?$options['logcallback']:false;
        $this->cache_db = new \Common\Lib\SavorRedis();
    }

    /**
     * For weixin server validation
     */
    private function checkSignature($str=''){
        $signature = isset($_GET['signature'])?$_GET['signature']:'';
        $signature = isset($_GET['msg_signature'])?$_GET['msg_signature']:$signature; //如果存在加密验证则用加密验证段
        $timestamp = isset($_GET['timestamp'])?$_GET['timestamp']:'';
        $nonce = isset($_GET['nonce'])?$_GET['nonce']:'';

        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce,$str);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        $check_sign = false;
        if($tmpStr == $signature){
            $check_sign = true;
        }
        return $check_sign;
    }

    /**
     * 过滤文字回复\r\n换行符
     * @param string $text
     * @return string|mixed
     */
    private function _auto_text_filter($text){
        if (!$this->_text_filter) return $text;
        return str_replace("\r\n", "\n", $text);
    }

    /**
     * xml格式加密，仅请求为加密方式时再用
     */
    private function generate($encrypt, $signature, $timestamp, $nonce){
        //格式化加密信息
        $format = "<xml>
		<Encrypt><![CDATA[%s]]></Encrypt>
		<MsgSignature><![CDATA[%s]]></MsgSignature>
		<TimeStamp>%s</TimeStamp>
		<Nonce><![CDATA[%s]]></Nonce>
		</xml>";
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        if(intval($aStatus['http_code'])==200){
            return $sContent;
        }else{
            return curl_error($oCurl);
        }
        curl_close($oCurl);
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    private function http_post($url,$param,$post_file=false){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if(PHP_VERSION_ID >= 50500 && class_exists('\CURLFile')){
            $is_curlFile = true;
        }else{
            $is_curlFile = false;
            if(defined('CURLOPT_SAFE_UPLOAD')){
                curl_setopt($oCurl, CURLOPT_SAFE_UPLOAD, false);
            }
        }
        if(is_string($param)){
            $str_post = $param;
        }elseif($post_file){
            if($is_curlFile){
                foreach($param as $key => $val){
                    if(substr($val, 0, 1) == '@'){
                        $param[$key] = new \CURLFile(realpath(substr($val,1)));
                    }
                }
            }
            $str_post = $param;
        }else{
            $post_params = array();
            foreach($param as $key=>$val){
                $post_params[] = $key."=".urlencode($val);
            }
            $str_post =  join("&", $post_params);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($oCurl, CURLOPT_POST,true);
        curl_setopt($oCurl, CURLOPT_POSTFIELDS,$str_post);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus['http_code'])==200){
            return $sContent;
        }else{
            return false;
        }
    }

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename,$value,$expired){
        $this->cache_db->select(1);
        $cache = $this->cache_db->set($cachename,$value,$expired);
        return $cache;
    }

    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename){
        $this->cache_db->select(1);
        $cache = $this->cache_db->get($cachename);
        return $cache;
    }

    /**
     * 日志记录，可被重载。
     * @param mixed $log 输入日志
     * @return mixed
     */
    protected function log($log){
        if($this->debug && function_exists($this->logcallback)){
            if(is_array($log)) $log = print_r($log,true);
            return call_user_func($this->logcallback,$log);
        }
    }

    /**
     * For weixin server validation
     * @param bool $return 是否返回
     */
    public function valid($return=false){
        $encryptStr="";
        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $postStr = file_get_contents("php://input");
            $array = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->encrypt_type = isset($_GET['encrypt_type']) ? $_GET['encrypt_type']: '';
            if($this->encrypt_type == 'aes'){ //aes加密
                $this->log($postStr);
                $encryptStr = $array['Encrypt'];
                $pc = new Prpcrypt($this->encodingAesKey);
                $array = $pc->decrypt($encryptStr,$this->appid);
                if(!isset($array[0]) || ($array[0] != 0)){
                    if(!$return){
                        die('decrypt error!');
                    }else{
                        return false;
                    }
                }
                $this->postxml = $array[1];
                if(!$this->appid){
                    $this->appid = $array[2];//为了没有appid的订阅号
                }
            }else{
                $this->postxml = $postStr;
            }
        }elseif(isset($_GET['echostr'])){
            $echoStr = $_GET['echostr'];
            if($return){
                if($this->checkSignature()){
                    return $echoStr;
                }else{
                    return false;
                }
            }else{
                if($this->checkSignature()){
                    die($echoStr);
                }else{
                    die('parames invalid');
                }
            }
        }

        if(!$this->checkSignature($encryptStr)){
            if($return){
                return false;
            }else{
                die('parames invalid');
            }
        }
        return true;
    }

    /**
     * 设置发送消息
     * @param array $msg 消息数组
     * @param bool $append 是否在原消息数组追加
     */
    public function Message($msg = '',$append = false){
        if(is_null($msg)){
            $this->_msg =array();
        }elseif(is_array($msg)){
            if($append){
                $this->_msg = array_merge($this->_msg,$msg);
            }else{
                $this->_msg = $msg;
            }
            return $this->_msg;
        }else{
            return $this->_msg;
        }
    }


    /**
     * 获取微信服务器发来的信息
     */
    public function getRev(){
        if ($this->_receive) return $this;
        $postStr = !empty($this->postxml)?$this->postxml:file_get_contents("php://input");
        //兼顾使用明文又不想调用valid()方法的情况
        $this->log($postStr);
        if(!empty($postStr)){
            $this->_receive = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this;
    }

    /**
     * 获取微信服务器发来的信息
     */
    public function getRevData(){
        return $this->_receive;
    }

    /**
     * 获取消息发送者
     */
    public function getRevFrom(){
        if(isset($this->_receive['FromUserName'])){
            return $this->_receive['FromUserName'];
        }else{
            return false;
        }
    }

    /**
     * 获取消息接受者
     */
    public function getRevTo(){
        if(isset($this->_receive['ToUserName'])){
            return $this->_receive['ToUserName'];
        }else{
            return false;
        }
    }

    /**
     * 获取接收消息的类型
     */
    public function getRevType(){
        if(isset($this->_receive['MsgType'])){
            return $this->_receive['MsgType'];
        }else{
            return false;
        }
    }

    /**
     * 获取消息ID
     */
    public function getRevID(){
        if(isset($this->_receive['MsgId'])){
            return $this->_receive['MsgId'];
        }else{
            return false;
        }
    }

    /**
     * 获取消息发送时间
     */
    public function getRevCtime() {
        if(isset($this->_receive['CreateTime'])){
            return $this->_receive['CreateTime'];
        }else{
            return false;
        }
    }

    /**
     * 获取接收消息内容正文
     */
    public function getRevContent(){
        if(isset($this->_receive['Content'])){
            return $this->_receive['Content'];
        }elseif(isset($this->_receive['Recognition'])){//获取语音识别文字内容，需申请开通
            return $this->_receive['Recognition'];
        }else{
            return false;
        }
    }

    /**
     * 获取接收消息图片
     */
    public function getRevPic(){
        if(isset($this->_receive['PicUrl'])){
            return array(
                'mediaid'=>$this->_receive['MediaId'],
                'picurl'=>(string)$this->_receive['PicUrl'],    //防止picurl为空导致解析出错
            );
        }else{
            return false;
        }
    }

    /**
     * 获取接收消息链接
     */
    public function getRevLink(){
        if(isset($this->_receive['Url'])){
            return array(
                'url'=>$this->_receive['Url'],
                'title'=>$this->_receive['Title'],
                'description'=>$this->_receive['Description']
            );
        }else{
            return false;
        }
    }

    /**
     * 获取接收地理位置
     */
    public function getRevGeo(){
        if(isset($this->_receive['Location_X'])){
            return array(
                'x'=>$this->_receive['Location_X'],
                'y'=>$this->_receive['Location_Y'],
                'scale'=>$this->_receive['Scale'],
                'label'=>$this->_receive['Label']
            );
        }else{
            return false;
        }
    }

    /**
     * 获取上报地理位置事件
     */
    public function getRevEventGeo(){
        if(isset($this->_receive['Latitude'])){
            return array(
                'x'=>$this->_receive['Latitude'],
                'y'=>$this->_receive['Longitude'],
                'precision'=>$this->_receive['Precision'],
            );
        }else{
            return false;
        }
    }

    /**
     * 获取接收事件推送
     */
    public function getRevEvent(){
        if(isset($this->_receive['Event'])){
            $array['event'] = $this->_receive['Event'];
        }
        if(isset($this->_receive['EventKey'])){
            $array['key'] = $this->_receive['EventKey'];
        }
        if(isset($array) && count($array) > 0){
            return $array;
        }else{
            return false;
        }
    }

    /**
     * 获取自定义菜单的扫码推事件信息
     *
     * 事件类型为以下两种时则调用此方法有效
     * Event	 事件类型，scancode_push
     * Event	 事件类型，scancode_waitmsg
     *
     * @return: array | false
     * array (
     *     'ScanType'=>'qrcode',
     *     'ScanResult'=>'123123'
     * )
     */
    public function getRevScanInfo(){
        if(isset($this->_receive['ScanCodeInfo'])){
            if(!is_array($this->_receive['ScanCodeInfo'])){
                $array=(array)$this->_receive['ScanCodeInfo'];
                $this->_receive['ScanCodeInfo']=$array;
            }else{
                $array=$this->_receive['ScanCodeInfo'];
            }
        }
        if(isset($array) && count($array) > 0){
            return $array;
        }else{
            return false;
        }
    }

    /**
     * 获取自定义菜单的图片发送事件信息
     *
     * 事件类型为以下三种时则调用此方法有效
     * Event	 事件类型，pic_sysphoto        弹出系统拍照发图的事件推送
     * Event	 事件类型，pic_photo_or_album  弹出拍照或者相册发图的事件推送
     * Event	 事件类型，pic_weixin          弹出微信相册发图器的事件推送
     *
     * @return: array | false
     * array (
     *   'Count' => '2',
     *   'PicList' =>array (
     *         'item' =>array (
     *             0 =>array ('PicMd5Sum' => 'aaae42617cf2a14342d96005af53624c'),
     *             1 =>array ('PicMd5Sum' => '149bd39e296860a2adc2f1bb81616ff8'),
     *         ),
     *   ),
     * )
     *
     */
    public function getRevSendPicsInfo(){
        if(isset($this->_receive['SendPicsInfo'])){
            if(!is_array($this->_receive['SendPicsInfo'])){
                $array=(array)$this->_receive['SendPicsInfo'];
                if(isset($array['PicList'])){
                    $array['PicList']=(array)$array['PicList'];
                    $item=$array['PicList']['item'];
                    $array['PicList']['item']=array();
                    foreach($item as $key => $value){
                        $array['PicList']['item'][$key]=(array)$value;
                    }
                }
                $this->_receive['SendPicsInfo']=$array;
            }else{
                $array=$this->_receive['SendPicsInfo'];
            }
        }
        if(isset($array) && count($array) > 0){
            return $array;
        }else{
            return false;
        }
    }

    /**
     * 获取自定义菜单的地理位置选择器事件推送
     *
     * 事件类型为以下时则可以调用此方法有效
     * Event	 事件类型，location_select        弹出地理位置选择器的事件推送
     *
     * @return: array | false
     * array (
     *   'Location_X' => '33.731655000061',
     *   'Location_Y' => '113.29955200008047',
     *   'Scale' => '16',
     *   'Label' => '某某市某某区某某路',
     *   'Poiname' => '',
     * )
     *
     */
    public function getRevSendGeoInfo(){
        if(isset($this->_receive['SendLocationInfo'])){
            if(!is_array($this->_receive['SendLocationInfo'])){
                $array=(array)$this->_receive['SendLocationInfo'];
                if(empty($array['Poiname'])){
                    $array['Poiname']="";
                }
                if(empty($array['Label'])){
                    $array['Label']="";
                }
                $this->_receive['SendLocationInfo']=$array;
            }else{
                $array=$this->_receive['SendLocationInfo'];
            }
        }
        if(isset($array) && count($array)>0){
            return $array;
        }else{
            return false;
        }
    }

    public static function xmlSafeStr($str){
        return '<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$str).']]>';
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    public static function data_to_xml($data) {
        $xml = '';
        foreach($data as $key => $val){
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml    .=  "<$key>";
            $xml    .=  ( is_array($val) || is_object($val)) ? self::data_to_xml($val)  : self::xmlSafeStr($val);
            list($key, ) = explode(' ', $key);
            $xml    .=  "</$key>";
        }
        return $xml;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public function xml_encode($data, $root='xml', $item='item', $attr='', $id='id', $encoding='utf-8') {
        if(is_array($attr)){
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr   = trim($attr);
        $attr   = empty($attr) ? '' : " {$attr}";
        $xml   = "<{$root}{$attr}>";
        $xml   .= self::data_to_xml($data, $item, $id);
        $xml   .= "</{$root}>";
        return $xml;
    }

    /**
     * 设置回复消息
     * Example: $obj->text('hello')->reply();
     * @param string $text
     */
    public function text($text=''){
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_TEXT,
            'Content'=>$this->_auto_text_filter($text),
            'CreateTime'=>time(),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->image('media_id')->reply();
     * @param string $mediaid
     */
    public function image($mediaid=''){
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_IMAGE,
            'Image'=>array('MediaId'=>$mediaid),
            'CreateTime'=>time(),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->voice('media_id')->reply();
     * @param string $mediaid
     */
    public function voice($mediaid=''){
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_VOICE,
            'Voice'=>array('MediaId'=>$mediaid),
            'CreateTime'=>time(),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复消息
     * Example: $obj->video('media_id','title','description')->reply();
     * @param string $mediaid
     */
    public function video($mediaid='',$title='',$description=''){
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_VIDEO,
            'Video'=>array(
                'MediaId'=>$mediaid,
                'Title'=>$title,
                'Description'=>$description
            ),
            'CreateTime'=>time(),
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复音乐
     * @param string $title
     * @param string $desc
     * @param string $musicurl
     * @param string $hgmusicurl
     * @param string $thumbmediaid 音乐图片缩略图的媒体id，非必须
     */
    public function music($title,$desc,$musicurl,$hgmusicurl='',$thumbmediaid='') {
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'CreateTime'=>time(),
            'MsgType'=>self::MSGTYPE_MUSIC,
            'Music'=>array(
                'Title'=>$title,
                'Description'=>$desc,
                'MusicUrl'=>$musicurl,
                'HQMusicUrl'=>$hgmusicurl
            ),
            'FuncFlag'=>$FuncFlag
        );
        if ($thumbmediaid) {
            $msg['Music']['ThumbMediaId'] = $thumbmediaid;
        }
        $this->Message($msg);
        return $this;
    }

    /**
     * 设置回复图文
     * @param array $newsData
     * 数组结构:
     *  array(
     *  	"0"=>array(
     *  		'Title'=>'msg title',
     *  		'Description'=>'summary text',
     *  		'PicUrl'=>'http://www.domain.com/1.jpg',
     *  		'Url'=>'http://www.domain.com/1.html'
     *  	),
     *  	"1"=>....
     *  )
     */
    public function news($newsData=array()){
        $FuncFlag = $this->_funcflag ? 1 : 0;
        $count = count($newsData);

        $msg = array(
            'ToUserName' => $this->getRevFrom(),
            'FromUserName'=>$this->getRevTo(),
            'MsgType'=>self::MSGTYPE_NEWS,
            'CreateTime'=>time(),
            'ArticleCount'=>$count,
            'Articles'=>$newsData,
            'FuncFlag'=>$FuncFlag
        );
        $this->Message($msg);
        return $this;
    }

    /**
     *
     * 回复微信服务器, 此函数支持链式操作
     * Example: $this->text('msg tips')->reply();
     * @param string $msg 要发送的信息, 默认取$this->_msg
     * @param bool $return 是否返回信息而不抛出到浏览器 默认:否
     */
    public function reply($msg=array(),$return = false){
        if(empty($msg)){
            if (empty($this->_msg)){//防止不先设置回复内容，直接调用reply方法导致异常
                return false;
            }
            $msg = $this->_msg;
        }
        $xmldata=  $this->xml_encode($msg);
        $this->log($xmldata);
        if($this->encrypt_type == 'aes'){ //如果来源消息为加密方式
            $pc = new Prpcrypt($this->encodingAesKey);
            $array = $pc->encrypt($xmldata, $this->appid);
            $ret = $array[0];
            if($ret != 0){
                $this->log('encrypt err!');
                return false;
            }
            $timestamp = time();
            $nonce = rand(77,999)*rand(605,888)*rand(11,99);
            $encrypt = $array[1];
            $tmpArr = array($this->token, $timestamp, $nonce,$encrypt);//比普通公众平台多了一个加密的密文
            sort($tmpArr, SORT_STRING);
            $signature = implode($tmpArr);
            $signature = sha1($signature);
            $xmldata = $this->generate($encrypt, $signature, $timestamp, $nonce);
            $this->log($xmldata);
        }
        if($return){
            return $xmldata;
        }else{
            echo $xmldata;
        }
    }

    /**
     * 获取access_token
     * @param string $appid 如在类初始化时已提供，则可为空
     * @param string $appsecret 如在类初始化时已提供，则可为空
     * @param string $token 手动指定access_token，非必要情况不建议用
     */
    public function checkAuth($appid='',$appsecret='',$token=''){
        if(!$appid || !$appsecret){
            $appid = $this->appid;
            $appsecret = $this->appsecret;
        }
        if($token){ //手动指定token，优先使用
            $this->access_token=$token;
            return $this->access_token;
        }
        $key_token = 'wechat_access_token'.$appid;
        $rs_token = $this->getCache($key_token);
        if($rs_token){
            $this->access_token = $rs_token;
            return $rs_token;
        }
        $result = $this->http_get(self::API_URL_PREFIX.self::AUTH_URL.'appid='.$appid.'&secret='.$appsecret);
        if($result){
            $json = json_decode($result,true);
            if(!$json || isset($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->access_token = $json['access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in'])-100 : 3600;
            $this->setCache($key_token,$this->access_token,$expire);
            return $this->access_token;
        }
        return false;
    }


    /**
     * 微信api不支持中文转义的json结构
     * @param array $arr
     */
    static function json_encode($arr){
        if(count($arr) == 0) return "[]";
        $parts = array ();
        $is_list = false;
        //Find out if the given array is a numerical array
        $keys = array_keys ( $arr );
        $max_length = count ( $arr ) - 1;
        if(($keys [0] === 0) && ($keys[$max_length] === $max_length)) { //See if the first key is 0 and last key is length - 1
            $is_list = true;
            for($i = 0; $i<count($keys); $i++){ //See if each key correspondes to its position
                if($i != $keys[$i]){ //A key fails at position check.
                    $is_list = false; //It is an associative array.
                    break;
                }
            }
        }
        foreach($arr as $key => $value){
            if(is_array($value)){ //Custom handling for arrays
                if($is_list){
                    $parts [] = self::json_encode ( $value ); /* :RECURSION: */
                }else{
                    $parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
                }
            }else{
                $str = '';
                if(!$is_list){
                    $str = '"' . $key . '":';
                }
                //Custom handling for multiple data types
                if(!is_string ($value) && is_numeric($value) && $value<2000000000){
                    $str .= $value; //Numbers
                }elseif ($value === false){
                    $str .= 'false'; //The booleans
                }elseif ($value === true){
                    $str .= 'true';
                }else{
                    $str .= '"' . addslashes ( $value ) . '"'; //All other things
                }
                // :TODO: Is there any more datatype we should be in the lookout for? (Object?)
                $parts [] = $str;
            }
        }
        $json = implode ( ',', $parts );
        if($is_list){
            return '[' . $json . ']'; //Return numerical JSON
        }
        return '{' . $json . '}'; //Return associative JSON
    }

    /**
     * 获取签名
     * @param array $arrdata 签名数组
     * @param string $method 签名方法
     * @return boolean|string 签名值
     */
    public function getSignature($arrdata,$method="sha1"){
        if(!function_exists($method)) return false;
        ksort($arrdata);
        $paramstring = "";
        foreach($arrdata as $key => $value){
            if(strlen($paramstring) == 0){
                $paramstring .= $key . "=" . $value;
            }else{
                $paramstring .= "&" . $key . "=" . $value;
            }
        }
        $Sign = $method($paramstring);
        return $Sign;
    }


    /**
     * 生成随机字串
     * @param number $length 长度，默认为16，最长为32字节
     * @return string
     */
    public function generateNonceStr($length=16){
        // 密码字符集，可任意添加你需要的字符
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for($i=0; $i<$length; $i++){
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }

    /**
     * 创建菜单(认证后的订阅号可用)
     * @param array $data 菜单数组数据
     * type可以选择为以下几种，其中5-8除了收到菜单事件以外，还会单独收到对应类型的信息。
     * 1、click：点击推事件
     * 2、view：跳转URL
     * 3、scancode_push：扫码推事件
     * 4、scancode_waitmsg：扫码推事件且弹出“消息接收中”提示框
     * 5、pic_sysphoto：弹出系统拍照发图
     * 6、pic_photo_or_album：弹出拍照或者相册发图
     * 7、pic_weixin：弹出微信相册发图器
     * 8、location_select：弹出地理位置选择器
     */
    public function createMenu($data){
        if(!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_post(self::API_URL_PREFIX.self::MENU_CREATE_URL.'access_token='.$this->access_token,self::json_encode($data));
        if($result){
            $json = json_decode($result,true);
            if(!$json || !empty($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 获取菜单(认证后的订阅号可用)
     * @return array('menu'=>array(....s))
     */
    public function getMenu(){
        if(!$this->access_token && !$this->checkAuth()) return false;
        $url = self::API_URL_PREFIX.self::MENU_GET_URL.'access_token='.$this->access_token;
        $result = $this->http_get($url);
        if($result){
            $json = json_decode($result,true);
            if(!$json || isset($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }


    public function getSelfmenu(){
        if(!$this->access_token && !$this->checkAuth()) return false;
        $url = self::API_URL_PREFIX.self::SELFMENU_GET_URL.'access_token='.$this->access_token;
        $result = $this->http_get($url);
        if($result){
            $json = json_decode($result,true);
            if(!$json || isset($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 删除菜单(认证后的订阅号可用)
     * @return boolean
     */
    public function deleteMenu(){
        if(!$this->access_token && !$this->checkAuth())	return false;
        $result = $this->http_get(self::API_URL_PREFIX.self::MENU_DELETE_URL.'access_token='.$this->access_token);
        if($result){
            $json = json_decode($result,true);
            if(!$json || !empty($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * 长链接转短链接接口
     * @param string $long_url 传入要转换的长url
     * @return boolean|string url 成功则返回转换后的短url
     */
    public function getShortUrl($long_url){
        if(!$this->access_token && !$this->checkAuth())	return false;
        $data = array(
            'action'=>'long2short',
            'long_url'=>$long_url
        );
        $result = $this->http_post(self::API_URL_PREFIX.self::SHORT_URL.'access_token='.$this->access_token,self::json_encode($data));
        if($result){
            $json = json_decode($result,true);
            if(!$json || !empty($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json['short_url'];
        }
        return false;
    }

    /**
     * 获取微信服务器IP地址列表
     * @return array('127.0.0.1','127.0.0.1')
     */
    public function getServerIp(){
        if(!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_get(self::API_URL_PREFIX.self::CALLBACKSERVER_GET_URL.'access_token='.$this->access_token);
        if($result){
            $json = json_decode($result,true);
            if(!$json || isset($json['errcode'])){
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json['ip_list'];
        }
        return false;
    }
}


class PKCS7Encoder{
    public static $block_size = 32;
    public function encode($text){
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if($amount_to_pad == 0){
            $amount_to_pad = PKCS7Encoder::block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for($index = 0; $index<$amount_to_pad; $index++){
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }
    public function decode($text){
        $pad = ord(substr($text, -1));
        if($pad<1 || $pad>PKCS7Encoder::$block_size){
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }
}
/**
 * Prpcrypt class
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt{
    public $key;
    public function __construct($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 兼容老版本php构造函数，不能在 __construct() 方法前边，否则报错
     */
    function Prpcrypt($k){
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     * @param string $text 需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text, $appid){
        try{
            //获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();//"aaaabbbbccccdddd";
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            // 网络字节序
            $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            //使用自定义的填充方式对明文进行补位填充
            $pkc_encoder = new PKCS7Encoder;
            $text = $pkc_encoder->encode($text);
            mcrypt_generic_init($module, $this->key, $iv);
            //加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
            //使用BASE64对加密后的字符串进行编码
            return array(ErrorCode::$OK, base64_encode($encrypted));
        }catch(Exception $e) {
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted, $appid){
        try{
            //使用BASE64对需要解密的字符串进行解码
            $ciphertext_dec = base64_decode($encrypted);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            mcrypt_generic_init($module, $this->key, $iv);
            //解密
            $decrypted = mdecrypt_generic($module, $ciphertext_dec);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        }catch(Exception $e){
            return array(ErrorCode::$DecryptAESError, null);
        }
        try{
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if(strlen($result) < 16){
                return "";
            }
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            if(!$appid){
                $appid = $from_appid;//如果传入的appid是空的，则认为是订阅号，使用数据中提取出来的appid
            }
        }catch(Exception $e){
            return array(ErrorCode::$IllegalBuffer, null);
        }
        if($from_appid != $appid){
            return array(ErrorCode::$ValidateAppidError, null);
        }
        //不注释上边两行，避免传入appid是错误的情况
        return array(0, $xml_content, $from_appid); //增加appid，为了解决后面加密回复消息的时候没有appid的订阅号会无法回复

    }

    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    private function getRandomStr(){
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for($i=0; $i<16; $i++){
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}
class ErrorCode{
    public static $OK = 0;
    public static $ValidateSignatureError = 40001;
    public static $ParseXmlError = 40002;
    public static $ComputeSignatureError = 40003;
    public static $IllegalAesKey = 40004;
    public static $ValidateAppidError = 40005;
    public static $EncryptAESError = 40006;
    public static $DecryptAESError = 40007;
    public static $IllegalBuffer = 40008;
    public static $EncodeBase64Error = 40009;
    public static $DecodeBase64Error = 40010;
    public static $GenReturnXmlError = 40011;
    public static $errCode=array(
        '0' => '处理成功',
        '40001' => '校验签名失败',
        '40002' => '解析xml失败',
        '40003' => '计算签名失败',
        '40004' => '不合法的AESKey',
        '40005' => '校验AppID失败',
        '40006' => 'AES加密失败',
        '40007' => 'AES解密失败',
        '40008' => '公众平台发送的xml不合法',
        '40009' => 'Base64编码失败',
        '40010' => 'Base64解码失败',
        '40011' => '公众帐号生成回包xml失败'
    );
    public static function getErrText($err) {
        if (isset(self::$errCode[$err])) {
            return self::$errCode[$err];
        }else {
            return false;
        };
    }
}
