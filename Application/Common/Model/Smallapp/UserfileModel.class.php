<?php
namespace Common\Model\Smallapp;
use Common\Lib\AliyunOss;
use Common\Model\BaseModel;
use Common\Lib\AliyunImm;
class UserfileModel extends BaseModel{
    protected $tableName='smallapp_userfile';

    public function pushDwonloadFile($file_info,$type){
        $redis = \Common\Lib\SavorRedis::getInstance();
        $redis->select(5);
        $cachedown_key = C('SAPP_FILE_DOWNLOAD');
        $resource_list = array();
        switch ($type){//1分享文件 2投屏文件 3视频(商务宴请) 4图片(商务宴请) 5视频(生日聚会) 6图片(生日聚会)
            case 2:
                $download_key = $cachedown_key.':file:'.$file_info['id'];
                $res_download = $redis->get($download_key);
                if(!empty($res_download)){
                    $res_download = json_decode($res_download,true);
                    if(isset($res_download['code']) && $res_download['code']==10000){
                        return $res_download;
                    }
                }
                $resource_type = 3;
                $md5_file = $file_info['md5_file'];
                $cache_key = C('SAPP_FILE_FORSCREEN').':'.$md5_file;
                $res_cache = $redis->get($cache_key);
                if(!empty($res_cache)) {
                    $imgs = json_decode($res_cache, true);
                    if(!empty($imgs)){
                        foreach ($imgs as $v){
                            $filename = str_replace(array('forscreen/','/'),array('','_'),$v);
                            $resource_list[]=array('url'=>$v,'filename'=>$filename);
                        }
                    }
                }
                $box_mac = $file_info['box_mac'];
                break;
            case 3:
            case 5:
                $video_ids = array();
                foreach ($file_info as $v){
                    $video_ids[]=$v['id'];
                    $file_info_arr = pathinfo($v['file_path']);
                    $filename = $file_info_arr['basename'];

                    $resource_list[]=array('url'=>$v['file_path'],'filename'=>$filename);
                }
                $video_ids_str = join('-',$video_ids);
                $download_key = $cachedown_key.':video:'.$video_ids_str;
                $box_mac = $file_info[0]['box_mac'];
                $resource_type = 1;
                break;
            case 4:
            case 6:
                $img_ids = array();
                foreach ($file_info as $v){
                    $img_ids[]=$v['id'];
                    $file_info_arr = pathinfo($v['file_path']);
                    $filename = $file_info_arr['basename'];

                    $resource_list[]=array('url'=>$v['file_path'],'filename'=>$filename);
                }
                $img_ids_str = join('-',$img_ids);
                $download_key = $cachedown_key.':image:'.$img_ids_str;
                $box_mac = $file_info[0]['box_mac'];
                $resource_type = 2;
                break;
            default:
                $resource_type = 0;
                $download_key = '';
                $box_mac = '';
        }
        if(!empty($resource_list)){
            $message = array('action'=>171,'resource_type'=>$resource_type,'resource_list'=>$resource_list);
            $m_netty = new \Common\Model\NettyModel();
            $res_netty = $m_netty->pushBox($box_mac,json_encode($message));
            $res_netty['push_downtime'] = date('Y-m-d H:i:s');
            $redis->set($download_key,json_encode($res_netty),86400);
        }
    }

    public function getConversionStatusByTaskId($task_id){
        $aliyun = new AliyunImm();
        $res = $aliyun->getImgResponse($task_id);
        switch ($res->Status){
            case 'Running':
                $status = 1;
                break;
            case 'Finished':
                $status = 2;
                $img_num = $res->PageCount;
                if($img_num==0){
                    $status = 3;
                }
                break;
            case 'Failed':
                $status = 3;
                break;
            default:
                $status = 3;
        }
        return $status;
    }

    public function getCreateOfficeConversionResult($res){
        $log_file_name = APP_PATH.'Runtime/Logs/'.'filecnv_'.date("Ymd").".log";
        $log_content = date('Y-m-d H:i:s').'|task_id|'.$res->TaskId.'|conversionresult|start'."\r\n";
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
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
                    $log_content = date('Y-m-d H:i:s').'|task_id|'.$res->TaskId.'|conversionresult|result'.json_encode($exl_files)."\r\n";
                    @file_put_contents($log_file_name, $log_content, FILE_APPEND);

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
                if($img_num==0){
                    $status = 3;
                }
                $log_content = date('Y-m-d H:i:s').'|task_id|'.$res->TaskId.'|conversionresult|end'."\r\n";
                @file_put_contents($log_file_name, $log_content, FILE_APPEND);
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