<?php
/**
 *达达快递订单相关
 * 
 */
namespace H5\Controller;
use Think\Controller;


class DadaController extends Controller{

    public function orderNotify(){
        $content = file_get_contents('php://input');
        $log_content = "nofity_data:$content";
        $this->addLog('',$log_content);
        if(!empty($content)) {
            $res = json_decode($content, true);
            if(!empty($res) && isset($res['order_id'])){
                $order_id=$res['order_id'];
                $data = array('client_id'=>$res['client_id'],'order_id'=>$res['order_id'],'update_time'=>$res['update_time']);
                asort($data, SORT_STRING);// 按键值升序排序
                $sign_data = array_values($data);
                $sign = md5(join('',$sign_data));
                if($sign!=$res['signature']){
                    $this->addLog($order_id,'验签失败');
                    die('fail');
                }
                $this->addLog($order_id,'验签成功');
                $this->addLog($order_id,"dada_data:$content");

                $order_id = intval($order_id);
                $model = M();
                $sql_order = "select * from savor_smallapp_order where id={$order_id}";
                $this->addLog($order_id, $sql_order);
                $result_order = $model->query($sql_order);
                if(!empty($result_order)){
                    $status_map = array('1'=>14,'2'=>15,'3'=>16,'4'=>17);
                    $dada_status = $res['order_status'];//待接单＝1,待取货＝2,配送中＝3,已完成＝4,已取消＝5, 已过期＝7,指派单=8,妥投异常之物品返回中=9, 妥投异常之物品返回完成=10,骑士到店=100,创建达达运单失败=1000
                    $this->addLog($order_id,"dada_status:$dada_status");
                    if(isset($status_map[$dada_status])){
                        $status = $status_map[$dada_status];
                        if($status==17){
                            $nowtime = date('Y-m-d H:i:s');
                            $sql_status = "UPDATE savor_smallapp_order SET status='{$status}',finish_time='{$nowtime}' WHERE id={$order_id}";
                        }else{
                            $sql_status = "UPDATE savor_smallapp_order SET status='{$status}' WHERE id={$order_id}";
                        }
                        $this->addLog($order_id, $sql_status);
                        $model->execute($sql_status);
                    }
                }
            }
        }
        echo 'success';
    }

    private function addLog($order_id,$msg){
        $log_content = date('Y-m-d H:i:s')." 订单号:$order_id-- $msg \r\n";
        $log_file_name = C('DADALOGS_PATH').'order_'.date('Ym').'.log';
        @file_put_contents($log_file_name, $log_content, FILE_APPEND);
    }


}
