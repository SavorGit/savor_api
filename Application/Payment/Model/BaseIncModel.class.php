<?php
namespace Payment\Model;
use Think\Model;
class BaseIncModel extends Model{

    protected $tableName='smallapp_redpacket';

    /**
     * 获取支付信息
     * @param array $trade_info
     * @return array
     */
    public function init_pay_tradeinfo($trade_info=array()){
        $pay_trade_info = array();
        if(!empty($trade_info)){
            $pay_trade_info = array('out_trade_no'=> $trade_info['trade_no'],'total_fee'=> $trade_info['total_fee'],
                'subject'=>$trade_info['trade_name'],'body'=>$trade_info['trade_name'],'buy_time'=>$trade_info['buy_time'],
            	'redirect_url'=>$trade_info['redirect_url'],
            );
            if(isset($trade_info['wx_openid'])){
            	$pay_trade_info['wx_openid'] = $trade_info['wx_openid'];
            }
        }
        return $pay_trade_info;
    }
    
    /**
     * 获取支付配置信息
     * @param array $payconfig
     * @return array
     */
    public function init_pay_config($payconfig){
        $payinfo = array(
            'appid'=>$payconfig['appid'],
            'partner'=>$payconfig['partner'],
            'key'=>$payconfig['key'],
            'seller_email'=>$payconfig['seller_email'],
        );
        return $payinfo;
    }
    
    public function update_refund($refund_data){
        $row_refund = 0;
        if(!empty($refund_data)){
            $sql_refund = "update cms_refund set ";
            $fields = "";
            foreach ($refund_data as $k=>$v){
                $fields.="$k='$v',";
            }
            $trade_no = $refund_data['trade_no'];
            $sql=$sql_refund.rtrim($fields,','). " where trade_no='$trade_no'";
            $row_refund = $this->execute($sql);
        }
        return $row_refund;
    }
    
    /**
     * 更新红包订单支付数据
     * @param array $order_extend 订单扩展信息
     * array('trade_no'=>订单号,
     'serial_no'=>流水号,
     'pay_fee'=>支付金额,
     'paylog_type'=>支付类型,
     'pay_type'=>支付方式(10微信)
     * @return boolean
     */
    public function handle_redpacket_notify($order_extend){
        $is_succ = false;
        $trade_no = $order_extend['trade_no'];
        $serial_no = $order_extend['serial_no'];
        $pay_fee = $order_extend['pay_fee'];
        $paylog_type = $order_extend['paylog_type'];
        $pay_type = $order_extend['pay_type'];
    
    
        $sql_order = "select * from savor_smallapp_redpacket where id='$trade_no'";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->query($sql_order);
        if(in_array($result_order[0]['status'],array(0,1,2))){
            // 判断订单支付金额是否正常
            $tmp_no_pay_fee = $result_order[0]['total_fee']-$result_order[0]['pay_fee']-$pay_fee;
            $no_pay_fee = sprintf("%01.2f",$tmp_no_pay_fee);
    
            if($no_pay_fee<=0){
                $status = 3;
            }else{
                $status = 2;
            }
            $pay_time = date('Y-m-d H:i:s');
            $update_condition = "update cms_order set status='$status',pay_time='$pay_time',pay_fee='$pay_fee',pay_type='$pay_type' ";
            $sql_uporder = "$update_condition where trade_no='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_num = $this->execute($sql_uporder);
            if($row_num){
                $is_succ = true;
            }
        }else{
            $is_succ = true;
        }
        if($is_succ){
            //推送红包到电视
        }
        return $is_succ;
    }

    /**
     * 更新退款数据
     * @param array $order_extend 订单扩展信息
     * array(
    'serial_no'=>流水号
    'trade_no'=>订单号,
    'paylog_type'=>支付类型,
    ）
     * @param boolean $is_code 是否发放券
     * @return boolean
     */
    public function handle_refund_notify($order_extend){
        $is_succ = false;
        $serial_no = $order_extend['serial_no'];
        $paylog_type = $order_extend['paylog_type'];
        $trade_no = empty($order_extend['trade_no'])?0:$order_extend['trade_no'];

        if(empty($trade_no)){
            $sql_serial = "select trade_no from cms_orderserial where serial_order='$serial_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_serial);
            $res_serial = $this->execute($sql_serial);
            $trade_no = $res_serial[0]['trade_no'];
        }
        $sql_order = "select o.*,r.batch_no,r.status from cms_order as o,cms_refund as r where o.trade_no='$trade_no'
        and o.trade_no=r.trade_no";
        $this->paynotify_log($paylog_type, $serial_no, $sql_order);
        $result_order = $this->execute($sql_order);
        if($result_order[0]['status'] == 1 && $result_order[0]['status'] == 5){
            $sql_uporder = "update cms_order set status=6 where trade_no='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uporder);
            $row_uporder = $this->execute($sql_uporder);

            $sql_uprefund = "update cms_refund set status=2,succ_time=now() where trade_no='$trade_no'";
            $this->paynotify_log($paylog_type, $serial_no, $sql_uprefund);
            $row_uprefund = $this->execute($sql_uprefund);
            if($row_uprefund){
                $is_succ = true;
            }
        }
        return $is_succ;
    }

    /**
     * 记录支付日志
     * @param string $paylog_type
     * @param int $pay_id
     * @param string $msg
     */
    public function paynotify_log($paylog_type,$pay_id, $msg){
        switch($paylog_type){
            case 1:
                $file_name = C('PAYLOGS_PATH').'wxpaypc_'.date('Ym').'.log';
                break;
            case 2:
                $file_name = C('PAYLOGS_PATH').'wxpaymobile_'.date('Ym').'.log';
                break;
            case 3:
                $file_name = C('PAYLOGS_PATH').'wxpayjsapi_'.date('Ym').'.log';
                break;
            case 100:
                $file_name = C('PAYLOGS_PATH').'wxrefund_'.date('Ym').'.log';
                break;
            default:
                $file_name = C('PAYLOGS_PATH').'notify_'.date('Ym').'.log';
        }
        $fp = fopen($file_name,"a");
        flock($fp, LOCK_EX) ;
        fwrite($fp,strftime("%Y/%m/%d %H:%M:%S",time())."\t 支付流水号：{$pay_id}-- $msg \t\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    
    /**
     * 获取系统类型
     * @return number
     */
    public function getos(){
        $otype = 1;//1:PC 2:mobile
        $ua = $_SERVER['HTTP_USER_AGENT'];
        if(preg_match('/ipad/i', $ua) || preg_match('/ipod/i', $ua) || preg_match('/iphone/i', $ua) || preg_match('/IOS/i', $ua) || preg_match('/Android/i', $ua)){
            $otype = 2;
        }
        return $otype;
    }

    public function host_name(){
        $http = 'http://';
        return $http.$_SERVER['HTTP_HOST'];
    }
    
}