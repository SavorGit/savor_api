<?php
/**
 * Project savor_api
 *
 * @author baiyutao <------@gmail.com> 2017-5-23
 */
namespace Screendistance\Controller;

use \Common\Controller\BaseController as BaseController;
/**
 * Class DistanceController
 * 客户端非酒楼环境，酒楼环境距离测量
 * @package Screendistance\Controller
 */
class DistanceController extends BaseController{
 	/**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getHotelDistance':
               // $this->valid_fields=array('lat'=>'1001','lng'=>'1001');
               // $this->is_verify = 1;
                $this->is_verify = 0;
                break;
            case 'getAllDistance':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }


    /**
     * getAlldis
     * 获取酒楼所有包间个数的数组
     * @return array|bool|mixed
     */
    private function getAlldis(){
        $hotelModel = new \Common\Model\HotelModel();
        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(15);
        $dkey = 'savor_hotel_distance';
        $h_all = $redis->get($dkey);
        if($h_all){
            $hotel_distance_arr = json_decode($h_all, true);
        }else{
            $field = 'id,name,addr,gps';
            $hotel_distance_arr = $hotelModel->getAllDis($field);
            if($hotel_distance_arr){
                foreach($hotel_distance_arr as $rk=>$rdv){
                    //求酒楼包间数量
                    $numbers = $hotelModel->getRoomNumByHotelId($rdv['id']);
                    $gp_arr = explode(',',$rdv['gps']);
                    $hotel_distance_arr[$rk]['roomnum'] = $numbers;
                    $hotel_distance_arr[$rk]['lat'] = $gp_arr[1];
                    $hotel_distance_arr[$rk]['lng'] = $gp_arr[0];
                    unset($hotel_distance_arr[$rk]['gps']);
                }
            }
            $redis->set($dkey, json_encode($hotel_distance_arr),86400);
        }
        //酒楼ID无经纬度无
        if(empty($lat)  || empty($lng)){
            if($hotel_distance_arr){
                foreach ($hotel_distance_arr as $rov) {
                    $ages[] = $rov['roomnum'];
                }
                array_multisort($ages, SORT_DESC, $hotel_distance_arr);
            }
        } else {
            if($hotel_distance_arr){
                //酒楼ID无经纬度有
                $hotel_distance_arr = $this->calculateDistance($hotel_distance_arr, $lat, $lng);
                foreach ($hotel_distance_arr as $rov) {
                    $rnm[] = $rov['roomnum'];
                    $kms[] = $rov['dis'];
                }
                //排序
                array_multisort($kms,SORT_ASC,$rnm, SORT_DESC, $hotel_distance_arr);
            }
        }
        return $hotel_distance_arr;
    }

    /**
     * calculateDistance
     * @desc 计算 得到km数组
     * @param $hotel_distance_arr
     * @param $lat 纬度
     * @param $lng 经度
     * @return array|bool
     */
    private function calculateDistance($hotel_distance_arr, $lat, $lng){


        $dismodel = new \Common\Model\DistanceModel();
        $h_dis_ar = $dismodel->range($lat,lng,$hotel_distance_arr);
        var_dump($h_dis_ar);
        die;
        return $h_dis_ar;
    }

    /**
     * getHotelDis
     * @desc 获取酒楼id所对应的数组
     * @param $hotelid 酒楼
     * @param $lat 纬度
     * @param $lng 经度
     * @return array|bool|mixed
     */
    private function getHotelDis($hotelid, $lat, $lng){

        $redis  =  \Common\Lib\SavorRedis::getInstance();
        $redis->select(15);
        $hotelModel = new \Common\Model\HotelModel();
        $dkey = 'savor_distance_'.$hotelid;
        //先判断缓存是否有该酒楼id数据savor_distance_id
        $h_dat = $redis->get($dkey);
        $h_ar = array();
        if($h_dat){
            $h_ar = json_decode($h_dat, true);
        }else{
            //判断有一个经纬度是否为空
            if( empty($lat)  || empty($lng) ) {
                //按包间数量算
                $h_ar = $this->getAlldis();
                if($h_ar){
                    foreach ($h_ar as $rov) {
                        $ages[] = $rov['roomnum'];
                    }
                    array_multisort($ages, SORT_DESC, $h_ar);
                }
            } else {
                $field = 'id,name,addr,gps';
                $hotel_distance_arr = $hotelModel->getHotelDis($field, $hotelid);
                if($hotel_distance_arr){
                    foreach($hotel_distance_arr as $rk=>$rdv){
                        $gp_arr = explode(',',$rdv['gps']);
                        $hotel_distance_arr[$rk]['lat'] = $gp_arr[1];
                        $hotel_distance_arr[$rk]['lng'] = $gp_arr[0];
                        unset($hotel_distance_arr[$rk]['gps']);

                    }
                    $h_ar = $this->calculateDistance($hotel_distance_arr, $lat, $lng);
                    foreach($h_ar as $h=>$hv) {
                        if($hv['id'] == $hotelid){
                            $get_hr  = $hv;
                            $get_hr['dis'] = 0;
                            unset($h_ar[$h]);
                            break;
                        }
                    }
                    array_unshift($h_ar, $get_hr);
                    $redis->set($dkey, json_encode($h_ar),86400);
                }
            }

        }
        return $h_ar;
    }




