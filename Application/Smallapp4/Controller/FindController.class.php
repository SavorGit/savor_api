<?php
namespace Smallapp4\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class FindController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':    //获取精选公开内容(已废弃)
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'showPic':
                $this->is_verify = 1;
                $this->valid_fields = array('forscreen_id'=>1001,'openid'=>1001);
                break;
            case 'redPacketJx':  //抢红包成功失败页面获取精选内容
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'choice':  //精选列表
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'findlist':  //发现列表
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'find_ids'=>1002);
                break;
            case 'recordViewfind':  //记录查看发现
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'id'=>1001,'type'=>1001);
                break;
            case 'videos':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1002,'page'=>1001);
            case 'images':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1002,'page'=>1001);
        }
        parent::_init_();
    }

    public function videos(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'];
        $pagesize = 10;

        //内容选择 1点播10条 2精选20 3公开20
        $content_num = array('num'=>50,'1'=>0.16,'2'=>0.42,'3'=>0.42);
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $default_avatar = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';

        $m_public = new \Common\Model\Smallapp\PublicModel();
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $key_findtop = C('SAPP_FIND_TOP');
        $res_findtop = $redis->get($key_findtop);
        $find_topids = array();
        if(!empty($res_findtop)) {
            $find_topids = json_decode($res_findtop, true);
        }

        if($page==1 && !empty($find_topids)){
            $top_list = array();
            $where = array('a.id'=>array('in',$find_topids),'a.res_type'=>2);
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $where['user.status'] = 1;
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $res_top = $m_public->getList($fields, $where,'a.id desc','');
            if(!empty($res_top)){
                $top_list = $this->handleFindlist($res_top,$openid,2);
            }
        }

        $find_key = C('SAPP_FIND_CONTENTNEW');
        $res_cache = $redis->get($find_key);
        if(!empty($res_cache)){
            $is_expire = 0;
            $find_data = json_decode($res_cache,true);
        }else{
            $is_expire = 1;
            //点播内容 获取最新一期设置为小程序的节目单
            $demand_num = $content_num['num']*$content_num['1'];
            $m_program_list =  new \Common\Model\ProgramMenuListModel();
            $where  = array('is_small_app'=>1);
            $order = " id desc";
            $program_info = $m_program_list->getInfo('id', $where, $order);

            $menu_id = $program_info['id'];
            $fields = 'ads.id,ads.name title,ads.description as content,ads.img_url,ads.portraitmedia_id,ads.duration,ads.create_time,media.id as media_id,media.oss_addr,media.oss_filesize as resource_size';
            $where = array('a.menu_id'=>$menu_id,'a.type'=>2);
            $where['media.id']  = array('not in',array('17614','19533'));
            $where['media.type'] = 1;
            $where['ads.is_sapp_show'] = 1;
            $order = 'a.sort_num asc';
            $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
            $res_demand = $m_program_menu_item->getList($fields,$where,$order,"0,$demand_num");
            $demand_list = array();
            $m_media = new \Common\Model\MediaModel();
            foreach($res_demand as $v){
                $create_time = viewTimes(strtotime($v['create_time']));
                $dinfo = array('id'=>$v['id'],'title'=>$v['title'],'forscreen_id'=>0,'res_type'=>2,'res_nums'=>1,'create_time'=>$create_time,
                    'hotel_name'=>'','avatarUrl'=>$default_avatar,'nickName'=>'小热点');
                if(!empty($v['content'])){
                    $dinfo['content'] = $v['content'];
                }else{
                    $dinfo['content'] = '';
                }
                //获取是否收藏、分享个数、收藏个数、获取播放次数
                $rets = $this->getFindnums($openid,$v['id'],3);
                $dinfo['is_collect'] = $rets['is_collect'];
                $dinfo['collect_num']= $rets['collect_num'];
                $dinfo['share_num']  = $rets['share_num'];

                $res_url = $oss_host.$v['oss_addr'];
                $forscreen_url = $v['oss_addr'];
                $duration = intval($v['duration']);
                $resource_size = $v['resource_size'];
                $res_id = $v['media_id'];

                if($v['portraitmedia_id']){
                    $res_media = $m_media->getMediaInfoById($v['portraitmedia_id']);
                    $res_url = $res_media['oss_addr'];
                }

                $pdetail = array('res_url'=>$res_url,'forscreen_url'=>$forscreen_url,'duration'=>$duration,
                    'resource_size'=>$resource_size,'res_id'=>$res_id);
                $oss_info = pathinfo($forscreen_url);
                $pdetail['filename'] = $oss_info['basename'];

                $img_url = $v['img_url']? $v['img_url'] :'media/resource/EDBAEDArdh.png';
                $pdetail['img_url'] = $oss_host.$img_url;
                $dinfo['pubdetail'] = array($pdetail);
                $dinfo['type'] = 1;
                $demand_list[] = $dinfo;
            }

            //精选内容
            $choice_num = $content_num['num']*$content_num['2'] + ($demand_num-count($demand_list));
            $where = array('a.status'=>2,'a.is_recommend'=>1,'a.res_type'=>2);
            if(!empty($find_topids)){
                $where['a.id'] = array('not in',$find_topids);
            }
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $res_choice = $m_public->getList($fields, $where,'id desc',"0,$choice_num");

            $choice_list = array();
            $choice_ids = array();
            foreach ($res_choice as $v){
                $choice_ids[] = $v['id'];
                $v['type'] = 2;
                $choice_list[] = $v;
            }

            //公开内容
            $public_num = $content_num['num']*$content_num['3'];
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $where = array('a.status'=>2,'a.res_type'=>2);
            $not_ids = array_merge($choice_ids,$find_topids);
            if(!empty($not_ids)){
                $where['a.id'] = array('not in',$not_ids);
            }
            $where['box.flag']   = 0;
            $where['box.state']  = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state']= 1;
            $order = 'a.id desc';
            $res_public = $m_public->getList($fields, $where, $order, "0,$public_num");
            $public_list = array();
            foreach ($res_public as $v){
                $v['type'] = 3;
                $public_list[] = $v;
            }
            $all_public = array_merge($choice_list,$public_list);
            $all_public = $this->handleFindlist($all_public,$openid);
            $find_data = array_merge($demand_list,$all_public);
            shuffle($find_data);
            $redis->set($find_key,json_encode($find_data),7200);
        }

        $res_data = $find_data;
        $all_res_datanum = count($res_data);
        $all_page = ceil($all_res_datanum/$pagesize);
        if($page<=$all_page){
            $offset = ($page-1)*$pagesize;
            $res_data = array_slice($res_data,$offset,$pagesize);
            if($is_expire==0){
                foreach ($res_data as $k=>$v){
                    if($v['type']==1){
                        $rets = $this->getFindnums($openid,$v['id'],3);
                    }else{
                        $rets = $this->getFindnums($openid,$v['forscreen_id'],2);
                    }
                    $res_data[$k]['is_collect'] = $rets['is_collect'];
                    $res_data[$k]['collect_num'] = $rets['collect_num'];
                    $res_data[$k]['share_num']  = $rets['share_num'];
                }
            }
        }else{
            $nowpage = $page-$all_page;
            $offset = ($nowpage-1)*$pagesize;
            $not_ids = $find_topids;
            foreach ($res_data as $v){
                if(in_array($v['type'],array(2,3))){
                    $not_ids[]=$v['id'];
                }
            }
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $where = array('a.status'=>2,'a.res_type'=>2);
            if(!empty($not_ids)){
                $public_ids = array_unique($not_ids);
                $where['a.id'] = array('not in',$public_ids);
            }
            $where['box.flag']   = 0;
            $where['box.state']  = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state']= 1;
            $order = 'a.id desc';
            $limit = "$offset,$pagesize";
            $res_public = $m_public->getList($fields, $where, $order,$limit);
            $res_data = $this->handleFindlist($res_public,$openid,3);
        }
        if($page==1 && !empty($top_list)){
            $res_data = array_merge($top_list,$res_data);
        }
        $this->to_back($res_data);
    }

    public function images(){
        $openid = $this->params['openid'];
        $page   = $this->params['page'];
        $pagesize = 5;

        $key_findtop = C('SAPP_FIND_TOP');
        $redis = SavorRedis::getInstance();
        $res_findtop = $redis->get($key_findtop);
        $find_topids = array();
        if(!empty($res_findtop)) {
            $find_topids = json_decode($res_findtop, true);
        }
        $m_public = new \Common\Model\Smallapp\PublicModel();
        if($page==1 && !empty($find_topids)){
            $top_list = array();
            $where = array('a.id'=>array('in',$find_topids),'a.res_type'=>1);
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $where['user.status'] = 1;
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $res_top = $m_public->getList($fields, $where,'id desc','');
            if(!empty($res_top)){
                $top_list = $this->handleFindlist($res_top,$openid,2);
            }
        }

        $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text as content,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
        $where = array('a.status'=>2,'a.res_type'=>1);
        if(!empty($find_topids)){
            $where['a.id'] = array('not in',$find_topids);
        }
        $where['box.flag']   = 0;
        $where['box.state']  = 1;
        $where['hotel.flag'] = 0;
        $where['hotel.state']= 1;
        $order = 'a.id desc';
        $all_nums = $page * $pagesize;
        $limit = "0,$all_nums";
        $res_public = $m_public->getList($fields, $where, $order,$limit);
        $res_data = $this->handleFindlist($res_public,$openid);

        if($page==1 && !empty($top_list)){
            $res_data = array_merge($top_list,$res_data);
        }
        $this->to_back($res_data);
    }

    public function findlist(){
        $openid = $this->params['openid'];
        $find_ids   = $this->params['find_ids'];
        $pagesize = 10;
        //内容选择 1点播10条 2精选20 3公开20
        $content_num = array('num'=>50,'1'=>0.4,'2'=>0.4,'3'=>0.2);
        //$content_num = array('num'=>50,'1'=>0,'2'=>0.6,'3'=>0.4);
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $default_avatar = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';

        $m_public = new \Common\Model\Smallapp\PublicModel();
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $prev_findids = array();
        if($find_ids){
            $find_ids = json_decode(stripslashes($find_ids),true);
            foreach ($find_ids as $v){
                $prev_findids[$v['type']][$v['id']]=$v['id'];
            }
        }
        $cache_key = C('SAPP_HAS_FIND').$openid;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $hasfind_data = json_decode($res_cache,true);
        }else{
            $hasfind_data = array();
        }
        $all_hasfind = array();
        foreach ($content_num as $k=>$v){
            $type = intval($k);
            if($type){
                $prev_ids = isset($prev_findids[$type])?array_values($prev_findids[$type]):array();
                $hasfind_ids = isset($hasfind_data[$type])?array_values($hasfind_data[$type]):array();
                $tmp_hasfind = array_merge($prev_ids,$hasfind_ids);
                if(!empty($tmp_hasfind)){
                    $all_hasfind[$type] = array_unique($tmp_hasfind);
                }
            }
        }
        $key_findtop = C('SAPP_FIND_TOP');
        $res_findtop = $redis->get($key_findtop);
        $find_topids = array();
        if($res_findtop){
            $find_topids = json_decode($res_findtop,true);
            if(!empty($find_topids)){
                foreach ($find_topids as $tk=>$tv){
                    if((isset($all_hasfind[2]) && in_array($tv,$all_hasfind[2])) || isset($all_hasfind[3]) && in_array($tv,$all_hasfind[3])){
                        unset($find_topids[$tk]);
                    }
                }
                $find_topids = array_values($find_topids);
            }
        }
        $top_list = array();
        if(!empty($find_topids)){
            $where = array('a.id'=>array('in',$find_topids));
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $where['user.status'] = 1;
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $res_top = $m_public->getList($fields, $where,'id desc','');
            $top_list = $this->handleFindlist($res_top,$openid,2);
        }
        $find_key = C('SAPP_FIND_CONTENT');
        $res_cache = $redis->get($find_key);
        if(!empty($res_cache)){
            $find_data = json_decode($res_cache,true);
        }else{
            //点播内容 获取最新一期设置为小程序的节目单
            $demand_num = $content_num['num']*$content_num['1'];
            $m_program_list =  new \Common\Model\ProgramMenuListModel();
            $where  = array('is_small_app'=>1);
            $order = " id desc";
            $program_info = $m_program_list->getInfo('id', $where, $order);
            $menu_id = $program_info['id'];
            $fields = 'ads.id,ads.name title,ads.img_url,ads.duration,ads.create_time,media.id as media_id,media.oss_addr,media.oss_filesize as resource_size';
            $where = array('a.menu_id'=>$menu_id,'a.type'=>2);
            $where['media.id']  = array('not in',array('17614','19533'));
            $where['media.type'] = 1;
            $where['ads.is_sapp_show'] = 1;
            $order = 'a.sort_num asc';
            $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
            $res_demand = $m_program_menu_item->getList($fields,$where,$order,"0,$demand_num");
            $demand_list = array();
            foreach($res_demand as $v){
                $create_time = viewTimes(strtotime($v['create_time']));
                $dinfo = array('id'=>$v['id'],'title'=>$v['title'],'forscreen_id'=>0,'res_type'=>2,'res_nums'=>1,'create_time'=>$create_time,
                    'hotel_name'=>'','avatarUrl'=>$default_avatar,'nickName'=>'小热点');

                //获取是否收藏、分享个数、收藏个数、获取播放次数
                $rets = $this->getFindnums($openid,$v['id'],3);
                $dinfo['is_collect'] = $rets['is_collect'];
                $dinfo['collect_num']= $rets['collect_num'];
                $dinfo['share_num']  = $rets['share_num'];

                $pdetail = array('res_url'=>$oss_host.$v['oss_addr'],'forscreen_url'=>$v['oss_addr'],'duration'=>intval($v['duration']),
                    'resource_size'=>$v['resource_size']);
                $oss_info = pathinfo($v['oss_addr']);
                $pdetail['filename'] = $oss_info['basename'];
                $pdetail['res_id'] = $v['media_id'];
                $img_url = $v['img_url']? $v['img_url'] :'media/resource/EDBAEDArdh.png';
                $pdetail['img_url'] = $oss_host.$img_url;
                $dinfo['pubdetail'] = array($pdetail);
                $dinfo['type'] = 1;
                $demand_list[] = $dinfo;
            }

            //精选内容
            $choice_num = $content_num['num']*$content_num['2'] + ($demand_num-count($demand_list));
            $where = array('a.status'=>2,'a.is_recommend'=>1);
            if(!empty($find_topids)){
                $where['a.id'] = array('not in',$find_topids);
            }
            $where['box.flag'] = 0;
            $where['box.state'] = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state'] = 1;
            $where['user.status'] = 1;
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $res_choice = $m_public->getList($fields, $where,'id desc',"0,$choice_num");

            $choice_list = array();
            $choice_ids = array();
            foreach ($res_choice as $v){
                $choice_ids[] = $v['id'];
                $v['type'] = 2;
                $choice_list[] = $v;
            }

            //公开内容
            $public_num = $content_num['num']*$content_num['3'];
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $where = array('a.status'=>2);
            $not_ids = array_merge($choice_ids,$find_topids);
            if(!empty($not_ids)){
                $where['a.id'] = array('not in',$not_ids);
            }
            $where['box.flag']   = 0;
            $where['box.state']  = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state']= 1;
            $order = 'a.id desc';
            $res_public = $m_public->getList($fields, $where, $order, "0,$public_num");
            $public_list = array();
            foreach ($res_public as $v){
                $v['type'] = 3;
                $public_list[] = $v;
            }
            $all_public = array_merge($choice_list,$public_list);
            $all_public = $this->handleFindlist($all_public,$openid);
            $find_data = array_merge($demand_list,$all_public);
            shuffle($find_data);
            $redis->set($find_key,json_encode($find_data),3600);
        }

        foreach ($find_data as $k=>$v){
            if(empty($all_hasfind)){
                break;
            }
            if((isset($all_hasfind[$v['type']]) && in_array($v['id'],$all_hasfind[$v['type']]))){
                unset($find_data[$k]);
            }
        }
        $find_total = count($find_data);
        if($find_total>=$pagesize){
            $res_data = array_slice($find_data,0,$pagesize);
        }else{
            $public_ids = array();
            foreach($find_data as $v){
                if($v['type']!=1){
                    $public_ids[] = $v['id'];
                }
            }
            if(!empty($all_hasfind)){
                if(isset($all_hasfind[2])){
                    foreach($all_hasfind[2] as $v){
                        $public_ids[] = $v;
                    }
                }
                if(isset($all_hasfind[3])){
                    foreach($all_hasfind[3] as $v){
                        $public_ids[] = $v;
                    }
                }
            }
            $key_publicids = C('SAPP_FIND_PUBLICIDS');
            $all_publicids = $redis->get($key_publicids);
            if(empty($all_publicids)){
                $fields= 'a.id';
                $where = array('a.status'=>2);
                $where['box.flag']   = 0;
                $where['box.state']  = 1;
                $where['hotel.flag'] = 0;
                $where['hotel.state']= 1;
                $order = 'a.id desc';
                $res_publicids = $m_public->getList($fields, $where, $order,'');
                $all_publicids = array();
                foreach ($res_publicids as $v){
                    $all_publicids[]=$v['id'];
                }
                $redis->set($key_publicids,json_encode($all_publicids),3600*2);
            }else{
                $all_publicids = json_decode($all_publicids,true);
            }
            $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $where = array('a.status'=>2);
            if(!empty($public_ids)){
                $public_ids = array_unique($public_ids);
                $not_publicids = array_diff($all_publicids,$public_ids);
            }else{
                $not_publicids = $all_publicids;
            }
            $size = $pagesize-$find_total;
            $public_in_ids = array_slice($not_publicids,0,$size);
            if(!empty($public_in_ids)){
                $where['a.id'] = array('in',$public_in_ids);
            }
            $where['box.flag']   = 0;
            $where['box.state']  = 1;
            $where['hotel.flag'] = 0;
            $where['hotel.state']= 1;
            $order = 'a.id desc';
            $all_public = $m_public->getList($fields, $where, $order,'');
            $res_data = $this->handleFindlist($all_public,$openid,3);

            $last_find_data = array_slice($find_data,0,$find_total);
            $res_data = array_merge($last_find_data,$res_data);
        }
        shuffle($res_data);
        if(!empty($top_list)){
            $res_data = array_merge($top_list,$res_data);
        }
        $this->to_back($res_data);
    }

    public function recordViewfind(){
        $openid = $this->params['openid'];
        $id = $this->params['id'];
        $type = $this->params['type'];
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $cache_key = C('SAPP_HAS_FIND').$openid;
        $res_cache = $redis->get($cache_key);
        if(!empty($res_cache)){
            $record_data = json_decode($res_cache,true);
        }else{
            $record_data = array();
        }
        $record_data[$type][$id]=$id;
        $redis->set($cache_key,json_encode($record_data));
        if(in_array($type,array(1,2,3))){//类型 1官方 2精选 3公开
            $data_type = $type+2;
            if($openid){
                $m_datalog = new \Common\Model\Smallapp\DatalogModel();
                $data = array('data_id'=>$id,'openid'=>$openid,'action_type'=>2,'type'=>$data_type,'ip'=>get_client_ip());
                $m_datalog->addData($data);
            }
        }
        $this->to_back(array());
    }


    public function choice(){
        $openid = $this->params['openid'];
        $page   = intval($this->params['page']) ? intval($this->params['page']) :1;

        $pagesize = 20;
        $redis = SavorRedis::getInstance();
        $redis->select(5);
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $public_list = array();

        //$all_nums = $page * $pagesize;
        //$order = 'a.create_time desc';
        $fields= 'a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
        $cache_key = C('SAPP_FIND_INDEX_RAND').$openid;
        if($page==1){

            $rts = $m_public->getWhere('id', array('is_recommend'=>1,'status'=>2));

            $rand_nums_arr = array_rand($rts,20);
            $public_ids = '';
            foreach($rand_nums_arr as $v){
                $public_ids .= $space. $rts[$v]['id'];
                $space      = ',';
            }
            //echo $public_ids;exit;
            $where = array();
            $where['a.id'] = array('in',$public_ids);


            $where['box.flag']       = 0;
            $where['box.state']      = 1;
            $where['hotel.flag']     = 0;
            $where['hotel.state']    = 1;
            $where['user.status']    = 1;
            $public_list = $m_public->getList($fields, $where);


            $redis->set($cache_key, json_encode($public_list),86400);
        }else {
            //获取第一页内容
            $public_list = $redis->get($cache_key);
            $public_list = json_decode($public_list,true);
            $rand_forscreen_ids = '';
            foreach($public_list as $key=>$v){
                $rand_forscreen_ids .=$space . $v['forscreen_id'];
                $space  = ',';
            }

            $all_nums = ($page-1) * $pagesize;
            $where = array();
            $where['a.forscreen_id'] = array('not in',$rand_forscreen_ids);
            $where['a.is_recommend'] = 1;
            $where['a.status']       = 2;

            $where['box.flag']       = 0;
            $where['box.state']      = 1;
            $where['hotel.flag']     = 0;
            $where['hotel.state']    = 1;
            $where['user.status']    = 1;

            $order = 'a.create_time desc';
            $limit = "limit 0,".$all_nums;
            $rt = $m_public->getList($fields, $where, $order, $limit);
            $public_list = array_merge($public_list,$rt);
        }
        foreach($public_list as $key=>$v){

            if(empty($v['avatarUrl'])){
                $public_list[$key]['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';

            }
            if(empty($v['nickName'])){
                $public_list[$key]['nickName'] = '游客';
            }
            $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url, duration,resource_size";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where,'');
            if($v['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                $pubdetail_info[0]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[0]['res_id']   = $tmp_arr[0];
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
            }else {
                foreach($pubdetail_info as $kk=>$vv){
                    $filename = explode('/', $vv['forscreen_url']);

                    $pubdetail_info[$kk]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
                    $pubdetail_info[$kk]['res_url']  = $vv['res_url'].'?x-oss-process=image/resize,p_20';
                }
            }
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            //$map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = "0";
            }else {
                $public_list[$key]['is_collect'] = "1";
            }
            $public_list[$key]['pubdetail'] = $pubdetail_info;
            //echo $v['create_time'];exit;
            $public_list[$key]['create_time'] = viewTimes(strtotime($v['create_time']));

            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;

            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);

            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();

            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $public_list[$key]['share_num'] = $share_num;


        }
        $this->to_back($public_list);

    }
    /**
     * @desc 获取精选公开内容
     */
    public function index(){
        $openid = $this->params['openid'];
        $page   = intval($this->params['page']) ? intval($this->params['page']) :1;

        $pagesize = 10;

        //获取用户的好友列表
        $m_friend = new \Common\Model\Smallapp\FriendModel();
        $fields = "f_openid";
        $where =array();
        $where['status'] = 1;

        //$friend_list = $m_friend->getWhere($fields, $where);
        //print_r($friend_list);exit;
        $friend_list = array();
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $public_list = array();

        $all_nums = $page * $pagesize;
        $order = 'a.create_time desc';
        if(!empty($friend_list)){//如果该用户有朋友公开的内容

        }else {//如果该用户没有朋友公开的内容
            //获取系统推荐
            $where = array();

            $where['a.is_recommend'] = 1;
            $where['a.status']       = 2;

            $where['box.flag']       = 0;
            $where['box.state']      = 1;
            $where['hotel.flag']     = 0;
            $where['hotel.state']    = 1;
            $rec_nums = $m_public->countWhere($where);
            $limit = "limit 0,".$all_nums;
            $fields= 'a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $order = 'a.create_time desc';
            $public_list = $m_public->getList($fields, $where, $order, $limit);

        }
        foreach($public_list as $key=>$v){

            if(empty($v['avatarUrl'])){
                $public_list[$key]['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';

            }
            if(empty($v['nickName'])){
                $public_list[$key]['nickName'] = '游客';
            }
            $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url, duration,resource_size";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where,'','limit 0,1');
            if($v['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                $pubdetail_info[0]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[0]['res_id']   = $tmp_arr[0];
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
            }else {
                foreach($pubdetail_info as $kk=>$vv){
                    $filename = explode('/', $vv['forscreen_url']);

                    $pubdetail_info[$kk]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
                }
            }
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            //$map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = "0";
            }else {
                $public_list[$key]['is_collect'] = "1";
            }
            $public_list[$key]['pubdetail'] = $pubdetail_info;
            //echo $v['create_time'];exit;
            $public_list[$key]['create_time'] = viewTimes(strtotime($v['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;

            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);

            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();

            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $public_list[$key]['share_num'] = $share_num;


        }
        $this->to_back($public_list);
    }
    public function showPic(){
        $forscreen_id = $this->params['forscreen_id'];
        $openid       = $this->params['openid'];
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();


        //播放数据+1
        $m_play_log = new \Common\Model\Smallapp\PlayLogModel();
        $nums = $m_play_log->countNum(array('res_id'=>$forscreen_id));
        if(empty($nums)){
            $data = array();
            $data['res_id'] = $forscreen_id;
            $data['type']   = 2;
            $data['nums']   = 1;
            $m_play_log->addInfo($data);
        }else {
            $where = array();
            $where['res_id'] = $forscreen_id;
            $where['type']   = 2;
            $m_play_log->where($where)->setInc('nums',1);

        }

        //echo $v['create_time'];exit;
        $fields= 'a.forscreen_id,a.forscreen_char,a.public_text,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
        $order = 'a.res_type desc,a.create_time desc';
        $maps = array();
        $maps['a.forscreen_id'] = $forscreen_id;
        $maps['hotel.state'] = 1;
        $maps['hotel.flag']  = 0;
        $maps['box.flag']    = 0;
        $maps['box.state']   = 1;
        $rec_pub_list = $m_public->getList($fields, $maps);
        $pub_info = $rec_pub_list[0];
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $field = "forscreen_id,resource_id,openid,box_mac,resource_type,imgs";
        $where = array();
        $where['forscreen_id'] = $forscreen_id;
        $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url,duration,resource_size";
        $where = array();
        $where['forscreen_id'] = $forscreen_id;
        $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
        foreach($pubdetail_info as $kk=>$vv){
            if($pub_info['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                $pubdetail_info[$kk]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[$kk]['res_id']   = $tmp_arr[$kk];
                $pubdetail_info[$kk]['vide_img'] = $pubdetail_info[$kk]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[$kk]['duration'] = secToMinSec(intval($pubdetail_info[$kk]['duration']));
            }else{
                $filename = explode('/', $vv['forscreen_url']);

                $pubdetail_info[$kk]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
            }


        }

        $map = array();
        $map['openid']=$openid;
        $map['res_id'] =$forscreen_id;

        $map['status'] = 1;
        $is_collect = $m_collect->countNum($map);


        $data = array();
        if(!empty($rec_pub_list)){

            if(empty($pub_info['avatarUrl'])){
                $pub_info['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';

            }
            if(empty($pub_info['nickName'])){
                $pub_info['nickName'] = '游客';
            }
            $create_time = viewTimes(strtotime($pub_info['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$forscreen_id;
            $map['type']   = 2;

            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$forscreen_id))->find();
            //分享个数
            $map = array();
            $map['res_id'] =$forscreen_id;
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);

            //播放次数
            $map = array();
            $map['res_id'] = $forscreen_id;
            $map['type']   = 2;
            $play_info = $m_play_log->getOne('nums',$map);
            $play_num  = intval($play_info['nums']);

            if(empty($is_collect)){
                $pub_info['is_collect'] = "0";
            }else {
                $pub_info['is_collect'] = "1";
            }
            $pub_info['create_time'] = $create_time;
            $pub_info['collect_num'] = $collect_num + $ret['nums'];
            $pub_info['share_num']   = $share_num;
            $pub_info['play_num']    = $play_num;

            $pub_info['pubdetail']   = $pubdetail_info;
            $this->to_back($pub_info);
        }else {
            $this->to_back($data);
        }
    }
    /**
     *
     */
    public function redPacketJx(){

        $openid = $this->params['openid'];

        $redis = SavorRedis::getInstance();
        $redis->select('5');
        $cache_key = C('SAPP_REDPACKET_JX');
        $ret = $redis->get($cache_key);
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $public_list = array();
        if(empty($ret)){

            //获取系统推荐
            $all_nums = 3;
            $where = array();

            $where['a.is_recommend'] = 1;
            $where['a.status']       = 2;

            $where['box.flag']       = 0;
            $where['box.state']      = 1;
            $where['hotel.flag']     = 0;
            $where['hotel.state']    = 1;
            $rec_nums = $m_public->countWhere($where);
            $limit = "limit 0,".$all_nums;
            $fields= 'a.forscreen_id,a.res_type,a.res_nums,a.is_pub_hotelinfo,
                    a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
            $order = 'a.create_time desc';
            $public_list = $m_public->getList($fields, $where, $order, $limit);
            $redis->set($cache_key, json_encode($public_list),3600);
        }else {
            $public_list = json_decode($ret,true);
        }


        foreach($public_list as $key=>$v){

            if(empty($v['avatarUrl'])){
                $public_list[$key]['avatarUrl'] = 'http://oss.littlehotspot.com/WeChat/MiniProgram/LaunchScreen/source/images/imgs/default_user_head.png';

            }
            if(empty($v['nickName'])){
                $public_list[$key]['nickName'] = '游客';
            }
            $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url, duration,resource_size";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where);
            if($v['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                $pubdetail_info[0]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[0]['res_id']   = $tmp_arr[0];
                $pubdetail_info[0]['vide_img'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = secToMinSec(intval($pubdetail_info[0]['duration']));
            }else {
                foreach($pubdetail_info as $kk=>$vv){
                    $filename = explode('/', $vv['forscreen_url']);

                    $pubdetail_info[$kk]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[$kk]['res_id']   = $tmp_arr[0];
                }
            }
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['forscreen_id'];
            //$map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $public_list[$key]['is_collect'] = "0";
            }else {
                $public_list[$key]['is_collect'] = "1";
            }
            $public_list[$key]['pubdetail'] = $pubdetail_info;
            //echo $v['create_time'];exit;
            $public_list[$key]['create_time'] = viewTimes(strtotime($v['create_time']));
            //收藏个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;

            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);

            $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();
            $ret = $m_collect_count->field('nums')->where(array('res_id'=>$v['forscreen_id']))->find();

            $public_list[$key]['collect_num'] = $collect_num + $ret['nums'];
            //分享个数
            $map = array();
            $map['res_id'] =$v['forscreen_id'];
            $map['type']   = 2;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $public_list[$key]['share_num'] = $share_num;


        }
        $this->to_back($public_list);
    }

    private function getFindnums($openid,$res_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $m_collect_count = new \Common\Model\Smallapp\CollectCountModel();

        $map = array('openid'=>$openid,'res_id'=>$res_id,'type'=>$type,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $is_collect = 0;
        }else {
            $is_collect = 1;
        }
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $collect_num = $m_collect->countNum($map);
        $ret = $m_collect_count->field('nums')->where(array('res_id'=>$res_id))->find();
        $collect_num = $collect_num+intval($ret['nums']);
        //分享个数
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $share_num = $m_share->countNum($map);

        $data = array('is_collect'=>$is_collect,'collect_num'=>$collect_num,'share_num'=>$share_num);
        return $data;
    }

    private function handleFindlist($all_public,$openid,$type=0){
        $os = check_phone_os();
        if($os==1){
            $format_webp = '/format,webp';
        }else{
            $format_webp = '';
        }
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $default_avatar = 'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg';
        $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
        foreach($all_public as $key=>$v){
            if($type){
                $all_public[$key]['type'] = $type;
            }
            $all_public[$key]['title'] = '';
            if(empty($v['avatarUrl'])){
                $all_public[$key]['avatarUrl'] = $default_avatar;
            }
            if(empty($v['nickName'])){
                $all_public[$key]['nickName'] = '游客';
            }
            $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url, duration,resource_size";
            $where = array();
            $where['forscreen_id'] = $v['forscreen_id'];
            $pubdetail_info = $m_pubdetail->getWhere($fields, $where,'');
            if($v['res_type']==2){
                $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                $pubdetail_info[0]['filename'] = $filename[2];
                $tmp_arr = explode('.', $filename[2]);
                $pubdetail_info[0]['res_id']   = $tmp_arr[0];
                $pubdetail_info[0]['img_url'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_450,m_fast";
                $pubdetail_info[0]['duration'] = intval($pubdetail_info[0]['duration']);
            }else {
                foreach($pubdetail_info as $kk=>$vv){
                    $filename = explode('/', $vv['forscreen_url']);
                    $pubdetail_info[$kk]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[$kk]['res_id'] = $tmp_arr[0];
                    $pubdetail_info[$kk]['img_url'] = $vv['res_url']."?x-oss-process=image/resize,m_mfit,h_300,w_300$format_webp";
                }
            }
            $all_public[$key]['pubdetail'] = $pubdetail_info;
            $all_public[$key]['create_time'] = viewTimes(strtotime($v['create_time']));

            //获取是否收藏、分享个数、收藏个数、获取播放次数
            $rets = $this->getFindnums($openid,$v['forscreen_id'],2);
            $all_public[$key]['is_collect'] = $rets['is_collect'];
            $all_public[$key]['collect_num'] = $rets['collect_num'];
            $all_public[$key]['share_num']  = $rets['share_num'];
        }
        return $all_public;
    }
}
