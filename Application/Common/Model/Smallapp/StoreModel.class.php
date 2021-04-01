<?php
namespace Common\Model\Smallapp;
use Common\Model\BaseModel;

class StoreModel extends BaseModel {
	protected $tableName='smallapp_store';

    public function getAllStores($area_id=1){
        $sql_hotel = $this->buildHotelSql($area_id);
        $sql_store = $this->buildStoreSql($area_id);
        $all_sql = "($sql_hotel) union ($sql_store) order by hotel_id desc";
        $result = $this->query($all_sql);
        return $result;
    }

    public function getHotelStore($area_id,$county_id=0,$food_style_id=0,$avg_id=0){
        $sql_hotel = $this->buildHotelSql($area_id,$county_id,$food_style_id,$avg_id);
        return $this->query($sql_hotel);
    }

    public function getLifeStore($area_id,$county_id=0,$cate_id=0,$avg_id=0){
        $sql_store = $this->buildStoreSql($area_id,$county_id,$cate_id,$avg_id);
        return $this->query($sql_store);
    }

    public function buildHotelSql($area_id,$county_id=0,$food_style_id=0,$avg_id=0){
        $sql_hotel = "select hotel.id as hotel_id,ext.hotel_cover_media_id as media_id,hotel.name,hotel.addr,hotel.tel,hotel.gps,ext.avg_expense,food.name tag_name,'120' as cate_id
            from savor_hotel as hotel left join savor_hotel_ext as ext on hotel.id=ext.hotel_id left join savor_hotel_food_style as food on ext.food_style_id=food.id
            where hotel.area_id={$area_id} and hotel.state=1 and hotel.flag=0";
        $hotel_box_types = C('HEART_HOTEL_BOX_TYPE');
        $hotel_box_type_str = join(',',array_keys($hotel_box_types));
        $sql_hotel.= " and hotel.hotel_box_type in ($hotel_box_type_str)";
        $sql_hotel.= " and hotel.id not in (7,482,504,791,508,844,845,597,201,493,883,53)";

        if($county_id){
            $sql_hotel.=" and hotel.county_id={$county_id}";
        }
        if($food_style_id){
            $sql_hotel.=" and ext.food_style_id=$food_style_id";
        }
        if($avg_id){
            $all_avg = C('PERSON_PRICE');
            $avg_info = $all_avg[$avg_id];
            $sql_hotel.=" and ext.avg_expense>{$avg_info['min']} and ext.avg_expense<={$avg_info['max']}";
        }
        $sql_hotel.=" order by hotel_id desc";
        return $sql_hotel;
    }

    public function buildStoreSql($area_id,$county_id=0,$cate_id=0,$avg_id=0){
        $sql_store = "select store.id as hotel_id,store.cover_media_id as media_id,store.name,store.addr,store.tel,store.gps,store.avg_expense,category.name as tag_name,store.category_id as cate_id
            from savor_smallapp_store as store left join savor_category as category on store.category_id=category.id 
            where store.area_id={$area_id} and store.status=1";
        if($county_id){
            $sql_store.=" and store.county_id={$county_id}";
        }
        if($cate_id){
            $m_category = new \Common\Model\Smallapp\CategoryModel();
            $res_category = $m_category->getDataList('id',array('parent_id'=>$cate_id),'id asc');
            if(!empty($res_category)){
                $cate_ids = array();
                foreach ($res_category as $v){
                    $cate_ids[]=$v['id'];
                }
                $cate_str = join(',',$cate_ids);
                $sql_store.=" and store.category_id in ({$cate_str})";
            }else{
                $sql_store.=" and store.category_id={$cate_id}";
            }
        }
        if($avg_id){
            $all_avg = C('PERSON_PRICE');
            $avg_info = $all_avg[$avg_id];
            $sql_store.=" and store.avg_expense>{$avg_info['min']} and store.avg_expense<={$avg_info['max']}";
        }
        $sql_store.=" order by hotel_id desc";

        return $sql_store;
    }


}