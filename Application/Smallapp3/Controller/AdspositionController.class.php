<?php
namespace Smallapp3\Controller;
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
        $position = intval($this->params['position']);
        $fields = 'id,name,media_id,linkcontent,clicktype,appid,position';
        $where = array('status'=>1);
        if($position){
            $where['position'] = $position;
        }
        $orderby = 'id desc';
        $m_adsposition = new \Common\Model\Smallapp\AdspositionModel();
        $res_positions = $m_adsposition->getDataList($fields,$where,$orderby);

        $result = array();
        if(!empty($res_positions)){
            $m_media = new \Common\Model\MediaModel();
            foreach ($res_positions as $k=>$v){
                $res_media = $m_media->getMediaInfoById($v['media_id']);
                $v['oss_addr'] = $res_media['oss_addr'];
                unset($v['media_id']);
		        $result[] = $v;
            }
        }
        $this->to_back($result);
    }
}
