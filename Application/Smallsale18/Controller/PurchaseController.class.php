<?php
namespace Smallsale18\Controller;
use \Common\Controller\CommonController as CommonController;

class PurchaseController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'register':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'name'=>1001,'idnumber'=>1001,'idcard'=>1001,
                    'mobile'=>1001,'verify_code'=>1001
                );
                break;
            case 'generatePoster':
                $this->valid_fields = array('openid'=>1001,'poster'=>1001);
                $this->is_verify = 1;
                break;
            case 'selectionList':
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
            case 'userList':
                $this->valid_fields = array('openid'=>1001,'page'=>1001);
                $this->is_verify = 1;
                break;
        }
        parent::_init_();
    }

    public function userList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 10;
        $m_staff = new \Common\Model\Integral\StaffModel();
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $res_staff = $m_staff->getMerchantStaff('a.id as staff_id,a.merchant_id',$where);
        if(empty($res_staff)){
            $this->to_back(93001);
        }
        $merchant_id = $res_staff[0]['merchant_id'];
        $m_dishorder = new \Common\Model\Smallapp\DishorderModel();
        $where = array('merchant_id'=>$merchant_id,'type'=>2);
        $fields = 'openid,count(id) as num,status';
        $orderby = 'status asc';
        $groupby = 'openid';
        $all_nums = $page * $pagesize;
        $res_order = $m_dishorder->getUserOrderNumList($fields,$where,$orderby,$groupby,0,$all_nums);
        $datalist = array();
        if(!empty($res_order)){
            $m_user = new \Common\Model\Smallapp\UserModel();
            foreach ($res_order as $v){
                $owhere = array('openid'=>$v['openid'],'type'=>2,'status'=>1);
                $order_num = $m_dishorder->countNum($owhere);

                $where = array('openid'=>$v['openid']);
                $fields = 'id user_id,openid,mobile,avatarUrl,nickName,name';
                $res_user = $m_user->getOne($fields, $where);
                $uinfo = array('openid'=>$v['openid'],'order_num'=>$order_num,'avatarUrl'=>$res_user['avatarUrl'],
                    'name'=>$res_user['name'],'mobile'=>$res_user['mobile']);
                $datalist[]=$uinfo;
            }
        }
        $res = array('datalist'=>$datalist);
        $this->to_back($res);
    }

    public function register(){
        $name = $this->params['name'];
        $openid = $this->params['openid'];
        $idnumber = $this->params['idnumber'];
        $idcard = $this->params['idcard'];
        $mobile = $this->params['mobile'];
        $verify_code = $this->params['verify_code'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $where['small_app_id'] = 5;
        $fields = 'id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $is_check = check_mobile($mobile);
        if(!$is_check){
            $this->to_back(93006);
        }
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(14);
        $sale_key = C('SAPP_SALE');
        $register_key = $sale_key.'purchaseregister:'.$mobile;
        $register_code = $redis->get($register_key);
        if($register_code!=$verify_code){
            $this->to_back(93040);
        }

        $data = array('name'=>$name,'idnumber'=>$idnumber,'idcard'=>$idcard,
            'mobile'=>$mobile,'role_id'=>3,'status'=>2);
        $where = array('id'=>$res_user['id']);
        $m_user->updateInfo($where,$data);
        $message = '您的申请已经成功提交，稍后会有工作人员与您核实信息。请保持通话畅通。';
        $res_data = array('message'=>$message);
        $this->to_back($res_data);
    }

    public function generatePoster(){
        $openid = $this->params['openid'];
        $poster = $this->params['poster'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array('openid'=>$openid);
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }
        $json_str = stripslashes(html_entity_decode($poster));
        $poster_info = json_decode($json_str,true);
        if(!empty($poster_info)){
            $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
            $poster_goods = array();
            foreach ($poster_info as $v){
                if(!empty($v)){
                    $fileds = 'a.id,a.merchant_id,a.price,merchant.is_changeprice';
                    $where = array('a.id'=>$v['id']);
                    $goods_info = $m_goods->getGoods($fileds,$where);
                    $goods_price = $goods_info[0]['price'];
                    $is_changeprice = $goods_info[0]['is_changeprice'];
                    $merchant_id = $goods_info[0]['merchant_id'];

                    if($is_changeprice && $v['price']>0){
                        $price = $v['price'];
                    }else{
                        $price = $goods_price;
                    }
                    if($price){
                        $ginfo = array('goods_id'=>$v['id'],'price'=>$price);
                        $poster_goods[$merchant_id][] = $ginfo;
                    }
                }
            }
            if($poster_goods){
                $m_purchaseposter = new \Common\Model\Smallapp\PurchaseposterModel();
                foreach ($poster_goods as $k=>$v){
                    $merchant_id = $k;
                    $data = array('openid'=>$openid,'merchant_id'=>$merchant_id);
                    $purchaseposter_id = $m_purchaseposter->add($data);
                    $pgoods = array();
                    foreach ($v as $pv){
                        $pgoods[]= array('purchaseposter_id'=>$purchaseposter_id,'goods_id'=>$pv['goods_id'],'price'=>$pv['price']);
                    }
                    $m_purchasegoods = new \Common\Model\Smallapp\PurchaseposterGoodsModel();
                    $m_purchasegoods->addAll($pgoods);
                }
            }
        }
        $this->to_back(array());
    }

    public function selectionList(){
        $page = intval($this->params['page']);
        $openid = $this->params['openid'];

        $m_user = new \Common\Model\Smallapp\UserModel();
        $where = array();
        $where['openid'] = $openid;
        $fields = 'id user_id,openid,mobile,avatarUrl,nickName,gender,status,is_wx_auth';
        $res_user = $m_user->getOne($fields, $where);
        if(empty($res_user)){
            $this->to_back(92010);
        }

        $pagesize = 10;
        $all_nums = $page * $pagesize;
        $m_purchaseposter = new \Common\Model\Smallapp\PurchaseposterModel();
        $fileds = 'a.id,a.openid,a.merchant_id,a.add_time,hotel.name';
        $where = array('a.openid'=>$openid);
        $res_poster = $m_purchaseposter->getPosterList($fileds,$where,'a.id desc',0,$all_nums);
        $datalist = array();
        if(!empty($res_poster)){
            $m_purchasegoods = new \Common\Model\Smallapp\PurchaseposterGoodsModel();
            foreach ($res_poster as $v){
                $pdate = date('Y-m-d',strtotime($v['add_time']));
                $info = array('id'=>$v['id'],'merchant_id'=>$v['merchant_id'],'name'=>$v['name'],'date'=>$pdate);

                $fileds = 'a.goods_id,a.price as sale_price,dg.name,dg.price,dg.cover_imgs,dg.status';
                $where = array('a.purchaseposter_id'=>$v['id']);
                $res_goods = $m_purchasegoods->getPosterGoods($fileds,$where);
                $goods = array();
                if(!empty($res_goods)){
                    $oss_host = "http://".C('OSS_HOST').'/';
                    foreach ($res_goods as $gv){
                        $ginfo = array('id'=>$gv['goods_id'],'name'=>$gv['name'],'price'=>$gv['sale_price'],'status'=>$gv['status']);
                        $cover_imgs_info = explode(',',$gv['cover_imgs']);
                        $ginfo['img'] = $oss_host.$cover_imgs_info[0]."?x-oss-process=image/resize,p_50/quality,q_80";
                        $goods[]=$ginfo;
                    }
                }
                $info['goods']=$goods;
                $datalist[$pdate][]=$info;
            }
        }
        $res = array('datalist'=>$datalist);
        $this->to_back($res);
    }


}