<?php
namespace Smallsale23\Controller;
use Common\Lib\Smallapp_api;
use \Common\Controller\CommonController as CommonController;

class WinecodeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'scanCode':
                $this->params = array('openid'=>1001,'idcode'=>1001);
                $this->is_verify = 1;
                break;
            case 'getImageCode':
                $this->params = array('openid'=>1001,'img_url'=>1001);
                $this->is_verify = 1;
                break;
            case 'association':
                $this->params = array('openid'=>1001,'goods_id'=>1001,'idcode'=>1001,'winecode'=>1001,'image'=>1002);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function scanCode(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $m_stock_record = new \Common\Model\Finance\StockRecordModel();
        $fileds = 'a.idcode,goods.id as goods_id,goods.name as goods_name,goods.link_type,cate.name as cate_name,spec.name as spec_name,unit.name as unit_name,unit.convert_type';
        $where = array('a.idcode'=>$idcode,'a.dstatus'=>1);
        $res_records = $m_stock_record->getStockRecordList($fileds,$where,'a.id desc','0,1');
        $goods_info = array();
        if(!empty($res_records[0]['idcode'])){
            if($res_records[0]['convert_type']!=1){
                $this->to_back(93115);
            }
            $goods_info = $res_records[0];
        }
        $this->to_back($goods_info);
    }


    public function getImageCode(){
        $openid = $this->params['openid'];
        $img_url = $this->params['img_url'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $ali_ocr = new \Common\Lib\AliyunOCR();
        $oss_host = get_oss_host('http');
        $res_ocr = $ali_ocr->RecognizeBasic($oss_host.$img_url);
        $res_data = json_decode($res_ocr['Data'],true);
        $code = '';
        if(!empty($res_data['prism_wordsInfo'])){
            $first_num = 0;
            $end_num = 0;
            foreach ($res_data['prism_wordsInfo'] as $k=>$v){
                $position6 = strpos($v['word'], "06");
                $position7 = strpos($v['word'], "07");
                $is_first = 0;
                if($position6!==false && $position6==0){
                    $is_first = 1;
                }elseif($position7!==false && $position7==0){
                    $is_first = 1;
                }
                if($is_first==1){
                    $first_num = $k;
                    $code.=$v['word'];
                }else{
                    if(!empty($code)){
                        $code.=$v['word'];
                    }
                }
                $end_words = "XXXX";
                $position = strpos($v['word'], $end_words);
                if($position!==false){
                    $end_num = $k;
                    break;
                }
            }
        }
        $this->to_back(array('winecode'=>$code,'image'=>$img_url));
    }

    public function association(){
        $openid = $this->params['openid'];
        $idcode = $this->params['idcode'];
        $winecode = $this->params['winecode'];
        $image = $this->params['image'];
        $goods_id = intval($this->params['goods_id']);

        $key = C('QRCODE_SECRET_KEY');
        $qrcode_id = decrypt_data($idcode,false,$key);
        $qrcode_id = intval($qrcode_id);
        $m_qrcode_content = new \Common\Model\Finance\QrcodeContentModel();
        $res_qrcode = $m_qrcode_content->getInfo(array('id'=>$qrcode_id));
        if(empty($res_qrcode)){
            $this->to_back(93080);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }

        $m_finnace_winecode = new \Common\Model\Finance\WinecodeModel();
        $res_data = $m_finnace_winecode->getInfo(array('winecode'=>$winecode));
        if(!empty($res_data)){
            $this->to_back(93113);
        }
        $res_data = $m_finnace_winecode->getInfo(array('idcode'=>$idcode));
        if(!empty($res_data)){
            $this->to_back(93114);
        }
        $add_data = array('goods_id'=>$goods_id,'idcode'=>$idcode,'winecode'=>$winecode);
        if(!empty($image)){
            $add_data['image'] = $image;
        }
        $id = $m_finnace_winecode->add($add_data);
        $this->to_back(array('id'=>$id));
    }

}
