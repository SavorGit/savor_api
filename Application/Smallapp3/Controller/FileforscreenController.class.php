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
                    'resource_name'=>1001,'mobile_brand'=>1002,'mobile_model'=>1002,'action'=>1002,
                    'resource_type'=>1002,'resource_size'=>1002,'res_sup_time'=>1002,'res_eup_time'=>1002,
                    'save_type'=>1002
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
        $save_type = intval($this->params['save_type']);

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
        if($save_type){
            $data['save_type'] = $save_type;
        }

        $accessKeyId = C('OSS_ACCESS_ID');
        $accessKeySecret = C('OSS_ACCESS_KEY');
        $endpoint = 'oss-cn-beijing.aliyuncs.com';
        $bucket = C('OSS_BUCKET');
        $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
        $aliyunoss->setBucket($bucket);
        $fileinfo = $aliyunoss->getObject($oss_addr,'');
        $md5_file = '';
        if($fileinfo){
            $md5_file = md5($fileinfo);
        }
        $data['md5_file'] = $md5_file;
        $m_forscreenrecord = new \Common\Model\Smallapp\ForscreenRecordModel();
        $forscreen_id = $m_forscreenrecord->add($data);

        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SAPP_FILE_FORSCREEN');
        $cache_key = $key.':'.$md5_file;
        $res_cache = $redis->get($cache_key);
        if(empty($res_cache)){
            $aliyun = new AliyunImm();
            $res = $aliyun->createOfficeConversion($oss_addr);
            $result = $this->getCreateOfficeConversionResult($res);
            if($result['status']==2){
                $redis->set($cache_key,json_encode($result['imgs']));
            }
            if($result['task_id'] && $md5_file){
                $task_key = $key.':'.$result['task_id'];
                $redis->set($task_key,$md5_file,1800);
            }
        }else{
            $imgs = json_decode($res_cache,true);
            $img_num = count($imgs);
            $oss_host = C('OSS_HOST');
            $result = array('status'=>2,'task_id'=>0,'percent'=>100,
                'oss_host'=>"http://$oss_host",'oss_suffix'=>'?x-oss-process=image/resize,p_20',
                'imgs'=>$imgs,'img_num'=>$img_num);
        }
        $result['forscreen_id'] = $forscreen_id;
        $this->to_back($result);
    }

    public function getresult(){
        $task_id = $this->params['task_id'];
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $key = C('SAPP_FILE_FORSCREEN');
        $task_key = $key.':'.$task_id;
        $md5_file = $redis->get($task_key);
        if($md5_file){
            $cache_key = $key.':'.$md5_file;
            $res_cache = $redis->get($cache_key);
        }else{
            $res_cache = array();
        }

        if(!empty($res_cache)){
            $imgs = json_decode($res_cache,true);
            $img_num = count($imgs);
            $oss_host = C('OSS_HOST');
            $result = array('status'=>2,'task_id'=>0,'percent'=>100,'oss_host'=>"http://$oss_host",'oss_suffix'=>'?x-oss-process=image/resize,p_20','imgs'=>$imgs,'img_num'=>$img_num);
        }else{
            $aliyun = new AliyunImm();
            $res = $aliyun->getImgResponse($task_id);
            $result = $this->getCreateOfficeConversionResult($res);
            if($result['status']==2 && $md5_file){
                $redis->set($cache_key,json_encode($result['imgs']));
            }
        }
        $this->to_back($result);
    }

    private function getCreateOfficeConversionResult($res){
        $oss_host = C('OSS_HOST');
        $bucket = C('OSS_BUCKET');
        $file_types = C('SAPP_FILE_FORSCREEN_TYPES');
        $img_num = 0;
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
                $img_num = $res->PageCount;
                $oss_url = str_replace("oss://$bucket/","",$res->TgtUri);
                $file_info = pathinfo($res->SrcUri);
                if($file_types[$file_info['extension']]==1){
                    $prefix = $oss_url;
                    $accessKeyId = C('OSS_ACCESS_ID');
                    $accessKeySecret = C('OSS_ACCESS_KEY');
                    $endpoint = 'oss-cn-beijing.aliyuncs.com';
                    $aliyunoss = new AliyunOss($accessKeyId, $accessKeySecret, $endpoint);
                    $aliyunoss->setBucket($bucket);
                    $exl_files = $aliyunoss->getObjectlist($prefix);
                    $tmp_imgs = array();
                    foreach ($exl_files as $v){
                        $img_info = pathinfo($v);
                        $tmp_imgs[$img_info['dirname']][]=$img_info['filename'];
                    }
                    foreach ($tmp_imgs as $k=>$v){
                        $img_list = $v;
                        sort($img_list,SORT_NUMERIC);
                        $tmp_imgs[$k]=$img_list;
                    }

                    $res_dir = array_keys($tmp_imgs);
                    $exl_dirname = '';
                    $tmp_dir = array();
                    foreach ($res_dir as $v){
                        $dir_info = pathinfo($v);
                        $exl_dirname = $dir_info['dirname'];
                        $dir_finfo = explode('s',$dir_info['filename']);
                        $tmp_dir[] = $dir_finfo[1];
                    }
                    sort($tmp_dir);
                    $tmp_imgs_sort = array();
                    foreach ($tmp_dir as $v){
                        $dir_key = $exl_dirname."/s$v";
                        $tmp_imgs_sort[$dir_key] = $tmp_imgs[$dir_key];
                    }
                    $imgs = array();
                    foreach ($tmp_imgs_sort as $k=>$v){
                        foreach ($v as $vv){
                            $oss_path = $k."/$vv.png";
                            $imgs[] = $oss_path;
                        }
                    }
                }else{
                    $imgs = array();
                    for($i=1;$i<=$res->PageCount;$i++){
                        $oss_path = $oss_url.$i.'.'.$res->TgtType;
                        $imgs[] = $oss_path;
                    }
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
        $result = array('status'=>$status,'task_id'=>$task_id,'percent'=>$percent,
            'oss_host'=>"http://$oss_host",'oss_suffix'=>'?x-oss-process=image/resize,p_20',
            'imgs'=>$imgs,'img_num'=>$img_num);
        return $result;
    }
}
