<?php
/**
 * @desc 餐厅端1.2-宣传片
 * @author zhang.yingtao
 * @since  20171204
 */
namespace Dinnerapp\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class AdvController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'doLogin':
                $this->is_verify = 1;
                $this->valid_fields = array('hotel_id'=>'1001');
                break;
        }
        parent::_init_();
    }
    public function getAdvList(){
        $hotel_id = $this->params['hotel_id'];
        $m_ads = new \Common\Model\AdsModel();
        $where = array();
        $where['a.hotel_id'] = $hotel_id;
        $where['a.type'] = 3;
        $where['a.state']= 1;
        $fields = "b.id,a.name chinese_name,b.oss_addr name,b.md5 ,'easyMd5' AS `md5_type`,b.oss_addr AS oss_path,
                   b.duration,b.surfix,a.img_url";
        $list = $m_ads->getAdsList( $fields,$where);
        $oss_host = C('TASK_REPAIR_IMG');
        foreach($list as $key=>$v){
            $list[$key]['oss_path'] = $oss_host.'/'.$v['oss_path'];
            $list[$key]['img_url']  = $oss_host.'/'.$v['img_url'];
        }
        $this->to_back($list);
    }
    
}