<?php
namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class CollectionController extends BaseController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch (ACTION_NAME){
            case 'getMyCollection':
                $this->is_verify = 0;
                $this->valid_fields = array('collecTime'=>1000);
                break;
            case 'addMyCollection':
                $this->is_verify = 1;
                $this->valid_fields = array('dailyid'=>1001);
                break;
            case 'isCollected':
                $this->is_verify = 1;
                $this->valid_fields = array('dailyid'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getMyCollection(){
        $update_time =  $this->params['collecTime'];
        $m_daily_collection = new \Common\Model\DailyCollectionModel();
        $device_id = $this->traceinfo['deviceid'];
        $where =" a.device_id='".$device_id."' and a.state=1";
        if(!empty($update_time)){
            $where .= " and a.update_time<'".$update_time."'";
        }
        $order = ' a.update_time desc';
        $limit =' 20';
        $data = $m_daily_collection->getList('b.id dailyid,a.update_time as collecTime,b.title,b.media_id imgUrl,c.name sourceName,e.bespeak_time',$where,$order,$limit);
        foreach($data as $key=>$v){
            $data[$key]['imgUrl'] = $this->getOssAddrByMediaId($v['imgUrl']);
            $data[$key]['bespeak_time'] = date('Y-m-d',strtotime($v['bespeak_time']));
        }
        $this->to_back($data);
    }
    /**
     * @desc 收藏、取消收藏
     */
    public function addMyCollection(){
        $dailyid = $this->params['dailyid'];    //文章id
        $m_daily_content = new \Common\Model\DailyContentModel();
        $info = $m_daily_content->getOneById('id',$dailyid);
        if(empty($info)){
            $this->to_back(40001);
        }
        $traceinfo = $this->traceinfo;
        $deviceid  = $traceinfo['deviceid'];
        if(empty($deviceid)){
            $this->to_back(18001);
        }
        $m_daily_collection = new \Common\Model\DailyCollectionModel();
        $where  = array();
        $where['dailyid'] = $dailyid;
        $where['device_id']= $deviceid;
        $info = $m_daily_collection->getInfo('id,state',$where);
        if(empty($info)){//未收藏
            $data = array();
            $data['dailyid']   = $dailyid;
            $data['device_id'] = $deviceid;
            $data['state']     =1;
            $ret = $m_daily_collection->addInfo($data);
            if($ret){
                $back_num = 10000;  //收藏成功
            }else {
                $back_num = 40002;  //收藏失败
            }
        }else {
            if($info['state'] ==0){//未收藏
                $data = array();
                $data['state'] = 1;
                $data['update_time'] = date('Y-m-d H:i:s');
                $ret = $m_daily_collection->editinfo($where,$data);
                if($ret){
                    $back_num = 10000;  //收藏成功
                }else {
                    $back_num = 40002;  //收藏失败
                }
            }else {//已收藏
                $data = array();
                $data['state'] = 0;
                $data['update_time'] = date('Y-m-d H:i:s');
                $ret = $m_daily_collection->editinfo($where,$data);
                if($ret){
                    $back_num = 10000;  //取消收藏成功
                }else {
                    $back_num = 40003;  //取消收藏失败
                } 
            }
        }
        $this->to_back($back_num);
    }
    /**
     * @desc 是否收藏过该文章
     */
    public function isCollected(){
        $dailyid = $this->params['dailyid'];  //文章id
        $deviceid = $this->traceinfo['deviceid'];
        if(empty($deviceid)){
            $this->to_back(18001);
        }
        $m_daily_collection = new \Common\Model\DailyCollectionModel();
        $where  = array();
        $where['dailyid'] = $dailyid;
        $where['device_id']= $deviceid;
        $info = $m_daily_collection->getInfo('state',$where);
        if(empty($info)){
            $data['state'] = 0;
        }else {
            $data['state'] = $info['state']; 
        }
        $this->to_back($data);
    }
}