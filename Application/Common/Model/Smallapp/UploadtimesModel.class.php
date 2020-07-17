<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class UploadtimesModel extends BaseModel{
	protected $tableName='smallapp_uploadtimes';

	public function addUploadtimes($data){
        $data_time = array('openid'=>$data['openid'],'box_mac'=>$data['box_mac'],'resource_size'=>$data['resource_size'],
            'res_sup_time'=>$data['res_sup_time'],'res_eup_time'=>$data['res_eup_time'],'up_time'=>$data['create_time'],
            );
        if(!empty($data['box_mac'])){
            $m_box = new \Common\Model\BoxModel();
            $res_box = $m_box->getHotelInfoByBoxMac($data['box_mac']);
            if(!empty($res_box)){
                $data_time['hotel_id'] = $res_box['hotel_id'];
                $data_time['room_id'] = $res_box['room_id'];
                $data_time['room_name'] = $res_box['room_name'];
                $data_time['box_id'] = $res_box['box_id'];
            }
        }
        $this->add($data_time);
    }
}