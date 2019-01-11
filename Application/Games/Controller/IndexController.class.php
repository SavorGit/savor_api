<?php
namespace Games\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class IndexController extends CommonController{
    /**
     * 构造函数
     */
    function _init_(){
       switch(ACTION_NAME){
           case 'isViewGame':
               $this->is_verify = 1;
               $this->valid_fields = array('game_id'=>1001);
               break;
           case 'gameList':
               $this->is_verify = 1;
               $this->valid_fields = array('page'=>1001);
               break;
           case 'getGameInfo':
               $this->is_verify = 1;
               $this->valid_fields = array('game_id'=>1001);
               break;
       } 
       parent::_init_();
    }
    /**
     * @desc 是否显示游戏banner
     */
    public function isViewGame(){
        $game_id = $this->params['game_id'];
        $m_games = new \Common\Model\Smallapp\GamesModel();
        $where['id'] = $game_id;
        $data = $m_games->getOne('status', $where);
        $data['img_url'] = 'http://oss.littlehotspot.com/media/resource/6h5RQdmAKn.jpg';
        $this->to_back($data);
    }
    /**
     * @desc 游戏列表
     */
    public function gameList(){
        $page = $this->params['page'] ? intval($this->params['page']) : 1;
        $pagesize = 10;
        $offset = ($page-1) * $pagesize;
        
        $limit = "limit $offset,$pagesize";
        $m_games = new \Common\Model\Smallapp\GamesModel();
        $oss_host = 'http://'. C('OSS_HOST').'/';
        $fields = "a.id as game_id,a.name game_name,concat('".$oss_host."',m.`oss_addr`) img_url";
        $where['status'] = 1;
        $order = 'a.sort_order desc';
        $list = $m_games->getWhere($fields, $where, $order, $limit);
        $this->to_back($list);
        
    }
    /**
     * @desc 获取游戏详情
     */
    public function getGameInfo(){
        $id = $this->params['game_id'];
        $m_games = new \Common\Model\Smallapp\GamesModel();
        $where = array();
        $where['id'] = $id;
        $where['status'] = 1;
        $fields = 'name,game_url,game_m_url';
        $game_info = $m_games->getOne($fields, $where);
        if(empty($game_info)){
            $this->to_back(90114);
        }else {
            $this->to_back($game_info);
        }
    }
}