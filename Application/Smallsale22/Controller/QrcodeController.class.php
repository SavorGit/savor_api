<?php
namespace Smallsale22\Controller;
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
            case 'dishQrcode':
                $this->is_verify = 1;
                $this->valid_fields = array('data_id'=>1001,'type'=>1001,'suid'=>1002,'box_id'=>1002,'taskid'=>1002,'time'=>1002);
                break;
            case 'scancode':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'content'=>1001);
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

    public function dishQrcode(){
        $data_id = $this->params['data_id'];
        $suid = $this->params['suid'];
        $box_id = $this->params['box_id'];
        $taskid = $this->params['taskid'];
        $time = $this->params['time'];
        $type = $this->params['type'];//24菜品商家 25单个菜品 26海报分销售卖商品 27 商城商家 28商城商品大屏购买 43团购商品销售二维码
        if(empty($suid)){
            $suid = 0;
        }
        $short_urls = C('SHORT_URLS');
        switch ($type){
            case 24:
                $code_url = $short_urls['SALE_DISHMERCHANT_QR'];
                $content = $code_url.'dish_'.$data_id.'_'.$type;
                break;
            case 25:
                $code_url = $short_urls['SALE_DISH_QR'];
                $content = $code_url.'dish_'.$data_id.'_'.$type;
                break;
            case 26:
            case 32:
                $code_url = $short_urls['SALE_SHOP_GOODS_QR'];
                $content = $code_url.'shop_'.$data_id.'_'.$type;
                if(!empty($suid)){
                    $content.='_'.$suid;
                }
                break;
            case 27:
                $code_url = $short_urls['SALE_SHOP_MERCHANT_QR'];
                $content = $code_url.'shop_'.$data_id.'_'.$type;
                if(!empty($suid)){
                    $content.='_'.$suid;
                }
                break;
            case 28:
                $code_url = $short_urls['SALE_SHOP_GOODS_QR'];
                $content = $code_url.'shop_'.$data_id.'_'.$type.'_'.$suid.'_'.$box_id;
                break;
            case 43:
                $code_url = $short_urls['SALE_SHOP_GOODS_QR'];
                $box_id = 0;
                $content = $code_url.'shop_'.$data_id.'_'.$type.'_'.$suid.'_'.$box_id.'_'.$taskid.'_'.$time;
                break;
            default:
                $code_url = $short_urls['SALE_DISH_QR'];
                $content = $code_url.'shop_'.$data_id.'_'.$type;
        }

        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        Qrcode::png($content,false,$errorCorrectionLevel, $matrixPointSize, 0);
    }

    public function scancode(){
        $openid = $this->params['openid'];
        $type = $this->params['type']; //1商品核销 2优惠券核销 3售酒抽奖优惠券核销 4实物奖品核销
        $content = $this->params['content'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff('a.openid',$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        switch ($type){
            case 1:
            case 3:
                $key = C('QRCODE_SECRET_KEY');
                $qrcode_id = decrypt_data($content,false,$key);
                $qrcode_id = intval($qrcode_id);
                $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
                $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
                if(empty($res_qrcode)){
                    $errcode_map = array('1'=>93207,'3'=>93208);
                    $this->to_back($errcode_map[$type]);
                }
                break;
            case 2:
                $param_coupon = decrypt_data($content);
                if(!is_array($param_coupon) || $param_coupon['type']!='coupon'){
                    $this->to_back(93206);
                }
                break;
            case 4:
                $param_goods = decrypt_data($content);
                if(!is_array($param_goods) || $param_goods['type']!='goods'){
                    $this->to_back(93212);
                }
                break;
        }
        $res_data = array('type'=>$type,'content'=>$content);
        $this->to_back($res_data);
    }

}