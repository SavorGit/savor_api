<?php
namespace H5\Controller;
use Think\Controller;

class ConstellationController extends Controller {
    
    /**
     * @desc 星座详情
     */
    public function detail(){
        $id = I('id',0,'intval');
        $m_constell = new \Common\Model\Smallapp\ConstellationModel();
        $res = $m_constell->getInfo(array('id'=>$id));
        $this->assign('vinfo',$res);
        $this->display('detail');
    }

}