<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\Qrcode;
class QrcodeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxQrcode':
                $this->is_verify = 1;
                $this->valid_fields = array('data_id'=>1001,'type'=>1001,'box_id'=>1002,'box_mac'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getBoxQrcode(){
        $data_id = $this->params['data_id'];
        $box_id = $this->params['box_id'];
        $box_mac = $this->params['box_mac'];
        $type = $this->params['type'];//34分享文件二维码 28商城商品大屏购买 37本地生活店铺二维码
        $short_urls = C('SHORT_URLS');
        $times = getMillisecond();
        switch ($type){
            case 34:
                $code_url = $short_urls['SHARE_FILE_QR'];
                $content = $code_url.'file_'.$data_id.'_'.$type.'_'.$box_id;
                break;
            case 35:
            case 28:
            case 37:
            case 38:
            case 39:
            case 24:
            case 41:
            case 42:
            case 44:
            case 45:
            case 46:
                $now_time = date('zH');
                $encode_key = "$type{$box_id}$now_time{$data_id}";
                $redis  =  \Common\Lib\SavorRedis::getInstance();
                $redis->select(5);
                $times = getMillisecond();
                $scene = $box_mac.'_'.$type.'_'.$times.'_'.$data_id;
                $cache_key = C('SAPP_QRCODE').$encode_key;
                if($type==24){
                    $expire_time = 3600*25;
                }elseif($type==42){
                    $expire_time = 3600*4;
                }else{
                    $expire_time = 3600*3;
                }
                $redis->set($cache_key,$scene,$expire_time);

                $hash_ids_key = C('HASH_IDS_KEY');
                $hashids = new \Common\Lib\Hashids($hash_ids_key);
                $s = $hashids->encode($encode_key);
                $short_urls = C('SHORT_URLS');
                $content = $short_urls['BOX_QR'].$s;
                break;
            default:
                $code_url = $short_urls['SHARE_FILE_QR'];
                $content = $code_url.'file_'.$data_id.'_'.$type.'_'.$box_id.'_'.$times;
        }
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }


}