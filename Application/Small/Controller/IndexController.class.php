<?php
namespace Small\Controller;

use \Common\Controller\CommonController as CommonController;

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