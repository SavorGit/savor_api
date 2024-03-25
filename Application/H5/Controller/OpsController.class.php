<?php

namespace H5\Controller;
use Think\Controller;

class OpsController extends Controller {


    public function testmap(){
        $lon = 113.28885448092;
        $lat = 23.139777303895;

        $key = C('TIAN_DITU_KEY');
        $url = "https://api.tianditu.gov.cn/geocoder?postStr={'lon':$lon,'lat':$lat,'ver':1}&type=geocode&tk=$key";
        $curl = new \Common\Lib\Curl();
        $result = '';
        $curl::get($url,$result,3);
        echo $result;
        echo '=====';
        if(!empty($result)){
            $res = json_decode($result,true);
            print_r($res);
        }
    }
}