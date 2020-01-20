<?php
/*
 * H5文件投屏
 */
namespace H5\Controller;
use Think\Controller;

class FileforscreenController extends Controller {

    public function index(){
        $openid = I('get.openid','');
        $source = I('get.source','');
        if(empty($openid)){
            die('Parameter error');
        }
        $m_forscreen = new \Common\Model\Smallapp\ForscreenRecordModel();
        $fields = 'id,imgs,resource_name,md5_file';
        $where = array('openid'=>$openid,'action'=>30,'save_type'=>2,'file_conversion_status'=>1);
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
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name']);
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
                $info = array('forscreen_id'=>$v['id'],'file_type'=>strtoupper($file_type),'file_name'=>$v['resource_name']);
                $frequent_screen[] = $info;
            }
        }
        if($source=='sale'){
            $display_html = 'sale';
        }else{
            $display_html = 'index';
        }
        $file_ext = C('SAPP_FILE_FORSCREEN_TYPES');
        $this->assign('file_ext',join(',',array_keys($file_ext)));
        $this->assign('latest_screen',$latest_screen);
        $this->assign('frequent_screen',$frequent_screen);
        $this->display($display_html);
    }

    public function addlog(){
        $os_agent = $_SERVER['HTTP_USER_AGENT'];
        $wx_browser = (bool) stripos($os_agent,'MicroMessenger');
        $res = array('code'=>10001,'msg'=>'fail');
        if($wx_browser){
            $redis = \Common\Lib\SavorRedis::getInstance();
            $redis->select(5);
            $key = C('SAPP_FILE_FORSCREEN');
            $cache_key = $key.':h5file_forscreen_report';
            $res_cache = $redis->get($cache_key);
            if(empty($res_cache)){
                $num = 1;
            }else{
                $num = intval($res_cache)+1;
            }
            $redis->set($cache_key,$num);

            $res['code'] = 10000;
            $res['msg'] = 'success';
        }
        $this->ajaxReturn($res,'JSONP');
    }

}