<?php
namespace Smallsale14\Controller;
use \Common\Controller\CommonController as CommonController;

class CollectionController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelTastList':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>1001,'openid'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }
    public function getHotelTastList(){
        $hotel_id = intval($this->params['hotel_id']);
        $openid   = trim($this->params['openid']);
        $page     = $this->params['page'] ? $this->params['page'] : 1;
        $this->checkUser($openid,$hotel_id);
        
        $m_task_hotel = new \Common\Model\Integral\TaskHotelModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $fields = "task.name ,concat('".$oss_host."',media.`oss_addr`) img_url,task.desc";
        $where = [];
        $where['a.hotel_id'] = $hotel_id;
        $where['task.state'] = 1;
        $where['task.flag']  = 1;
        $pagesize = 20;
        $start = ($page - 1) * $pagesize;
        $order = 'task.id asc';
        $task_list = $m_task_hotel->alias('a')
                                  ->join('savor_integral_task task on a.task_id=task.id','left')
                                  ->join('savor_media media on task.media_id=media.id','left')
                                  ->field($fields)
                                  ->where($where)
                                  ->order($order)
                                  ->limit($start,$pagesize)
                                  ->select();
        $this->to_back($task_list);
    }
    
    private function checkUser($openid,$hotel_id){
        
    }
}