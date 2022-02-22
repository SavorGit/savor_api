<?php
namespace Smallapp46\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class HotelController extends CommonController{
    var $avg_exp_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recList':
                $this->is_verify =1;
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,'count_id'=>1002,
                    'food_style_id'=>1002,'avg_exp_id'=>1002,'latitude'=>1002,'longitude'=>1002
                );
                break;
            case 'getExplist':
                $this->is_verify = 0;
                break;
            case 'hotdrinksHotels':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
        }
        $this->avg_exp_arr = array(
            'agv_name'=>array('人均价格','100以下','100-200','200以上'),
            'agv_lisg'=>array(
                array('id'=>0,'name'=>'人均价格'),
                array('id'=>1,'name'=>'100以下'),
                array('id'=>2,'name'=>'100-200'),
                array('id'=>3,'name'=>'200以上')
            )
        );
        parent::_init_();
    }

    public function recList(){
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
        $hotel_box_type_arr = array_keys($hotel_box_type_arr);
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        $latitude = $this->params['latitude'];
        $longitude = $this->params['longitude'];
        $pagesize = 10;
        $m_hotel = new \Common\Model\HotelModel();
        $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name,a.gps";
        $where = array();
        if($area_id){
            $where['a.area_id'] = $area_id;
        }
        if($county_id){
            $where['a.county_id'] = $county_id;
        }
        if($food_style_id){
            $where['b.food_style_id'] = $food_style_id;
        }
        if($avg_id){
            $where['avg_expense'] = $this->getAvgWhere($avg_id);
        }
        $where['a.state'] = 1;
        $where['a.flag']  = 0;
        $where['a.hotel_box_type'] = array('in',$hotel_box_type_arr);
        $where['a.id'] = array('not in','7,482,504,791,508,844,845,597,201,493,883,53');
        $order = " a.id asc";
        $offset = $page * $pagesize;
        $limit = " 0 ,".$offset;
        $hotel_list = $m_hotel->alias('a')
            ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
            ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
            ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
            ->field($fields)
            ->where($where)
            ->order()
            ->limit()
            ->select();
        $bd_lnglat = array();
        if($longitude>0 && $latitude>0 ) {
            $bd_lnglat = getgeoByTc($latitude, $longitude);
        }
        foreach($hotel_list as $key=>$v){
            $sql ="select id from savor_integral_merchant where hotel_id=".$v['hotel_id']." and status=1";
            $merchant_info = M()->query($sql);
            if(!empty($merchant_info)){
                $merchant_info = $merchant_info[0];
                $hotel_list[$key]['merchant_id'] = $merchant_info['id'];
            }else {
                $hotel_list[$key]['merchant_id'] = 0;
            }
            if(empty($v['food_name'])){
                $hotel_list[$key]['food_name'] = '';
            }
            if($v['img_url']){
                $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
                $hotel_list[$key]['ori_img_url'] = $v['img_url'];
            }else {
                $hotel_list[$key]['img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                $hotel_list[$key]['ori_img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
            }

            $hotel_list[$key]['dis'] = '';
            if($v['gps']!='' && $longitude>0 && $latitude>0){
                $latitude = $bd_lnglat[0]['y'];
                $longitude = $bd_lnglat[0]['x'];

                $gps_arr = explode(',',$v['gps']);
                $dis = geo_distance($latitude,$longitude,$gps_arr[1],$gps_arr[0]);
                $hotel_list[$key]['dis_com'] = $dis;
                if($dis>1000){
                    $tmp_dis = $dis/1000;
                    $dis = sprintf('%0.2f',$tmp_dis);
                    $dis = $dis.'km';
                }else{
                    $dis = intval($dis);
                    $dis = $dis.'m';
                }
                $hotel_list[$key]['dis'] = $dis;
            }else {
                $hotel_list[$key]['dis'] = '';
            }
        }
        sortArrByOneField($hotel_list,'dis_com');
        $hotel_list = array_slice($hotel_list,0,$offset);
        $this->to_back($hotel_list);
    }

    public function getExplist(){
        $data = $this->avg_exp_arr;
        $this->to_back($data);
    }

    public function hotdrinksHotels(){
        $box_mac = $this->params['box_mac'];
        $page = intval($this->params['page']);
        $pagesize = isset($this->params['pagesize'])?intval($this->params['pagesize']):10;
        $start = ($page-1)*$pagesize;

        $m_box = new \Common\Model\BoxModel();
        $where = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $fields = "box.id as box_id,hotel.id as hotel_id,hotel.area_id";
        $box_info = $m_box->getBoxByCondition($fields,$where);
        $area_id = $box_info[0]['area_id'];

        $m_hotelgoods = new \Common\Model\Smallapp\HotelgoodsModel();
        $fields = 'h.hotel_id';
        $where = array('g.type'=>array('in',array(40,43)),'g.status'=>1);
        $where['h.hotel_id'] = array('not in','7,482,504,791,508,844,845,597,201,493,883,53,925');
        $res_ghotels = $m_hotelgoods->getGoodsList($fields,$where,'','','h.hotel_id');
        $hotel_ids = array();
        foreach ($res_ghotels as $v){
            $hotel_ids[]=$v['hotel_id'];
        }
        $datalist = array();
        if(!empty($hotel_ids)){
            $oss_host = 'http://'. C('OSS_HOST').'/';
            $m_hotel = new \Common\Model\HotelModel();
            $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name,a.gps";
            $where = array('a.area_id'=>$area_id,'a.state'=>1,'a.flag'=>0);
            $where['a.id'] = array('in',$hotel_ids);
            $order = " a.pinyin asc";
            $hotel_list = $m_hotel->alias('a')
                ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
                ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
                ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->limit($start,$pagesize)
                ->select();

            foreach($hotel_list as $key=>$v){
                if(empty($v['food_name'])){
                    $hotel_list[$key]['food_name'] = '';
                }
                if($v['img_url']){
                    $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
                    $hotel_list[$key]['ori_img_url'] = $v['img_url'];
                }else {
                    $hotel_list[$key]['img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                    $hotel_list[$key]['ori_img_url'] = 'http://oss.littlehotspot.com/media/resource/kS3MPQBs7Y.png';
                }
            }
            $datalist = $hotel_list;
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    private function getAvgWhere($avg_id){
        switch ($avg_id){
            case 1:
                $where = array('LT',100);
                break;
            case 2:
                $where = array(array('EGT',100),array('ELT',200));
                break;
            case 3:
                $where = array('GT',200);
                break;
            default:
                $where = array();
        }
        return $where;
        
    }
}