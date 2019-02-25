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
}