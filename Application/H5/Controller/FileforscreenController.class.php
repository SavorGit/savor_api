<?php
/*
 * H5文件投屏
 */
namespace H5\Controller;
use Think\Controller;

class FileforscreenController extends Controller {

    public function index(){
        $file_ext = C('SAPP_FILE_FORSCREEN_TYPES');
        $this->assign('file_ext',join(',',array_keys($file_ext)));
        $this->display();
    }

    public function launch_file(){
        $openid = I('get.openid','');
        if(empty($openid)){
            die('Parameter error');
        }
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'id,imgs,resource_name,md5_file';
        $where = array('openid'=>$openid,'action'=>30);
        $where['md5_file'] = array('neq','');
        $order = 'id desc';
        $res_latest = $m_forscreen->getWhere($fields,$where,$order,4,'md5_file');
        $latest_screen = array();
        $frequent_screen = array();
        if(!empty($res_latest)){
            $latest_md5_file = array();
            foreach ($res_latest as $v){
                $latest_md5_file[] = $v['md5_file'];
                $imgs = json_decode($v['imgs'],true);
                $file_type = pathinfo($imgs[0],PATHINFO_EXTENSION);
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name'].".$file_type");
                $latest_screen[] = $info;
            }
            $fields.=',count(id) as num';
            $order = 'num desc';
            $res_frequent = $m_forscreen->getWhere($fields,$where,$order,8,'md5_file');
            foreach ($res_frequent as $v){
                if(in_array($v['md5_file'],$latest_md5_file)){
                    continue;
                }
                if(count($frequent_screen)>=4){
                    break;
                }
                $imgs = json_decode($v['imgs'],true);
                $file_type = pathinfo($imgs[0],PATHINFO_EXTENSION);
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name'].".$file_type");
                $frequent_screen[] = $info;
            }
        }
        $file_ext = C('SAPP_FILE_FORSCREEN_TYPES');
        $this->assign('file_ext',join(',',array_keys($file_ext)));
        $this->assign('latest_screen',$latest_screen);
        $this->assign('frequent_screen',$frequent_screen);
        $this->display();
    }

}