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
                $this->valid_fields = array('data_id'=>1001,'type'=>1001,'box_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getBoxQrcode(){
        $data_id = $this->params['data_id'];
        $box_id = $this->params['box_id'];
        $type = $this->params['type'];//34分享文件二维码
        $short_urls = C('SHORT_URLS');
        $times = getMillisecond();
        switch ($type){
            case 34:
                $code_url = $short_urls['SHARE_FILE_QR'];
                $content = $code_url.'file_'.$data_id.'_'.$type.'_'.$box_id.'_'.$times;
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