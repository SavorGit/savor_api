<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class DatadisplayModel extends BaseModel{
	protected $tableName='smallapp_datadisplay';

	public function recordDisplaynum($datalist,$area_id){
	    $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
	    $m_ads = new \Common\Model\AdsModel();
	    $add_date = date('Y-m-d');
        foreach ($datalist as $v){
            if($v['type']==1){
                $forscreen_record_id = $v['res_id'];
                $forscreen_id = $v['forscreen_id'];
                $res_record = $this->getInfo(array('forscreen_id'=>$forscreen_id,'area_id'=>$area_id,'add_date'=>$add_date));
                if(!empty($res_record)){
                    $this->where(array('id'=>$res_record['id']))->setInc('display_num',1);
                }else{
                    $res_info = $m_forscreen->getInfo(array('id'=>$forscreen_record_id));
                    $imgs = json_decode($res_info['imgs'],true);
                    $oss_addr = $imgs[0];
                    $data = array('forscreen_id'=>$forscreen_id,'resource_id'=>$res_info['resource_id'],'area_id'=>$area_id,
                        'oss_addr'=>$oss_addr,'display_num'=>1,'type'=>2,'add_date'=>$add_date);
                    $this->add($data);
                }
            }else{
                $ads_id = $v['ads_id'];
                $res_record = $this->getInfo(array('ads_id'=>$ads_id,'type'=>1,'area_id'=>$area_id,'add_date'=>$add_date));
                if(!empty($res_record)){
                    $this->where(array('id'=>$res_record['id']))->setInc('display_num',1);
                }else{
                    $field = 'a.media_id,b.name,b.oss_addr';
                    $res_adsinfo = $m_ads->getAdsList($field,array('a.id'=>$ads_id),'b.id desc','0,1');
                    $data = array('ads_id'=>$ads_id,'media_id'=>$res_adsinfo[0]['media_id'],'resource_name'=>$res_adsinfo[0]['name'],'oss_addr'=>$res_adsinfo[0]['oss_addr'],
                        'area_id'=>$area_id,'display_num'=>1,'type'=>1,'add_date'=>$add_date);
                    $this->add($data);
                }
            }
        }
        return true;
    }
}