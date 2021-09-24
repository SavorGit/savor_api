<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class ForscreenHistoryController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getList':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'page'=>1001,'is_speed'=>1000);
                break;
            case 'datalist':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'page'=>1001,'is_speed'=>1002);
                break;

        }
        parent::_init_();
    }
    public function getList(){
        $openid   = $this->params['openid'];
        $box_mac  = $this->params['box_mac'];
        $page     = $this->params['page'] ? intval($this->params['page']) :1 ;
        $is_speed = $this->params['is_speed'] ? intval($this->params['is_speed']) : 0;
        $cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;

        $pagesize = 10;
        $redis = SavorRedis::getInstance();
        $redis->select('5');
        $keys = $redis->keys($cache_key);
        $oss_host = 'http://'. C('OSS_HOST').'/';
        if(empty($keys)){
            $data = array();
        }else {
            $data = array();
            $list = $redis->lgetrange($cache_key, 0, -1);
            foreach($list as $key=>$v){
                $v = json_decode($v,true);
                if($is_speed==0 && $v['is_speed']==1){
                    continue;
                }
                $imgs = json_decode($v['imgs']);
                $tmp = array();
                $resource_size = 0;
                if(!empty($v['resource_size'])){
                    $resource_size = $v['resource_size'];
                }
                if($v['action']==2){
                    $tmp['resource_id'] = $v['resource_id'];
                    $tmp['resource_size'] = $resource_size;
                    $tmp['imgurl']      = $oss_host.$imgs[0]."?x-oss-process=video/snapshot,t_3000,f_jpg,w_90,m_fast";
                    $tmp['res_url']     = $oss_host.$imgs[0];
                    $tmp['res_type']    = 2;
                    $tmp['forscreen_url']= $imgs[0];
                    $imgs_arr = explode('/', $imgs[0]);
                    $tmp['filename'] = $imgs_arr[2];
                    $tmp['duration'] = secToMinSec(intval($v['duration']));
                    $data[$v['forscreen_id']]['res_type'] = 2;
                    $data[$v['forscreen_id']]['forscreen_id'] = $v['forscreen_id'];
                    $data[$v['forscreen_id']]['is_speed'] = $v['is_speed'];
                    $data[$v['forscreen_id']]['is_box_have'] = 0;
                    $data[$v['forscreen_id']]['resource_size'] = $resource_size;
                    $data[$v['forscreen_id']]['filename'] = $v['forscreen_id'];
                    $data[$v['forscreen_id']]['duration'] = $v['duration'];
                    $data[$v['forscreen_id']]['list'][0] = $tmp;
                    $data[$v['forscreen_id']]['create_time'] = viewTimes(intval($v['forscreen_id']/1000));
                }else if($v['action']==4){
                    $tmp['resource_id'] = $v['resource_id'];
                    $tmp['resource_size'] = $resource_size;
                    $tmp['imgurl']      = $oss_host.$imgs[0].'?x-oss-process=image/resize,p_20';;
                    $tmp['res_url']     = $oss_host.$imgs[0];
                    $tmp['res_type']    = 1;
                    $tmp['forscreen_char'] = $v['forscreen_char'];
                    $tmp['forscreen_url']= $imgs[0];
                    $imgs_arr = explode('/', $imgs[0]);
                    $tmp['filename'] = $imgs_arr[2];
                    if(isset($v['quality_type'])){
                        $tmp['quality_type'] = $v['quality_type'];
                    }else{
                        $tmp['quality_type'] = 3;
                    }
                    $data[$v['forscreen_id']]['res_type'] = 1;
                    $data[$v['forscreen_id']]['forscreen_id'] = $v['forscreen_id'];
                    $data[$v['forscreen_id']]['is_speed'] = $v['is_speed'];
                    $data[$v['forscreen_id']]['is_box_have'] = 0;
                    $data[$v['forscreen_id']]['list'][] = $tmp;
                    $data[$v['forscreen_id']]['create_time'] = viewTimes(intval($v['forscreen_id']/1000));
                    $data[$v['forscreen_id']]['music_id'] = intval($v['music_id']);
                    $data[$v['forscreen_id']]['music_oss_addr'] = !empty($v['music_oss_addr']) ? $v['music_oss_addr'] :'';
                }
            }
            foreach($data as $key=>$v){
                $data[$key]['res_nums'] = count($v['list']);
            }
            sortArrByOneField($data, 'forscreen_id',true);
            $data = array_slice($data, 0,$pagesize*$page);
        }
        $this->to_back($data);
    }

    public function datalist(){
        $openid   = $this->params['openid'];
        $box_mac  = $this->params['box_mac'];
        $page     = intval($this->params['page']);
        $is_speed = $this->params['is_speed'] ? intval($this->params['is_speed']) : 0;
        $pagesize = 100;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $user_info = $m_user->getOne('id,avatarUrl,nickName', array('openid'=>$openid,'status'=>1));
        $avatarUrl = $user_info['avatarUrl'];
        $nickName = $user_info['nickName'];

        $m_box = new \Common\Model\BoxModel();
        $bwhere = array('box.mac'=>$box_mac,'box.state'=>1,'box.flag'=>0);
        $res_box = $m_box->getBoxByCondition('hotel.name as hotel_name',$bwhere);
        $hotel_name = $res_box[0]['hotel_name'];


        $redis = SavorRedis::getInstance();
        $redis->select('5');
        $cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
        $res_history_cache = $redis->lgetrange($cache_key, 0, -1);
        $oss_host = 'http://'. C('OSS_HOST').'/';

        $history_forscreen_data = array();
        $history_forscreen_ids = array();
        if(!empty($res_history_cache)){
            $data = array();
            foreach($res_history_cache as $key=>$v){
                $v = json_decode($v,true);
                if($is_speed==0 && $v['is_speed']==1){
                    continue;
                }
                $imgs = json_decode($v['imgs']);
                $resource_size = 0;
                if(!empty($v['resource_size'])){
                    $resource_size = $v['resource_size'];
                }
                $oss_info = pathinfo($imgs[0]);
                $filename = $oss_info['basename'];
                $forscreen_id = $v['forscreen_id'];
                $list_info = array('resource_id'=>$v['resource_id'],'resource_size'=>$resource_size,'imgurl'=>$oss_host.$imgs[0],
                    'res_url'=>$oss_host.$imgs[0],'forscreen_url'=>$imgs[0],'filename'=>$filename,
                );
                if($v['action']==2){
                    $list_info['imgurl'] = $list_info['imgurl']."?x-oss-process=video/snapshot,t_3000,f_jpg,w_90,m_fast";
                    $list_info['res_type'] = 2;
                    $list_info['duration'] = intval($v['duration']);

                    $data[$forscreen_id]['forscreen_id'] = $forscreen_id;
                    $data[$forscreen_id]['is_speed'] = $v['is_speed'];
                    $data[$forscreen_id]['is_box_have'] = 0;
                    $data[$forscreen_id]['create_time'] = intval($forscreen_id/1000);
                    $data[$forscreen_id]['avatarUrl'] = $avatarUrl;
                    $data[$forscreen_id]['nickName'] = $nickName;
                    $data[$forscreen_id]['hotel_name'] = $hotel_name;
                    $data[$forscreen_id]['ctype'] = 1;
                    $whtype = 0;//0默认 1横版 2竖版
                    if(!empty($v['width']) && !empty($v['height'])){
                        if($v['width']>$v['height']){
                            $whtype = 1;
                        }else{
                            $whtype = 2;
                        }
                    }
                    $data[$forscreen_id]['whtype'] = $whtype;
                    $data[$forscreen_id]['res_type'] = 2;
                    $data[$forscreen_id]['list'][] = $list_info;
                }elseif($v['action']==4){
                    $list_info['imgurl'] = $list_info['imgurl']."?x-oss-process=image/resize,p_20";
                    $list_info['res_type'] = 1;
                    $list_info['forscreen_char']=$v['forscreen_char'];

                    $data[$forscreen_id]['forscreen_id'] = $forscreen_id;
                    $data[$forscreen_id]['is_speed'] = $v['is_speed'];
                    $data[$forscreen_id]['is_box_have'] = 0;
                    $data[$forscreen_id]['create_time'] = intval($forscreen_id/1000);
                    $data[$forscreen_id]['avatarUrl'] = $avatarUrl;
                    $data[$forscreen_id]['nickName'] = $nickName;
                    $data[$forscreen_id]['hotel_name'] = $hotel_name;
                    $data[$forscreen_id]['ctype'] = 1;
                    $data[$forscreen_id]['whtype'] = 0;
                    $data[$forscreen_id]['res_type'] = 1;
                    $data[$forscreen_id]['music_id'] = intval($v['music_id']);
                    $data[$forscreen_id]['music_oss_addr'] = !empty($v['music_oss_addr']) ? $v['music_oss_addr'] :'';
                    $data[$forscreen_id]['list'][] = $list_info;
                }
            }
            foreach($data as $key=>$v){
                $data[$key]['res_nums'] = count($v['list']);
                $history_forscreen_ids[]=$key;
                $history_forscreen_data[] = $data[$key];
            }
        }
        $m_public = new \Common\Model\Smallapp\PublicModel();
        $fields= 'a.id,a.forscreen_id,a.res_type,a.res_nums,a.public_text,a.create_time,hotel.name hotel_name,user.avatarUrl,user.nickName';
        $where = array('a.openid'=>$openid,'a.status'=>array('in',array(1,2)),'box.state'=>1,'box.flag'=>0,'hotel.state'=>1,'hotel.flag'=>0);
        if(!empty($history_forscreen_ids)){
            $where['a.forscreen_id'] = array('not in',$history_forscreen_ids);
        }
        $order = 'a.id desc';
        $res_public = $m_public->getList($fields, $where, $order,'');
        $public_data = array();
        if(!empty($res_public)){
            $m_pubdetail = new \Common\Model\Smallapp\PubdetailModel();
            $oss_host = 'http://'. C('OSS_HOST').'/';
            foreach ($res_public as $v){
                $fields = "concat('".$oss_host."',`res_url`) res_url, res_url as forscreen_url,duration,resource_size,width,height";
                $pubdetail_info = $m_pubdetail->getWhere($fields, array('forscreen_id'=>$v['forscreen_id']),'');
                $whtype = 0;//0默认 1横版 2竖版
                if($v['res_type']==2){
                    if($pubdetail_info[0]['width']>$pubdetail_info[0]['height']){
                        $whtype = 1;
                    }else{
                        $whtype = 2;
                    }
                    $filename = explode('/', $pubdetail_info[0]['forscreen_url']);

                    $pubdetail_info[0]['filename'] = $filename[2];
                    $tmp_arr = explode('.', $filename[2]);
                    $pubdetail_info[0]['resource_id']   = $tmp_arr[0];
                    $pubdetail_info[0]['imgurl'] = $pubdetail_info[0]['res_url']."?x-oss-process=video/snapshot,t_5000,f_jpg,w_450,m_fast";
                    $pubdetail_info[0]['duration'] = intval($pubdetail_info[0]['duration']);
                }else {
                    foreach($pubdetail_info as $kk=>$vv){
                        $filename = explode('/', $vv['forscreen_url']);
                        $pubdetail_info[$kk]['filename'] = $filename[2];
                        $tmp_arr = explode('.', $filename[2]);
                        $pubdetail_info[$kk]['resource_id'] = $tmp_arr[0];
                        $pubdetail_info[$kk]['imgurl'] = $vv['res_url']."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                    }
                }

                $create_time = strtotime($v['create_time']);
                $public_data[] = array('forscreen_id'=>$v['forscreen_id'],'create_time'=>$create_time,
                    'avatarUrl'=>$avatarUrl,'nickName'=>$nickName,
                    'hotel_name'=>$v['hotel_name'],'ctype'=>2,'res_type'=>$v['res_type'],'whtype'=>$whtype,
                    'list'=>$pubdetail_info,'res_nums'=>count($pubdetail_info)
                );
            }
        }

        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'id,imgs,resource_name,resource_type,resource_size,md5_file,hotel_name,create_time';
        $where = array('openid'=>$openid,'action'=>30,'save_type'=>2,'file_conversion_status'=>1,'del_status'=>1,'small_app_id'=>1);
        $where['md5_file'] = array('neq','');
        $order = 'id desc';
        $res_file = $m_forscreen->getForscreenFileList($fields,$where,$order);
        $file_data = array();
        if(!empty($res_file)){
            $img_host = 'https://'.C('OSS_HOST').'/Html5/images/mini-push/pages/forscreen/forfile/';
            $file_ext_images = C('SAPP_FILE_FORSCREEN_IMAGES');
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $cache_key = C('SAPP_FILE_FORSCREEN');
            foreach ($res_file as $v){
                $imgs = json_decode($v['imgs'],true);
                $file_info = pathinfo($imgs[0]);
                $file_type = $file_info['extension'];
                $res_cache = $redis->get($cache_key.':'.$v['md5_file']);
                $page_num = 0;
                if(!empty($res_cache)) {
                    $imgs = json_decode($res_cache, true);
                    $page_num = count($imgs);
                }
                $file_size = formatBytes($v['resource_size']);
                $ext_img = $img_host.$file_ext_images[strtolower($file_type)];

                $info = array('forscreen_id'=>$v['id'],'create_time'=>strtotime($v['create_time']),'avatarUrl'=>$avatarUrl,
                    'nickName'=>$nickName,'hotel_name'=>$v['hotel_name'],'ctype'=>3,
                    'file_type'=>strtoupper($file_type),'resource_name'=>$v['resource_name'],
                    'file_name'=>$file_info['basename'],'page_num'=>$page_num,'file_size'=>$file_size,'ext_img'=>$ext_img
                );
                $file_data[] = $info;
            }
        }

        $all_data = array_merge($history_forscreen_data,$public_data,$file_data);

        sortArrByOneField($all_data, 'create_time',true);
        $offset = ($page-1)*$pagesize;
        $data = array_slice($all_data, $offset,$pagesize);
        foreach ($data as $k=>$v){
            $data[$k]['create_time'] = viewTimes($v['create_time']);
        }
        $this->to_back($data);
    }

    public function deldata(){
        $openid = $this->params['openid'];
        $forscreen_id = $this->params['forscreen_id'];
        $box_mac  = $this->params['box_mac'];
        $ctype = $this->params['ctype'];//内容类型1投屏内容 2公开内容 3投屏文件

        $m_user = new \Common\Model\Smallapp\UserModel();
        $res = $m_user->getOne('id',array('openid'=>$openid,'status'=>1),'id desc');
        if(empty($res)){
            $this->to_back(92010);
        }

        switch ($ctype){
            case 1:
                $redis = SavorRedis::getInstance();
                $redis->select('5');
                $cache_key = C('SAPP_HISTORY_SCREEN').$box_mac.":".$openid;
                $res_history_cache = $redis->lgetrange($cache_key, 0, -1);
                if(!empty($res_history_cache)){
                    $is_del = 0;
                    $is_share = 0;
                    foreach ($res_history_cache as $k=>$v){
                        $vinfo = json_decode($v,true);
                        if($vinfo['forscreen_id']==$forscreen_id){
                            $is_del = 1;
                            if($vinfo['is_share']==1){
                                $is_share = 1;
                            }
                            $index = $k;
                            $redis->lset($cache_key,$index,'del');
                        }
                    }
                    if($is_del){
                        $redis->lrem($cache_key,'del',0);
                        $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
                        $where = array('openid'=>$openid,'forscreen_id'=>$forscreen_id);
                        $m_forscreenrecord->updateInfo($where,array('del_status'=>2,'update_time'=>date('Y-m-d H:i:s')));
                        if($is_share){
                            $m_public = new \Common\Model\Smallapp\PublicModel();
                            $m_public->updateInfo($where,array('status'=>0));
                        }
                    }
                }
                break;
            case 2:
                $where = array('openid'=>$openid,'forscreen_id'=>$forscreen_id);
                $m_public = new \Common\Model\Smallapp\PublicModel();
                $m_public->updateInfo($where,array('status'=>0));
                break;
            case 3:
                $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
                $res_forscreen = $m_forscreenrecord->getInfo(array('id'=>$forscreen_id));
                if(!empty($res_forscreen)){
                    $m_forscreenrecord->updateInfo(array('id'=>$forscreen_id),array('del_status'=>2,'update_time'=>date('Y-m-d H:i:s')));
                    $where = array('openid'=>$openid,'action'=>30,'save_type'=>2,'file_conversion_status'=>1,'md5_file'=>$res_forscreen['md5_file']);
                    $m_forscreenrecord->updateInfo($where,array('del_status'=>2,'update_time'=>date('Y-m-d H:i:s')));
                }
                break;
        }
        $this->to_back(array());
    }
}