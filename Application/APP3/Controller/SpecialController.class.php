<?php
/**
 * @desc 专题接口
 * @author zhang.yingtao
 * @since  2017-07-07
 */
namespace APP3\Controller;
use Think\Controller;
use Common\Controller\BaseController as BaseController;
class SpecialController extends BaseController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getSpecialName':
                $this->is_verify = 0;
                break;
            case 'getSpecialList':
                $this->is_verify =0;
                $this->valid_fields = array('sort_num'=>'1000');
                break;
            case 'specialGroupDetail':
                $this->is_verify = 1;
                $this->valid_fields = array('id'=>'1000');
                break;
           /*  case 'getSpecialGroupList':
                $this->is_verify = 1;
                $this->valid_fields = array('update_time'=>'1000');
                break; */
            case 'specialGroupList':
                $this->is_verify = 1;
                $this->valid_fields = array('update_time'=>'1000');
                break;
        }
        parent::_init_();
        $actionname  = strtolower(ACTION_NAME);          //方法名称
        $versionname = $this->traceinfo['versionname'];  //版本号
        $versionname = str_replace('.', '', $versionname);
        if($actionname =='getspecialname' && $versionname >='302'){
            $this->getSpecialName302();
        }
    }
    /**
     * @desc 获取专题名称
     */
    public function getSpecialName(){
        $m_sys_config = new \Common\Model\SysConfigModel();
        $where = "'system_special_title'";
        $info = $m_sys_config->getInfo($where);
        $data =  array();
        if(!empty($info)){
            $data['specialName'] = $info[0]['config_value'];
        }else {
            $data['specialName'] = '';
        }
       
        $this->to_back($data);
    }
    /**
     * @desc 获取专题名称
     */
    public function getSpecialName302(){
        $m_special_group =  new \Common\Model\SpecialGroupModel();
        $where = ' 1';
        $order = ' update_time desc ';
        $limit = ' limit 1';
        $type  = 1;
        $info = $m_special_group->getInfo($where,$order , $limit ,$type );
        $data['specialName'] = $info['name'];
        $this->to_back($data);
    }
    /**
     * @desc 专题列表
     */
    public function getSpecialList(){
        $sort_num = $this->params['sort_num'];
        $category_id = 103;
        $orders = 'mco.sort_num desc';
        $now = date("Y-m-d H:i:s",time());
        $where = '1=1';
        $where .= ' AND mco.state = 2   and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
        if($sort_num){
            $where .=" and mco.sort_num<$sort_num ";
        }
        $artModel = new \Common\Model\ArticleModel();
        $size = $this->params['numPerPage'] ? $this->params['numPerPage'] :20;
        
        $result = $artModel->getSpecialList($where, $orders,$size);
        
        $deviceid = $this->traceinfo['deviceid'];
        foreach ($result as $key=>$val) {
            
            $result[$key]['imageURL'] = $this->getOssAddr($val['imageURL']) ;
            $result[$key]['contentURL'] = $this->getContentUrl($val['contentURL']);
            if(!empty($val['name'])){
                
                    $ttp = explode('/', $val['name']);
                    $result[$key]['name'] = $ttp[2];
                
                }
            unset($result[$key]['name']);
            foreach($val as $sk=>$sv){
                if (empty($sv)) {
                    unset($result[$key][$sk]);
                }
            }
            $result[$key]['updateTime'] = date('Y-m-d',strtotime($val['createTime']));
            
        }
        if($result){
            $count = count($result);
            if($count<20){
                $nextPage = 0;
            }else{
                $where = '1=1';
                $where .= ' AND mco.state = 2   and mco.hot_category_id ='.$category_id. ' AND (((mco.bespeak=1 or mco.bespeak=2) AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
                $order  = 'sort_num asc';
                $info = $artModel->alias('mco')->where($where)->order($order)->limit(1)->find();
                $sort_num_get = $info['sort_num'];
                //获取传过去最后一条
                $sort_pass_last = $result[$count-1]['sort_num'];
                if($sort_num_get == $sort_pass_last){
                    $nextPage = 0;
                }else{
                    $nextPage = 1;
                }

            }
            $data['list'] = $result;
            $data['nextpage'] = $nextPage;
        }else{
            $data = array();
        }
        $this->to_back($data);
    }
    /**
     * @desc 获取专题详情页
     */
    public function specialGroupDetail(){
        $id = $this->params['id'];
        $data = array();
        $m_special_group = new \Common\Model\SpecialGroupModel();
        $order = $limit =  '';
        $where =  '1=1';
        if(empty($id)){
            $order = ' update_time desc ';
            $limit = ' limit 1';
            $type  = 1;
            $info = $m_special_group->getInfo($where,$order , $limit ,$type );
        } else {
            $where .= ' and id ='.$id;
            $type  = 1;
            $info = $m_special_group->getInfo($where,$order,$limit ,$type);
            
        }
        if(empty($info)){
            $this->to_back(19101);
        }
        
        $info['img_url'] = $this->getOssAddr($info['img_url']);
        $info['contentUrl'] = C('CONTENT_HOST').'admin/SpecialgroupShow/showsp?id='.$info['id'];
        $m_special_relation = new \Common\Model\SpecialRelationModel();
        $relationInfo = $m_special_relation->getInfoBySpecialId($info['id']);
        $artModel = new \Common\Model\ArticleModel();
        $flag = 0;
        foreach($relationInfo as $key=>$v){
            if($v['sgtype'] == 1){
                $stext = $v['stext'];
                unset($relationInfo[$key]);
                $relationInfo[$flag]['sgtype'] = 1;
                $relationInfo[$flag]['stext'] = $stext;
                $flag ++;
            }else if($v['sgtype']==2){
                $now = date("Y-m-d H:i:s",time());
                
                $where = ' 1=1 and mco.id='.$v['sarticleid'].' AND mco.state = 2    AND (((mco.bespeak=1 or mco.bespeak=2) 
                           AND mco.bespeak_time < "'.$now.'") or mco.bespeak=0)';
                
                
                $artinfo = $artModel->getCateList($where);
                unset($relationInfo[$key]);
                if($artinfo){
                    $tmp = array();
                    $artinfo = $this->changeList($artinfo);
                    $artinfo = $artinfo[0];
                    $artinfo['sgtype'] = 2;
                    $relationInfo[$flag] = $artinfo;
                    $flag ++;
                }
                
                
            }else if($v['sgtype']==3) {
                $img_url = $this->getOssAddrByMediaId($v['spictureid']);
                unset($relationInfo[$key]);
                $relationInfo[$flag]['sgtype']  = '3';
                $relationInfo[$flag]['img_url'] = $img_url;
                $flag ++;
            }else if($v['sgtype']==4){
                $stitle = $v['stitle'];
                unset($relationInfo[$key]);
                $relationInfo[$flag]['sgtype']  = '4';
                $relationInfo[$flag]['stitle']  = $stitle;
                $flag ++;
            }
            
        }
        
        $info['list'] = $relationInfo;
        $this->to_back($info);
    }
    /**
     * @desc 获取专题组列表
     */
    public function specialGroupList(){
        //$id = I('id','0','intval');
        $update_time = $this->params['updateTime'];
        
        $pageSize = I('pageSize','20','intval');
        $m_special_group = new \Common\Model\SpecialGroupModel();
        $order = '';
        $where = ' ';
        if(!empty($update_time)){
            $where .= " and update_time<'".$update_time."'"; 
        }
        $where .=" and state=1";
        $order = ' update_time desc';
        $limit = " limit {$pageSize}";
        $info = $m_special_group->getList($where,$order ,$limit);
        foreach($info as $key=>$v){
            $info[$key]['img_url'] = $this->getOssAddr($v['img_url']);
        }
        
        if($info){
            $count = count($info);
            if($count<$pageSize){
                $nextPage = 0;
            }else{
                
                $where =" and update_time<'".$info[$count-1]['update_time']."'";
                $order = ' update_time desc';
                $limit =  ' limit 1';
                $nextInfo = $m_special_group->getList($where,$order ,$limit);
                if(empty($nextInfo)){
                    $nextPage = 0;
                }else {
                    $nextPage = 1;
                }
            }    
            $data['list'] = $info;
            $data['nextpage'] = $nextPage;
        }else {
            $data = array();
        }
        $this->to_back($data);
    }
    private function changeList($res){
       
        if($res){
            $m_media = new \Common\Model\MediaModel();
            $m_Content = new \Common\Model\ContentModel();
            $m_picturs = new \Common\Model\PicturesModel();
    
            foreach ($res as $vk=>$val) {
                
                if($val['logo']){
                    $logoMediainfo = $m_media->getMediaInfoById($val['logo']);
                    $res[$vk]['logo'] = $logoMediainfo['oss_addr'];
                }
    
                $res[$vk]['contentURL'] = $this->getContentUrl($val['contentURL']);
                $res[$vk]['imageURL'] = $this->getOssAddr($val['imageURL']);
                $res[$vk]['videoURL'] = substr($val['videoURL'], 0, strpos($val['videoURL'],'.f'));
                $len = count($val['name']);
                if($len != 0) {
                    $res[$vk]['canplay'] = 1;
                    $res[$vk]['name'] = substr($val['name'], strripos($val['name'],'/')+1);
                }
                if($val['type']==3){
                    if(empty($val['content'])){
                        $res[$vk]['type'] = 4;
                    }
                }
                if($val['type']==2){
                    $res[$vk]['colTuJi'] = $m_picturs->getCountPics($val['artid']);
    
                }
                unset($res[$vk]['content']);
    
    
                $res[$vk]['updateTime'] = date('Y-m-d',strtotime($val['createTime']));
                foreach($val as $sk=>$sv){
                    if (empty($sv)) {
                        unset($res[$vk][$sk]);
                    }
                }
    
            }
        }
        return $res;
         
    }
}