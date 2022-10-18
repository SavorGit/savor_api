<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;

class SalecodeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'info':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'task_user_id'=>1001);
                break;
        }
        parent::_init_();
    }

    public function info(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $task_id = $this->params['task_user_id'];

        $where = array('a.openid'=>$openid,'merchant.hotel_id'=>$hotel_id,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.id as staff_id,a.openid,a.level,merchant.type,user.id as user_id';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_usertask = new \Common\Model\Integral\TaskuserModel();
        $fields = "a.id as task_user_id,task.id task_id,task.name task_name,task.goods_id,task.integral,
        task.desc,task.task_type,task.status,task.flag,task.end_time as task_expire_time";
        $where = array('a.id'=>$task_id);
        $res_usertask = $m_usertask->getUserTaskList($fields,$where,'a.id desc');
        if(empty($res_usertask)){
            $this->to_back(93069);
        }
        $goods_id = $res_usertask[0]['goods_id'];
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if(empty($res_goods)){
            $this->to_back(93034);
        }

        $m_media = new \Common\Model\MediaModel();
        $poster_img = '';
        if(!empty($res_goods['poster_media_id'])){
            $res_media = $m_media->getMediaInfoById($res_goods['poster_media_id'],'https');
            $poster_img = $res_media['oss_addr'];
        }
        $expire_time = strtotime("+7 day");
        $task_expire_time = strtotime($res_usertask[0]['task_expire_time']);
        if($expire_time>$task_expire_time){
            $expire_time = $task_expire_time;
        }
        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $sale_uid = $hashids->encode($res_staff[0]['user_id']);

        $host_name = 'https://'.$_SERVER['HTTP_HOST'];
        $qrcode = $host_name."/Smallsale22/qrcode/dishQrcode?data_id={$goods_id}&type=43&suid={$sale_uid}&box_id=0&taskid={$task_id}&time={$expire_time}";
        $desc = '扫码即可购买'.'（'.date('Y.m.d',$expire_time).'前有效）';
        $company = '北京热点投屏科技发展有限公司';
        $data = array('goods_id'=>$goods_id,'name'=>$res_goods['name'],'price'=>$res_goods['price'],'line_price'=>$res_goods['line_price'],
            'qrcode'=>$qrcode,'poster_img'=>$poster_img,'desc'=>$desc,'company'=>$company
        );
        $this->to_back($data);
    }

}