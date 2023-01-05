<?php
namespace Small\Controller;
use Think\Controller;

class JumpController extends Controller {

    public function index(){
        $id = I('get.id','');
        $hash_ids_key = C('HASH_IDS_KEY');
        $hashids = new \Common\Lib\Hashids($hash_ids_key);
        $res_decode = $hashids->decode($id);
        $invitation_id = intval($res_decode[0]);

        $redis = new \Common\Lib\SavorRedis();
        $redis->select(14);
        $invite_key = C('SAPP_SALE_INVITATION_JUMP_URL').$invitation_id;
        $res_cache = $redis->get($invite_key);
        $jump_url = '#';
        if(!empty($res_cache)){
            $jump_url = $res_cache;
        }
        $this->assign('jump_url',$jump_url);
        $this->display();
    }

}