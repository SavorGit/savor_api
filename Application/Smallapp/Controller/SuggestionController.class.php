<?php
/**
 * @desc   小程序意见反馈
 * @author zhang.yingtao
 * @since  2018-08-27
 */

namespace Smallapp\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class SuggestionController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'pushInfo':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'ideas'=>1001,'mobile'=>1000);
            break;
        }
    }
    /**
     * @desc 接受用户意见反馈 
     */
    public function pushInfo(){
        $openid = $this->params['openid'];
        $box_mac= $this->params['box_mac'];
        $ideas  = $this->params['ideas'];
        $mobile = $this->params['mobile'] ? $this->params['mobile'] : '';
        $data = array();
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['ideas']  = $ideas;
        $data['mobile'] = $mobile;
        $data['create_time'] = date('Y-m-d H:i:s');
        $m_smallapp_suggestion = new \Common\Model\Smallapp\SuggestionModel(); 
        $ret = $m_smallapp_suggestion->addInfo($data,$type = 1);
        if($ret){
            $this->to_back(10000);
        }else {
            $this->to_back(91011);
        }
    }
}