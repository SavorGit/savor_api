<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class MessageController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);
        $page = intval($this->params['page']);
        $pagesize = 20;

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $res_user = $m_user->getOne('id', $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $m_message = new \Common\Model\Smallapp\MessageModel();
        $where = array('hotel_id'=>$hotel_id,'type'=>array('in',array(8,9,10)));
        $offset = ($page-1)*$pagesize;
        $res_message = $m_message->getDatas('*',$where,'id desc',"$offset,$pagesize",'');
        $datalist = array();
        if(!empty($res_message)){
            $all_prizes = array('1'=>'一等奖','2'=>'二等奖','3'=>'三等奖','0'=>'');
            $m_activityapply = new \Common\Model\Smallapp\ActivityapplyModel();
            $m_activityprize = new \Common\Model\Smallapp\ActivityprizeModel();
            $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
            $m_orderlocal = new \Common\Model\Smallapp\OrderlocationModel();
            $m_order = new \Common\Model\Smallapp\OrderModel();
            foreach ($res_message as $v){
                switch ($v['type']){
                    case 8:
                        $where = array('a.id'=>$v['content_id']);
                        $fields = 'activity.name,activity.prize,activity.type,a.id,a.openid,a.box_mac,a.box_name,a.prize_id,a.status,a.add_time';
                        $res_apply = $m_activityapply->getApplyDatas($fields,$where,'a.id desc','0,1','');

                        $add_time = date('Y.m.d H:i',strtotime($res_apply[0]['add_time']));
                        $where = array('openid'=>$res_apply[0]['openid']);
                        $res_user = $m_user->getOne('id,openid,avatarUrl,nickName', $where,'id desc');
                        $res_prize = $m_activityprize->getInfo(array('id'=>$res_apply[0]['prize_id']));
                        $content = "{$res_apply[0]['box_name']}包间抽中了{$all_prizes[$res_prize['level']]}“{$res_prize['name']}“，请及时处理。";

                        $info = array('id'=>$v['id'],'name'=>$res_apply[0]['name'],'content'=>$content,'nickName'=>$res_user['nickName'],
                            'avatarUrl'=>$res_user['avatarUrl'],'add_time'=>$add_time);
                        $datalist[]=$info;
                        break;
                    case 9:
                        $res_user = $m_user->getOne('id,openid,avatarUrl,nickName,invite_time', array('id'=>$v['content_id']),'id desc');
                        $content = "{$res_user['nickName']}已成功注册为热点会员";
                        $add_time = date('Y.m.d H:i',strtotime($res_user['invite_time']));

                        $info = array('id'=>$v['id'],'name'=>'注册会员','content'=>$content,'nickName'=>$res_user['nickName'],
                            'avatarUrl'=>$res_user['avatarUrl'],'add_time'=>$add_time);
                        $datalist[]=$info;
                        break;
                    case 10:
                        $order_id = $v['content_id'];
                        $gfields = 'goods.id as goods_id,goods.name as goods_name';
                        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                        $res_local = $m_orderlocal->getInfo(array('order_id'=>$order_id));
                        $content = "{$res_local['room_name']}包间客人要购买酒水“{$res_goods[0]['goods_name']}“，请及时处理。";
                        $add_time = date('Y.m.d H:i',strtotime($v['add_time']));

                        $res_order = $m_order->getInfo(array('id'=>$order_id));
                        $where = array('openid'=>$res_order['openid']);
                        $res_user = $m_user->getOne('id,openid,avatarUrl,nickName', $where,'id desc');
                        $info = array('id'=>$v['id'],'name'=>'酒水订单','content'=>$content,'nickName'=>$res_user['nickName'],
                            'avatarUrl'=>$res_user['avatarUrl'],'add_time'=>$add_time);
                        $datalist[]=$info;
                        break;
                }

            }
        }
        $this->to_back(array('datalist'=>$datalist));
    }


}