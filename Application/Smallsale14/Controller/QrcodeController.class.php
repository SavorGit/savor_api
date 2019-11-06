<?php
namespace Smallsale14\Controller;
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
                $this->valid_fields = array('box_mac'=>1002,'uid'=>1002,'type'=>1001,'goods_id'=>1001);
                break;
            case 'inviteQrcode':
                $this->is_verify = 1;
                $this->valid_fields = array('qrinfo'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getBoxQrcode(){
        $box_mac = $this->params['box_mac'];
        $goods_id = $this->params['goods_id'];
        $uid = $this->params['uid'];
        $type = $this->params['type'];//22购物二维码 23销售二维码
        if(!empty($box_mac)){
            $m_box = new \Common\Model\BoxModel();
            $map = array();
            $map['a.mac'] = $box_mac;
            $map['a.state'] = 1;
            $map['a.flag']  = 0;
            $map['d.state'] = 1;
            $map['d.flag']  = 0;
            $box_info = $m_box->getBoxInfo('a.id as box_id', $map);
            if(empty($box_info)){
                $this->to_back(70001);
            }
        }
        $times = getMillisecond();
        switch ($type){
            case 22:
                $scene = 'ag_'.$box_mac.'_'.$type.'_'.$goods_id.'_'.$uid.'_'.$times;
                break;
            case 23:
                $scene = 'ag_'.$box_mac.'_'.$type.'_'.$goods_id.'_'.$uid.'_'.$times;
                break;
            default:
                $scene = 'ag_'.$box_mac.'_'.$type.'_'.$goods_id.'_'.$times;
        }
        $short_urls = C('SHORT_URLS');
        $content = $short_urls['SALE_BOX_QR'].$scene;
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }

    public function inviteQrcode(){
        $qrinfo = $this->params['qrinfo'];
        $short_urls = C('SHORT_URLS');
        $content = $short_urls['SALE_INVITE_QR'].$qrinfo;
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }


}