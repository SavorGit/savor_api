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
                break;
            case 'inviteQrcode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
        }
        parent::_init_();
    }

    public function getBoxQrcode(){
        $box_mac = $this->params['box_mac'];
        $goods_id = $this->params['goods_id'];
        $type = $this->params['type'];//22购物二维码
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

    public function inviteQrcode(){
        $openid = $this->params['openid'];
        $m_hotel_invite_code = new \Common\Model\HotelInviteCodeModel();
        $fields = 'id,hotel_id,bind_mobile,openid';
        $where = array('openid'=>$openid,'state'=>1,'flag'=>0);
        $res_invite_code = $m_hotel_invite_code->getOne($fields,$where);
        if($res_invite_code['type']!=2){
            $this->to_back(93001);
        }

        $cache_key = C('SAPP_SALE_INVITE_QRCODE');
        $uniq_id = uniqid('',true);
        $invite_cache_key = $res_invite_code['id'].'&'.$uniq_id;
        $code_key = $cache_key.$res_invite_code['id'].":$invite_cache_key";

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $redis->set($code_key,$res_invite_code['id'],300);
        $encode_key = encrypt_data($invite_cache_key);

        $content ="http://rd0.cn/sale?p=$encode_key";
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }


}