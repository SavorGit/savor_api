<?php
/**
 * @desc U盘制作节目单
 * @author zhang.yingtao
 * @since 20171101
 */
namespace Common\Model\Flashmenu;
use Think\Model;

class FlashMenuModel extends Model
{
	protected $tableName='flash_menu';
	
    public function getInfo($fields,$where,$order,$limit){
        $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }	
    public function addInfo($data){
        $this->add($data);
        $id = $this->getLastInsID();
        return $id;
    }
}