    /**
     * checkDisInfo
     * @desc 检验酒楼id以及经纬度的合法性
     * @access public
     * @param $hotelid 酒楼id
     * @return array 返回经纬度数组
     * lat		纬度值
     *  lng	经度值
     */
    private function checkDisInfo($hotelid){
        $hotelModel = new \Common\Model\HotelModel();
        if(!empty($hotelid)){
            if(!is_numeric($hotelid)){
                $this->to_back(10007);
            }
            $hotelinfo = $hotelModel->find($hotelid);
            if(count($hotelinfo) == 0){
                $this->to_back(10007);
            }else{
                $gps_arr = explode(',', $hotelinfo['gps']);
            }
        }
        $lat = $this->params['lat'];
        $lng = $this->params['lng'];
        if(empty($lat)){
            $lat = $gps_arr[1];
        } else{
            if($lat<0 || $lat>90){
                $this->to_back(17001);
            }
        }

        if(empty($lng)){
            $lng = $gps_arr[0];
        } else{
            if($lng<0 || $lng>180){
                $this->to_back(17002);
            }
        }

        $lng = $this->sctonum($lng);


        $lat = $this->sctonum($lat);

        return array('lat'=>$lat,'lng'=>$lng);

    }

    /**
     * @desc 非酒楼和酒楼环境对应距离
     * @access public
     * 	lat		纬度值
     *  lng	经度值
     * 酒楼录入按经度，纬度
     * @return json
     */
    public function getHotelDistance(){
        $hotelid = $this->params['hotelid'];
        $gps_arr = $this->checkDisInfo($hotelid);
        $lat = $gps_arr['lat'];
        $lng = $gps_arr['lng'];
        $h_ar = array();

        if($hotelid){
            $data = $this->getHotelDis($hotelid,$lat , $lng);
        }else{
            //全部数据拿出
            $data = $this->getAlldis();
        }

        if($data){
            $h_ar = array_slice($data,0,3);
            foreach($h_ar as $da=>$dv){
                if($dv['dis'] >=1){
                    $h_ar[$da]['dis'] = $dv['dis'].'km';
                }else{
                    $h_ar[$da]['dis'] = ($dv['dis']*1000).'m';
                }
            }
        }
        $this->to_back($h_ar);
    }

    public function getAllDistance(){
        $page_size = 10;
        $page_num = $this->params['pageNum'];
        if(!$page_num){
            $page_num = 1;
        }
        $hotelid = $this->params['hotelid'];
        $gps_arr = $this->checkDisInfo($hotelid);
        $lat = $gps_arr['lat']; //纬度
        $lng = $gps_arr['lng']; //经度
        $h_ar = array();
        if($hotelid){
            $data = $this->getHotelDis($hotelid,$lat , $lng);
        }else{
            //全部数据拿出
            $data = $this->getAlldis();
        }
        if($data){
            $start = ($page_num-1)*$page_size;
            $h_ar = array_slice($data,$start,$page_size);
            foreach($h_ar as $da=>$dv){
                if(isset($dv['dis'])){
                    if($dv['dis'] >=1){
                        $h_ar[$da]['dis'] = $dv['dis'].'km';
                    }else{
                        $h_ar[$da]['dis'] = ($dv['dis']*1000).'m';
                    }
                }

            }
        }
        $this->to_back($h_ar);
    }


    /**
     * @param $num         科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认6位
     * @return string
     */

   public function sctonum($num, $double = 6){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }else{
            return $num;
        }
    }

}