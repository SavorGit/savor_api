<?php
/**
 * @desc  推送消息
 * @since 2019-05-14
 * @author zhang.yingtao
 */
namespace Small\Controller;
use Common\Lib\SavorRedis;
use \Common\Controller\CommonController as CommonController;

class SendTopicController extends CommonController{
    /**
     * 构造函数
     */
    function _init_() {
        switch(ACTION_NAME) {
            case 'reSendTopicMessage':  //重新推送虚拟小平台消息
                $this->is_verify = 1;
                $this->valid_fields = array('request_id'=>1001,'type'=>1001,'content'=>1001,
                                            'base64_decode'=>1001,'code'=>1001
                );
                break;
        }
        parent::_init_();
    }
    public function reSendTopicMessage(){
        
        $code          = $this->params['code'];         //主题推送状态  10000为成功
        
        if($code!=10000){
            $request_id    = $this->params['request_id'];   //请求标识
            $type          = $this->params['type'];         //主题类型
            $content       = $this->params['content'];      //主题推送的源字符串
            $base64_decode = str_replace('\\', '', $this->params['base64_decode']);//主题推送解密后的字符串
            $vt_small_topic_type_arr = C("VIRTUAL_SMALL_SEND_MESSAGE_TYPE");
            if(in_array($type, $vt_small_topic_type_arr) ){
                $rt = sedVsTopicMessage($content,$type);
                while (!is_object($rt)){
                    $rt = sedVsTopicMessage($content,$type);
                    sleep('1');
                }
                $this->to_back(10000);
            }else {
                $this->to_back(16211);
            }
        }else {
            $this->to_back(16212);
        }
    }
}