<?php
namespace Small\Controller;
use \Common\Controller\CommonController as CommonController;

class FlashMemeryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getMenuList':  //根据酒楼获取当前节目单
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001);
                break;    
        }
        parent::_init_();
    }
    
    /**
     * @desc 获取某个酒楼下的节目单
     */
    public function getMenuList(){
        $hotel_id = $this->params['hotel_id'];  //酒楼id
        $hotel_id = intval($hotel_id);
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getOneById('hotel_box_type,state,flag',$hotel_id);
        if(empty($hotel_info)){//该酒楼不存在
            $this->to_back(16200);
        }
        if($hotel_info['flag'] !=0){//该酒楼已删除
            $this->to_back(16201);
        }
        if($hotel_info['state'] !=1){ //该酒楼为非正常酒楼
            $this->to_back(16202);
        }
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getOneById('id,name,area_id,level,iskey,state,hotel_box_type',$hotel_id);
        $result = array();
        $result['hotel_info'] = $hotel_info;
        $menuhotelModel = new \Common\Model\MenuHotelModel();
        $adsModel = new \Common\Model\AdsModel();
        
        //获取广告期号
        $per_arr = $menuhotelModel->getadsPeriod($hotel_id);
        
        if(empty($per_arr)){
            $this->to_back(10000);
        }
        $menuid = $per_arr[0]['menuId'];
        //获取节目单的节目数据start
        $pro_arr = $adsModel->getproInfo($menuid);   
        $pro_arr = $this->changeadvList($pro_arr,1);
        //获取节目单的节目数据end
        
        //获取节目单的广告数据start
        $ads_arr = $adsModel->getadsInfo($menuid);
        $ads_arr = $this->changeadvList($ads_arr,2);
        //获取节目单的广告数据end
        
        //获取节目单的宣传片start
        $adv_arr = $adsModel->getadvInfo($hotel_id, $menuid);
        $adv_arr = $this->changeadvList($adv_arr,1);
        
        $menu_list = array_merge($pro_arr,$ads_arr,$adv_arr);
        foreach($menu_list as $key=>$v){
            $sort_arr[$key]['order'] = $v['order'];
        }
        array_multisort($sort_arr,SORT_ASC,$menu_list);
        $result['menu_list'] = $menu_list; 
        //获取节目单的宣传片end
        //print_r($menu_list);exit;
        foreach($menu_list as $key=>$v){
            $ids [] = $v['id'];
        }
        $menu_num = md5(json_encode($ids));
        
        $m_flash_menu = new \Common\Model\Flashmenu\FlashMenuModel();
        $m_flash_menu_item = new \Common\Model\Flashmenu\FlashMenuItemModel();
        $where = array();
        $where['hotel_id'] = $hotel_id;
        $where['menu_num'] = $menu_num;
        $order = 'id desc ';
        $limit = 1;
        $flash_info = $m_flash_menu->getInfo('*',$where,$order,$limit);
        
        
        if(empty($flash_info)){
            //获取历史节目单
            $where = array();
            $order = 'id desc';
            $limit = 1;
            $last_flash_menu = $m_flash_menu->getInfo('id',$where,$order,$limit);
            $last_flash_menu_item = $m_flash_menu_item->getList($last_flash_menu[0]['id']);
            
            $result['last_menu'] = $last_flash_menu_item;
            
            
            $data['hotel_id'] = $hotel_id;
            $data['menu_num'] = $menu_num;
            $data['period'] = $per_arr[0]['period'];
            $data['menu_pub_time'] = $per_arr[0]['pubTime'];
            
            $data['create_time'] = date('Y-m-d H:i:s');
            $flash_id = $m_flash_menu->addInfo($data);
            foreach($menu_list as $key=>$v){
                $tmp = array();
                $tmp['flash_id'] = $flash_id;
                $tmp['media_id'] = $v['id'];
                $tmp['name']     = $v['name'];
                $tmp['md5']      = $v['md5'];
                $tmp['md5_type'] = $v['md5_type'];
                $tmp['type']     = $v['type'];
                $tmp['oss_path'] = $v['oss_path'];
                $tmp['duration'] = $v['duration'];
                $tmp['suffix']   = $v['suffix'];
                $tmp['chinese_name'] = $v['chinese_name'];
                $tmp['order']    = $v['order'];
                $m_flash_menu_item->addInfo($tmp);
            }
            
        }else {
            //获取上一期节目单信息
            $where = array();
            $where['hotel_id'] = $hotel_id;
            $where['menu_num'] = array('neq',$menu_num);
            $order = ' id desc';
            $limit = 1;
            $last_menu_info = $m_flash_menu->getInfo('*',$where,$order,$limit);
            if(!empty($last_menu_info)){
                $where = array();
                $where['flash_id'] = $last_menu_info[0]['id'];
                $order = ' id asc';
                $last_flash_menu_item = $m_flash_menu_item->getList('media_id,name,md5,md5_type,type,oss_path,duration,suffix,chinese_name,order',$where,$order);
                $result['last_menu'] = $last_flash_menu_item;
            }else {
                $result['last_menu'] = array();
            }
        }
       
        $this->to_back($result);
        /* $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId']; */
       
        
    }
    /**
     * changeadvList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    private function changeadvList($res,$type){
        if($res){
            foreach ($res as $vk=>$val) {
                if($type==1){
                    $res[$vk]['order'] =  $res[$vk]['sortNum'];
                    unset($res[$vk]['sortNum']);
                }
                
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
            }
    
        }
        return $res;
        //如果是空
    }
}