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

    public function mpQrcode(){
        $order_id = I('get.oid',0,'intval');
        $color = array("r"=>255,"g"=>255,"b"=>255);

        $m_small_app = new Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $times = getMillisecond();
        $data['scene'] = $order_id.'_'.$times;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/thematic/money_blessing/grab";//扫描后对应的path
        $data['width'] = "280";//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = true;
        $data = json_encode($data);
        $m_small_app->getSmallappCode($tokens,$data);
    }

}