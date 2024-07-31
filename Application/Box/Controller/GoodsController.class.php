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
        $box_info = $m_box->getBoxInfo('a.id as box_id,d.id as hotel_id,d.short_name,d.area_id', $map);
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info[0]['hotel_id'];
        $short_name = $box_info[0]['short_name'];
        $area_id = $box_info[0]['area_id'];

        $version = isset($_SERVER['HTTP_X_VERSION'])?$_SERVER['HTTP_X_VERSION']:'';
        $is_olddata=1;
        if($version>2022033101){
            $is_olddata=0;
        }

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
		$seckill_goods_config = C('SECKILL_GOODS_CONFIG');

        $roll_content = array();
        $datalist = array();

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(9);
        $key = C('FINANCE_HOTELSTOCK').':'.$hotel_id;
        $res_cache = $redis->get($key);
        $hotel_stock = array();
        if(!empty($res_cache)) {
            $hotel_stock = json_decode($res_cache, true);
        }

        $res_goods = array();
        if(!empty($hotel_stock['goods_ids'])){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $fields = 'id as goods_id,name as goods_name,model_media_id,price,line_price,finance_goods_id,end_time,is_seckill,
            start_time,end_time';
            $where = array('type'=>43,'status'=>1,'finance_goods_id'=>array('in',$hotel_stock['goods_ids']));
            $res_goods = $m_goods->getDataList($fields,$where,'id desc');

        }
        $nowtime = date('Y-m-d H:i:s');
        if(!empty($res_goods)){
            $m_config = new \Common\Model\SysConfigModel();
            $all_config = $m_config->getAllconfig();
            $roll_content = json_decode($all_config['seckill_roll_content'],true);
            $goods_info = array();
            $m_goods_price_hotel = new \Common\Model\Smallapp\GoodsPriceHotelModel();
            foreach ($res_goods as $v){
                $goods_id = $v['goods_id'];
                $res_price = $m_goods_price_hotel->getGoodsPrice($goods_id,$area_id,$hotel_id);
                $price = intval($res_price['price']);
                $line_price = intval($res_price['line_price']);

                $goods_info[]="{$v['goods_name']}({$price}元)";

                if($v['is_seckill']==1 && $v['end_time']>=$nowtime){
                    $end_time = strtotime($v['end_time']);
                    $now_time = time();
                    $remain_time = $end_time-$now_time>0?$end_time-$now_time:0;
                    $m_media = new \Common\Model\MediaModel();
                    $res_media = $m_media->getMediaInfoById($v['model_media_id']);
                    $info = array('goods_id'=>$goods_id,'image'=>$res_media['oss_path'],'price'=>$price,
                        'line_price'=>$line_price,'remain_time'=>intval($remain_time),'hotel_name'=>$short_name
                    );
                    $datalist[]=$info;
                }
            }
            if(!empty($goods_info)){
                $goods_info = array_unique($goods_info);
                if(count($goods_info)>1){
                    $goods_content = join("、",$goods_info);
                }else{
                    $goods_content = $goods_info[0]."                                                                                   ";
                }
                $roll_content[]=$goods_content;
            }else{
                $roll_content = array();
            }
        }
        $res_data = array('datalist'=>$datalist,'left_pop_wind'=>$seckill_goods_config['left_pop_wind'],'marquee'=>$seckill_goods_config['marquee'],
            'roll_content'=>$roll_content);
        $this->to_back($res_data);
    }
	
}