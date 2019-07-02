<?php
namespace Common\Lib;

use imm\Request\V20170906 as Imm;

/**
 * 阿里云OSS
 *
 */
require_once APP_PATH.'Common/Lib/AliyunOpenapi/aliyun-php-sdk-core/Config.php';
class AliyunImm{

    public function file2img($oss_addr){
        $region_id = C('IMM_REGION_ID');
        $access_id = C('IMM_ACCESS_ID');
        $access_key = C('IMM_ACCESS_KEY');
        $projectName = C('IMM_PROJECT');
        $oss_bucket = C('OSS_BUCKET');

        $iClientProfile = \DefaultProfile::getProfile($region_id,$access_id,$access_key);
        $client = new \DefaultAcsClient($iClientProfile);

        // 创建文档转换任务
        $request = new Imm\CreateOfficeConversionTaskRequest();
        $request->setProject($projectName);
        // 设置待转换对文件OSS路径

        $request->setEndPage(-1);//转换全部页，设置为-1
        $request->setMaxSheetRow(-1);//转换所有行，设置为-1
        $request->setMaxSheetCol(-1);//转换所有列，设置为-1
        $request->setMaxSheetCount(-1);//转换所有Sheet，设置为-1
        $request->setSrcUri("oss://$oss_bucket/$oss_addr");
        // 设置文件输出格式
        $request->setTgtType("png");
        // 设置转换后的输出路径
        $addr_info = pathinfo($oss_addr);
        $out_addr = str_replace(".{$addr_info['extension']}","_{$addr_info['extension']}/",$oss_addr);
        $request->setTgtUri("oss://$oss_bucket/$out_addr");
        $response = $client->getAcsResponse($request);
        return $response;
    }

    public function getImgResponse($task_id){
        $region_id = C('IMM_REGION_ID');
        $access_id = C('IMM_ACCESS_ID');
        $access_key = C('IMM_ACCESS_KEY');
        $projectName = C('IMM_PROJECT');
        $iClientProfile = \DefaultProfile::getProfile($region_id,$access_id,$access_key);
        $client = new \DefaultAcsClient($iClientProfile);

        $request = new Imm\GetOfficeConversionTaskRequest();
        $request->setTaskId($task_id);
        $request->setProject($projectName);
        $response = $client->getAcsResponse($request);
        return $response;
    }

}
?>