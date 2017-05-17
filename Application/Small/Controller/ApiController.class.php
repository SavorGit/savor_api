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
class ApiController extends CommonController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {

            case 'getDownloadList':
                $this->is_verify = 1;
                $this->valid_fields=array('hotelid'=>'1001','type'=>'1001',);
                
                break;
        }
        parent::_init_();
    }
    public function getDownloadList(){
        //'DOWNLOAD_HOTEL_INFO_TYPE'=>array('ads'=>1,'adv'=>2,'pro'=>3,'vod'=>4,'logo'=>5,'load'=>6)
        $hotelModel = new \Common\Model\HotelModel();
        $hotelid = $this->params['hotelid']; //hotelid
        $type = $this->params['type'];    //类型：
        $hotel_info_type_arr = C('DOWNLOAD_HOTEL_INFO_TYPE');  //下载来源数组
        if(!is_numeric($hotelid)){
            $this->to_back(10007);
        }
        $count = $hotelModel->where(array('id'=>$hotelid))->count();
        if($count == 0){
            $this->to_back(10007);
        }
        //判断酒店id是否存在

        if(!array_key_exists($type, $hotel_info_type_arr)){
            $this->to_back(16001);
        }
        $d_type = $hotel_info_type_arr[$type];

        switch ($d_type) {
            case 1:
                //广告
                $this->getadsData($hotelid);
                break;
                //宣传片
            case 2:
                $this->getadvData($hotelid);
                break;
                //节目
            case 3:
                $this->getproData($hotelid);
                break;
                //手机点播
            case 4:
                $this->getvodData($hotelid);
                break;
                //logo数据
            case 5:
                $this->getlogoData($hotelid);
                break;
                //loading图l
            case 6:
                $this->getloadData($hotelid);
                break;
            default:
                break;

        }

    }


    /**
     * getadsData 获取酒楼广告类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getadsData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        //获取广告期号
        $per_arr = $apiModel->getadsPeriod($hotelid);
        $menuid = $per_arr[0]['menuId'];
        $ads_arr = $apiModel->getadsInfo($menuid);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $ads_arr;
        $this->to_back($data);

    }

    /**
     * getadvData 获取酒楼广告类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getadvData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        //获取广告期号
        $per_arr = $apiModel->getadsPeriod($hotelid);
        $menuid = $per_arr[0]['menuId'];
        $adv_arr = $apiModel->getadvInfo($hotelid, $menuid);
        $adv_arr = $this->changeadvList($adv_arr);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $adv_arr;
        $this->to_back($data);

    }


    /**
     * getproData 获取酒楼节目类型数据
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getproData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        //获取广告期号
        $per_arr = $apiModel->getadsPeriod($hotelid);
        $menuid = $per_arr[0]['menuId'];
        $pro_arr = $apiModel->getproInfo($menuid);
        $pro_arr = $this->changeadvList($pro_arr);
        $data = array();
        $data['period'] = $per_arr[0]['period'];
        $data['pub_time'] = $per_arr[0]['pubTime'];
        $data['menu_hotel_id'] = $per_arr[0]['menuHotelId'];
        $data['media_list'] = $pro_arr;
        $this->to_back($data);

    }


    /**
     * getvodData 获取酒楼手机点播
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getvodData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        //获取广告期号
        $vod_per_arr = $apiModel->getvodPeriod();
        $version = $vod_per_arr[0]['period'];
        $ver_arr = $apiModel->getvodInfo();
        $ver_arr = $this->changevodList($ver_arr, $version);
        $data = array();
        $data['period'] = $version;
        $data['media_list'] = $ver_arr;
        $this->to_back($data);

    }



    /**
     * getloadData 获取酒楼手机loading图
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getloadData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        $load_arr = $apiModel->getloadInfo($hotelid);
        $load_arr = $this->changeadvList($load_arr);
        $data = array();
        $data['period'] = $load_arr[0]['version'];
        $data['media_list'] = $logo_arr;
        $this->to_back($data);
    }



    /**
     * getlogoData 获取酒楼手机logo
     * @access public
     * @param $hotelid 酒楼id
     * @return array
     */
    public function getlogoData($hotelid){
        $apiModel = new \Common\Model\ApiModel();
        $logo_arr = $apiModel->getlogoInfo($hotelid);
        $logo_arr = $this->changeadvList($logo_arr);
        $data = array();
        $data['period'] = $logo_arr[0]['version'];
        $data['media_list'] = $logo_arr;
        $this->to_back($data);
    }

    /**
     * changeadvList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    public function changeadvList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['order'] =  $res[$vk]['sortNum'];
                unset($res[$vk]['sortNum']);
            }

        }
        return $res;
        //如果是空
    }

    /**
     * changevodList  将已经数组修改字段名称
     * @access public
     * @param $res
     * @return array
     */
    public function changevodList($res,$version){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['order'] =  $res[$vk]['sortNum'];
                $res[$vk]['version'] =  $version;
                unset($res[$vk]['sortNum']);
            }

        }
        return $res;
        //如果是空
    }


    public function changeadvsfserList($res){
        if($res){
            foreach ($res as $vk=>$val) {
                $res[$vk]['order'] =  $res[$vk]['sortNum'];
                unset($res[$vk]['sortNum']);
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
                }
                if(!empty($val['name'])){
                    $ttp = explode('/', $val['name']);
                    $res[$vk]['name'] = $ttp[2];
                }
            }

        }
        return $res;
        //如果是空
    }
}