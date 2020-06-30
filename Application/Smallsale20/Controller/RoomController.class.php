<?php
namespace Smallsale20\Controller;
use \Common\Controller\CommonController as CommonController;

class RoomController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getRoomList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'box_mac'=>1001);
                break;
            case 'getWelcomeBoxlist':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getRoomList(){
        $m_box = new \Common\Model\BoxModel();
        $hotel_id = $this->params['hotel_id'];
        $box_mac  = $this->params['box_mac'];
        $fields = 'a.id,a.mac box_mac,a.name box_name ';
        $where  = array('d.id'=>$hotel_id,'d.state'=>1,'d.flag'=>0,
            'a.state'=>1,'a.flag'=>0);
        $order = 'a.id asc';
        $list = $m_box->alias('a')
                      ->join('savor_room c on a.room_id= c.id','left')
                      ->join('savor_hotel d on c.hotel_id=d.id','left')
                      ->field($fields)
                      ->where($where)
                      ->order($order)
                      ->select();
        $box_list = $box_name_list = array();
        $box_index = 0; 
        foreach($list as $key=>$v){
            $box_list[] = $v;
            $box_name_list[] = $v['box_name'];
            if($v['box_mac']==$box_mac){
                $box_index = $key;
            }        
        }
        $data['box_list'] = $box_list;
        $data['box_name_list'] = $box_name_list;
        $data['box_index']= $box_index;
        $this->to_back($data);
    }

    public function getWelcomeBoxlist(){
        $hotel_id = intval($this->params['hotel_id']);

        $m_welcome = new \Common\Model\Smallapp\WelcomeModel();
        $fields = 'id,box_mac,type';
        $where = array('hotel_id'=>$hotel_id);
        $where['status'] = array('in',array(1,2));
        $where['finish_time'] = array('egt',date('Y-m-d H:i:s'));
        $res_welcome = $m_welcome->getDataList($fields,$where,'id desc');
        $play_boxs = array();
        $is_allplay = 0;
        if(!empty($res_welcome)){
            foreach ($res_welcome as $v){
                if($v['type']==2){
                    $is_allplay = 1;
                }else{
                    $play_boxs[$v['box_mac']]=$v['id'];
                }
            }
        }

        $fields = 'a.id as box_id,c.id as room_id,c.name as room_name,a.name as box_name,a.mac as box_mac';
        $m_box = new \Common\Model\BoxModel();
        $res_box = $m_box->getBoxListByHotelRelation($fields,$hotel_id);
        $box_list = $box_name_list = array();

        if(!empty($res_box)) {
            $play_boxlist = $noplay_boxlist = array();
            foreach ($res_box as $k=>$v) {
                $box_mac = $v['box_mac'];
                $box_name = $v['box_name'];
                if($is_allplay){
                    $box_name = "$box_name(正在播放)";
                    $play_boxlist[] = array('id'=>$v['box_id'],'name'=>$box_name,'box_mac'=>$box_mac);
                }elseif(array_key_exists($box_mac,$play_boxs)){
                    $box_name = "$box_name(正在播放)";
                    $play_boxlist[] = array('id'=>$v['box_id'],'name'=>$box_name,'box_mac'=>$box_mac);
                }else{
                    $noplay_boxlist[] = array('id'=>$v['box_id'],'name'=>$box_name,'box_mac'=>$box_mac);
                }
            }
            $box_list = array_merge($noplay_boxlist,$play_boxlist);
            array_unshift($box_list,array('id'=>0,'name'=>'全部包间','box_mac'=>2));
            array_unshift($box_list,array('id'=>999999,'name'=>'请选择包间','box_mac'=>''));
            foreach ($box_list as $v){
                $box_name_list[]=$v['name'];
            }
        }

        $res_data = array('box_list'=>$box_list,'box_name_list'=>$box_name_list);
        $this->to_back($res_data);
    }

}