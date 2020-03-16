<?php
namespace Smallsale18\Controller;
use \Common\Controller\CommonController as CommonController;

class OrderController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'dishOrderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'status'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'dishorderProcess':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
            case 'dishOrderdetail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'order_id'=>1001);
                break;
        }
        parent::_init_();
    }


    public function dishOrderlist(){
        $openid = $this->params['openid'];
        $status = intval($this->params['status']);
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        $type = isset($this->params['type'])?intval($this->params['type']):0;//类型0 全部 1普通订单 2分销订单 3代理人订单
        if(empty($pagesize)){
            $pagesize =10;
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        if($type && $type==3){
            $where = array('openid'=>$openid,'type'=>2);
        }else{
            $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
            $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
            if(empty($res_staff)){
                $this->to_back(93001);
            }
            $merchant_id = $res_staff[0]['merchant_id'];
            $where = array('merchant_id'=>$merchant_id);
            if($type){
                $where['type'] = $type;
            }
        }

        if($status){
            $where['status'] = $status;
        }
        $all_nums = $page * $pagesize;
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $fields = 'id as order_id,merchant_id,openid,price,amount,total_fee,status,contact,phone,address,delivery_time,remark,add_time,finish_time';
        $res_order = $m_dishorder->getDataList($fields,$where,'id desc',0,$all_nums);
        $datalist = array();
        if($res_order['total']){
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_merchant = new \Common\Model\Integral\MerchantModel();
            $m_media = new \Common\Model\MediaModel();
            $datalist = $res_order['list'];
            $oss_host = "http://".C('OSS_HOST').'/';
            foreach($datalist as $k=>$v){
                $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));
                if($v['finish_time']=='0000-00-00 00:00:00'){
                    $datalist[$k]['finish_time'] = '';
                }
                $order_id = $v['order_id'];
                $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
                $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                $goods = array();
                foreach ($res_goods as $gv){
                    $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['goods_name'],'price'=>$gv['price'],'amount'=>$gv['amount'],
                        'status'=>$gv['status']);
                    $cover_imgs_info = explode(',',$gv['cover_imgs']);
                    $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                    $goods[]=$ginfo;
                }

                $where = array('m.id'=>$v['merchant_id']);
                $fields = 'm.id,hotel.name,ext.hotel_cover_media_id';
                $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
                $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$v['merchant_id']);
                $merchant['img'] = '';
                if(!empty($res_merchant[0]['hotel_cover_media_id'])){
                    $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
                    $merchant['img'] = $res_media['oss_addr'];
                }

                $datalist[$k]['merchant'] = $merchant;
                $datalist[$k]['goods'] = $goods;
                $datalist[$k]['goods_id']=$goods[0]['id'];
                $datalist[$k]['goods_name']=$goods[0]['name'];
                $datalist[$k]['goods_img'] = $goods[0]['img'];
                $datalist[$k]['price'] = $v['total_fee'];
            }
        }
        $res_data = array('datalist'=>$datalist);
        $this->to_back($res_data);
    }

    public function dishOrderdetail(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $res_order = $m_dishorder->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['merchant_id']!=$merchant_id){
            $this->to_back(93036);
        }
        $res_order['order_id'] = $order_id;
        unset($res_order['id'],$res_order['openid'],$res_order['staff_id'],$res_order['dishgoods_id'],$res_order['price'],$res_order['pay_type']);

        $oss_host = "http://".C('OSS_HOST').'/';
        $res_order['add_time'] = date('Y-m-d H:i',strtotime($res_order['add_time']));
        if($res_order['finish_time']=='0000-00-00 00:00:00'){
            $res_order['finish_time'] = '';
        }
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
        $goods = array();
        foreach ($res_goods as $gv){
            $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['goods_name'],'price'=>$gv['price'],'amount'=>$gv['amount'],
                'status'=>$gv['status']);
            $cover_imgs_info = explode(',',$gv['cover_imgs']);
            $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
            $goods[]=$ginfo;
        }
        $res_order['goods'] = $goods;

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $m_media = new \Common\Model\MediaModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id,hotel.name,ext.hotel_cover_media_id';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $merchant = array('name'=>$res_merchant[0]['name'],'merchant_id'=>$res_order['merchant_id']);
        $merchant['img'] = '';
        if(!empty($res_merchant[0]['hotel_cover_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_merchant[0]['hotel_cover_media_id']);
            $merchant['img'] = $res_media['oss_addr'];
        }
        $res_order['merchant'] = $merchant;
        $this->to_back($res_order);
        $this->to_back($res_order);
    }

    public function dishorderProcess(){
        $openid = $this->params['openid'];
        $order_id = intval($this->params['order_id']);

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $res_order = $m_dishorder->getInfo(array('id'=>$order_id));
        if(empty($res_order) || $res_order['merchant_id']!=$merchant_id){
            $this->to_back(93036);
        }
        $m_dishorder->updateData(array('id'=>$order_id),array('status'=>2,'finish_time'=>date('Y-m-d H:i:s')));
        $this->to_back(array());
    }

}