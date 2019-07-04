<?php
namespace Box\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class BoxLogController extends CommonController{ 
    var $box_log_arr;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'isUploadLog':
                $this->is_verify =1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_();
        $this->box_log_arr = array(array('box_mac'=>'00226D655202','log_type'=>0),
                                   array('box_mac'=>'FCD5D900B44A','log_type'=>0),
                                   array('box_mac'=>'00226D584178','log_type'=>0),
                                   array('box_mac'=>'FCD5D900B3BD','log_type'=>0),
                                   array('box_mac'=>'00226D583D92','log_type'=>0),
                                   array('box_mac'=>'00226D583CF4','log_type'=>2),
        ); 
    }
    public function isUploadLog(){
        $box_mac = $this->params['box_mac'];
        $box_arr = array_column($this->box_log_arr, 'box_mac');
        $data = array();
        if(in_array($box_mac, $box_arr)){
            foreach($this->box_log_arr as $key=>$v){
                if($box_mac == $v['box_mac']){
                    $data['log_type'] = $v['log_type'];
                    break;
                }
            }
            $this->to_back($data);
        }else {
            $this->to_back(70001);
        }
        
    }
}