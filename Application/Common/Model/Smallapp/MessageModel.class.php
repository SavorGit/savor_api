<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class MessageModel extends BaseModel{
	protected $tableName='smallapp_message';

    public function getDatas($fields,$where,$order,$limit,$group){
        $data = $this->field($fields)->where($where)->order($order)->group($group)->limit($limit)->select();
        return $data;
    }

	/*
	 * $type 类型1赞(喜欢内容),2内容审核,3优质内容,4领取红包,5购买订单,6发货订单,7销售端酒水点播提醒,8活动中奖,9注册会员
	 */
	public function recordMessage($openid,$content_id,$type,$status=0){
	    switch ($type){
            case 1:
                $where = array('openid'=>$openid,'content_id'=>$content_id,'type'=>$type);
                $res_data = $this->getDataList('*',$where,'id desc');
                if(!empty($res_data)){
                    if($status==0){
                        $this->delData(array('id'=>$res_data[0]['id']));
                    }
                }else{
                    if($status){
                        $data = $where;
                        $data['read_status'] = 1;
                        $this->add($data);
                    }
                }
                break;
            case 4:
            case 5:
                $data = array('openid'=>$openid,'content_id'=>$content_id,'type'=>$type,'read_status'=>1);
                $this->add($data);
                break;
            case 8:
                $data = array('hotel_id'=>$openid,'content_id'=>$content_id,'type'=>$type,'read_status'=>1);
                $this->add($data);
                break;
            case 9:
                $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
                $fields = 'a.id,a.hotel_id';
                $m_staff = new \Common\Model\Integral\StaffModel();
                $res_staff = $m_staff->getMerchantStaff($fields,$where);
                $data = array('staff_openid'=>$openid,'hotel_id'=>$res_staff[0]['hotel_id'],'content_id'=>$content_id,
                    'type'=>$type,'read_status'=>1);
                $this->add($data);
                break;
        }

        return true;
    }
}