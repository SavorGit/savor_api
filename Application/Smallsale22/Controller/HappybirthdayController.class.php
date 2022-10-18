<?php
namespace Smallsale22\Controller;
use \Common\Controller\CommonController as CommonController;
class HappybirthdayController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'happylist':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function happylist(){
        $datalist = array();
        $m_birthday = new \Common\Model\Smallapp\BirthdayModel();
        $res_birthday = $m_birthday->getDataList('*','','id desc');
        $m_media = new \Common\Model\MediaModel();
        $birthday_scence_adv = C('SCENCE_ADV_BIRTHDAY');
        
        foreach ($res_birthday as $v){
            $name_arr = explode('-',$v['name']);
            $res_media = $m_media->getMediaInfoById($v['media_id']);
            $file_info = pathinfo($res_media['oss_addr']);
            $info = array('name'=>$v['name'],'res_url'=>$res_media['oss_addr'],'file_name'=>$file_info['basename'],
                'title'=>$name_arr[0],'sub_title'=>$name_arr[1],'duration'=>$res_media['duration'],'resource_size'=>$res_media['oss_filesize']
            );
            $info['ads_img_url'] = $birthday_scence_adv[$v['id']]['ads_img_url'];
            $info['countdown'] = $birthday_scence_adv[$v['id']]['countdown'];
            $datalist[] = $info;
        }
        $this->to_back($datalist);
    }
}