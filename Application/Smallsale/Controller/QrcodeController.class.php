<?php
namespace Smallsale\Controller;
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
                $this->valid_fields = array('box_mac'=>1001,'type'=>1001,'goods_id'=>1001);
        }
        parent::_init_();
    }

    public function getBoxQrcode(){
        $box_mac = $this->params['box_mac'];
        $goods_id = $this->params['goods_id'];
        $type = $this->params['type'];//1购物二维码
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
        $scene = 'ag_'.$box_mac.'_'.$type.'_'.$goods_id;
        $content ="http://rd0.cn/ag?g=$scene";
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }
}