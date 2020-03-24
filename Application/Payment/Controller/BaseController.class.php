<?php
namespace Payment\Controller;
use Think\Controller;
/**
 * @desc 基础类
 *
 */
class BaseController extends Controller {
    
    public function __construct(){
        parent::__construct();
        $this->handlePublicParams();
    }
    
    public function handlePublicParams(){
        $this->assign('host_name',$this->host_name());
        $this->assign('public_url',$this->host_name().'/Public');
        
    }
    public function host_name(){
        $http = 'http://';
        return $http.$_SERVER['HTTP_HOST'];
    }
    public function getPayConfig($pk_type=0){
        $m_baseinc = new \Payment\Model\BaseIncModel();
        $res_config = $m_baseinc->getPayConfig($pk_type);
        return $res_config;
    }

}