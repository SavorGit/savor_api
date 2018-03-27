<?php
/**
 * @desc 提供小平台城市接口
 */
namespace BaseData\Controller;
use Think\Controller;
use \Common\Controller\CommonController as CommonController;
class SystemController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'systemTime':
                $this->is_verify = 0;
                break;
            case 'removeAPICaData':
                $this->is_verify = 0;
                break;
        }
        parent::_init_();
    }
    public function systemTime(){
        $data['sys_time'] = time();
        $this->to_back($data); 
    }

    /*
    * @desc 清除api接口缓存文件
    */
    public function removeAPICaData(){
        $path = APP_PATH.'Runtime';
        $this->removeDir($path, $path);
        $ar = scandir($path);
        if( count($ar) == 2) {
             echo '已经删除成功';
        } else {
            echo '删除失败';
        }

    }

    public function removeDir($path, $bath) {
        if( is_dir($path) ) {
            $name_arr = scandir($path);
            foreach($name_arr as $nk=>$nv) {
                if($nv != '.' && $nv != '..') {
                    $sub_dir = $path.DIRECTORY_SEPARATOR.$nv;
                    echo $sub_dir."<br/>";
                    if(is_dir($sub_dir)) {
                        $this->removeDir($sub_dir, $bath);
                    } else {
                        $bool = unlink($sub_dir);
                    }
                }
            }
        }
        if($path!=$bath){
            //防止把最根目录删除
            rmdir($path);
        }
    }
}