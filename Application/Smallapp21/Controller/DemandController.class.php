<?php
namespace Smallapp21\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\SavorRedis;
class DemandController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getList':
                 $this->is_verify = 1;
                 $this->valid_fields = array('page'=>1001,'openid'=>1001);
            break;
            case 'getProgramList':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'openid'=>1000);
            break;
        }
        parent::_init_();
    }
    public function getList(){
        $oss_host = "http://".C('OSS_HOST').'/';

        $m_home = new \Common\Model\HomeModel();
        $size = 10;
        $page = $this->params['page'] ?  $this->params['page']: 1;
        $openid = $this->params['openid'] ;
        $fields = "content.id,content.title,content.duration,
                   CONCAT('".$oss_host."',content.`img_url`) img_url,
                   CONCAT('".$oss_host."',media.oss_addr) tx_url,'1' as type";
        $where = array();
        $where['a.state'] = 1;
        $order = 'a.sort_num asc';
        $limit = " limit 0,".$page*$size;
        $data = $m_home->getWhere($fields, $where, $order, $limit);
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        foreach($data as $key=>$v){
            $map = array();
            $map['openid']=$openid;
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $is_collect = $m_collect->countNum($map);
            if(empty($is_collect)){
                $data[$key]['is_collect'] = 0;
            }else {
                $data[$key]['is_collect'] = 1;
            }
            $map = array();
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $collect_num = $m_collect->countNum($map);
            $data[$key]['collect_num'] = $collect_num;
            //分享个数
            $map = array();
            $map['res_id'] =$v['id'];
            $map['type']   = 1;
            $map['status'] = 1;
            $share_num = $m_share->countNum($map);
            $data[$key]['share_num'] = $share_num;
            $tx_url = $v['tx_url'];
            $tx_url_arr = explode('?', $tx_url);
            $data[$key]['tx_url'] = $tx_url_arr[0];
        }
        $this->to_back($data);
    }
    /**
     * @desc 获取小程序主节目单
     */
    public function getProgramList(){
        $oss_host = "http://".C('OSS_HOST').'/';
        $pagesize = 10;
        $page = $this->params['page'] ?  $this->params['page']: 1;
        $openid = $this->params['openid'] ;
        
        //获取最新一期设置为小程序的节目单
        $m_program_list =  new \Common\Model\ProgramMenuListModel();
        $fields = "";
        $where  = array();
        $where['is_small_app'] = 1;
        $order = " id desc";
        $program_info = $m_program_list->getInfo('id', $where, $order);
        $menu_id = $program_info['id'];
        
        $m_program_menu_item = new \Common\Model\ProgramMenuItemModel();
        
        $fields = 'ads.id,ads.name title,ads.img_url,ads.duration,media.oss_addr tx_url';
        $where = array();
        $where['a.menu_id'] = $menu_id;
        $where['a.type']    = 2;
        $where['media.id']     = array('neq',17614);
        $order  = ' a.sort_num asc';
        $menu_item_arr = $m_program_menu_item->alias('a')
                            ->join('savor_ads ads on a.ads_id = ads.id','left')
                            ->join('savor_media media on ads.media_id =media.id ','left')
                            ->field($fields)
                            ->where($where)
                            ->order($order)
                            ->select();
                          
        $offset = $page*$pagesize;
        $menu_item_arr = array_slice($menu_item_arr, 0,$offset);
        foreach($menu_item_arr as $key=>$v){
            $v['img_url'] = $v['img_url']? $v['img_url'] :'media/resource/EDBAEDArdh.png';
            $menu_item_arr[$key]['img_url'] = $oss_host.$v['img_url'];
            $res_arr = explode('/', $v['tx_url']);
            $menu_item_arr[$key]['filename'] = $res_arr[2];
            $menu_item_arr[$key]['tx_url']= $oss_host.$v['tx_url'];
            $menu_item_arr[$key]['duration']= secToMinSec($v['duration']);
            $menu_item_arr[$key]['type'] = '3';
            
            //获取是否收藏、分享个数、收藏个数
            $rets = $this->getPubShareInfo($openid,$v['id'],3);
            $menu_item_arr[$key]['is_collect'] = $rets['is_collect'];
            $menu_item_arr[$key]['collect_num']= $rets['collect_num'];
            $menu_item_arr[$key]['share_num']  = $rets['share_num'];
        }
        $this->to_back($menu_item_arr);
    }
    private function getPubShareInfo($openid,$ads_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $map = array();
        $map['openid']=$openid;
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $data['is_collect'] = 0;
        }else {
            $data['is_collect'] = 1;
        }
        $map = array();
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $collect_num = $m_collect->countNum($map);
        $data['collect_num'] = $collect_num;
        //分享个数
        $map = array();
        $map['res_id'] =$ads_id;
        $map['type']   = $type;
        $map['status'] = 1;
        $share_num = $m_share->countNum($map);
        $data['share_num'] = $share_num;
        return $data;
    }
}