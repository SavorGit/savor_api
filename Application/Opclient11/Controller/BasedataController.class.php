<?php
namespace Opclient11\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class BasedataController extends BaseController{ 
    private $option_user_skill_arr;
    private $task_emerge;
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getAreaList':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
        $this->option_user_skill_arr = C('OPTION_USER_SKILL_ARR');
        $this->task_emerge = C('TASK_EMERGE_ARR');
    }
    public function getAreaList(){
        $m_area = new \Common\Model\AreaModel();
        $list = $m_area->getHotelAreaList();
        array_unshift( $list,array('id'=>9999,'region_name'=>'全国'));
        $this->to_back($list);
    }
    /**
     * @desc 任务类型
     */
    public function getTaskTypeList(){
        
        $option_user_skill_arr = $this->option_user_skill_arr;
        $data = array();
        
        foreach($option_user_skill_arr as $key=>$v){
            switch($key){
                case '3':
                   $data[$key]['bref'] = '信';
                   break;
                case '4':
                   $data[$key]['bref'] = '网';
                   break;
               case '6':
                   $data[$key]['bref'] = '安';
                   break;
               case '7':
                   $data[$key]['bref'] = '修';
                   break;
            }
            $data[$key]['type_name'] = $v;
        }
        $this->to_back($data);
        
    }
    /**
     * @desc 任务紧急程度类型
     */
    public function taskEmergeList(){
        $this->to_back($this->task_emerge);
    }
}