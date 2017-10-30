<?php
/**
 * @desc   每日知享app首页
 * @author zhang.yingtao
 * @since  2017-09-18
 */

namespace Dailyknowledge\Controller;
use Think\Controller;
use \Common\Controller\BaseController;
class IndexController extends BaseController{
    private $weekarray ;
    /**
     * @desc 构造函数
     */
    function _init_(){
        switch(ACTION_NAME){
            case 'getList':
                $this->is_verify = 0;
                $this->valid_fields = array('bespeak_time'=>1000);
                break;
        }
        parent::_init_();
        $this->weekarray=array('0'=>"日",'1'=>"一",'2'=>"二",'3'=>"三",'4'=>"四",'5'=>"五",'6'=>"六");
    }
    /**
     * @desc 获取卡片列表
     */
    public function getList(){
        $bespeak_time = $this->params['bespeak_time'];
        $fields = ' a.id dailyid,a.artpro, a.media_id imgUrl,a.title,a.desc,d.name sourceName,c.bespeak_time,c.dailyauthor,c.dailyart';
        $where  = ' 1=1';
        if(!empty($bespeak_time)){
            $where .=" and c.bespeak_time<'".$bespeak_time."'";
        }else {
            //$where .= " and to_days(c.`bespeak_time`) = to_days(now())";
            //$where .=" and to_days(c.`bespeak_time`) <= to_days(now())";
            $where .= " and c.bespeak_time<='".date('Y-m-d H:i:s')."'";
        }
        $order =" c.bespeak_time desc, b.sort_num asc";
        $limit = 11;

        $m_daily_content = new \Common\Model\DailyContentModel();

        $alist = $m_daily_content->getList($fields,$where,$order,$limit);

        $list = array_slice($alist,0,10);
        $nexlist = array_slice($alist,10,1);
        $m_daily_relation = new \Common\Model\DailyRelationModel();
        $dailyauthor = $list[0]['dailyauthor'];
        $dailyart = $list[0]['dailyart'];
        foreach($list as $key=>$val){
            unset($list[$key]['dailyauthor']);
            unset($list[$key]['dailyart']);
            $content_list = array();
            $list[$key]['imgUrl'] = $this->getOssAddrByMediaId($val['imgUrl']);
             $list[$key]['share_url'] = C('CONTENT_HOST').'admin/dailycontentshow/'.$val['dailyid'];
            $content_detail = $m_daily_relation->getListByDailyid('dailytype,stext,spictureid spicture',$val['dailyid']);
            $list[$key]['sourceName'] = '';
            foreach($content_detail as $k=>$v){
                if($v['dailytype']==3){
                    $content_detail[$k]['spicture'] = $this->getOssAddrByMediaId($v['spicture']);
                    unset($content_detail[$k]['stext']);
                }else if($v['dailytype'] ==1){
                    unset($content_detail[$k]['spicture']);
                }
                
            }
            $content_list['dailyid'] = $list[$key]['dailyid'];
            $content_list['imgUrl'] = $list[$key]['imgUrl'];
            $content_list['title']   = $list[$key]['title'];
            $content_list['bespeak_time']  = date('Y-m-d',strtotime($list[$key]['bespeak_time']));
            //$content_list['sourceName']  = $list[$key]['sourceName'];
            $content_list['sourceName']  = '';
            $content_list['desc'] = $list[$key]['desc'];
            
            $content_list['details']  = $content_detail;
            $list[$key]['contentDetail'] = $content_list; 
            unset($list[$key]['bespeak_time']); 
        }
        $week = $month = $day = '';
        if(!empty($list)){

            $week  = '星期'.$this->weekarray[date('w',strtotime($list[0]['contentDetail']['bespeak_time']))] ;
            $month = date('n',strtotime($list[0]['contentDetail']['bespeak_time'])).'月';
            $day   = date('d',strtotime($list[0]['contentDetail']['bespeak_time']));
            $bespeak_time = date('Y-m-d',strtotime($list[0]['contentDetail']['bespeak_time']));
        }
        if(!empty($nexlist)) {
            $nweek  = '星期'.$this->weekarray[date('w',strtotime($nexlist[0]['bespeak_time']))] ;
            $nmonth = date('n',strtotime($nexlist[0]['bespeak_time']));
            $nday   = date('d',strtotime($nexlist[0]['bespeak_time']));
            $data['nextpage'] = array(
                'next'=>1,
                'week'=>$nweek,
                'month'=>$nmonth,
                'day'=>$nday,
            );
        }else{
            $data['nextpage'] = array(
                'next'=>0,
                'week'=>'',
                'month'=>'',
                'day'=>'',
            );
        }

        $data['week'] = $week;
        $data['month']= $month;
        $data['day']  = $day;
        $data['dailyauthor']  = $dailyauthor;
        $data['dailyart']  = $dailyart;
        $data['list'] = $list;
        $this->to_back($data);
    }
}