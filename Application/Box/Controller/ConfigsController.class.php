<?php
namespace Box\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class ConfigsController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getBoxVolume':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1001);
                break;
        }
        parent::_init_();
        
    }
    public function getBoxVolume(){
        $box_mac = $this->params['box_mac'];
        $m_box = new \Common\Model\BoxModel();
        $map = array();
        $map['a.mac'] = $box_mac;
        $map['a.state'] = 1;
        $map['a.flag']  = 0;
        $map['d.state'] = 1;
        $map['d.flag']  = 0;
        $box_info = $m_box->getBoxInfo('a.box_carousel_volume,a.box_pro_demand_volume,a.box_content_demand_volume,
                                        a.box_video_froscreen_volume,a.box_img_froscreen_volume,a.box_tv_volume', $map);
        
        if(empty($box_info)){
            $this->to_back(70001);
        }
        $box_info = $box_info[0];
        $m_sysconfig = new \Common\Model\SysConfigModel();
        $sysconfig = $m_sysconfig->getAllconfig();
        $config_volume = array(
            'box_carousel_volume'=>'机顶盒轮播音量','box_pro_demand_volume'=>'机顶盒公司节目点播音量',
            'box_content_demand_volume'=>'机顶盒用户内容点播音量','box_video_froscreen_volume'=>'机顶盒视频投屏音量',
            'box_img_froscreen_volume'=>'机顶盒图片投屏音量','box_tv_volume'=>'机顶盒电视音量',
            
        );
        $sys_vol_data = array();
        foreach ($config_volume as $k=>$v){
            if(empty($box_info[$k])){
                $configValue = $sysconfig[$k];
            }else {
                $configValue = $box_info[$k];
            }
            $sys_vol_data[]=array('label'=>$v,'configKey'=>$k,'configValue'=>$configValue);
        }
        $res_box['sys_volume'] = $sys_vol_data;
        //$res_box[]= json_encode($sys_vol_data,JSON_UNESCAPED_UNICODE);
        $this->to_back($res_box);
    }
    
}