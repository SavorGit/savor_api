<?php
namespace Common\Lib;
use OSS\OssClient;
use OSS\Core\OssException;
/**
 * 阿里云OSS
 *
 */
require_once APP_PATH.'Common/Lib/AliyunOss/autoload.php';
class AliyunOss{
    private $ossClient = null;
    private $endpoint = '';
    private $accessKeyId = '';
    private $accessKeySecret = '';
    private $bucket = '';
    private $error='';
	public function __construct($accessKeyId,$accessKeySecret,$endpoint){
	    $this->endpoint = $endpoint;
	    $this->accessKeyId = $accessKeyId;
	    $this->accessKeySecret = $accessKeySecret;
	    $this->ossClient = $this->getOssClient();
	}
	
	/**
	 * 连接OSS
	 * @return NULL|\OSS\OssClient
	 */
	public function getOssClient(){
	    try {
	        $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint, false);
	    } catch (OssException $e) {
	        $error = __FUNCTION__ . "creating OssClient instance: FAILED". $e->getMessage();
	        $this->error = $error;
	        return false;
	    }
	    return $ossClient;
	}
	
	/**
	 * 获取错误信息
	 * @return string
	 */
	public function getError(){
	    return $this->error;
	}
	
	/**
	 * 设置存储桶
	 * @param string $bucket 存储通名称
	 * @return string
	 */
	public function setBucket($bucket){
	    $this->bucket = $bucket;
	    return $bucket;
	}
	
	/**
	 * 获取存储桶
	 * @return string
	 */	
	public function getBucket(){
	    return $this->bucket;
	}
	
	/**
	 * 创建存储桶
	 * @return Ambigous <string, string>
	 */
	public function createBucket(){
	    $ossClient = $this->ossClient;
	    if (is_null($ossClient)){
	        $this->error = '创建OSS连接失败';
	        exit();
	    }
	    $bucket = $this->bucket;
	    $acl = OssClient::OSS_ACL_TYPE_PUBLIC_READ;
	    try {
	        $create_bucket = $ossClient->createBucket($bucket, $acl);
	    } catch (OssException $e) {
	        $message = $e->getMessage();
	        if (\OSS\Core\OssUtil::startsWith($message, 'http status: 403')) {
	            $this->error = "Please Check your AccessKeyId and AccessKeySecret" . "\n";
	        } elseif (strpos($message, "BucketAlreadyExists") !== false) {
	            $this->error = "Bucket already exists. Please check whether the bucket belongs to you, or it was visited with correct endpoint. " . "\n";
	        }
	        return false;
	    }
	    return $create_bucket;
	}
	
	/**
	 * 上传本地文件
	 * @param string $file_name 文件名称
	 * @param string $file_path 本地文件路径
	 */
	public function uploadFile($file_name, $file_path){
	    try{
	        $ossClient = $this->ossClient;
	        $bucket = $this->bucket;
	        $upload = $ossClient->uploadFile($bucket, $file_name, $file_path);
	    }catch(OssException $e) {
	        $this->error = __FUNCTION__ . ": FAILED". $e->getMessage();
            return false;
	    }
	    return $upload;
	}

	/**
	 * 下载文件到服务器
	 * @param string $oss_addr 文件地址
	 * @param string $localfile 下载到服务器文件名称
	 */
	public function getObjectToLocalFile($oss_addr, $localfile){
	    $ossClient = $this->ossClient;
	    $options = array(
	        OssClient::OSS_FILE_DOWNLOAD => $localfile,
	    );
	    $bucket = $this->bucket;
	    try{
	        $download = $ossClient->getObject($bucket, $oss_addr, $options);
	    } catch(OssException $e) {
	        $this->error = __FUNCTION__ . ": FAILED". $e->getMessage();
	        return false;
	    }
	    return $download;
	}
	
	/**
	 * 范围下载
	 * @param string $oss_addr 文件地址
	 * @param string $range 文件范围如: 0-200 如不设置则为整个文件下载
	 * @return string
	 */
	public function getObject($oss_addr,$range='0-199'){
	    $ossClient = $this->ossClient;
	    $bucket = $this->bucket;
	    try{
	        if($range){
	            $options = array(OssClient::OSS_RANGE => $range);
	        }else{
	            $options = NULL;
	        }
	        $content = $ossClient->getObject($bucket,$oss_addr,$options);
	    } catch(OssException $e) {
	        $this->error = __FUNCTION__ . ": FAILED". $e->getMessage();
	        return false;
	    }
	    return $content;
	}

    public function deleteObject($oss_addr){
        $ossClient = $this->ossClient;
        $bucket = $this->bucket;
        try{
            $content = $ossClient->deleteObject($bucket,$oss_addr);
        } catch(OssException $e) {
            $this->error = __FUNCTION__ . ": FAILED". $e->getMessage();
            return false;
        }
        return $content;
    }

	public function getObjectlist($prefix,$nextMarker='',$maxkeys=1000){
	    $file_list = array();
        $ossClient = $this->ossClient;
        $bucket = $this->bucket;
        $delimiter = '';
        $options = array(
            'delimiter' => $delimiter,
            'prefix' => $prefix,
            'max-keys' => $maxkeys,
            'marker' => $nextMarker,
        );
        try {
            $listObjectInfo = $ossClient->listObjects($bucket, $options);
        } catch (OssException $e) {
            $this->error = __FUNCTION__ . ": FAILED". $e->getMessage();
            return false;
        }
        $objectList = $listObjectInfo->getObjectList(); // object list
        if (!empty($objectList)) {
            foreach ($objectList as $objectInfo) {
                $file_list[] = $objectInfo->getKey();
            }
        }
//        $prefixList = $listObjectInfo->getPrefixList(); // directory list
//        if (!empty($prefixList)) {
//            foreach ($prefixList as $prefixInfo) {
//                $file_list[] = $prefixInfo->getPrefix();
//            }
//        }
        return $file_list;
    }

    /**
     * 获取一个文件（Object）的元数据信息
     * @param string $oss_addr 文件地址
     * @return string
     */
    public function getObjectMeta($oss_addr){
        $ossClient = $this->ossClient;
        $bucket = $this->bucket;
        $content = $ossClient->getObjectMeta($bucket, $oss_addr);
        return $content;
    }
	
}
?>