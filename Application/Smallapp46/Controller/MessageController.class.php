<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class MessageController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'modulelist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001);
                break;
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1002);
                break;
        }
        parent::_init_();
    }

    public function modulelist(){
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $fields = 'count(id) as num,type';
        $where = array('openid'=>$openid);
        $group = 'type';
        $res_message = $m_message->getDatas($fields,$where,'','',$group);
        if(empty($res_message)){
            $resp_data = array('total_num'=>0,'datalist'=>array());
            $this->to_back($resp_data);
        }
        $total_num = 0;
        $has_types = array();
        foreach ($res_message as $v){
            $has_types[]=$v['type'];
            $total_num = $v['num'] + $total_num;
        }
        $unwhere = array('openid'=>$openid,'read_status'=>1);
        $group = 'type';
        $res_unmessage = $m_message->getDatas($fields,$unwhere,'','',$group);
        $un_read_nums = array();
        if(!empty($res_unmessage)){
            foreach ($res_unmessage as $v){
                $un_read_nums[$v['type']] = $v['num'];
            }
        }
        $m_redpacketreceive = new \Common\Model\Smallapp\RedpacketReceiveModel();
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $all_message_types = C('MESSAGETYPE_LIST');
        $datas = array();
        $oss_host = get_oss_host();
        foreach ($all_message_types as $v){
            if(in_array($v['type'],$has_types)){
                $unread_num = 0;
                if(isset($un_read_nums[$v['type']])){
                    $unread_num = $un_read_nums[$v['type']];
                }
                $mwhere = array('openid'=>$openid,'type'=>$v['type']);
                $res_mdata = $m_message->getDataList('*',$mwhere,'id desc',0,1);
                $add_time = date('Y-m-d H:i',strtotime($res_mdata['list'][0]['add_time']));

                switch ($v['type']){
                    case 1:
                        $content = '有人喜欢了您公开的内容哦～';
                        break;
                    case 2:
                        $content = '您公开的内容有了新的审核动态～';
                        break;
                    case 3:
                        $content = '您公开内容被系统认定为优质内容';
                        if($res_mdata['list'][0]['hotel_num']>0){
                            $content.="，预计将在{$res_mdata['list'][0]['hotel_num']}个酒楼进行展示。";
                        }
                        break;
                    case 4:
                        $res_redpacket = $m_redpacketreceive->getRedpacketInfo('u.nickName',array('a.id'=>$res_mdata['list'][0]['content_id']));
                        $content = "您成功领取了{$res_redpacket['nickName']}的红包";
                        break;
                    case 5:
                        $res_ordergoods = $m_ordergoods->getOrdergoodsList('goods.name,goods.parent_id',array('og.order_id'=>$res_mdata['list'][0]['content_id']),'og.id asc',0,1);
                        if($res_ordergoods[0]['parent_id']){
                            $res_pgoods = $m_goods->getInfo(array('id'=>$res_ordergoods[0]['parent_id']));
                            $gname = $res_pgoods['name'];
                        }else{
                            $gname = $res_ordergoods[0]['name'];
                        }
                        $content = "您已成功购买“{$gname}”，请等待发货";
                        break;
                    case 6:
                        $res_ordergoods = $m_ordergoods->getOrdergoodsList('goods.name,goods.parent_id',array('og.order_id'=>$res_mdata['list'][0]['content_id']),'og.id asc',0,1);
                        if($res_ordergoods[0]['parent_id']){
                            $res_pgoods = $m_goods->getInfo(array('id'=>$res_ordergoods[0]['parent_id']));
                            $gname = $res_pgoods['name'];
                        }else{
                            $gname = $res_ordergoods[0]['name'];
                        }
                        $content = "您购买的“{$gname}”，已发货，请注意查收";
                        break;
                    default:
                        $content = '';
                }
                $v['image'] = $oss_host.$v['image'];
                $v['unread_num'] = $unread_num;
                $v['content'] = $content;
                $v['add_time'] = $add_time;
                $datas[]=$v;
            }
        }
        $resp_data = array('total_num'=>$total_num,'datalist'=>$datas);
        $this->to_back($resp_data);
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $type = intval($this->params['type']);

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid,'small_app_id'=>1);
        $user_info = $m_user->getOne('id', $where, 'id desc');
        if(empty($user_info)){
            $this->to_back(90116);
        }
        $m_message = new \Common\Model\Smallapp\MessageModel();
        $where = array('openid'=>$openid,'type'=>$type);
        $res_message = $m_message->getDataList('*',$where,'id desc');
        $unread_list = $read_list = array();
        if(!empty($res_message)){
            $oss_host = get_oss_host();
            $default_avatar = $oss_host.'media/resource/btCfRRhHkn.jpg';
            $m_collect = new \Common\Model\Smallapp\CollectModel();
            $m_public = new \Common\Model\Smallapp\PublicModel();
            $m_redpacketreceive = new \Common\Model\Smallapp\RedpacketReceiveModel();
            $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
            foreach ($res_message as $v){
                switch ($v['type']){
                    case 1:
                        $res_collect = $m_collect->getOne('*',array('id'=>$v['content_id']),'id desc');
                        $res_user = $m_user->getOne('nickName,avatarUrl',array('openid'=>$res_collect['openid']), 'id desc');
                        $res_forscreen = $m_forscreen->getWhere('forscreen_id,resource_type,imgs',array('forscreen_id'=>$res_collect['res_id']),'id desc','0,1','');
                        $imgs = json_decode($res_forscreen[0]['imgs'],true);
                        if($res_forscreen[0]['resource_type']==1){
                            $content = '喜欢了你的照片';
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                        }else{
                            $content = '喜欢了你的视频';
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=video/snapshot,t_5000,f_jpg,ar_auto";
                        }
                        $info = array('nickName'=>$res_user['nickName'],'avatarUrl'=>$res_user['avatarUrl'],'content'=>$content,
                            'img_url'=>$img_url,'add_time'=>date('Y-m-d H:i',strtotime($v['add_time'])));
                        break;
                    case 2:
                        $res_public = $m_public->getOne('*',array('id'=>$v['content_id']),'id desc');
                        $res_user = $m_user->getOne('nickName,avatarUrl',array('openid'=>$res_public['openid']), 'id desc');
                        $res_forscreen = $m_forscreen->getWhere('forscreen_id,resource_type,imgs',array('forscreen_id'=>$res_public['forscreen_id']),'id desc','0,1','');
                        $imgs = json_decode($res_forscreen[0]['imgs'],true);
                        $audit_status_str = array('2'=>'通过了','3'=>'未通过');
                        if($res_forscreen[0]['resource_type']==1){
                            $content = "你公开的照片{$audit_status_str[$v['audit_status']]}审核";
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                        }else{
                            $content = "你公开的视频{$audit_status_str[$v['audit_status']]}审核";
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=video/snapshot,t_5000,f_jpg,ar_auto";
                        }
                        $info = array('nickName'=>$res_user['nickName'],'avatarUrl'=>$res_user['avatarUrl'],'content'=>$content,
                            'img_url'=>$img_url,'add_time'=>date('Y-m-d H:i',strtotime($v['add_time'])));
                        break;
                    case 3:
                        $res_public = $m_public->getOne('*',array('id'=>$v['content_id']),'id desc');
                        $res_forscreen = $m_forscreen->getWhere('forscreen_id,resource_type,imgs',array('forscreen_id'=>$res_public['forscreen_id']),'id desc','0,1','');
                        $imgs = json_decode($res_forscreen[0]['imgs'],true);
                        if($res_forscreen[0]['resource_type']==1){
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=image/resize,m_mfit,h_300,w_300";
                        }else{
                            $img_url = $oss_host.$imgs[0]."?x-oss-process=video/snapshot,t_5000,f_jpg,ar_auto";
                        }
                        $content = "你公开的内容被系统认定为优质内容";
                        if($v['good_status']==2 && $v['hotel_num']>0){
                            $content.="预计将在{$v['hotel_num']}个酒楼进行展示";
                        }
                        $info = array('content'=>$content,'img_url'=>$img_url,'add_time'=>date('Y-m-d H:i',strtotime($v['add_time'])));
                        break;
                    case 4:
                        $res_redpacket = $m_redpacketreceive->getRedpacketInfo('a.money,u.nickName',array('a.id'=>$v['content_id']));
                        $content = "您成功领取了{$res_redpacket['nickName']}的红包{$res_redpacket['money']}元";
                        $info = array('content'=>$content,'add_time'=>date('Y-m-d H:i',strtotime($v['add_time'])));
                        break;
                    default:
                        $info = array();
                }
                if(isset($info['nickName']) && empty($info['nickName']) && empty($info['avatarUrl'])){
                    $info['nickName']='游客';
                    $info['avatarUrl']=$default_avatar;
                }
                if($v['read_status']==1){
                    $unread_list[$v['id']]=$info;
                }else{
                    $read_list[]=$info;
                }
            }
        }

        if(!empty($unread_list)){
            $where = array();
            $where['id'] = array('in',array_keys($unread_list));
            $m_message->updateData($where,array('read_status'=>2));
            $unread_list = array_values($unread_list);
        }
        $res_data = array('unread_list'=>$unread_list,'read_list'=>$read_list);
        $this->to_back($res_data);
    }


}