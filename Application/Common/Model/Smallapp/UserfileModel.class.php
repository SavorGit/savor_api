<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;
use Common\Lib\AliyunImm;
class UserfileModel extends BaseModel{
	protected $tableName='smallapp_userfile';

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
                if($img_num==0){
                    $status = 3;
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