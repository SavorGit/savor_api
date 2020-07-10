<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;
class DatalogController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'recordlog':
                $this->is_verify = 1;
                $this->valid_fields = array('openid'=>1001,'data_id'=>1001,'type'=>1001,'action_type'=>1001);
                break;
            case 'recordWifiErr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1000,'err_info'=>1000,'openid'=>1001,'mobile_brand'=>1001,
                                            'mobile_model'=>1001,'platform'=>1001,
                                            'system'=>1001,'version'=>1001,
                );
                break;
        }
        parent::_init_();
    }


    public function recordWifiErr(){
        $box_mac = $this->params['box_mac'];
        $openid = $this->params['openid'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];
        $platform = $this->params['platform'];
        $system   = $this->params['system'];
        $version  = $this->params['version'];

        $err_info = str_replace('\\', '', $this->params['err_info']);
        $m_err_info = new \Common\Model\Smallapp\WifiErrModel();
        $data['box_mac'] = $box_mac !='undefined' ? $box_mac :'';
        $data['openid'] = !empty($openid)?$openid:'';
        $data['err_info'] = $err_info;
        if(!empty($mobile_brand))   $data['mobile_brand'] = $mobile_brand;
        if(!empty($mobile_model))   $data['mobile_model'] = $mobile_model;
        if(!empty($platform))       $data['platform'] = $platform;
        if(!empty($system))         $data['system']   = $system;
        if(!empty($version))        $data['version']  = $version;
        
        if($data['box_mac']){
            $m_err_info->addInfo($data);
            $this->to_back(10000);
        }else {
            $this->to_back(30052);

        }
    }

    public function recordlog(){
        $openid = $this->params['openid'];
        $data_id = intval($this->params['data_id']);
        $type = $this->params['type'];//1广告 2商品
        $action_type = $this->params['action_type'];//动作类型1点击,2查看,3点击购买
        $ip = get_client_ip();

        switch ($type){
            case 1:
                $m_ads = new \Common\Model\Smallapp\AdspositionModel();
                $res_ads = $m_ads->getInfo(array('id'=>$data_id));
                $name = $res_ads['name'];
                break;
            case 2:
                $m_goods = new \Common\Model\Smallapp\GoodsModel();
                $res_goods = $m_goods->getInfo(array('id'=>$data_id));
                $name = $res_goods['name'];
                break;
            default:
                $name = '';
        }
        if($name){
            $data = array('data_id'=>$data_id,'name'=>$name,'openid'=>$openid,'action_type'=>$action_type,'type'=>$type,'ip'=>$ip);
            $m_datalog = new \Common\Model\Smallapp\DatalogModel();
            $m_datalog->add($data);
        }

        $res = array();
        $this->to_back($res);
    }
}