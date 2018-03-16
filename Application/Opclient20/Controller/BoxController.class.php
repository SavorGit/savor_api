<?php
/**
 * @desc 机顶盒信息
 * @author zhang.yingtao
 * @since  2018-01-17
 */
namespace Opclient20\Controller;
use Think\Controller;
use \Common\Lib\SavorRedis;
use \Common\Controller\BaseController as BaseController;
class BoxController extends BaseController{ 
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'stateConf':
                $this->is_verify = 0;
            break;
            case 'contentDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            case 'getDownloadAds':
                $this->is_verify = 1;
                $this->valid_fields = array('ads_download_period'=>1001,'box_id'=>1001);
                break;
            case 'getDownloadPro':
                $this->is_verify = 1;
                $this->valid_fields = array('pro_download_period');
                break;
            
            case 'getPubProgram':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            case 'oneKeyCheck':
                $this->is_verify = 1;
                $this->valid_fields = array('box_id'=>1001);
                break;
            
        }
        parent::_init_();
    }
    /**
     * @desc 机顶盒状态列表
     */
    public function stateConf(){
        $box_state_arr = C('BOX_STATE');
        $data = array();
        foreach($box_state_arr as $key=>$v){
            $tmp['id'] = $key;
            $tmp['name'] = $v;
            $data[] = $tmp;
        }
        $this->to_back($data);
    }
    /**
     * @desc 内容详情
     */
    public function contentDetail(){
        $box_id = $this->params['box_id'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.id'] = $box_id;
        $where['a.flag'] = 0;
        //$where['a.state'] = 1;
        $box_info = $m_box->getBoxInfo('a.id box_id,a.name,a.mac,c.name room_name,d.id hotel_id',$where);
        $box_info = $box_info[0];
        if(empty($box_info)){
            $this->to_back(70001);
        }
        
        $data = array();
        $data['room_name']= $box_info['room_name'];
        $data['box_name'] = $box_info['name'];
        $data['box_mac']  = $box_info['mac'];
        
        $data['log_upload_time'] = '无';
        
        //获取机顶盒维修记录
        $redMo = new \Common\Model\RepairBoxUserModel();
        $field = 'sys.remark nickname, date_format(sru.create_time,"%m-%d  %H:%i") ctime ';
        $co['mac'] = $box_info['mac'];
        $rinfo = $redMo->getRepairUserInfo($field, $co);
        if (empty($rinfo)) {
            $data['repair_record'] = array();
        } else {
            $data['repair_record'] = $rinfo;
        }
        //获取心跳
        /* $m_heart_log = new \Common\Model\HeartLogModel();
        $where = array();
        $where['type'] = 2;
        //$where['box_mac'] = $box_info['mac'];
        $where['box_id']  = $box_info['box_id'];
        $box_heart_info = $m_heart_log->getInfo('last_heart_time,ads_period,pro_period,adv_period,pro_download_period,ads_download_period,adv_period',$where);
         */ 
        $redis = SavorRedis::getInstance();
        $redis->select(13);
        $key = "heartbeat:"."2:".$box_info['mac']; 
        $box_heart_info = $redis->get($key);
        $box_heart_info = json_decode($box_heart_info,true);
        $box_heart_info['last_heart_time'] = date('Y-m-d H:i:s',strtotime($box_heart_info['date'])); 
        $box_heart_info['period'] = $box_heart_info['ads_period'] ;
        
        if(empty($box_heart_info)){
            $data['loss_hours'] = '失联30天以上';
        }else {
            $data['last_heart_time'] = $box_heart_info['last_heart_time'];
            $diff_time = time() - strtotime($box_heart_info['last_heart_time']);
            
            $diff_hours = $diff_time/3600;
            $heart_loss_hours = C('HEART_LOSS_HOURS');
            
            if($diff_hours>$heart_loss_hours){
                $data['loss_hours'] = '失联'.floor($diff_hours).'小时';
            }else {
                $data['loss_hours'] = '正常';
            }
        }
        
        
        $hotel_id = $box_info['hotel_id'];
        //节目状态
        $pro_same_flag = 0;
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        $ads = new \Common\Model\AdsModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
        if(!empty($menu_info)){
            $program_menu_num = $menu_info['menu_num'];
            
            if($program_menu_num == $box_heart_info['pro_period']){//节目单号与上报一致
                $data['pro_period_state'] = '已更新到最新';
                $pro_same_flag = 1;
            }else {//节目单号与上报不一致
                $data['pro_period_state'] = '版本不是最新';
                $pro_same_flag = 0;
            }  
            if($box_heart_info['pro_download_period'] && $box_heart_info['pro_download_period'] !=$box_heart_info['pro_period'] ){
                $data['pro_download_period'] = $box_heart_info['pro_download_period'];
            }else {
                $data['pro_download_period'] = '';
            }
            
        }
        //广告状态
        //获取该机顶盒最新广告
        $ads_same_flag = 0;
        $box_ads_arr = $this->getBoxAdsList($box_id);
        if($box_ads_arr){
            $pub_ads_peroid = $box_ads_arr['box_ads_num'];  //发布的广告期号
            if($pub_ads_peroid == $box_heart_info['ads_period']){
                $data['ads_period_state'] = '已更新到最新';
                $ads_same_flag = 1;
            }else {
                $data['ads_period_state'] = '版本不是最新';
                $ads_same_flag = 0;
            }
            
        }
        if($box_heart_info['ads_download_period'] && ($box_heart_info['ads_download_period'] !=$box_heart_info['ads_period'])){
            $data['ads_download_period'] = $box_heart_info['ads_download_period'];
        }else {
            $data['ads_download_period'] = '';
        }
        
        //宣传片状态
        //获取当前机顶盒宣传片列表
        $adv_same_flag = 0;
        $box_adv_arr = $this->getBoxAdvList($hotel_id);
        
        if($box_adv_arr){
            $pub_adv_peroid = $box_adv_arr['box_adv_num'].$program_menu_num;
            
            //echo $pub_adv_peroid;exit;
            if($pub_adv_peroid == $box_heart_info['adv_period']){
                $adv_same_flag = 1;
            }else {
                $adv_same_flag = 0;
            }
        }
        
        
        //当前播放列表
        $data['pro_period'] = $box_heart_info['pro_period'];  //当前节目期号
        $data['ads_period'] = $box_heart_info['ads_period'];  //当前广告期号
        //print_r($data);exit;  
        $program_list = array();  
        if($data['pro_period']){
            $m_program_list = new \Common\Model\ProgramMenuListModel();
            $program_info  = $m_program_list->getOne('id', array('menu_num'=>$data['pro_period']));
            $m_program_item = new \Common\Model\ProgramMenuItemModel();
            if($program_info){
                $program_list = $m_program_item->field('ads_name as name,location_id,sort_num,type')
                                               ->where(array('menu_id'=>$program_info['id'],'type'=>array('in','1,2,3')))
                                               ->order('sort_num asc')
                                               ->select();

                
                foreach($program_list as $key=>$v){
                    if($v['type'] ==2){
                        
                        if($pro_same_flag ==0){
                            $program_list[$key]['flag'] = 0;
                        }else {
                            $program_list[$key]['flag'] = 1;
                        }
                        $program_list[$key]['type'] = '节目';
                    }
                    
                    if($v['type'] ==3){
                        if($adv_same_flag==0){
                            $program_list[$key]['flag'] = 0;
                            $program_list[$key]['type'] = '宣传片';
                        }else {
                            
                            if($box_adv_arr['media_list'][$v['sort_num']]){
                                
                                $program_list[$key] = $box_adv_arr['media_list'][$v['sort_num']];
                                $program_list[$key]['flag'] = 1;
                                $program_list[$key]['type'] = '宣传片';
                            }else {
                                unset($program_list[$key]);
                            }
                        }

                    }
                    if($v['type']==1){
                        if($ads_same_flag ==0){
                            $program_list[$key]['flag'] = 0;
                            $program_list[$key]['type'] = '广告';
                        }else {
                            
                            if($box_ads_arr['media_list'][$v['location_id']]){
                                
                                $program_list[$key] = $box_ads_arr['media_list'][$v['location_id']];
                                $program_list[$key]['flag'] = 1;
                                $program_list[$key]['type'] = '广告';
                            }else {
                                
                                unset($program_list[$key]);
                            }
                        }
                        
                    }
                    
                }

                $result = array();
                foreach($program_list as $key=>$v){
                    $result[] = $v;
                }
                
                $data['program_list'] = $result;
            }
        }
        $this->to_back($data);
    }
    /**
     * @desc 获取机顶盒最新广告列表 
     */
    private function getBoxAdsList($box_id){
        $m_pub_ads_box = new \Common\Model\PubAdsBoxModel();
        $max_adv_location = C('MAX_ADS_LOCATION_NUMS');
        $now_date = date('Y-m-d H:i:s');
        $data =  array();
        //$v_keys = 0;
        for($i=1;$i<=$max_adv_location;$i++){
            $adv_arr = $m_pub_ads_box->getAdsList($box_id,$i);  //获取当前机顶盒得某一个位置得广告
            $adv_arr = $this->changeadvList($adv_arr);
             
            if(!empty($adv_arr)){
                $flag =0;
                foreach($adv_arr as $ak=>$av){
                    if($av['start_date']>$now_date){
                        $flag ++;
                    }
                    if($flag==2){
                        unset($adv_arr[$ak]);
                        break;
                    }
                     
                    $ads_arr['pub_ads_id']  = $av['pub_ads_id'];
                    $ads_arr['create_time'] = $av['create_time'];
                    $ads_arr['location_id'] = $av['location_id'];
                    $ads_num_arr[] = $ads_arr;
                    $ads_time_arr[] = $av['create_time'];
                    
                    $tmp = array();
                    $tmp['name'] = $av['chinese_name'];
                    $tmp['type'] = 1;
                    $tmp['location_id'] = $av['location_id'];
                    $data['media_list'][$av['location_id']] = $tmp;
                     
                }
            }
        }
        if(!empty($ads_num_arr)){//如果该机顶盒下广告位不为空
             
            $ads_time_str = max($ads_time_arr);
            $box_ads_num = date('YmdHis',strtotime($ads_time_str));
            $data['box_ads_num'] = $box_ads_num;
        }
        return $data;
    }
    /**
     * @desc 获取当前机顶盒的宣传片列表
     */
    private function getBoxAdvList($hotel_id){
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        //获取最新节目单
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
        
        $data = array();
        if(empty($menu_info)){//该酒楼未设置节目单
            return $data;
        }
        $menu_id = $menu_info['menu_id'];
        $menu_num= $menu_info['menu_num'];
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        $adv_arr = $m_program_menu_item->getadvInfo($hotel_id, $menu_id);
        
        foreach($adv_arr as $key=>$v){
            $temp =array();
            $tmp['name'] = $v['chinese_name'];
            $tmp['type'] = 3;
            $tmp['sort_num'] = $v['sortNum'];
            $data['media_list'][$v['sortNum']] = $tmp;
        }

        $m_ads = new \Common\Model\AdsModel();
        $adv_proid_info = $m_ads->getWhere(array('hotel_id'=>$hotel_id,'type'=>3),'max(update_time) as max_update_time');
        $adv_proid = date('YmdHis',strtotime($adv_proid_info[0]['max_update_time']));
        $data['box_adv_num'] = $adv_proid;
        return $data;
    }
    
    private function changeadvList($res,$type=1){
        if($res){
            foreach ($res as $vk=>$val) {
                if(!empty($val['sortNum'])){
                    if($type==1){
                        $res[$vk]['order'] =  $res[$vk]['sortNum'];
                    }else {
                        $res[$vk]['location_id'] = $res[$vk]['sortNum'];
                    }
                     
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
    
    /**
     * @desc 获取当前下载中的节目列表
     */
    public function getDownloadPro(){
        $pro_download_period = $this->params['pro_download_period'];
        
        $m_program_list = new \Common\Model\ProgramMenuListModel();
        $program_info  = $m_program_list->getOne('id', array('menu_num'=>$pro_download_period));
        
        $m_program_item = new \Common\Model\ProgramMenuItemModel();
        if($program_info){
            $program_list = $m_program_item->field("ads_name,location_id,sort_num,case type
				when 1 then 'ads'
				when 2 then 'pro'
				when 3 then 'adv' END AS type")
            ->where(array('menu_id'=>$program_info['id'],'type'=>array('in','2,3')))
            ->order('sort_num asc')
            ->select();
            $this->to_back($program_list);
        }else {
            $this->to_back(30115);
        }
    }
    
    /**
     * @desc 获取当前下载中的广告列表
     */
    public function getDownloadAds(){
        $ads_download_period = $this->params['ads_download_period'];
        $box_id = $this->params['box_id'];
        
        
        $redis = new SavorRedis();
        $redis->select(12);
        $cache_key = C('PROGRAM_ADS_CACHE_PRE').$box_id;
        $ads_list = $redis->get($cache_key);
        $ads_list = json_decode($ads_list,true);
        $data = array();
        if($ads_list && $ads_list['menu_num'] == $ads_download_period){
            $m_pub_ads = new \Common\Model\PubAdsModel();
            foreach($ads_list['ads_list'] as $key=>$v){
                $media_info = $m_pub_ads->getPubAdsInfoByid('med.name,med.oss_addr,med.duration',array('pads.id'=>$v['pub_ads_id']));
                $temp['name'] = $media_info['name'];
                $temp['type'] = '广告';
                $data[] = $temp;
            }
            $this->to_back($data);
        }else {
            $this->to_back('30114');
        } 
    }
    
    /**
     * @desc 获取当前应该播放的节目单列表
     */
    public function getPubProgram(){
        $box_id = $this->params['box_id'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.id'] = $box_id;
        $where['a.flag'] = 0;
        $where['a.state'] = 1;
        $box_info = $m_box->getBoxInfo('a.name,a.mac,d.id hotel_id',$where);
        $box_info = $box_info[0];
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $hotel_id = $box_info['hotel_id'];
        
        $m_new_menu_hotel = new \Common\Model\ProgramMenuHotelModel();
        $menu_info = $m_new_menu_hotel->getLatestMenuid($hotel_id);   //获取最新的一期节目单
        
        
        $m_program_item = new \Common\Model\ProgramMenuItemModel();
        if($menu_info){
            $program_list = $m_program_item->field('ads_name name,location_id,sort_num,type')
            ->where(array('menu_id'=>$menu_info['menu_id'],'type'=>array('in','1,2,3')))
            ->order('sort_num asc')
            ->select();
            
            if(empty($program_list)){
                $this->to_back(30115);
            }
            
            
            
            $box_ads_arr = $this->getBoxAdsList($box_id);
            $box_adv_arr = $this->getBoxAdvList($hotel_id);
            //print_r($program_list);exit;
            
            $data = array();
            foreach($program_list as $key=>$v){
                if($v['type'] ==2){
                    $v['type'] = '节目';
                    $data[] = $v;
                }
            
                if($v['type'] ==3){
                    
            
                    if($box_adv_arr['media_list'][$v['sort_num']]){
                        $box_adv_arr['media_list'][$v['sort_num']]['type'] = '宣传片';
                        $data[] = $box_adv_arr['media_list'][$v['sort_num']];
                        
                    }else {
                        unset($program_list[$key]);
                    }
                    
                }
                if($v['type']==1){
                    
                    if($box_ads_arr['media_list'][$v['location_id']]){
                        
                        $box_ads_arr['media_list'][$v['location_id']]['type'] = '广告';
                        $data[] = $box_ads_arr['media_list'][$v['location_id']];
                        
                    }else {
                        unset($program_list[$key]);
                    }
                
                }
                
            }
            $result['program_list'] = $data;
            $result['menu_num'] = $menu_info['menu_num'];
            $result['ads_menu_num'] = $box_ads_arr['box_ads_num'];
            $this->to_back($result);
        
        }else {
            $this->to_back(30115);
        }
    }
    /**
     * @desc 一键检测结果
     */
    public function oneKeyCheck(){
        $box_id = $this->params['box_id'];
        $m_box = new \Common\Model\BoxModel();
        $where = array();
        $where['a.id'] = $box_id;
        $where['a.flag'] = 0;
        $where['a.state'] = 1;
        $box_info = $m_box->getBoxInfo('a.id box_id,a.name,a.mac,d.id hotel_id',$where);
        $box_info = $box_info[0];
        if(empty($box_info)){//该机顶盒不存在
            $this->to_back(70001);
        }        
        $redis = new SavorRedis();
        $redis->select(10);
        
        $cache_key = C('NET_REPORT_KEY').$box_id;
        $net_info = $redis->get($cache_key);
        $data = array();
        $m_heart_log = new \Common\Model\HeartLogModel();
        if(empty($net_info)){//如果没有网络上报
            
            //查看小平台心跳
            
            $where =  array();
            $where['hotel_id'] = $box_info['hotel_id'];
            $where['type']     = 1;
            $hotel_heart_info = $m_heart_log->getInfo('last_heart_time',$where);
            if(empty($hotel_heart_info)){
                $data['small_device_name']  = '小平台';
                $data['small_device_state'] = '离线'; 
            }else {
                $difff_time = time() - strtotime($hotel_heart_info['last_heart_time']);
                if($difff_time>600){
                    $data['small_device_name']  = '小平台';
                    $data['small_device_state'] = '离线';
                }else {
                    $data['small_device_name']  = '小平台';
                    $data['small_device_state'] = '在线';
                }
            }
            //查看机顶盒心跳
            $where =  array();
            $where['box_id'] = $box_info['box_id'];
            $where['type']     = 2;
            $box_heart_info = $m_heart_log->getInfo('last_heart_time',$where);
            $data['box_device_name'] = $box_info['name'];
            if(empty($box_heart_info)){
                $data['box_device_state'] = '离线  无法投屏点播';
            }else {
                $difff_time = time() - strtotime($box_heart_info['last_heart_time']);
                if($difff_time>600){
                    $data['box_device_state'] = '离线  无法投屏点播';
                }else {
                    $data['box_device_state'] = '在线  无法投屏点播';
                }
            }
        }else {
            $net_info = json_decode($net_info,true);
            if($net_info['netty_conn']==1){
                $data['small_device_name']  = '小平台';
                $data['small_device_state'] = '在线';
            }else {
                $data['small_device_name']  = '小平台';
                $data['small_device_state'] = '离线';
            }
            $where =  array();
            $where['box_id'] = $box_info['box_id'];
            $where['type']     = 2;
            $box_heart_info = $m_heart_log->getInfo('last_heart_time',$where);
            $data['box_device_name'] = $box_info['name'];
            if(empty($box_heart_info)){
                if($net_info['inn_delay']==''){
                    $data['box_device_state'] = '离线  不可以投屏点播';
                }else {
                    $data['box_device_state'] = '离线  可以投屏点播';
                }
                
            }else {
                $difff_time = time() - strtotime($box_heart_info['last_heart_time']);
                if($difff_time>600){
                    $data['box_device_state'] = '离线  无法投屏点播';
                }else {
                    $data['box_device_state'] = '在线  可以投屏点播';
                }
            }
            $data['box_net_state'] = '网络延时：外网('.$net_info['out_delay'].'毫秒)  内网('.$net_info['inn_delay'].'毫秒)';
            
            
        }
        $data['remark'] = array('离线原因(仅供参考)','1、机顶盒没开机','2、局域网拥堵','3、外网网络断开等等');
        $this->to_back($data);
    }
    
}