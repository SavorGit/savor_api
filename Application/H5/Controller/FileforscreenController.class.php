<?php
/*
 * H5文件投屏
 */
namespace H5\Controller;
use Think\Controller;

class FileforscreenController extends Controller {

    public function index(){
        $file_ext = C('SAPP_FILE_FORSCREEN_TYPES');
        $this->assign('file_ext',join(',',$file_ext));
        $this->display();
    }

}