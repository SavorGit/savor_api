<?php
namespace Common\Model;
use Think\Model;

class PicturesModel extends Model{
    protected $tableName='mb_pictures';
    public function getPicsByContentId($contentid){
        $sql = 'select a.detail from savor_mb_pictures as a 
                left join savor_mb_content as b on a.contentid=b.id
                where a.contentid='.$contentid.' and b.state=2';
        $result = $this->query($sql);
        return $result[0];
    }
}