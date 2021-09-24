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
                $this->valid_fields = array('openid'=>1001,'data_id'=>1002,'type'=>1001,'action_type'=>1001);
                break;
            case 'recordWifiErr':
                $this->is_verify = 1;
                $this->valid_fields = array('box_mac'=>1000,'err_info'=>1000,'openid'=>1000,'mobile_brand'=>1000,
                                            'mobile_model'=>1000,'platform'=>1000,
                                            'system'=>1000,'version'=>1000,
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
        }
        $this->to_back(10000);
    }

    public function recordlog(){
        $openid = $this->params['openid'];
        $data_id = intval($this->params['data_id']);
        $type = $this->params['type'];//类型 1广告,2商品,3发现-官方,4发现-精选,5发现-公开 6本地生活店铺-领取优惠券 7本地文件投屏H5
        $action_type = $this->params['action_type'];//动作类型1点击,2查看,3点击购买
        //(如type是6则:1领取 2允许领取成功 3拒绝领取失败、type是7则1文件选择 2文件选择成功 3文件点击投屏 4文件上传成功)
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
            case 6:
                $m_store = new \Common\Model\Smallapp\StoreModel();
                $res_store = $m_store->getInfo(array('id'=>$data_id));
                $name = $res_store['name'];
                break;
            default:
                $name = '';
        }
        if($name || $type==7){
            $data = array('data_id'=>$data_id,'name'=>$name,'openid'=>$openid,'action_type'=>$action_type,'type'=>$type,'ip'=>$ip);
            $m_datalog = new \Common\Model\Smallapp\DatalogModel();
            $m_datalog->add($data);
        }
        $res = array();
        if($type==7){
            $res = array('code'=>10000,'msg'=>'success');
            $this->ajaxReturn($res,'JSONP');
        }else{
            $this->to_back($res);
        }
    }
}