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
            case 'getAllRepairUser':
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
            case 'getRepairRecordListByUserid':
                $this->is_verify = 1;
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

    public function changeRepairInfo($box_info, $nextpage){
        $dap = array();
        $rdeitalModel = new \Common\Model\RepairDetailModel();
        foreach ($box_info as $bk=>$bv) {
            $st_array = array();
            $dac = array();
            $ch_ar['rpid'] = explode(',', $bv['rpid']);
            $ch_ar['remark'] = explode('&&&', $bv['remark']);
            $ch_ar['state'] = explode(',', $bv['state']);
            $ch_ar['ctime'] = explode(',', $bv['ctime']);
            $ch_ar['boxtype'] = explode(',', $bv['boxtype']);
            $ch_ar['state'] = explode(',', $bv['state']);
            foreach($ch_ar['rpid'] as $cm=> $ck) {
                if ($bv['boxtype'] == 2) {
                    $tp= '机顶盒';
                    $tname = $bv['mac_name'];
                } else {
                    $tp = '小平台';
                    $tname = '';
                }
                $st_array['create_time'] = $ch_ar['ctime'][$cm];
                //获取解决是否
                if ( $ch_ar['state'][$cm] == 1) {
                    $st_array['state'] = '已解决：'.$tp.$tname;
                } elseif ( $ch_ar['state'][$cm] == 2) {
                    $st_array['state'] = '未解决：'.$tp.$tname;
                }
                //获取出错条件
                $rinfo = $rdeitalModel->fetchDataWhere(array('repair_id'=>$ch_ar['rpid'][$cm]),'','repair_type',2);
                if ( empty($rinfo) ) {
                    $st_array['repair_error'] = '';
                } else {
                    $repair_arr =  C('HOTEL_DAMAGE_CONFIG');
                    $dam_str = '';
                    foreach ($rinfo as $rv) {
                        $dam_str .= $repair_arr[$rv['repair_type']].',';
                    }
                    $dam_str = substr($dam_str,0 , -1);
                    $st_array['repair_error'] = $dam_str;
                }

                $st_array['remark'] = $ch_ar['remark'][$cm];
                $dac[] = $st_array;
            }
            foreach ($dac as $key => $row)
            {
                $volumbe[$key]  = $row['create_time'];
            }
            array_multisort($volumbe, SORT_DESC, $dac);
            $dap[$bk]['nickname'] = $bv['username'];
            $dap[$bk]['datetime'] = date("Y-m-d",
                strtotime($bv['datetime']));
            $dap[$bk]['hotel_name'] = $bv['hotel_name'];
            $dap[$bk]['repair_list'] = $dac;
        }
        $data['list'] = $dap;
        $data['isNextPage'] = $nextpage;
        $this->to_back($data);
    }

    public function getRepairBoxInfo($userid, $start, $size){
        $redMo = new \Common\Model\RepairBoxUserModel();
        $field = " sys.remark username,GROUP_CONCAT(sru.id) rpid,
                GROUP_CONCAT(sru.remark SEPARATOR '&&&') remark,
                GROUP_CONCAT(sru.state) state,GROUP_CONCAT(sru
                .create_time) ctime,sru.type boxtype,sbo.name
                mac_name,sru.datetime,sru.hotel_id,sru.mac,sht.name
                hotel_name ";
        if ( $userid ) {
            $condition = ' 1=1 and sru.userid ='.$userid;
            $group = 'sru.datetime,sru.hotel_id,sru.mac';
        } else {
            $condition = ' 1=1 ';
            $group = 'sru.datetime,sru.hotel_id,sru.userid,sru
            .mac';
        }
        $order = " CONCAT(sru.DATETIME,sru.create_time) DESC ";
        $box_info = $redMo->getRepairInfo($field, $condition, $group, $order, $start, $size);
        return $box_info;
    }

    public function getRepairRecordListByUserid() {
        $userid = $this->params['userid'];   //用户名
        $size  =  15;
        $start = empty($this->params['page_num'])?1:$this->params['page_num'];
        $start  = ( $start-1 ) * $size;
        $nextpage = 1;
        if ($userid == 0) {
            //获取所有
            $box_info = $this->getRepairBoxInfo($userid, $start, $size);
            //获取下一页是否有记录
            $box_info_next = $this->getRepairBoxInfo($userid, $start+$size, $size);
            if(empty($box_info_next)) {
                $nextpage = 0;
            }
        } else {
            $m_sysuser = new \Common\Model\SysUserModel();
            $where['status']   =1;
            $where['id'] = $userid;
            $userinfo = $m_sysuser->getUserInfo($where,'id as userid,username',2);
            if ( empty($userinfo) ) {
                $this->to_back('30001');    //用户不存在
            } else {
                $box_info = $this->getRepairBoxInfo($userid, $start, $size);
                //获取下一页是否有记录
                $box_info_next = $this->getRepairBoxInfo($userid, $start+$size, $size);
                if(empty($box_info_next)) {
                    $nextpage = 0;
                }

            }
        }
        $dat = $this->changeRepairInfo($box_info, $nextpage);

    }

    public function getAllRepairUser() {
        $m_sysuser = new \Common\Model\SysUserModel();
        $where['status']   =1;
        $userinfo = $m_sysuser->getUserInfo($where,'id as userid,username,remark as nickname',2);
        if(empty($userinfo)){
            $this->to_back('30001');    //用户不存在
        }
        $usr = array(
            'userid'=>0,
            'username'=>'所有人',
            'nickname'=>'所有人',

        );
        array_unshift($userinfo, $usr);
        $this->to_back($userinfo);
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
            $rep_str = $this->params['repair_num_str'];
            $rep_arr = explode(',', $rep_str);
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
        $reason_str = $this->params['repair_num_str'];
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
        if(mb_strlen($save['remark'],'utf8') > 100) {
            $this->to_back('30055');
        }

        if(empty($save['remark']) && empty($reason_str)) {
            $this->to_back('30054');
        }

    }

}