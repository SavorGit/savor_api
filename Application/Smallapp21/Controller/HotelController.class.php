<?php
namespace Smallapp21\Controller;
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
                $this->valid_fields = array('page'=>1001,'area_id'=>1001,
                                            'count_id'=>1000,'food_style_id'=>1000,
                                            'avg_exp_id'=>1000
                );
                break;
            case 'getExplist':
                $this->is_verify = 0;
                break;
        }
        $this->avg_exp_arr = array('agv_name'=>array('人均价格','100以下','100-200','200以上'),
                                    'agv_lisg'=>array(array('id'=>0,'name'=>'人均价格'),array('id'=>1,'name'=>'100以下'),
                                                      array('id'=>2,'name'=>'100-200'),array('id'=>3,'name'=>'200以上')
                                    ));
        parent::_init_();
    }
    /**
     * @desc 推荐餐厅列表
     */
    public function recList(){
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $hotel_box_type_arr = C('HEART_HOTEL_BOX_TYPE');
        $hotel_box_type_arr = array_keys($hotel_box_type_arr);
        
        $page     = $this->params['page'] ? $this->params['page'] :1;
        $area_id  = $this->params['area_id'] ? $this->params['area_id'] :1;
        $county_id = $this->params['county_id'];
        $food_style_id = $this->params['food_style_id'];
        $avg_id   = $this->params['avg_exp_id'];
        
        $pagesize = 10;
        
        $offset = $page * $pagesize;
        
        $m_hotel = new \Common\Model\HotelModel();
        $fields = "a.id hotel_id,a.media_id,a.name,a.addr,a.tel,b.food_style_id,
                   b.avg_expense,concat('".$oss_host."',c.`oss_addr`) as img_url,
                   d.name food_name";
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
        $where['a.id'] = array('not in','7,482,504,791,508,844,845,597');
        $order = " a.id asc";
        $limit = " 0 ,".$offset;
        
        $hotel_list = $m_hotel->alias('a')
                ->join('savor_hotel_ext b on a.id=b.hotel_id','left')
                ->join('savor_media c on b.hotel_cover_media_id=c.id','left')
                ->join('savor_hotel_food_style d on b.food_style_id=d.id','left')
                ->field($fields)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
        foreach($hotel_list as $key=>$v){
            if(empty($v['food_name'])){
                $hotel_list[$key]['food_name'] = '';
            }
            $hotel_list[$key]['img_url'] = $v['img_url'].'?x-oss-process=image/resize,p_20';
        }
        $this->to_back($hotel_list);
        
        
    }
    public function getExplist(){
        $data = $this->avg_exp_arr;
        $this->to_back($data);
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
        }
        return $where;
        
    }
}