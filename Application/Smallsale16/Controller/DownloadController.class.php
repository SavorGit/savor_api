<?php
namespace Smallsale16\Controller;
use \Common\Controller\CommonController;
use Common\Lib\SavorRedis;
class DownloadController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'index':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001);
                break;
            
        }
        parent::_init_();
    }
    public function index(){
        $page = $this->params['page'];
        $m_rest_download = new \Common\Model\Smallapp\RestdownloadModel();
        $order = 'a.sort desc';
        $where = array();
        $where['a.status'] = 1;
        $oss_host = 'https://'.C('OSS_HOST').'/';
        $fields = "concat('".$oss_host."',media.`oss_addr`) oss_url,a.id,a.name";
        $pagesize = 10;
        $limit = "limit 0,".$page*$pagesize;
        $data = $m_rest_download->getList($fields, $where, $order, $limit);
        $this->to_back($data);
    }
}