<?php
namespace H5\Controller;
use Think\Controller;
use Common\Lib\Qrcode;

class QrcodeController extends Controller {

    public function index(){
        $url = I('get.url');
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        //生成二维码图片
        Qrcode::png($url,false,$errorCorrectionLevel, $matrixPointSize, 2);
    }


}