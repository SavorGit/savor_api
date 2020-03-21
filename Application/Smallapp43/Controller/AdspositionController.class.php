<?php
namespace Smallapp43\Controller;
use \Common\Controller\CommonController;
class AdspositionController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAdspositionList':
                $this->is_verify = 1;
                $this->valid_fields = array('position'=>1001);
                break;
        }
        parent::_init_();
    }

    /**
     * @desc 获取广告位列表
     */
    public function getAdspositionList(){
        $position = $this->params['position'];
        $fields = 'id,name,media_id,linkcontent,clicktype,appid,position,bindtap';
        $where = array('status'=>1);
        $orderby = 'sort desc,id desc';
        $m_adsposition = new \Common\Model\Smallapp\AdspositionModel();
        
        $result = array();
        if($position && strstr($position, ',')){
            
            $where['position'] = array('in',$position);
            $res_positions = $m_adsposition->getDataList($fields,$where,$orderby);
            if(!empty($res_positions)){
                $m_media = new \Common\Model\MediaModel();
                foreach ($res_positions as $k=>$v){
                    $res_media = $m_media->getMediaInfoById($v['media_id']);
                    $v['oss_addr'] = $res_media['oss_addr'];
                    unset($v['media_id']);
                    $result[$v['position']][] = $v;
                }
            }
        }else {
            $where['position'] = $position;
            $order =" a.order desc";
                        
            $res_positions = $m_adsposition->getDataList($fields,$where,$orderby);
            
            if(!empty($res_positions)){
                $m_media = new \Common\Model\MediaModel();
                foreach ($res_positions as $k=>$v){
                    $res_media = $m_media->getMediaInfoById($v['media_id']);
                    $v['oss_addr'] = $res_media['oss_addr'];
                    unset($v['media_id']);
                    $result[] = $v;
                }
            }
            
        }
        $this->to_back($result);
        
    }
}
