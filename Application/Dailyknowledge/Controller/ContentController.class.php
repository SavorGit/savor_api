<?php
namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class ContentController extends BaseController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch (ACTION_NAME){
            case 'getAllList':
                $this->is_verify = 0;
                $this->valid_fields = array('bespeak_time'=>1000);
                break;
            case 'getDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('dailyid'=>1001);
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取所有文章内容列表
     */
    public function getAllList(){
        $bespeak_time = $this->params['bespeak_time'];
        $fields = 'a.id dailyid, a.media_id imgUrl,a.title,d.name sourceName,c.bespeak_time';
        $where  = ' 1=1';
        if(!empty($bespeak_time)){
            $where .=" and c.bespeak_time<'".$bespeak_time."'";
        }else {
            //$where .= " and to_days(c.`bespeak_time`) = to_days(now())";
            //$where .=" and to_days(c.`bespeak_time`) <= to_days(now())";
            $where .= " and c.bespeak_time<='".date('Y-m-d H:i:s')."'";
        }
        $order =" c.bespeak_time desc,b.sort_num asc";
        $limit = 20;
        
        $m_daily_content = new \Common\Model\DailyContentModel();
        $list = $m_daily_content->getList($fields,$where,$order,$limit);
        $data = array();
        if(!empty($list)){
            
            $count = count($list);
            $bespeak_time = $list[$count -1]['bespeak_time'];
            $data['bespeak_time'] = date('Y-m-d',strtotime($bespeak_time));
            foreach($list as $key=>$v){
                $list[$key]['imgUrl'] = $this->getOssAddrByMediaId($v['imgUrl']);
                $list[$key]['bespeak_time'] = date('Y-m-d',strtotime($v['bespeak_time']));
                $list[$key]['sourceName'] = '';
            }
            
            $data['list'] = $list;
        }
        $this->to_back($data);
    }
    public function getDetail(){
        $id = $this->params['dailyid'];   //知享文章id
        $m_daily_content = new \Common\Model\DailyContentModel();
        $fields = 'a.id dailyid,a.title,a.media_id imgUrl,a.title,a.desc,c.name sourceName,b.dailytype,b.stext,b.spictureid spicture,e.bespeak_time,a.artpro';
        $info = $m_daily_content->getDetailById($fields,$id);
        if(empty($info)){
            $this->to_back(40001);
        }
        $details = array();
        foreach($info as $key=>$v){
            $details[$key]['dailytype'] = $v['dailytype'];
            if($v['dailytype']==1){
                $details[$key]['stext'] = $v['stext'];
            } else if($v['dailytype'] ==3){
                $details[$key]['spicture'] = $this->getOssAddrByMediaId($v['spicture']);
            }
        }
        $data['artpro'] = $info[0]['artpro'];
        $data['dailyid'] = $info[0]['dailyid'];
        $data['title'] = $info[0]['title'];
        $data['imgUrl']  = $this->getOssAddrByMediaId($info[0]['imgUrl']) ;
        //$data['sourceName'] = $info[0]['sourceName'];
        $data['sourceName'] = '';
        $data['desc'] = $info[0]['desc'];
        $data['bespeak_time'] = date('Y-m-d',strtotime($v['bespeak_time']));
        $data['share_url'] = C('CONTENT_HOST').'admin/dailycontentshow/'.$id;
        //是否收藏
        $m_daily_collection = new \Common\Model\DailyCollectionModel();
        $where = array();
        $where['dailyid'] = $id;
        $where['device_id']= $this->traceinfo['deviceid'];
        $where['state']  =1;
        $coinfo = $m_daily_collection->getInfo('id',$where);
        if(empty($coinfo)){
            $data['is_collect'] = 0;
        }else {
            $data['is_collect'] = 1;
        }
        $data['details'] =$details;
        $this->to_back($data);
    }
}