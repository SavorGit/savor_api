<?php
/**
 * Project savor_api
 *
 * @author baiyutao <------@gmail.com> 2017-5-16
 */
namespace Small\Controller;

use \Common\Controller\CommonController as CommonController;


/**
 * Class ApiController
 * 云平台PHP接口
 * @package Small\Controller
 */
class IndexController extends CommonController{
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 0;
            break;
        }
    }
    function index(){
        
        header('Location:http://www.littlehotspot.com');
    }
    
}