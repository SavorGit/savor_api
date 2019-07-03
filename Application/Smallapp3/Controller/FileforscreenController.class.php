<?php
namespace Smallapp3\Controller;
use Common\Lib\AliyunImm;
use \Common\Controller\CommonController as CommonController;
use Common\Lib\AliyunOss;

class FileforscreenController extends CommonController{

    function _init_() {
        switch(ACTION_NAME) {
            case 'fileconversion':
                $this->is_verify =1;
                $this->valid_fields = array('openid'=>1001,'box_mac'=>1001,'oss_addr'=>1001,
                    'resource_name'=>1001,'mobile_brand'=>1000,'mobile_model'=>1000,'action'=>1000,
                    'resource_type'=>1000,'resource_size'=>1000,'res_sup_time'=>1000,'res_eup_time'=>1000
                );
                break;
            case 'getresult':
                $this->is_verify = 1;
                $this->valid_fields = array('task_id'=>1001);
                break;
            
        }
        parent::_init_();
    }


    public function fileconversion(){
        $openid = $this->params['openid'];
        $box_mac = $this->params['box_mac'];
        $oss_addr = $this->params['oss_addr'];
        $mobile_brand = $this->params['mobile_brand'];
        $mobile_model = $this->params['mobile_model'];
        $resource_name = $this->params['resource_name'];
        $action = $this->params['action'] ? $this->params['action'] : 0;
        $resource_type = $this->params['resource_type'] ? $this->params['resource_type'] : 0;
        $resource_size = $this->params['resource_size'] ? $this->params['resource_size'] :0;
        $res_sup_time = $this->params['res_sup_time'] ? $this->params['res_sup_time'] : 0;
        $res_eup_time = $this->params['res_eup_time'] ? $this->params['res_eup_time'] : 0;
        $imgs = json_encode(array($oss_addr));

        $data = array();
        $data['openid'] = $openid;
        $data['box_mac']= $box_mac;
        $data['action'] = $action;
        $data['resource_type'] = $resource_type;
        $data['mobile_brand'] = $mobile_brand;
        $data['mobile_model'] = $mobile_model;
        $data['imgs'] = $imgs;
        $data['res_sup_time']= $res_sup_time;
        $data['res_eup_time']= $res_eup_time;
        $data['resource_size'] = $resource_size;
        $data['resource_name'] = $resource_name;
        $data['create_time'] = date('Y-m-d H:i:s');

        $accessKeyId = C('OSS_ACCESS_ID');
        $accessKeySecret = C('OSS_ACCESS_KEY');
        $endpoint = C('OSS_HOST');
        $bucket = C('OSS_BUCKET');
        $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
        $aliyunoss->setBucket($bucket);
        $fileinfo = $aliyunoss->getObject($oss_addr,'');
        $data['md5_file'] = md5($fileinfo);

        $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
        $forscreen_id = $m_forscreenrecord->add($data);


        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SAPP_FILE_FORSCREEN');
        $cache_key = $key.':'.$data['md5_file'];
        $res_cache = $redis->get($cache_key);
        if(empty($res_cache)){
            $aliyun = new AliyunImm();
            $res = $aliyun->createOfficeConversion($oss_addr);
            $result = $this->getCreateOfficeConversionResult($res);
            if($result['status']==2){
                $redis->set($cache_key,json_encode($result['imgs']));
            }
        }else{
            $imgs = json_decode($res_cache,true);
            $result = array('status'=>2,'task_id'=>0,'percent'=>100,'imgs'=>$imgs);
        }
        $result['forscreen_id'] = $forscreen_id;
        $this->to_back($result);
    }

    public function getresult(){
        $task_id = $this->params['task_id'];
        $aliyun = new AliyunImm();
        $res = $aliyun->getImgResponse($task_id);
        $result = $this->getCreateOfficeConversionResult($res);
        $this->to_back($result);
    }

    private function getCreateOfficeConversionResult($res){
        $oss_host = C('OSS_HOST');
        switch ($res->Status){
            case 'Running':
                $status = 1;
                $task_id = $res->TaskId;
                $percent = $res->Percent;
                $imgs = array();
                break;
            case 'Finished':
                $status = 2;
                $task_id = 0;
                $percent = 100;
                $img_host = str_replace('oss://redian-development',"http://$oss_host",$res->TgtUri);
                $imgs = array();
                for($i=1;$i<=$res->PageCount;$i++){
                    $imgs[] = $img_host.$i.'.'.$res->TgtType;
                }
                break;
            case 'Failed':
                $status = 3;
                $task_id = 0;
                $percent = 0;
                $imgs = array();
                break;
            default:
                $status = 0;
                $task_id = 0;
                $percent = 0;
                $imgs = array();
        }
        $result = array('status'=>$status,'task_id'=>$task_id,'percent'=>$percent,'imgs'=>$imgs);
        return $result;
    }




}
