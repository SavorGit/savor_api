<?php
namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class KeywordsController extends BaseController{
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch(ACTION_NAME){
            case 'getAllKeywords':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    /**
     * @desc 获取所有关键词
     */
    public function getAllKeywords(){
        
        $data = array();
        $m_daily_home = new \Common\Model\DailyContentModel();
        $result = $m_daily_home->getTodayKeyWords();     //获取今天发布的知享文章的关键词
        
        if(empty($result)){
            $this->to_back('40004');
        }
        $list = array();
        
        foreach($result as $v){
            $list[] = $v['keyword'];
        }
        $data['list'] = $list;
        $data['put_time'] = date('Y-m-d');
        $this->to_back($data);
    }
}