<?php
namespace Smallappops\Controller;
use \Common\Controller\CommonController as CommonController;

class BbsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getCategory':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'addUser':
                $this->valid_fields = array('openid'=>1001,'avatar_url'=>1001,'nick_name'=>1001);
                $this->is_verify = 1;
                break;
            case 'addContent':
                $this->valid_fields = array('openid'=>1001,'category_id'=>1001,'bbs_user_id'=>1001,'title'=>1001,'content'=>1002,'images'=>1002);
                $this->is_verify = 1;
                break;
            case 'userCenter':
                $this->valid_fields = array('openid'=>1001);
                $this->is_verify = 1;
                break;
            case 'myContents':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'contentList':
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'category_id'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'contentInfo':
                $this->valid_fields = array('openid'=>1001,'content_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addComment':
                $this->valid_fields = array('openid'=>1001,'bbs_user_id'=>1001,'content_id'=>1001,
                    'content'=>1001,'comment_id'=>1002);
                $this->is_verify = 1;
                break;
            case 'getCommentList':
                $this->valid_fields = array('openid'=>1001,'bbs_user_id'=>1001,'content_id'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'addCollect':
                $this->valid_fields = array('openid'=>1001,'bbs_user_id'=>1001,'content_id'=>1001);
                $this->is_verify = 1;
                break;
            case 'addLike':
                $this->valid_fields = array('openid'=>1001,'bbs_user_id'=>1001,'content_id'=>1001,'comment_id'=>1002);
                $this->is_verify = 1;
                break;


        }
        parent::_init_();
    }


    public function getCategory(){
        $openid = $this->params['openid'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $category = C('BBS_CATEGORY');
        $oss_host = get_oss_host();
        $all_data = array();
        foreach ($category as $v){
            $v['icon'] = $oss_host.$v['icon'];
            $v['page'] = 1;
            $v['selected'] = false;
            $all_data[]=$v;
        }
        $this->to_back($all_data);
    }

    public function addUser(){
        $openid = $this->params['openid'];
        $avatar_url = $this->params['avatar_url'];
        $nick_name = $this->params['nick_name'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_bbsuser = new \Common\Model\BbsUserModel();
        $res_bbsuser = $m_bbsuser->getInfo(array('nick_name'=>$nick_name));
        if(!empty($res_bbsuser)){
            $this->to_back(94101);
        }
        $now_openid = encrypt_data($openid,C('USER_SECRET_KEY'));
        $res_bbsuser = $m_bbsuser->getInfo(array('openid'=>$now_openid));
        if(empty($res_bbsuser)){
            $bbs_user_id = $m_bbsuser->add(array('openid'=>$now_openid,'avatar_url'=>$avatar_url,'nick_name'=>$nick_name));
        }else{
            $bbs_user_id = $res_bbsuser['id'];
        }
        $this->to_back(array('bbs_user_id'=>$bbs_user_id));
    }

    public function addContent(){
        $openid = $this->params['openid'];
        $bbs_user_id = intval($this->params['bbs_user_id']);
        $category_id = intval($this->params['category_id']);
        $title = $this->params['title'];
        $content = $this->params['content'];
        $images = $this->params['images'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_bbscontent = new \Common\Model\BbsContentModel();
        $add_data = array('bbs_user_id'=>$bbs_user_id,'category_id'=>$category_id,'title'=>$title);
        if(!empty($content))    $add_data['content'] = $content;
        if(!empty($images))     $add_data['images'] = $images;
        $content_id = $m_bbscontent->add($add_data);
        $this->to_back(array('content_id'=>$content_id));
    }

    public function contentInfo(){
        $openid = $this->params['openid'];
        $content_id = intval($this->params['content_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $cache_key = C('SAPP_OPS').'bbs:viewcontent:'.date('Ymd').":$content_id.$openid";
        $res_cache = $redis->get($cache_key);
        $m_content = new \Common\Model\BbsContentModel();
        if(empty($res_cache)){
            $m_content->where(array('id'=>$content_id))->setInc('view_num');
        }
        $redis->set($cache_key,json_encode(array('openid'=>$openid,'time'=>date('Y-m-d H:i:s'))),86400);

        $now_openid = encrypt_data($openid,C('USER_SECRET_KEY'));
        $m_bbsuser = new \Common\Model\BbsUserModel();
        $res_user = $m_bbsuser->getInfo(array('openid'=>$now_openid));
        $bbs_user_id = $res_user['id'];
        $m_collect = new \Common\Model\BbsCollectModel();
        $is_collect = 0;
        $res_collect = $m_collect->getInfo(array('bbs_user_id'=>$bbs_user_id,'content_id'=>$content_id));
        if(!empty($res_collect)){
            $is_collect = 1;
        }
        $m_like = new \Common\Model\BbsLikeModel();
        $res_like = $m_like->getInfo(array('bbs_user_id'=>$bbs_user_id,'content_id'=>$content_id,'type'=>1));
        $is_like = 0;
        if(!empty($res_like)){
            $is_like = 1;
        }
        $res_content = $m_content->getInfo(array('id'=>$content_id));
        $res_bbsuser = $m_bbsuser->getInfo(array('id'=>$res_content['bbs_user_id']));
        $oss_host = get_oss_host();
        $avatar_url = $oss_host.$res_bbsuser['avatar_url']."?x-oss-process=image/resize,p_50/quality,q_80";
        $images = array();
        if(!empty($res_content['images'])){
            $images_arr = explode(',',$res_content['images']);
            foreach ($images_arr as $v){
                if(!empty($v)){
                    $images[]=$oss_host.$v;
                }
            }
        }
        $res_data = array('nick_name'=>$res_bbsuser['nick_name'],'avatar_url'=>$avatar_url,'title'=>$res_content['title'],'content'=>$res_content['content'],
            'images'=>$images,'add_time'=>$res_content['add_time'],'comment_num'=>$res_content['comment_num'],'like_num'=>$res_content['like_num'],
            'collect_num'=>$res_content['collect_num'],'is_like'=>$is_like,'is_collect'=>$is_collect
        );
        $m_content->updateHotNum($content_id,$res_content['view_num'],$res_content['like_num'],$res_content['comment_num'],$res_content['collect_num']);
        $this->to_back($res_data);
    }

    public function myContents(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//1发布,2评论,3收藏
        $page = intval($this->params['page']);
        $size = 10;
        $start = ($page-1)*$size;

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $now_openid = encrypt_data($openid,C('USER_SECRET_KEY'));
        $m_bbsuser = new \Common\Model\BbsUserModel();
        $res_bbsuser = $m_bbsuser->getInfo(array('openid'=>$now_openid));
        $bbs_user_id = $res_bbsuser['id'];
        $fields = 'content.id as content_id,content.title,content.images,content.add_time,content.hot_num,content.comment_num,content.like_num';
        $orderby = 'content.id desc';
        switch ($type){
            case 1:
                $where = array('content.bbs_user_id'=>$bbs_user_id);
                $m_content = new \Common\Model\BbsContentModel();
                $res_content = $m_content->getContentList($fields,$where,$orderby,$start,$size);
                break;
            case 2:
                $m_comment = new \Common\Model\BbsCommentModel();
                $where = array('a.bbs_user_id'=>$bbs_user_id,'a.type'=>1);
                $res_content = $m_comment->getCommentContentList($fields,$where,$orderby,$start,$size,'a.content_id');
                break;
            case 3:
                $m_collect = new \Common\Model\BbsCollectModel();
                $where = array('a.bbs_user_id'=>$bbs_user_id);
                $res_content = $m_collect->getCommentContentList($fields,$where,$orderby,$start,$size);
                break;
            default:
                $res_content = array();
        }
        $datalist = array();
        if(!empty($res_content)){
            $oss_host = get_oss_host();
            foreach ($res_content as $v){
                $image = '';
                if(!empty($v['images'])){
                    $image_arr = explode(',',$v['images']);
                    $image = $oss_host.$image_arr[0]."?x-oss-process=image/quality,q_70";
                }
                $v['add_time'] = date('Y.m.d H:i',strtotime($v['add_time']));
                $v['image'] = $image;
                $datalist[]=$v;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function contentList(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);//1热榜,2最新
        $category_id = intval($this->params['category_id']);
        $page = intval($this->params['page']);
        $size = 10;
        $start = ($page-1)*$size;

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $now_openid = encrypt_data($openid,C('USER_SECRET_KEY'));
        $m_bbsuser = new \Common\Model\BbsUserModel();
        $res_bbsuser = $m_bbsuser->getInfo(array('openid'=>$now_openid));
        $bbs_user_id = $res_bbsuser['id'];

        $fields = 'content.id as content_id,content.title,content.images,content.add_time,content.hot_num,content.comment_num,content.like_num';
        $orderby = 'content.id desc';
        $where = array();
        if($category_id){
            $where['content.category_id'] = $category_id;
        }
        if($type==1){
            $now_time = date('Y-m-d H:i:s');
            $where['content.hot_start_time'] = array('ELT',$now_time);
            $where['content.hot_end_time'] = array('EGT',$now_time);
            $orderby = 'content.hot_num desc';
        }
        $m_content = new \Common\Model\BbsContentModel();
        $res_content = $m_content->getContentList($fields,$where,$orderby,$start,$size);
        $datalist = array();
        if(!empty($res_content)){
            $oss_host = get_oss_host();
            foreach ($res_content as $v){
                $image = '';
                if(!empty($v['images'])){
                    $image_arr = explode(',',$v['images']);
                    $image = $oss_host.$image_arr[0]."?x-oss-process=image/quality,q_70";
                }
                $v['add_time'] = date('Y.m.d H:i',strtotime($v['add_time']));
                $v['image'] = $image;
                $datalist[]=$v;
            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function addComment(){
        $openid = $this->params['openid'];
        $bbs_user_id = intval($this->params['bbs_user_id']);
        $content_id = intval($this->params['content_id']);
        $comment_id = intval($this->params['comment_id']);
        $content = trim($this->params['content']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_content = new \Common\Model\BbsContentModel();
        $add_data = array('content_id'=>$content_id,'bbs_user_id'=>$bbs_user_id,'content'=>$content,'type'=>1);
        if(!empty($comment_id)){
            $add_data['type'] = 2;
            $add_data['comment_id'] = $comment_id;
        }
        if($add_data['type']==1){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(14);
            $cache_key = C('SAPP_OPS').'bbs:addcomment:'.date('Ymd').":$content_id.$openid";
            $res_cache = $redis->get($cache_key);
            if(!empty($res_cache)){
                $this->to_back(94102);
            }
            $redis->set($cache_key,json_encode(array('openid'=>$openid,'time'=>date('Y-m-d H:i:s'))),86400);
            $m_content->where(array('id'=>$content_id))->setInc('comment_num');
        }
        $m_comment = new \Common\Model\BbsCommentModel();
        $comment_id = $m_comment->add($add_data);
        if($add_data['type']==1){
            $res_content = $m_content->getInfo(array('id'=>$content_id));
            $m_content->updateHotNum($content_id,$res_content['view_num'],$res_content['like_num'],$res_content['comment_num'],$res_content['collect_num']);
        }
        $this->to_back(array('comment_id'=>$comment_id));
    }

    public function addCollect(){
        $openid = $this->params['openid'];
        $bbs_user_id = intval($this->params['bbs_user_id']);
        $content_id = intval($this->params['content_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_collect = new \Common\Model\BbsCollectModel();
        $res_collect = $m_collect->getInfo(array('bbs_user_id'=>$bbs_user_id,'content_id'=>$content_id));
        $m_content = new \Common\Model\BbsContentModel();
        if(!empty($res_collect)){
            $m_content->where(array('id'=>$content_id))->setInc('collect_num',-1);
            $m_collect->delData(array('id'=>$res_collect['id']));
            $collect_id = 0;
        }else{
            $m_content->where(array('id'=>$content_id))->setInc('collect_num');
            $collect_id = $m_collect->add(array('bbs_user_id'=>$bbs_user_id,'content_id'=>$content_id));
        }

        $res_content = $m_content->getInfo(array('id'=>$content_id));
        $m_content->updateHotNum($content_id,$res_content['view_num'],$res_content['like_num'],$res_content['comment_num'],$res_content['collect_num']);

        $this->to_back(array('collect_id'=>$collect_id));
    }

    public function addLike(){
        $openid = $this->params['openid'];
        $bbs_user_id = intval($this->params['bbs_user_id']);
        $content_id = intval($this->params['content_id']);
        $comment_id = intval($this->params['comment_id']);

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $m_like = new \Common\Model\BbsLikeModel();
        $where_data = array('bbs_user_id'=>$bbs_user_id,'content_id'=>$content_id,'type'=>1);
        if(!empty($comment_id)){
            $where_data['comment_id'] = $comment_id;
            $where_data['type'] = 2;
        }
        $res_like = $m_like->getInfo($where_data);
        $m_content = new \Common\Model\BbsContentModel();
        if(!empty($res_like)){
            $m_like->delData(array('id'=>$res_like['id']));
            $like_id = 0;
            if($where_data['type']==1){
                $m_content->where(array('id'=>$content_id))->setInc('like_num',-1);
            }
        }else{
            $like_id = $m_like->add($where_data);
            if($where_data['type']==1){
                $m_content->where(array('id'=>$content_id))->setInc('like_num');
            }
        }

        $res_content = $m_content->getInfo(array('id'=>$content_id));
        $m_content->updateHotNum($content_id,$res_content['view_num'],$res_content['like_num'],$res_content['comment_num'],$res_content['collect_num']);

        $this->to_back(array('like_id'=>$like_id));
    }

    public function getCommentList(){
        $openid = $this->params['openid'];
        $bbs_user_id = intval($this->params['bbs_user_id']);
        $content_id = intval($this->params['content_id']);
        $page = intval($this->params['page']);
        $pagesize = 10;

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $offset = ($page-1)*$pagesize;
        $m_comment = new \Common\Model\BbsCommentModel();
        $fields = 'a.id,a.comment_id,a.content,a.add_time,buser.nick_name,buser.avatar_url';
        $res_comment = $m_comment->getCommentList($fields,array('a.content_id'=>$content_id,'a.type'=>1),'a.id desc',$offset,$pagesize);
        $comment_ids = array();
        foreach ($res_comment as $v){
            $comment_ids[]=$v['id'];
        }
        $oss_host = get_oss_host();
        $son_comments = array();
        $like_nums = array();
        $is_like_data = array();
        if(!empty($comment_ids)){
            $res_son_comment = $m_comment->getCommentList($fields,array('a.comment_id'=>array('in',$comment_ids),'a.type'=>2),'a.id desc');
            foreach ($res_son_comment as $scv){
                $scv['avatar_url'] = $oss_host.$scv['avatar_url']."?x-oss-process=image/resize,p_50/quality,q_80";
                $son_comments[$scv['comment_id']][]=$scv;
                $comment_ids[]=$scv['id'];
            }
            $m_like = new \Common\Model\BbsLikeModel();
            $lwhere = array('comment_id'=>array('in',$comment_ids),'type'=>2);
            $res_like_comment = $m_like->getALLDataList('count(id) as num,comment_id',$lwhere,'','','comment_id');
            foreach ($res_like_comment as $v){
                $like_nums[$v['comment_id']]=$v['num'];
            }
            $lwhere['bbs_user_id']=$bbs_user_id;
            $res_like_comment = $m_like->getALLDataList('comment_id',$lwhere,'','','');
            foreach ($res_like_comment as $v){
                $is_like_data[]=$v['comment_id'];
            }
        }
        $data_list = array();
        foreach ($res_comment as $v){
            $avatar_url = $oss_host.$v['avatar_url']."?x-oss-process=image/resize,p_50/quality,q_80";
            $comment_id = $v['id'];
            $like_num = isset($like_nums[$comment_id])?$like_nums[$comment_id]:0;
            $like_num = $like_num>999?'999+':$like_num;
            $is_like = in_array($comment_id,$is_like_data)?1:0;

            $comment_list = array();
            if(isset($son_comments[$comment_id])){
                foreach ($son_comments[$comment_id] as $sonv){
                    $like_son_num = isset($like_nums[$sonv['id']])?$like_nums[$sonv['id']]:0;
                    $like_son_num = $like_son_num>999?'999+':$like_son_num;
                    $is_son_like = in_array($sonv['id'],$is_like_data)?1:0;
                    $comment_list[]=array('comment_id'=>$sonv['id'],'nick_name'=>$sonv['nick_name'],'avatar_url'=>$sonv['avatar_url'],
                        'content'=>$sonv['content'],'add_time'=>$sonv['add_time'],'like_num'=>$like_son_num,'is_like'=>$is_son_like);
                }
            }
            $data_list[]=array('comment_id'=>$comment_id,'nick_name'=>$v['nick_name'],'avatar_url'=>$avatar_url,'content'=>$v['content'],
                'add_time'=>$v['add_time'],'like_num'=>$like_num,'is_like'=>$is_like,'comment_list'=>$comment_list);
        }
        $m_content = new \Common\Model\BbsContentModel();
        $res_content = $m_content->getInfo(array('id'=>$content_id));
        $this->to_back(array('datalist'=>$data_list,'comment_num'=>$res_content['comment_num']));

    }

    public function userCenter(){
        $openid = $this->params['openid'];

        $m_opstaff = new \Common\Model\Smallapp\OpsstaffModel();
        $res_staff = $m_opstaff->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_staff)){
            $this->to_back(94001);
        }
        $now_openid = encrypt_data($openid,C('USER_SECRET_KEY'));
        $m_bbsuser = new \Common\Model\BbsUserModel();
        $res_bbsuser = $m_bbsuser->getInfo(array('openid'=>$now_openid));

        $res_data = array('bbs_user_id'=>0);
        if(!empty($res_bbsuser)){
            $oss_host = get_oss_host();
            $avatar_url = $oss_host.$res_bbsuser['avatar_url']."?x-oss-process=image/resize,p_50/quality,q_80";

            $bbs_user_id = $res_bbsuser['id'];
            $join_day = round((time()-strtotime($res_bbsuser['add_time']))/86400);
            $join_day = $join_day>0?$join_day:1;
            $m_content = new \Common\Model\BbsContentModel();
            $cfields = 'count(id) as num,sum(collect_num) as collect_num,sum(comment_num) as comment_num,sum(like_num) as like_num';
            $res_content = $m_content->getDataList($cfields,array('bbs_user_id'=>$bbs_user_id),'id desc');
            $content_num = intval($res_content[0]['num']);
            $collect_num = intval($res_content[0]['collect_num']);
            $comment_num = intval($res_content[0]['comment_num']);
            $like_num = intval($res_content[0]['like_num']);

            $join_day_str = "今天是你加入热点社区的第{$join_day}天";
            $res_data = array('bbs_user_id'=>$bbs_user_id,'content_num'=>$content_num,'collect_num'=>$collect_num,'comment_num'=>$comment_num,'like_num'=>$like_num,
                'join_day_str'=>$join_day_str,'nick_name'=>$res_bbsuser['nick_name'],'avatar_url'=>$avatar_url
            );
        }
        $this->to_back($res_data);
    }

    public function deuser(){
        $oepnid = I('get.openid','');
        $now_openid = decrypt_data($oepnid,false,C('USER_SECRET_KEY'));
        echo $now_openid;
    }
}
