<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController;
class PlaytimeController extends CommonController{

    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getTimeList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }

    public function getTimeList(){
        $res_data = array(
            array('name'=>'单次','value'=>0,'checked'=>true),
            array('name'=>'20分钟','value'=>1200000,'checked'=>false),//单位为毫秒
            array('name'=>'40分钟','value'=>2400000,'checked'=>false),
            array('name'=>'60分钟','value'=>3600000,'checked'=>false),
        );
        $this->to_back($res_data);
    }
    
}