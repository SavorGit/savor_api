<?php
/**
 * @desc U盘制作节目单
 * @author zhang.yingtao
 * @since 20171101
 */
namespace Common\Model\Flashmenu;
use Think\Model;

class FlashMenuItemModel extends Model
{
	protected $tableName='flash_menu_item';
	
    public function getList($fields,$where,$order,$limit){
        
        $data = $this->field($fields)->where($where)->order($order)->limit($limit)->select();
        return $data;
    }	
    public function addInfo($data){
        $ret = $this->add($data);
        return $ret;
    }
}