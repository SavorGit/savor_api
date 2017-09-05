<?php
/**
 * @AUTHOR: baiyutao.
 * @PROJECT: PhpStorm
 * @FILE: HotelController.class.php
 * @CREATE ON: 2017/9/4 13:25
 * @VERSION: X.X
 * @desc:运维端酒店信息获取
 * @purpose:HotelController
 */
namespace Opclient\Controller;
use \Common\Controller\BaseController as BaseController;

class BoxController extends BaseController {
    private $state_ar;
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelBoxDamageConfig':
                $this->is_verify = 0;
                break;
            case 'InsertBoxDamage':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'box_mac'=>'1001',
                    'userid'=>'1001',
                    'state'=>'1001',
                    'type'=>'1001',
                    'hotel_id'=>'1001',
                );
                break;
        }
        //解决未解决对应数组
        $this->state_ar = array(1, 2);
        parent::_init_();
    }

    /*
     * @desc 获取酒楼损坏基本信息
     * @method getHotelBoxDamageConfig
     * @access public
     * @http null
     * @return json
     */
    public function getHotelBoxDamageConfig() {
        $damage_config = C('HOTEL_DAMAGE_CONFIG');
        $d_config_arr = array();
        foreach ($damage_config as $dk=>$dv) {
            $d_config_arr[] = array('id'=>$dk,
                  'reason'=>$dv
            );
        }
        $data['list'] = $d_config_arr;
        $this->to_back($data);
    }

    public function InsertBoxDamage() {
        $save['hotel_id'] = intval($this->params['hotel_id']);
        $save['mac'] = $this->params['box_mac'];
        $save['userid'] = intval($this->params['userid']);
        $save['state'] = $this->params['state'];
        $save['remark'] = empty($this->params['remark'])?'':$this->params['remark'];
        $save['type'] = $this->params['type'];
        $this->disposeTips($save);
        $save['flag'] = 0;
        $save['create_time'] = date("Y-m-d H:i:s");
        $save['update_time'] = date("Y-m-d H:i:s");
        $save['datetime'] = date("Ymd");
        $repairMo = new \Common\Model\RepairBoxUserModel();
        $repairMo->startTrans();
        $bool = $repairMo->addData($save);
        if ( $bool ) {
            $rep_arr = array (
                '1','2','3'
            );
            $rdeital_arr = array();
            $insertid = $repairMo->getLastInsID();
            foreach ($rep_arr as $rv) {
                $rdeital_arr[] = array(
                    'repair_id'=>$insertid,
                    'repair_type'=>$rv,
                );
            }
            $redMo = new \Common\Model\RepairDetailModel();
            $bop = $redMo->addData($rdeital_arr, 2);
            if ($bop) {
                $repairMo->commit();
                $this->to_back(10000);
            } else {
                $repairMo->rollback();
                $this->to_back('30055');
            }
        } else {
            $repairMo->rollback();
            $this->to_back('30055');
        }
    }


    /*
     * @desc 酒楼信息错误提示
     * @method disposeTips
     * @access public
     * @http null
     * @param hotelId int
     * @return json
     */
    public function disposeTips($save) {
        $reason_str = $this->params['reason_str'];
        //检测酒楼是否存在且正常
        $m_hotel = new \Common\Model\HotelModel();
        $hotel_info = $m_hotel->getInfoById($save['hotel_id'], 'id');
        if( empty($hotel_info) ) {
            $this->to_back('16100');   //该酒楼不存在或被删除
        }

        $m_sysuser = new \Common\Model\SysUserModel();
        $where['id'] = $save['userid'];
        $where['status']   =1;
        $userinfo = $m_sysuser->getUserInfo($where,'username,
            remark as nickname,password');
        if(empty($userinfo)){
            $this->to_back('30001');    //用户不存在
        }

        if (!in_array($save['state'], $this->state_ar) ) {
            $this->to_back('30051');
        }

        if($save['type'] == 1) {
            $con['hotel_id'] = $save['hotel_id'];
            $con['mac_addr'] = $save['mac'];
            $hextModel  = new \Common\Model\HotelExtModel();
            $info = $hextModel->getOnerow($con);
            if(empty($info)){
                $this->to_back('30053');
            }
        } elseif ($save['type'] == 2) {
            $con['mac'] = $save['mac'];
            $boxModel  = new \Common\Model\BoxModel();
            $info = $boxModel->getOnerow($con);
            if(empty($info)){
                $this->to_back('30052');
            }
        }

        if(empty($save['remark']) && empty($reason_str)) {
            $this->to_back('30054');
        }

    }

}