<?php
/*
 * 优选内容
 */
namespace Smallapp3\Controller;
use \Common\Controller\CommonController;
class OptimizeController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'getOptimizeList':
                $this->is_verify = 1;
                $this->valid_fields = array('page'=>1001,'openid'=>1001);
                break;
            case 'detail':
                $this->is_verify = 1;
                $this->valid_fields = array('goods_id'=>1001,'openid'=>1001,'uid'=>1002);
                break;
        }
        parent::_init_();
    }

    public function getOptimizeList(){
        $openid = $this->params['openid'];
        $page = intval($this->params['page']);
        $pagesize = 20;
        $all_nums = $page * $pagesize;
        $type = 10;//10官方活动促销,20我的活动,30积分兑换现金,40优选
        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $fields = 'id,name,media_id';
        $where = array('type'=>$type,'status'=>2);
        $orderby = 'id desc';
        $res_goods = $m_goods->getDataList($fields,$where,$orderby,0,$all_nums);

        $m_media = new \Common\Model\MediaModel();
        $datalist = array();
        foreach ($res_goods['list'] as $v){
            $media_id = $v['media_id'];
            $media_info = $m_media->getMediaInfoById($media_id);
            $oss_path = $media_info['oss_path'];
            $oss_path_info = pathinfo($oss_path);

            if($media_info['type']==2){
                $img_url = $media_info['oss_addr'];
            }else{
                $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
            }

            $dinfo = array('id'=>$v['id'],'img_url'=>$img_url,'duration'=>$media_info['duration'],'tx_url'=>$media_info['oss_addr'],
                'filename'=>$oss_path_info['basename']);
            $rets = $this->getFindnums($openid,$v['id'],4);
            $dinfo['is_collect'] = $rets['is_collect'];
            $dinfo['collect_num'] = $rets['collect_num'];
            $dinfo['share_num']  = $rets['share_num'];
            $datalist[] = $dinfo;
        }
        $this->to_back($datalist);
    }

    public function detail(){
        $uid = $this->params['uid'];
        $openid = $this->params['openid'];
        $goods_id = intval($this->params['goods_id']);

        $m_goods = new \Common\Model\Smallapp\GoodsModel();
        $res_goods = $m_goods->getInfo(array('id'=>$goods_id));
        if($res_goods['status']!=2){
            $this->to_back(92020);
        }

        $ip = get_client_ip();
        $data = array('data_id'=>$goods_id,'name'=>$res_goods['name'],'openid'=>$openid,'action_type'=>2,'type'=>2,'ip'=>$ip);
        $m_datalog = new \Common\Model\Smallapp\DatalogModel();
        $m_datalog->add($data);

        $data = array('goods_id'=>$goods_id,'appid'=>$res_goods['appid'],'buybutton'=>$res_goods['buybutton'],'is_storebuy'=>$res_goods['is_storebuy']);
        $page_url = $res_goods['page_url'];
        if($uid){
            $hash_ids_key = C('HASH_IDS_KEY');
            $hashids = new \Common\Lib\Hashids($hash_ids_key);
            $decode_info = $hashids->decode($uid);
            if(empty($decode_info)){
                $this->to_back(90101);
            }
            $sale_uid = $decode_info[0];
            if($res_goods['appid']=='wx13e41a437b8a1d2e'){
                $params = array(
                    'promotionCodeReq'=>array(
                        'materialId'=>$res_goods['jd_url'],
                        'chainType'=>3,
                        'subUnionId'=>"$sale_uid"
                    )
                );
                $res = jd_union_api($params,'jd.union.open.promotion.bysubunionid.get');
                $click_url = urlencode($res['data']['clickURL']);

                $m_sysconfig = new \Common\Model\SysConfigModel();
                $all_config = $m_sysconfig->getAllconfig();
                $jd_config = json_decode($all_config['jd_union_smallapp'],true);
                $page_url = '/pages/proxy/union/union?spreadUrl='.$click_url.'&customerinfo='.$jd_config['customerinfo'];
            }
        }
        $data['jd_url'] = $page_url;

        $media_id = $res_goods['media_id'];
        $m_media = new \Common\Model\MediaModel();
        $media_info = $m_media->getMediaInfoById($media_id);
        $data['video_url'] = $media_info['oss_addr'];
        $data['media_type'] = $media_info['type'];
        if($media_info['type']==2){
            $img_url = $media_info['oss_addr'];
        }else{
            $img_url = $media_info['oss_addr'].'?x-oss-process=video/snapshot,t_1000,f_jpg,w_450';
        }

        $oss_path_info = pathinfo($media_info['oss_path']);
        $pinfo = array('res_url'=>$data['video_url'],'forscreen_url'=>$media_info['oss_path'],'duration'=>$media_info['duration'],
            'resource_size'=>$media_info['oss_filesize'],'filename'=>$oss_path_info['basename'],'res_id'=>$media_id,'img_url'=>$img_url);

        $detail_content = array();
        $detail_imgmedia_ids = $res_goods['detail_imgmedia_ids'];
        if(!empty($detail_imgmedia_ids)){
            $detail_imgmedia_ids = json_decode($detail_imgmedia_ids,true);
            foreach ($detail_imgmedia_ids as $v){
                if(!empty($v)){
                    $media_info = $m_media->getMediaInfoById($v);
                    $detail_content[] = $media_info['oss_addr'];
                }
                
            }
        }
        $data['detail_content'] = $detail_content;
        $rets = $this->getFindnums($openid,$goods_id,4);
        $data['is_collect'] = $rets['is_collect'];
        $data['collect_num'] = $rets['collect_num'];
        $data['share_num']  = $rets['share_num'];
        $data['pubdetail'] = array($pinfo);
        $this->to_back($data);
    }

    private function getFindnums($openid,$res_id,$type){
        $m_collect = new \Common\Model\Smallapp\CollectModel();
        $m_share   = new \Common\Model\Smallapp\ShareModel();
        $map = array('openid'=>$openid,'res_id'=>$res_id,'type'=>$type,'status'=>1);
        $is_collect = $m_collect->countNum($map);
        if(empty($is_collect)){
            $is_collect = 0;
        }else {
            $is_collect = 1;
        }
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $collect_num = $m_collect->countNum($map);

        //分享个数
        $map = array('res_id'=>$res_id,'type'=>$type,'status'=>1);
        $share_num = $m_share->countNum($map);

        $data = array('is_collect'=>$is_collect,'collect_num'=>$collect_num,'share_num'=>$share_num);
        return $data;
    }

}
