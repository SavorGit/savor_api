<?php
/**
 * @AUTHOR: baiyutao.
 * @PROJECT: PhpStorm
 * @FILE: HotelController.class.php
 * @CREATE ON: 2017/9/4 13:25
 * @VERSION: X.X
 * @desc:任务外包APP
 * @purpose:TasksubcontractController
 */
namespace Tasksubcontract\Controller;
use \Common\Controller\BaseController as BaseController;
use Common\Lib\RecordLog;
use Think\Log;

class BoxController extends BaseController {
    private $state_ar;
    private $img_up = 'http://oss.littlehotspot.com/log/resource/standalone/moble/';
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelBoxDamageConfig':
                $this->is_verify = 0;
                break;
            case 'InsertBoxDamage':
                $this->is_verify = 1;
                $this->valid_fields = array(
                    'bid'=>'1001',
                    'hotel_id'=>'1001',
                    'srtype'=>'1001',
                    'userid'=>'1001',
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
        $damage_config = C('HOTEL_STANDALONE_CONFIG');
        $d_config_arr = array();
        foreach ($damage_config as $dk=>$dv) {
            $d_config_arr[] = array('id'=>$dk,
                  'reason'=>$dv
            );
        }
        $data['list'] = $d_config_arr;
        $this->to_back($data);
    }


    public function sctonum($num, $double = 6){
        if(false !== stripos($num, "e")){
             $a = explode("e",strtolower($num));
             return bcmul($a[0], bcpow(10, $a[1], $double), $double);

            //$this->to_back(17001);
        }else{
            return $num;
        }
    }

    public function InsertBoxDamage() {

        $params = $_SERVER['HTTP_TRACEINFO'];
        file_put_contents(LOG_PATH.'baiyu.log', $params.PHP_EOL,  FILE_APPEND);

        $save['hotel_id'] = intval($this->params['hotel_id']);
        $save['bid'] = $this->params['bid'];
        $save['userid'] = intval($this->params['userid']);
        $save['remark'] = empty($this->params['remark'])?'':$this->params['remark'];
        $save['current_location'] = empty($this->params['current_location'])?'':$this->params['current_location'];
        $save['repair_type'] = empty($this->params['repair_type'])?'':$this->params['repair_type'];
        $save['repair_img'] = str_replace( C('IMG_UP_SUBCONTACT'),'', $this->params['repair_img']);
        $save['srtype'] = $this->params['srtype'];
        $this->disposeTips($save);
        $lo = explode(',', $this->traceinfo['location']);
        $lng = $this->sctonum( $lo[1]);
        $lat = $this->sctonum( $lo[0]);
        if($lat<0 || $lat>90){
            $this->to_back(17001);
        }
        if($lng<0 || $lng>180){
            $this->to_back(17002);
        }
        $save['gps'] = $this->traceinfo['location'];
        //$save['gps'] = json_encode($save['gps']);
        $save['flag'] = 0;
        $save['create_time'] = date("Y-m-d H:i:s");
        $save['datetime'] = date("Ymd");
        $repairMo = new \Common\Model\SubcontractTaskModel();

        $repairMo->startTrans();
        $bool = $repairMo->addData($save);
        if ( $bool ) {
            if($save['srtype'] == 2) {
                $dat['hotel_id'] = $save['hotel_id'];
                $dat['install_state'] = 1;
                $dat['insert_state'] = 1;
                //获取酒楼信息
                $hotelModel = new \Common\Model\HotelModel();
                $ho_info = $hotelModel->find($save['hotel_id']);
                $dat['hotel_address'] = $ho_info['addr'];
                $dat['hotel_linkman'] = $ho_info['contractor'];
                $dat['hotel_linkman_tel'] = $ho_info['tel'];
                $opModel = new \Common\Model\OptiontaskModel();
                $bop = $opModel->addData($dat, 1);
                if ($bop) {
                    $repairMo->commit();
                    $this->to_back(10000);
                } else {
                    $repairMo->rollback();
                    $this->to_back('30055');
                }
            }else{
                $repairMo->commit();
                $this->to_back(10000);
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
        $reason_str = $this->params['repair_type'];

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



        $con['id'] = $save['bid'];
        $boxModel  = new \Common\Model\BoxModel();
        $info = $boxModel->getOnerow($con);
        if(empty($info)){
            $this->to_back('30052');
        }


        if($save['srtype'] == 2 && empty($save['remark']) && empty($reason_str)) {
            $this->to_back('30054');
        }

    }

}