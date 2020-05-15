<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class OrderModel extends BaseModel{
	protected $tableName='smallapp_order';

    public function getUserOrderNumList($fields,$where,$orderby,$groupby,$start=0,$size=0){
        if($start >= 0 && $size){
            $data = $this->field($fields)
                ->where($where)
                ->order($orderby)
                ->group($groupby)
                ->limit($start,$size)
                ->select();
        }else{
            $data = $this->field($fields)
                ->where($where)
                ->order($orderby)
                ->group($groupby)
                ->select();
        }
        return $data;
    }

    public function getOrderInfo($fields,$where){
        $data = $this->alias('o')
            ->field($fields)
            ->join('savor_integral_merchant m on o.merchant_id=m.id','left')
            ->join('savor_hotel hotel on m.hotel_id=hotel.id','left')
            ->join('savor_hotel_ext ext on hotel.id=ext.hotel_id','left')
            ->where($where)
            ->select();
        return $data;
    }

    public function getReceiveOrders($order_id){
        $fields = 'o.id,o.openid,o.amount,o.add_time,u.nickName,u.avatarUrl';
        $where = array('o.gift_oid'=>$order_id);
        $res_data = $this->alias('o')
            ->field($fields)
            ->join('savor_smallapp_user u on o.openid=u.openid','left')
            ->where($where)
            ->select();
        $data = array();
        if(!empty($res_data)){
            $receive_num = 0;
            $receive_list = array();
            foreach ($res_data as $v){
                $receive_num+=$v['amount'];
                $info = array('openid'=>$v['openid'],'avatarUrl'=>$v['avatarUrl'],'nickName'=>$v['nickName'],
                    'amount'=>$v['amount'],'add_time'=>$v['add_time'],'time_str'=>viewTimes(strtotime($v['add_time'])));
                $receive_list[]=$info;
            }
            $data = array('rnum'=>$receive_num,'list'=>$receive_list);
        }
        return $data;
    }

	public function sendMessage($order_id){
	    $res_order = $this->getInfo(array('id'=>$order_id));

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $where = array('m.id'=>$res_order['merchant_id']);
        $fields = 'm.id as merchant_id,m.mobile,hotel.id as hotel_id,hotel.name as hotel_name';
        $res_merchant = $m_merchant->getMerchantInfo($fields,$where);
        $activity_phone = $res_merchant[0]['mobile'];

        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $m_staff = new \Common\Model\Integral\StaffModel();
        $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
        $m_user = new \Common\Model\Smallapp\UserModel();
        $m_ordergoods = new \Common\Model\Smallapp\OrdergoodsModel();
        $where = array('og.order_id'=>$order_id);
        $og_fields = 'og.goods_id,goods.name,goods.staff_id';
        $res_goods = $m_ordergoods->getOrdergoodsList($og_fields,$where,'og.id asc');

        $goods_name = array();
        $is_notify_merchant = 0;
        if(!empty($res_goods)){
            $send_staff = array();
            foreach ($res_goods as $ov){
                $goods_name[]=$ov['name'];
                if(!in_array($ov['staff_id'],$send_staff) && $res_order['otype']!=5){
                    $res_staff = $m_staff->getInfo(array('id'=>$ov['staff_id']));
                    if(!empty($res_staff['openid'])){
                        $where = array('openid'=>$res_staff['openid']);
                        $fields = 'id user_id,openid,mobile';
                        $res_user = $m_user->getOne($fields, $where);
                        if(!empty($res_user) && !empty($res_user['mobile'])){
                            $activity_phone = $res_user['mobile'];
                        }
                    }
                    $template_code = $ucconfig['dish_send_salemanager'];
                    $res_data = $alisms::sendSms($activity_phone,array(),$template_code);
                    if($res_data->Code=='OK'){
                        $is_notify_merchant = 1;
                    }
                    $data = array('type'=>11,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                        'url'=>'','tel'=>$activity_phone,'resp_code'=>$res_data->Code,'msg_type'=>3
                    );
                    $m_account_sms_log->addData($data);
                }else{
                    $send_staff[]=$ov['staff_id'];
                }
            }
        }

        if(count($goods_name)==1){
            $params = array('goods_name'=>$goods_name[0]);
            $template_code = $ucconfig['dish_send_buyer'];
        }else{
            $params = array('hotel_name'=>$res_merchant[0]['hotel_name']);
            $template_code = $ucconfig['dish_send_cartsbuyer'];
        }
        $res_data = $alisms::sendSms($res_order['phone'],$params,$template_code);
        if($res_data->Code=='OK'){
            $is_notify_merchant = 1;
        }
        $data = array('type'=>12,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
            'url'=>join(',',$params),'tel'=>$res_order['phone'],'resp_code'=>$res_data->Code,'msg_type'=>3
        );
        $m_account_sms_log->addData($data);
        return $is_notify_merchant;
    }
}