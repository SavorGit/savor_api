<?php

/**
 * 接口返回数据定义
 * @author zhang.yingtao 
 *
 */
//整体接口返回数据格式
class ApiResp{
    var $code = 10000;
    var $msg = '成功';
    var $result = array();
}

//城市对象
class CityObj{
    var $area_id    = 0;    //城市id
    var $parent_id  = 0;    //上级城市id
    var $area_name  = '';   //城市名字空对象格式
    var $area_type  = 2;    //地区类型
    var $is_hotcity = 0;    //是否是热门城市
    var $sort_order = '';   //排序字段
    var $pinyin     = '';   //拼音
    var $first      = '';   //首字母
}
