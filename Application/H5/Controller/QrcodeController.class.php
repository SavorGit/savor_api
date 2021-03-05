<?php
namespace H5\Controller;
use Think\Controller;
use Common\Lib\Qrcode;
use Common\Lib\AliyunOss;

class QrcodeController extends Controller {

    public function index(){
        $url = I('get.url');
        $errorCorrectionLevel = 'L';//容错级别
        $matrixPointSize = 5;//生成图片大小
        //生成二维码图片
        Qrcode::png($url,false,$errorCorrectionLevel, $matrixPointSize, 2);
    }

    public function mpQrcode(){
        $qrinfo = I('get.qrinfo','');
        $color = array("r"=>255,"g"=>255,"b"=>255);

        $m_small_app = new \Common\Lib\Smallapp_api();
        $tokens  = $m_small_app->getWxAccessToken();
        header('content-type:image/png');
        $data = array();
        $times = getMillisecond();
        $data['scene'] = $qrinfo.'_'.$times;//自定义信息，可以填写诸如识别用户身份的字段，注意用中文时的情况
        $data['page'] = "pages/thematic/money_blessing/grab";//扫描后对应的path
        $data['width'] = "280";//自定义的尺寸
        $data['auto_color'] = false;//是否自定义颜色
        $data['line_color'] = $color;//自定义的颜色值
        $data['is_hyaline'] = true;
        $data = json_encode($data);
        $m_small_app->getSmallappCode($tokens,$data);
    }

    public function bonusQrcode(){
        $qrinfo = I('get.qrinfo','');
        $expire_seconds = 3600*4;
        $data = array(
            'expire_seconds'=>$expire_seconds,
            'action_name'=>"QR_STR_SCENE",
            'action_info'=>array(
                'scene'=>array('scene_str'=>$qrinfo)
            ),
        );
        $wechat = new \Common\Lib\Wechat();
        $res = $wechat->qrcodecreate(json_encode($data));
        if(empty($res)){
            $res = $wechat->qrcodecreate(json_encode($data));
        }
        $res_info = json_decode($res,true);
        if(!empty($res_info['url'])){
            $errorCorrectionLevel = 'L';//容错级别
            $matrixPointSize = 5;//生成图片大小
            Qrcode::png($res_info['url'],false,$errorCorrectionLevel, $matrixPointSize, 0);
        }
    }

    public function testmd5(){
        $accessKeyId = C('OSS_ACCESS_ID');
        $accessKeySecret = C('OSS_ACCESS_KEY');
        $endpoint = 'oss-cn-beijing.aliyuncs.com';
        $bucket = C('OSS_BUCKET');
        $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
        $aliyunoss->setBucket($bucket);

        $oss_addr = 'forscreen/resource/1575698056011.mp4';
        $resource_size = 2728665;

        $range = '0-199';
        $bengin_info = $aliyunoss->getObject($oss_addr,$range);
        $last_size = $resource_size-1;
        $last_range = $last_size - 199;
        $last_range = $last_range.'-'.$last_size;
        $end_info = $aliyunoss->getObject($oss_addr,$last_range);
        $file_str = md5($bengin_info).md5($end_info);
        $fileinfo = strtoupper($file_str);

        if(!empty($fileinfo)){
            echo md5($fileinfo);
            echo '===';
            $fileinfo = $aliyunoss->getObject($oss_addr,'');
            echo md5($fileinfo);
        }else{
            echo 'fail';
        }

    }

}