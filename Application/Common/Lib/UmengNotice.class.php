<?php
//defined('IN_PHPCMS') or exit('No permission resources.');
namespace Common\Lib;

use Common\Lib\Curl;
use Umengnotice\Android\AndroidUnicast;
use Umengnotice\Android\AndroidListcast;
use Umengnotice\Ios\IOSListcast;
use Umengnotice\Ios\IOSUnicast;


require_once APP_PATH.'Common/Lib/Umengnotice/autoload.php';

class UmengNotice {
    private $umeng_api_config ;
    public function __construct(){
        $this->umeng_api_config = C('UMENT_API_CONFIG');
    }


    public function sendAndroidUnicast() {
        try {
            $unicast = new AndroidUnicast();
            $unicast->setAppMasterSecret($this->appMasterSecret);
            $unicast->setPredefinedKeyValue("appkey",           $this->appkey);
            $unicast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your device tokens here
            $unicast->setPredefinedKeyValue("device_tokens",    "xx");
            $unicast->setPredefinedKeyValue("ticker",           "Android unicast ticker");
            $unicast->setPredefinedKeyValue("title",            "Android unicast title");
            $unicast->setPredefinedKeyValue("text",             "Android unicast text");
            $unicast->setPredefinedKeyValue("after_open",       "go_app");
            // Set 'production_mode' to 'false' if it's a test device.
            // For how to register a test device, please see the developer doc.
            $unicast->setPredefinedKeyValue("production_mode", "true");
            // Set extra fields
            $unicast->setExtraField("test", "helloworld");
            print("Sending unicast notification, please wait...\r\n");
            $unicast->send();
            print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            print("Caught exception: " . $e->getMessage());
        }
    }


    public function  umeng_android($type){

      switch ($type) {
          case 'unicast':
              $obj = new AndroidUnicast();
              break;

          case 'listcast':
              $obj = new AndroidListcast();
              break;

      }
      return $obj;
    }
    public function  umeng_ios($type){
        switch ($type) {
            case 'unicast':
                $obj = new IOSUnicast();
                break;

            case 'listcast':
                $obj = new IOSListcast();
                break;

        }
        return $obj;
    }

}