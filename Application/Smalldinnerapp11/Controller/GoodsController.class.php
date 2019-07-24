<?php
namespace Smalldinnerapp11\Controller;
use \Common\Controller\CommonController as CommonController;

class GoodsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getGoodslist':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'type'=>1001,'hotel_id'=>1001,'openid'=>1001);
                break;
            case 'addActivityGoods':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'oss_addr'=>1001,'price'=>1001,
                    'start_time'=>1001,'end_time'=>1001,'scope'=>1001,'gid'=>1002);
                break;

        }
        parent::_init_();
    }

    public function getGoodslist(){
        $openid = $this->params['openid'];
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 4;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 15;
        $all_nums = $page * $pagesize;
        $type = $this->params['type'];//10官方活动促销,20我的活动
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'g.id as goods_id,g.name,g.img_addr,g.video_addr,g.price,g.rebate_integral,g.jd_url,g.resource_type';
        if($type==20){
            $fields .= ' ,g.start_time,g.end_time,g.scope,g.status';
        }
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>$type);
        $where['g.status'] = array('in',array(1,2,3));
//        if($type==20){
//            $where['h.openid'] = $openid;
//        }
        $orderby = 'g.id desc';
        $limit = "0,$all_nums";
        $res_goods = $m_hotelgoods->getList($fields,$where,$orderby,$limit);
        $datalist = $res_goods;
        $oss_host = 'http://'.C('OSS_HOST').'/';
        foreach ($datalist as $k=>$v){
            $datalist[$k]['img_addr'] = $v['img_addr'];
            if(!empty($v['img_addr'])){
                $datalist[$k]['img_addrurl'] = $oss_host.$v['img_addr'];
            }else{
                $datalist[$k]['img_addrurl'] = '';
            }

            $datalist[$k]['video_addr'] = $v['video_addr'];
            if(!empty($v['video_addr'])){
                $datalist[$k]['video_addrurl'] = $oss_host.$v['video_addr'];
            }else{
                $datalist[$k]['video_addrurl'] = '';
            }

        }
        $data = array('datalist'=>$datalist);
        $this->to_back($data);
    }

    public function addActivityGoods(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $oss_addr = $this->params['oss_addr'];
        $price = $this->params['price'];
        $start_time = $this->params['start_time'];
        $end_time = $this->params['end_time'];
        $scope = intval($this->params['scope']);//0全部,1包间,2非包间
        $gid = intval($this->params['gid']);

        $tempInfo = pathinfo($oss_addr);
        $surfix = $tempInfo['extension'];
        if($surfix){
            $surfix = strtolower($surfix);
        }
        $typeinfo = C('RESOURCE_TYPEINFO');
        if(isset($typeinfo[$surfix])){
            $type = $typeinfo[$surfix];
        }else{
            $type = 0;
        }
        $tmp_start_time = strtotime($start_time);
        $tmp_end_time = strtotime($end_time);
        if($tmp_start_time>$tmp_end_time){
            $this->to_back(92012);
        }
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 4;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $res_hotelgoods = $m_hotelgoods->getInfo(array('hotel_id'=>$hotel_id,'openid'=>$openid));
        if(!empty($res_hotelgoods)){
            if(!$gid || ($gid && $res_hotelgoods['goods_id']!=$gid)){
                $this->to_back(92013);
            }
        }
        $data = array('resource_type'=>$type,'price'=>$price,'type'=>20,
            'start_time'=>date('Y-m-d 00:00:00',$tmp_start_time),
            'end_time'=>date('Y-m-d 23:59:59',$tmp_end_time),'scope'=>$scope,
            'status'=>1
        );
        if($type==1){
            $data['video_addr'] = $oss_addr;
        }
        if($type==2){
            $data['img_addr'] = $oss_addr;
        }

        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        if($gid){
            $m_goods->updateData(array('id'=>$gid),$data);
        }else{
            $gid = $m_goods->addData($data);
            $hotelgoods_data = array('hotel_id'=>$hotel_id,'openid'=>$openid,'goods_id'=>$gid);
            $m_hotelgoods->addData($hotelgoods_data);
        }
        $res_data = array('gid'=>intval($gid),'resource_type'=>$type);
        $this->to_back($res_data);
    }



}