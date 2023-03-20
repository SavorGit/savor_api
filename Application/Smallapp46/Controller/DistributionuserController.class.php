<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class DistributionuserController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                break;
            case 'deluser':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'sale_uid'=>1001);
                break;
            case 'invite':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'invite_uid'=>1001);
                break;
            case 'orderlist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'type'=>1001,'page'=>1001);
                break;
        }
        parent::_init_();
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $page = $this->params['page'];

        $datalist = array();
        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid));
        $invite_uid = 0;
        if(!empty($res_duser) && $res_duser['level']==1){
            $pagesize = 10;
            $start = ($page-1)*$pagesize;
            $fields = 'a.id as sale_uid,user.openid,user.nickName,user.avatarUrl';
            $res_data = $m_distuser->getUserDatas($fields,array('a.parent_id'=>$res_duser['id'],'a.status'=>1),'a.id desc',"$start,$pagesize");
            if(!empty($res_data)){
                $datalist = $res_data;
            }
            $invite_uid = $res_duser['id'];
        }
        $res_data = array('datalist'=>$datalist,'invite_uid'=>$invite_uid);
        $this->to_back($res_data);
    }

    public function deluser(){
        $openid = $this->params['openid'];
        $sale_uid = $this->params['sale_uid'];

        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid,'status'=>1));
        $parent_id = intval($res_duser['id']);
        $res_sale_user = $m_distuser->getInfo(array('id'=>$sale_uid));
        if(!empty($res_sale_user) && $res_sale_user['parent_id']==$parent_id){
            $m_distuser->updateData(array('id'=>$sale_uid),array('status'=>2));
        }
        $this->to_back(array());
    }

    public function invite(){
        $openid = $this->params['openid'];
        $invite_uid = $this->params['invite_uid'];

        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid,'status'=>1));
        if(empty($res_duser)){
            $res_invite_user = $m_distuser->getInfo(array('id'=>$invite_uid));
            if($openid!=$res_invite_user['openid']){
                $add_data = array('openid'=>$openid,'parent_id'=>$invite_uid,'beinvited_time'=>date('Y-m-d H:i:s'),'level'=>2,'status'=>1);
                $m_distuser->add($add_data);
            }
        }
        $init_wx_user = C('INIT_WX_USER');
        $m_user = new \Common\Model\Smallapp\UserModel();
        $res_user = $m_user->getOne('avatarUrl,nickName,mobile',array('openid'=>$openid,'status'=>1),'id desc');
        $status = 2;
        if(empty($res_user['avatarUrl']) || empty($res_user['nickName']) || empty($res_user['mobile']) || $res_user['nickName']==$init_wx_user['nickName'] || $res_user['avatarUrl']==$init_wx_user['avatarUrl']){
            $status = 1;
        }
        $this->to_back(array('info_status'=>$status));
    }

    public function orderlist(){
        $openid = $this->params['openid'];
        $type = $this->params['type'];//售卖类型0全部,1直接售卖,2分销售卖
        $page = $this->params['page'];
        $pagesize = 10;

        $m_distuser = new \Common\Model\Smallapp\DistributionUserModel();
        $res_duser = $m_distuser->getInfo(array('openid'=>$openid));
        $datalist = array();
        $total_income_money = 0;
        if(!empty($res_duser)){
            $start = ($page-1)*$pagesize;
            if($res_duser['level']==1){
                $sale_uids = array();
                $res_uids = $m_distuser->getDataList('id',array('parent_id'=>$res_duser['id'],'status'=>1),'id desc');
                foreach ($res_uids as $v){
                    $sale_uids[]=$v['id'];
                }
                switch ($type){
                    case 1:
                        $where = array('sale_uid'=>$res_duser['id'],'status'=>array('egt',51));
                        break;
                    case 2:
                        if(!empty($sale_uids)){
                            $where = array('sale_uid'=>array('in',$sale_uids),'status'=>array('egt',51));
                        }else{
                            $where = array();
                        }
                        break;
                    default:
                        $sale_uids[]=$res_duser['id'];
                        $where = array('sale_uid'=>array('in',$sale_uids),'status'=>array('egt',51));
                }
            }else{
                $where = array('sale_uid'=>$res_duser['id'],'status'=>array('egt',51));
            }
            $fields = 'id as order_id,goods_id,price,amount,otype,total_fee,status,contact,buy_type,sale_uid,add_time';
            if(!empty($where)){
                $m_order = new \Common\Model\Smallapp\OrderModel();
                $res_order = $m_order->getDataList($fields,$where,'id desc',$start,$pagesize);
                if($res_order['total']){
                    $m_ordersettlement = new \Common\Model\Smallapp\OrdersettlementModel();
                    $swhere = array('distribution_user_id'=>$where['sale_uid']);
                    $res_allsettle = $m_ordersettlement->getDataList('sum(money) as total_money',$swhere,'id desc');
                    $total_income_money = $res_allsettle[0]['total_money'];

                    $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
                    $datalist = $res_order['list'];
                    $oss_host = get_oss_host();
                    $all_status = C('ORDER_STATUS');
                    foreach($datalist as $k=>$v){
                        $datalist[$k]['type'] = $v['otype'];
                        $datalist[$k]['status_str'] = $all_status[$v['status']];
                        $datalist[$k]['add_time'] = date('Y-m-d H:i',strtotime($v['add_time']));

                        $order_id = $v['order_id'];
                        $gfields = 'goods.id as goods_id,goods.name as goods_name,goods.gtype,goods.attr_name,goods.parent_id,
                goods.model_media_id,goods.price,goods.cover_imgs,goods.merchant_id,goods.status,og.amount';
                        $res_goods = $m_ordergoods->getOrdergoodsList($gfields,array('og.order_id'=>$order_id),'og.id asc');
                        $goods = array();
                        foreach ($res_goods as $gv){
                            $goods_name = $gv['goods_name'];
                            $cover_imgs_info = explode(',',$gv['cover_imgs']);
                            $img = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                            $ginfo = array('id'=>$gv['goods_id'],'name'=>$goods_name,'price'=>$gv['price'],'amount'=>$gv['amount'],
                                'status'=>$gv['status'],'img'=>$img);
                            $goods[]=$ginfo;
                        }
                        $datalist[$k]['goods'] = $goods;
                        $res_settlement = $m_ordersettlement->getInfo(array('order_id'=>$order_id,'distribution_user_id'=>$res_duser['id']));
                        $income_money = 0;
                        if(!empty($res_settlement)){
                            $income_money = $res_settlement['money'];
                        }
                        $datalist[$k]['income_money'] = $income_money;
                        $distribution = array('money'=>0);
                        if($v['buy_type']==2 && $v['sale_uid']!=$res_duser['id']){
                            $res_sale_settlement = $m_ordersettlement->getInfo(array('order_id'=>$order_id,'distribution_user_id'=>$v['sale_uid']));
                            if(!empty($res_sale_settlement)){
                                $distribution['money'] = $res_sale_settlement['money'];
                                $fields = 'a.id as sale_uid,user.openid,user.nickName,user.avatarUrl';
                                $res_udata = $m_distuser->getUserDatas($fields,array('a.id'=>$v['sale_uid']),'a.id desc',"0,1");
                                $distribution['nickName'] = $res_udata[0]['nickName'];
                                $distribution['avatarUrl'] = $res_udata[0]['avatarUrl'];
                            }
                        }
                        $datalist[$k]['distribution'] = $distribution;
                    }
                }
            }
        }
        $this->to_back(array('total_income_money'=>$total_income_money,'datalist'=>$datalist));
    }

}