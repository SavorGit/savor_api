<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class CommentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'subComment':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'score'=>1001,'content'=>1001,'staff_id'=>1001);
                break;
            
        }
        parent::_init_();
   
    }
    public function subComment(){
        $openid = $this->params['openid'];
        
        $score  = intval($this->params['score']);
        $content = $this->params['content'];
        $staff_id = $this->params['staff_id'];
        if($score>5){
            $this->to_back(90155);
        }
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = [];
        $where['id'] = $staff_id;
        $where['status'] = 1;
        $where['hotel_id'] = array('neq',0);
        $where['room_id']  = array('neq',0);
        $res_staff = $m_staff->getInfo($where);
        if(empty($res_staff)){
            $this->to_back(90156);
        }
        
        
        
        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        
        if(!empty($user_info)){
            $m_comment = new \Common\Model\Smallapp\CommentModel();
            $data = array();
            
            $data['staff_id']= $staff_id;
            $data['user_id'] = $user_info['id'];
            $data['score']   = $score;
            $data['content']  = $content;
            $data['status']  = 1;
            
            
            $ret = $m_comment->add($data);
            if($ret){
                $this->to_back(10000);
            }else {
                $this->to_back(90154);
            }
        }else{
            $this->to_back(90116);
        }
        
        
    }
}