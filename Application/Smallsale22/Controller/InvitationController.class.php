<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;
class InvitationController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'confirmdata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'box_mac'=>1002,'name'=>1001,'book_time'=>1001,
                    'people_num'=>1002,'mobile'=>1001,'room_id'=>1002,'contact_name'=>1002,'contact_mobile'=>1002,
                    'theme_id'=>1002,'desc'=>1002,'is_sellwine'=>1002,'images'=>1002,'room_type'=>1002,'table_name'=>1002);
                break;
            case 'themes':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'version'=>1002);
                break;
            case 'initdata':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001);
                break;
            case 'datalist':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'hotel_id'=>1001,'page'=>1001,'pagesize'=>1002);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'invitation_id'=>1001);
                break;
        }

        parent::_init_();
    }

    public function confirmdata(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $box_mac = $this->params['box_mac'];
        $book_time = $this->params['book_time'];
        $name = trim($this->params['name']);
        $people_num = $this->params['people_num'];
        $mobile = $this->params['mobile'];
        $room_id = intval($this->params['room_id']);
        $contact_name = trim($this->params['contact_name']);
        $contact_mobile = $this->params['contact_mobile'];
        $theme_id = intval($this->params['theme_id']);
        $desc = trim($this->params['desc']);
        $is_sellwine = intval($this->params['is_sellwine']);//是否显示酒水信息 1显示，2不显示
        $images = $this->params['images'];
        $room_type = intval($this->params['room_type']);//1包间、2大厅
        $table_name = trim($this->params['table_name']);
        $send_type = intval($this->params['send_type']);//1短信发送,2微信和短信发送

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_hotelinvitation = new \Common\Model\Smallapp\HotelInvitationConfigModel();
        $res_invitation = $m_hotelinvitation->getInfo(array('hotel_id'=>$hotel_id));
        if(empty($res_invitation)){
            $this->to_back(93077);
        }
        if($room_type==2){
            $m_hotel = new \Common\Model\HotelModel();
            $res_hotel = $m_hotel->getOneById('id,name',$hotel_id);
            $hotel_name = $res_hotel['name'];
            $room_id = 0;
            $box_id = 0;
            $box_name = '';
            $room_name = $table_name;
        }else{
            $m_room = new \Common\Model\RoomModel();
            $fields = 'room.id as room_id,room.name as room_name,hotel.id as hotel_id,hotel.name as hotel_name';
            $where  = array('room.id'=>$room_id,'hotel.state'=>1,'hotel.flag'=>0,'room.state'=>1,'room.flag'=>0);
            $rets = $m_room->alias('room')
                ->join('savor_hotel hotel on hotel.id=room.hotel_id','left')
                ->field($fields)
                ->where($where)
                ->find();
            $hotel_id = $rets['hotel_id'];
            $hotel_name = $rets['hotel_name'];
            $room_id = $rets['room_id'];
            $box_id = 0;
            $box_name = '';
            $room_name = $rets['room_name'];
        }

        if(empty($images)){
            $images = '';
        }
        $book_time = date('Y-m-d H:i:s',strtotime($book_time));
        $adata = array('openid'=>$openid,'name'=>$name,'hotel_id'=>$hotel_id,'hotel_name'=>$hotel_name,
            'room_id'=>$room_id,'room_name'=>$room_name,'box_id'=>$box_id,'box_name'=>$box_name,'box_mac'=>$box_mac,
            'book_time'=>$book_time,'theme_id'=>$theme_id,'is_sellwine'=>$is_sellwine,'images'=>$images,'send_type'=>$send_type
        );
        if($send_type==1){
            $adata['send_time'] = date('Y-m-d H:i:s');
        }
        if($room_type>0){
            $adata['room_type'] = $room_type;
        }
        if(!empty($people_num)){
            $adata['people_num'] = intval($people_num);
        }
        if(!empty($mobile)){
            $adata['mobile'] = $mobile;
        }
        if(!empty($contact_name)){
            $adata['contact_name'] = $contact_name;
        }
        if(!empty($contact_mobile)){
            $adata['contact_mobile'] = $contact_mobile;
        }
        if(!empty($desc)){
            $adata['desc'] = $desc;
        }
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $invitation_id = $m_invitation->add($adata);
        //发送短信
        $ucconfig = C('ALIYUN_SMS_CONFIG');
        $alisms = new \Common\Lib\AliyunSms();
        $book_time = date('Y.m.d-H:00',strtotime($book_time));
        $params = array('book_time'=>$book_time,'hotel_name'=>$hotel_name,'room_name'=>$room_name);
        $content_book = "【小热点】{$hotel_name} ---尊敬的贵宾您好！已为您预定{$book_time}在本餐厅的[$room_name]用餐，恭候您的光临。";
        if(!empty($contact_name)){
            $content_book .= "{$contact_name}为您服务，";
        }
        if(!empty($contact_mobile)){
            $params['tel'] = $contact_mobile;
            $content_tel = "联系电话：{$contact_mobile}。";
            $template_code = $ucconfig['send_invitation_to_user_has_mobile_link'];
        }else{
            $content_tel = '';
            $template_code = $ucconfig['send_invitation_to_user_link'];
        }
        $is_send = check_sendsms_content($mobile,$params,$template_code);
        if($is_send==0){
            $expire_time = strtotime($adata['book_time']) + 86400*7;
            $param_data = array(
                'jump_wxa'=>array(
                    'path'=>'/mall/pages/wine/post_book/index',
                    'query'=>"id=$invitation_id&status=1",
                ),
                'is_expire'=>true,
                'expire_time'=>$expire_time,
            );
            $config = C('SMALLAPP_CONFIG');
            $wechat = new \Common\Lib\Wechat($config);
            $res_generate = $wechat->generatescheme(json_encode($param_data,JSON_UNESCAPED_UNICODE));
            $res_info = json_decode($res_generate,true);
            if($res_info['errcode']!=0){
                $res_generate = $wechat->generatescheme(json_encode($param_data,JSON_UNESCAPED_UNICODE));
                $res_info = json_decode($res_generate,true);
            }
            $p_invitation_id = 1;
            if($res_info['openlink']){
                $p_invitation_id = $invitation_id;
                $redis = new \Common\Lib\SavorRedis();
                $redis->select(14);
                $invite_key = C('SAPP_SALE_INVITATION_JUMP_URL').$invitation_id;
                $redis->set($invite_key,$res_info['openlink'],86400*30);
            }
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $res_encode = $hashids->encode($p_invitation_id);
            $params['code'] = $res_encode;
            $content_url = "本店有多种知名白酒,平价销售,酒水预定请咨询店内。更多优惠活动、招牌菜、地址导航，请点击查看。邀请函也可转发给您的朋友赴约。https://mobile.littlehotspot.com/rds/$res_encode";

            $emsms = new \Common\Lib\EmayMessage();
            $content = $content_book.$content_tel.$content_url;
            $res_data = $emsms->sendSMS($content,$mobile);
            $resp_code = $res_data->code;
            /*
            $res_data = $alisms::sendSms($mobile,$params,$template_code);
            $resp_code = $res_data->Code;
            */
            $data = array('type'=>15,'status'=>1,'create_time'=>date('Y-m-d H:i:s'),'update_time'=>date('Y-m-d H:i:s'),
                'url'=>join(',',$params),'tel'=>$mobile,'resp_code'=>$resp_code,'msg_type'=>3
            );
            $m_account_sms_log = new \Common\Model\AccountMsgLogModel();
            $m_account_sms_log->addData($data);
        }
        $this->to_back(array('invitation_id'=>$invitation_id));
    }

    public function themes(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $version = $this->params['version'];

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }

        $all_themes = C('INVITATION_THEME');
        $all_themes = array_values($all_themes);
        $all_data = array();
        foreach ($all_themes as $k=>$v){
            if($version>='1.9.37'){
                if($v['is_display']==1){
                    $v['bg_img'] = $v['bg_img'].'?x-oss-process=image/resize,p_50/quality,q_60';
                    $all_data[]=$v;
                }
            }else{
                if($v['is_display']==0){
                    $v['bg_img'] = $v['bg_img'].'?x-oss-process=image/resize,p_50/quality,q_60';
                    $all_data[]=$v;
                }
            }
        }
        $this->to_back(array('datalist'=>$all_data));
    }

    public function initdata(){
        $openid = $this->params['openid'];
        $hotel_id = intval($this->params['hotel_id']);

        $m_merchant = new \Common\Model\Integral\MerchantModel();
        $res_merchant = $m_merchant->getInfo(array('hotel_id'=>$hotel_id,'status'=>1));
        if(empty($res_merchant) || $res_merchant['status']!=1){
            $this->to_back(93035);
        }
        $m_goods = new \Common\Model\Smallapp\DishgoodsModel();
        $where = array('merchant_id'=>$res_merchant['id'],'status'=>1,'type'=>24);
        $orderby = 'sort desc,id desc';
        $res_goods = $m_goods->getDataList('*',$where,$orderby,0,5);
        $images = array();
        if($res_goods['total']){
            $oss_host = get_oss_host();
            foreach ($res_goods['list'] as $k=>$v){
                $img_url = '';
                $img_path = '';
                if(!empty($v['cover_imgs'])){
                    $cover_imgs_info = explode(',',$v['cover_imgs']);
                    if(!empty($cover_imgs_info[0])){
                        $img_path = $cover_imgs_info[0];
                        $img_url = $oss_host.$cover_imgs_info[0];
                    }
                }
                $images[] = array('id'=>$v['id'],'img_url'=>$img_url,'img_path'=>$img_path);
            }
        }
        $m_hotelinvitation = new \Common\Model\Smallapp\HotelInvitationConfigModel();
        $res_iconfig = $m_hotelinvitation->getInfo(array('hotel_id'=>$hotel_id));
        //$this->to_back(array('images'=>$images,'is_open_sellplatform'=>$res_iconfig['is_open_sellplatform']));
        $this->to_back(array('images'=>$images,'is_open_sellplatform'=>$res_iconfig['is_open_sellplatform'],
                             'is_view_wine_switch'=>$res_iconfig['is_view_wine_switch']));
    }

    public function datalist(){
        $openid = $this->params['openid'];
        $hotel_id = $this->params['hotel_id'];
        $page = intval($this->params['page']);
        $pagesize = $this->params['pagesize'];
        if(empty($pagesize)){
            $pagesize = 10;
        }

        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $start = ($page-1)*$pagesize;
        $fields = 'id,room_name,name,book_time';
        $res_invitation = $m_invitation->getDataList($fields,array('openid'=>$openid),'id desc',$start,$pagesize);
        $datalist = array();
        foreach ($res_invitation['list'] as $v){
            $book_time = date('m.d H:i',strtotime($v['book_time']));
            $datalist[]=array('invitation_id'=>$v['id'],'room_name'=>$v['room_name'],'name'=>$v['name'],'book_time'=>$book_time);
        }
        $this->to_back(array('datalist'=>$datalist));
    }

    public function detail(){
        $openid = $this->params['openid'];
        $invitation_id = intval($this->params['invitation_id']);
        $where = array('a.openid'=>$openid,'a.status'=>1,'merchant.status'=>1);
        $field_staff = 'a.openid,merchant.type';
        $m_staff = new \Common\Model\Integral\StaffModel();
        $res_staff = $m_staff->getMerchantStaff($field_staff,$where);
        if(empty($res_staff)){
            $this->to_back(93014);
        }
        $m_invitation = new \Common\Model\Smallapp\InvitationModel();
        $res_data = $m_invitation->getInfo(array('id'=>$invitation_id));

        $book_time = date('m.d H:i',strtotime($res_data['book_time']));
        $data = array('id'=>$invitation_id,'room_name'=>$res_data['room_name'],'book_time'=>$book_time,'people_num'=>$res_data['people_num'],
            'name'=>$res_data['name'],'mobile'=>$res_data['mobile']);
        $this->to_back($data);
    }
}