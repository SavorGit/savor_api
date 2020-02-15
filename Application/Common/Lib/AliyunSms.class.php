<?php
namespace Common\Lib;

require_once APP_PATH.'Common/Lib/AliyunOpenapi/aliyun-dysms-php-sdk/api_sdk/vendor/autoload.php';
use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

// 加载区域结点配置
Config::load();

/**
 * 阿里云OSS
 *
 */

class AliyunSms{

    static $acsClient = null;

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient() {
        $product = "Dysmsapi";
        $domain = "dysmsapi.aliyuncs.com";
        $accessKeyId = C('IMM_ACCESS_ID');
        $accessKeySecret = C('IMM_ACCESS_KEY');
        $region = C('IMM_REGION_ID');
        $endPointName = C('IMM_REGION_ID');

        if(static::$acsClient == null) {
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送短信
     * @return stdClass
     */
    public static function sendSms($phone,$params,$template_code) {
        $request = new SendSmsRequest();

        //$request->setProtocol("https");//可选-启用https协议
        $request->setPhoneNumbers("$phone");
        $request->setSignName("小热点");
        $request->setTemplateCode("$template_code");
        if(!empty($params)){
            $request->setTemplateParam(json_encode($params, JSON_UNESCAPED_UNICODE));
        }
        //$request->setOutId("yourOutId");// 可选，设置流水号
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        return $acsResponse;
    }

}
?>