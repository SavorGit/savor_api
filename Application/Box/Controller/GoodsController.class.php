<?php
namespace Box\Controller;
use \Common\Controller\CommonController as CommonController;
class GoodsController extends CommonController{


    function _init_() {
        switch(ACTION_NAME) {
            case 'getSeckillGoods':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;

        }
        parent::_init_();
    }

    public function getSeckillGoods(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];

        $version = isset($_SERVER['HTTP_X_VERSION'])?$_SERVER['HTTP_X_VERSION']:'';
        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $roll_content = array();
        if($version>2022030203){
            $roll_content = array();
            $nowtime = date('Y-m-d H:i:s');
            $fields = 'g.id as goods_id,g.model_media_id,g.price,g.line_price,g.roll_content,g.end_time';
            $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.is_seckill'=>1,'g.status'=>1);
            $where['g.start_time'] = array('elt',$nowtime);
            $where['g.end_time'] = array('egt',$nowtime);
            $res_goods = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','');
            $datalist = array();
            if(!empty($res_goods)){
                $m_config = new \Common\Model\SysConfigModel();
                $all_config = $m_config->getAllconfig();
                $roll_content = json_decode($all_config['seckill_roll_content'],true);
                
                foreach ($res_goods as $v){
                    $goods_id = $v['goods_id'];
                    $end_time = strtotime($v['end_time']);
                    $now_time = time();
                    $remain_time = $end_time-$now_time>0?$end_time-$now_time:0;
                    if($remain_time==0){
                        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
                        $m_goods->updateData(array('id'=>$goods_id),array('status'=>2,'flag'=>3));
                    }
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($v['model_media_id']);
                    $info = array('goods_id'=>$goods_id,'image'=>$res_media['oss_path'],'price'=>intval($v['price']),
                        'line_price'=>intval($v['line_price']),'remain_time'=>intval($remain_time)
                    );
                    $datalist[]=$info;
                }
            }
            $res_data = array('datalist'=>$datalist);
        }else{
            $nowtime = date('Y-m-d H:i:s');
            $fields = 'g.id as goods_id,g.model_media_id,g.price,g.line_price,g.roll_content,g.end_time';
            $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.is_seckill'=>1,'g.status'=>1);
            $where['g.start_time'] = array('elt',$nowtime);
            $where['g.end_time'] = array('egt',$nowtime);
            $res_goods = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','0,1');
            $res_data = array('remain_time'=>0);
            $goods_id = 0;
            if(!empty($res_goods)){
                $goods_id = $res_goods[0]['goods_id'];
                $roll_content = json_decode($res_goods[0]['roll_content'],true);

                $end_time = strtotime($res_goods[0]['end_time']);
                $now_time = time();
                $remain_time = $end_time-$now_time>0?$end_time-$now_time:0;
                if($remain_time==0){
                    $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
                    $m_goods->updateData(array('id'=>$goods_id),array('status'=>2,'flag'=>3));
                }
                $m_media = new \Common\Model\MediaModel();
                $res_media = $m_media->getMediaInfoById($res_goods[0]['model_media_id']);
                $res_data['goods_id'] = $goods_id;
                $res_data['image'] = $res_media['oss_path'];
                $res_data['price'] = intval($res_goods[0]['price']);
                $res_data['line_price'] = intval($res_goods[0]['line_price']);
                $res_data['remain_time'] = intval($remain_time);
            }
        }
        $fields = 'g.id as goods_id,g.name as goods_name,g.price';
        $where = array('h.hotel_id'=>$hotel_id,'g.type'=>43,'g.status'=>1);
        $res_allgoods = $m_hotelgoods->getGoodsList($fields,$where,'g.id desc','');
        if(!empty($res_allgoods)){
            $goods_info = array();
            foreach ($res_allgoods as $v){
                $price = intval($v['price']);
                $goods_info[]="{$v['goods_name']}({$price}元)";
            }
            $goods_info = array_unique($goods_info);
            $goods_content = join('、',$goods_info);
            $roll_content[]="本店有售：".$goods_content.'，更多活动，扫码获取。';
        }
        $res_data['roll_content'] = $roll_content;
        $this->to_back($res_data);
    }

}