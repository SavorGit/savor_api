<?php

/**
 * ThinkPHP 简体中文语言包
 */
return array(
	'parmas_not_null'=>'必传参数不能为空',
	'parmas_null'=>'参数为空',
	'traceinfo_not_null'=>'traceinfo不能为空',
	'mobile_deviceid_not_match'=>'用户手机设备不一致',
	'token_not_null'=>'令牌不能为空',
	'token_has_expired'=>'token已失效',
	'sign_error'=>'签名错误',
	'success'=>'成功',
    
    //心跳上报
    'heart_mac_period_not_null'=>'mac地址不能为空',
    'heart_clientid_range_err'=>'clinetid不在有效范围内',
    'heart_mac_invalid'=>'mac地址非法',
    'heart_hotelid_invalid'=>'hotelid非法',
	//用户相关
	'user_not_exist' =>'用户不存在',
	'user_login_err'=>'用户登录失败',

	 //记录用户首次使用app
    'first_use_have_data'=>'该设备已记录首次使用数据',
    'first_use_push_err'=>'记录失败',
	//客户端类型
	'ctype_illegal'=>'类型标识错误',
	'cltype_insert_fail'=>'数据插入失败',
    //下载统计相关
    'download_source_error'=>'下载来源非法',
    'client_error'=>'客户端类型非法',
    'download_data_insert_error'=>'统计数据入库失败',
    'this_facility_have_download'=>'该设备已经下载安装过',
    
    //抽奖
    'box_not_set_award'=>'该机顶盒未设置奖项',
    'box_award_record_error'=>'机顶盒上报中奖信息失败',
    'have_not_this_box'=>'机顶盒不存在',
    'this_award_have_empty'=>'该奖项已经被抽空',
    'this_award_not_have_current'=>'该奖品未设置当前奖品剩余数量',


    //云平台PHP接口
    'down_hotel_infotype_error'=>'酒楼下载文件来源类型非法',
    'small_platform_hotel_error'=>'该酒楼为非正常状态',
    'small_platform_report_error'=>'上报数据入库失败',
    'small_platform_report_type_error'=>'上报数据类型错误',
    'hotel_not_set_small_plat_upgrade_version'=>'该酒楼未设置升级包',
    'have_not_this_small_plat_upgrade_war'=>'升级包不存在',
    'have_not_upgrade_sql'=>'无升级sql',
    //客户端获取投屏酒楼距离接口
    'lat_illegal'=>'纬度值非法',
    'lng_illegal'=>'经度值非法',
    //用户收藏接口
    'deviceid_error'=>'用户设备号不可为空',
    'artid_error'=>'文章id非法',
    'artid_not_check'=>'文章id未审核通过',
    'addmycollection_insert_fail'=>'收藏插入失败',
    'addmycollection_update_fail'=>'收藏更新失败',
    //创富生活接口
    'hot_category_id_error'=>'分类id参数错误',
    'content_not_check_pass'=>'该文章不存在或未通过审核',
    'not_demand_content'=>'该内容不可点播',
	//专题组接口
	'special_group_not_exist'=>'专题组不存在',
    
    //运维客户端接口
    'option_user_not_exist'=>'用户不存在',
    'option_user_pwd_error'=>'密码错误',
    'option_user_illegeal'=>'用户非法',
    'option_user_pro' =>'用户是否解决必选',
    'option_box_not_exists'=>'机顶盒mac不存在',
    'option_pla_not_exists'=>'小平台mac不存在',
    'option_reason_not_empty'=>'请选择维修记录或填写备注',
    'option_insert_fail'=>'插入记录失败',
    'option_error_report_not_exist'=>'该异常记录不存在',
    'option_error_hotel_not_exist'=>'该异常记录不包含该酒楼',
    'option_notallow_remark'=>'备注限制100字',
    //运维客户端接口1.1
    'option_user_role_empty'=>'该账号未设置运维角色',
    'option_user_role_error'=>'账号角色非法',
    'option_task_empty'=>'该任务不存在',
    'option_task_execuser_illegal'=>'该执行者无此权限',
    'option_boxid_not_null'=>'版位与是否解决为必填项',
    'option_task_state_error'=>'该任务状态不对',
    'option_task_upload_pic_error'=>'上传照片数超过最大值',
    'option_task_record_error'=>'该版位维修记录已提交',
    'option_task_infocheck_error'=>'该任务信息检测已提交',
    'option_task_netmodify_error'=>'该任务网络改造已提交',
    'option_task_record_fail'=>'该版位维修记录提交失败',
    'option_task_submit_fail'=>'执行任务失败',
    'option_task_installl_error'=>'该任务安装流程已完成',
    'option_task_not_new_task'=>'该任务不是新任务', 
    'option_task_refuse_err'=>'任务拒绝失败',
    'option_task_appoint_err'=>'任务指派失败',
    'option_user_manage_city_err'=>'没有该城市的权限',

    //每日知享接口
    'daily_content_not_exist'=>'文章不存在',
    'daily_content_collection_err'=>'收藏失败',
    'daily_content_not_collection_err'=>'取消收藏失败',
    'daily_keywords_empty'=>'关键词为空',
    'daily_user_add_fail'=>'用户添加失败',
    'daily_user_ptype_notnull'=>'人群设定不可为空',
    'daily_user_update_fail'=>'用户更新失败',
    'daily_user_tel_illegal'=>'手机号输入有误',
    'daily_user_code_notnull'=>'验证码不可为空',
    'daily_user_code_illegal'=>'验证码错误或已过期',
    'daily_user_code_min'=>'一分钟内请勿重复获取验证码',
    'daily_code_send_fail'=>'验证码发送失败',
    //升级接口
    'version_device_type_err'=>'clientname错误',
    'program_hotel_not_exist'=>'该酒楼不存在',
    'program_hotel_have_delete'=>'该酒楼已删除',
    'program_hotel_abnormal'=>'该酒楼为非正常酒楼',
    'program_hotel_not_net_box'=>'该酒楼为非网络版',
    'program_hotel_box_empty'=>'该就楼下没有正常的机顶盒',
    'program_hotel_menu_empty'=>'该酒楼未设置节目单',
    'program_ads_num_empty'=>'该广告号不存在',
    'program_menu_update_donwstate_error'=>'酒楼下载节目单更新下载状态失败',
    'program_hotel_have_donw_success'=>'该酒楼已成功下载过',
);