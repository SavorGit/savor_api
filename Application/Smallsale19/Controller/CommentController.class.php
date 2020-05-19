<?php
namespace Smallsale19\Controller;
use \Common\Controller\CommonController;

class CommentController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'commentlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'start_date'=>1002,
                    'end_date'=>1002,'page'=>1001,'pagesize'=>1002);
                break;
        }
        parent::_init_();
    }

    public function commentlist(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = intval($this->params['pagesize']);
        if(empty($pagesize)){
            $pagesize = 10;
        }
        $start_date = $this->params['start_date'];
        $end_date = $this->params['end_date'];

        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $fields = 'a.id,a.openid,merchant.type,a.hotel_id,a.room_id';
        $res_staff = $m_staff->getMerchantStaff($fields,$where);
        if(empty($res_staff) || $res_staff[0]['type']!=2){
            $this->to_back(93001);
        }
        if(empty($start_date) || empty($end_date)){
            $start_date = date('Y-m-d', strtotime("-30 day"));
            $end_date = date('Y-m-d');
        }else{
            $start_date = date('Y-m-d',strtotime($start_date));
            $end_date = date('Y-m-d',strtotime($end_date));
        }

        $res_data = array('datalist'=>array(),'total'=>0,'avg_score'=>0,
            'start_date'=>$start_date,'end_date'=>$end_date);
        if(empty($res_staff[0]['hotel_id']) || empty($res_staff[0]['room_id'])){
            $this->to_back($res_data);
        }

        $m_comment = new \Common\Model\Smallapp\CommentModel();
        $where = array('staff_id'=>$res_staff[0]['id'],'status'=>1);
        $start_time = "$start_date 00:00:00";
        $end_time = "$end_date 23:59:59";
        $where['add_time'] = array(array('egt',$start_time),array('elt',$end_time), 'and');

        $all_nums = $page * $pagesize;
        $res_comment = $m_comment->getDataList('*',$where,'id desc',0,$all_nums);
        if(!empty($res_comment['total'])){
            $res_data['total'] = $res_comment['total'];
            $condition = array('staff_id'=>$res_staff[0]['id'],'status'=>1);
            $res_score = $m_comment->getCommentInfo('avg(score) as score',$condition);
            $res_data['avg_score'] = sprintf("%01.1f",$res_score[0]['score']);

            $m_user = new \Common\Model\Smallapp\UserModel();
            $redis = new \Common\Lib\SavorRedis();
            $redis->select(15);
            $datalist = array();

            $m_commenttag = new \Common\Model\Smallapp\CommenttagidsModel();
            foreach ($res_comment['list'] as $v){
                $where = array('id'=>$v['user_id']);
                $fields = 'openid,avatarUrl,nickName';
                $res_user = $m_user->getOne($fields, $where);
                $info = array('openid'=>$res_user['openid'],'nickName'=>$res_user['nickName'],
                    'avatarUrl'=>$res_user['avatarUrl'],'score'=>$v['score'],'content'=>$v['content'],
                    'time'=>date('Y-m-d H:i',strtotime($v['add_time'])));
                if(empty($info['content'])){
                    $tag_where = array('a.comment_id'=>$v['id']);
                    $res_tags = $m_commenttag->getCommentTags('tag.name',$tag_where);
                    if(!empty($res_tags)){
                        $tags = array();
                        foreach ($res_tags as $tv){
                            $tags[]=$tv['name'];
                        }
                        $info['content'] = join(',',$tags);
                    }
                }
                $staff_info = $m_staff->getInfo(array('id'=>$v['staff_id']));
                $hotel_id = $staff_info['hotel_id'];
                $room_id = $staff_info['room_id'];
                $cache_key = 'savor_room_'.$room_id;
                $redis_room_info = $redis->get($cache_key);
                $room_info = json_decode($redis_room_info, true);
                $cache_key = 'savor_hotel_'.$hotel_id;
                $redis_hotel_info = $redis->get($cache_key);
                $hotel_info = json_decode($redis_hotel_info, true);
                $info['hotel_name'] = $hotel_info['name'];
                $info['room_name'] = $room_info['name'];
                $datalist[] = $info;
            }
            $res_data['datalist'] = $datalist;
        }
        $this->to_back($res_data);
    }

}