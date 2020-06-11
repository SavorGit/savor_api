<?php
namespace Smallapp46\Controller;
use \Common\Controller\CommonController as CommonController;

class ExpressController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getExpress':
                $this->is_verify = 1;
                $this->valid_fields = array('order_id'=>1001,'express_id'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getExpress(){
        $order_id = intval($this->params['order_id']);
        $express_id = isset($this->params['express_id'])?intval($this->params['express_id']):0;
        $m_order = new \Common\Model\Smallapp\OrderModel();
        $res_order = $m_order->getInfo(array('id'=>$order_id));
        if(empty($res_order)){
            $this->to_back(93036);
        }
        $m_orderexpress = new \Common\Model\Smallapp\OrderexpressModel();
        $res_express = $m_orderexpress->getExpress($order_id,$express_id);
        $this->to_back($res_express);
    }


}