<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class CouponController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'banner':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'receive':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'coupon_id'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'coupon_user_id'=>1001);
                break;
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'status'=>1001);
                break;
        }
        parent::_init_();
    }

    public function banner(){
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_coupon = new \Common\Model\Smallapp\CouponModel();
        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
        $nowtime = date('Y-m-d H:i:s');
        $where = array('status'=>1);
        $where['start_time'] = array('elt',$nowtime);
        $where['end_time'] = array('egt',$nowtime);
        $res_coupon = $m_coupon->getDataList('*',$where,'id desc');
        $datalist = array();
        if(!empty($res_coupon)){
            foreach ($res_coupon as $v){
                $status = 1;//立即领取
                $res_coupon_user = $m_coupon_user->getInfo(array('openid'=>$openid,'coupon_id'=>$v['id']));
                if(!empty($res_coupon_user) && $res_coupon_user['ustatus']==1){
                    $status = 2;
                }
                $info = array('coupon_id'=>$v['id'],'name'=>$v['name'],'remark'=>$v['remark'],'status'=>$status);
                $datalist[]=$info;
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function receive(){
        $openid = $this->params['openid'];
        $coupon_id = intval($this->params['coupon_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_coupon = new \Common\Model\Smallapp\CouponModel();
        $coupon_info = $m_coupon->getInfo(array('id'=>$coupon_id));

        $add_data = array('openid'=>$openid,'coupon_id'=>$coupon_id,'money'=>$coupon_info['money'],
            'min_price'=>$coupon_info['min_price'],'max_price'=>$coupon_info['max_price'],'ustatus'=>1
        );

        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
        $res_coupon_user = $m_coupon_user->getInfo(array('openid'=>$openid,'coupon_id'=>$coupon_id));
        if(empty($res_coupon_user)){
            $m_coupon_user->add($add_data);
        }else{
            if($res_coupon_user['ustatus']==2){
                $m_coupon_user->add($add_data);
            }
        }
        $this->to_back(array());
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);
        $ustatus = intval($this->params['status']);//1待使用 2已使用 3已过期


        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }

        $where = array('a.openid'=>$openid,'coupon.type'=>$type);
        if($ustatus){
            $where['a.ustatus'] = $ustatus;
        }
        $fields = 'a.*,coupon.use_range,coupon.range_finance_goods_ids';
        $m_coupon_user = new \Common\Model\Smallapp\UserCouponModel();
        $res_coupon = $m_coupon_user->getUsercouponDatas($fields,$where,'a.id desc','');
        $unused = $used = $expired = array();
        if(!empty($res_coupon)){
            $m_hotel = new \Common\Model\HotelModel();
            $nowtime = date('Y-m-d H:i:s');
            foreach ($res_coupon as $v){
                $res_hotel = $m_hotel->getInfoById($v['hotel_id'],'name');
                $expire_time = date('Y.m.d H:i',strtotime($v['end_time']));
                $start_time = date('Y.m.d H:i',strtotime($v['start_time']));
                if($v['min_price']>0){
                    $min_price = "满{$v['min_price']}可用";
                }else{
                    $min_price = '无门槛立减券';
                }
                $range_goods = array();
                if($v['use_range']==1){
                    $range_str = '全部活动酒水';
                }else{
                    $range_str = '部分活动酒水';
                    $range_finance_goods_ids = explode(',',trim($v['range_finance_goods_ids'],','));
                    $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                    $res_data = $m_hotelgoods->getStockGoodsList($v['hotel_id'],0,1000);
                    if(!empty($res_data)){
                        foreach ($res_data as $gv){
                            if(in_array($gv['finance_goods_id'],$range_finance_goods_ids)){
                                $range_goods[]=$gv['name'];
                            }
                        }
                    }
                }
                $info = array('coupon_user_id'=>$v['id'],'money'=>$v['money'],'min_price'=>$min_price,'expire_time'=>"有效期至{$expire_time}",
                    'hotel_name'=>$res_hotel['name'],'range_str'=>$range_str,'range_goods'=>$range_goods,'start_time'=>$start_time,'end_time'=>$expire_time
                );

                switch ($v['ustatus']){
                    case 1:
                        if($nowtime>$v['end_time']){
                            $expired[]=$info;
                            $m_coupon_user->updateData(array('id'=>$v['id']),array('ustatus'=>3));
                        }else{
                            $unused[]=$info;
                        }
                        break;
                    case 2:
                        $used[]=$info;
                        break;
                    case 3:
                        $expired[]=$info;
                        break;
                }
            }
        }
        $res_data = array('unused'=>$unused,'used'=>$used,'expired'=>$expired);
        $this->to_back($res_data);
    }

    public function detail(){
        $openid = $this->params['openid'];
        $coupon_id = intval($this->params['coupon_user_id']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'status'=>1);
        $user_info = $m_user->getOne('id,openid,mpopenid',$where,'');
        if(empty($user_info)){
            $this->to_back(90157);
        }
        $m_coupon = new \Common\Model\Smallapp\UserCouponModel();
        $where = array('a.id'=>$coupon_id);
        $fields = 'a.*,coupon.use_range,coupon.range_finance_goods_ids';
        $res_coupon = $m_coupon->getUsercouponDatas($fields,$where,'a.id desc','');
        $res_data = array();
        if(!empty($res_coupon)){
            $coupon_info = $res_coupon[0];
            $range_goods = array();
            if($coupon_info['use_range']==1){
                $range_str = '全部活动酒水';
            }else{
                $range_str = '部分活动酒水';
                $range_finance_goods_ids = explode(',',trim($coupon_info['range_finance_goods_ids'],','));
                $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
                $res_data = $m_hotelgoods->getStockGoodsList($coupon_info['hotel_id'],0,1000);
                if(!empty($res_data)){
                    foreach ($res_data as $v){
                        if(in_array($v['finance_goods_id'],$range_finance_goods_ids)){
                            $range_goods[]=$v['name'];
                        }
                    }
                }
            }
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getInfoById($coupon_info['hotel_id'],'name');
            $host_name = 'https://'.$_SERVER['HTTP_HOST'];

            $en_data = array('type'=>'coupon','id'=>$coupon_id);
            $data_id = encrypt_data(json_encode($en_data));
            $qrcode_url = $host_name."/smallapp46/qrcode/getCouponQrcode?data_id={$data_id}";
            if($coupon_info['min_price']>0){
                $min_price = "满{$coupon_info['min_price']}可用";
            }else{
                $min_price = '无门槛立减券';
            }
            $start_time = date('Y.m.d H:i',strtotime($coupon_info['start_time']));
            $end_time = date('Y.m.d H:i',strtotime($coupon_info['end_time']));
            $start_time = "有效期：{$start_time}";
            $end_time = "至{$end_time}";

            $res_data = array('openid'=>$openid,'coupon_id'=>$coupon_id,'money'=>$coupon_info['money'],'use_range'=>$coupon_info['use_range'],
                'range_str'=>$range_str,'min_price'=>$min_price,'start_time'=>$start_time,'end_time'=>$end_time,
                'range_goods'=>$range_goods,'hotel_name'=>$res_hotel['name'],'qrcode_url'=>$qrcode_url
            );
        }
        $this->to_back($res_data);
    }


}