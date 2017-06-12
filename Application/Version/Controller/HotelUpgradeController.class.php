<?php
namespace Version\Controller;
use Think\Controller;
use \Common\Controller\BaseController as BaseController;
class HotelUpgradeController extends BaseController{
    /**
     * 构造函数
     */
    public function _init_(){
        switch (ACTION_NAME){
            case 'index':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function index(){
        $traceinfo = $this->traceinfo;
        $clent_arr  = C('HOTEL_CLIENT_NAME_ARR');
        $device_type = $clent_arr[$traceinfo['clientname']];
        $versioncode = $traceinfo['versioncode'];
        $clentname = $traceinfo['clientname'];
        $m_version_upgrade = new \Common\Model\DeviceUpgradeModel();
        $upgrade_info = $m_version_upgrade->getLastOneByDevice($device_type);
        
        $data = array();
        $data = array();
        if($versioncode<= $upgrade_info['version_max'] && $versioncode>= $upgrade_info['version_min']){
            $now_version = $upgrade_info['version'];
            $m_version = new \Common\Model\DeviceVersionModel();
            $data = $m_version->getOneByVersionAndDevice($now_version, $device_type);
            $data['oss_addr'] =$this->getOssAddr($data['oss_addr']);
            $data['update_type'] = $upgrade_info['update_type'];
            $remark = explode("|", $data['remark']);
            $data['remark'] = $remark;
            $this->to_back($data);
        }else {
            $this->to_back(array());
        }
    }
}
