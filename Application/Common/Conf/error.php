<?php
/**
 * 错误码以及错误信息配置定义
 */
return array(
	'errorinfo'=>array(
		'1001'=>'parmas_not_null',
		'1002'=>'parmas_null',
		'1003'=>'traceinfo_not_null',
		'1004'=>'mobile_deviceid_not_match',
		'1005'=>'token_not_null',
		'1006'=>'token_has_expired',
		'1007'=>'sign_error',
        '1008'=>'wechat_encrypts_data_decrypts_failed',

		'10000'=>'success',
	    //心跳上报
	    '10004'=>'heart_mac_period_not_null',
	    '10005'=>'heart_clientid_range_err',
	    '10006'=>'heart_mac_invalid',
	    '10007'=>'heart_hotelid_invalid',
	    
		//用户操作报错
		'12001'=>'mobile_illegal',
		'12002'=>'user_not_exist',
		'12003' =>'user_login_err',

		//短信
        '21001'=>'send_sms_error',

		//记录用手首次使用app
	    '20001'=>'first_use_have_data',
	    '20002'=>'first_use_push_err',
		//客户端传类型报错
		'13001'=>'ctype_illegal',
		'13002'=>'cltype_insert_fail',
	    //下载统计类型能够报错
	    '14001'=>'download_source_error',
	    '14002'=>'client_error',
	    '14003'=>'download_data_insert_error',
	    '14004'=>'this_facility_have_download',
	    //抽奖
	    '15001'=>'box_not_set_award',
	    '15002'=>'box_award_record_error',
	    '15003'=>'have_not_this_box',
	    '15004'=>'this_award_have_empty',
	    '15005'=>'this_award_not_have_current',

		//云平台PHP接口
		'16001'=>'down_hotel_infotype_error',
	    
	    '16100'=>'small_platform_hotel_error',
	    '16101'=>'small_platform_report_error',
	    '16102'=>'small_platform_report_type_error',
	    '16103'=>'hotel_not_set_small_plat_upgrade_version',
	    '16104'=>'have_not_this_small_plat_upgrade_war',
        '16105'=>'have_not_upgrade_sql',
	    
	    '16200'=>'program_hotel_not_exist',
	    '16201'=>'program_hotel_have_delete',
	    '16202'=>'program_hotel_abnormal',
	    '16203'=>'program_hotel_not_net_box',
	    '16204'=>'program_hotel_box_empty',
	    '16205'=>'program_hotel_menu_empty',
	    '16206'=>'program_ads_num_empty',
	    '16207'=>'program_menu_update_donwstate_error',
	    '16208'=>'program_hotel_have_donw_success',
		'16209'=>'stas_time_illegal',
		'16210'=>'stas_time_insert_fail',
	    '16211'=>'virtual_small_data_erro',
	    '16212'=>'virtual_small_code_erro',
	    //客户端获取投屏酒楼距离接口
		'17001'=>'lat_illegal',
		'17002'=>'lng_illegal',
		//用户收藏接口
		'18001'=>'deviceid_error',
		'18002'=>'artid_error',
		'18003'=>'addmycollection_insert_fail',
		'18004'=>'addmycollection_update_fail',
		'18005'=>'artid_not_check',
	    //创富生活接口
	    '19001'=>'hot_category_id_error',
        '19002'=>'content_not_check_pass',
	    '19003'=>'not_demand_content',
	    //专题组接口
	    '19101'=>'special_group_not_exist',
	    
	    //运维端接口
	    '30001'=>'option_user_not_exist',
	    '30002'=>'option_user_pwd_error',
	    '30003'=>'option_error_report_not_exist',
	    '30004'=>'option_error_hotel_not_exist',
		'30050'=>'option_user_illegeal',
		'30051'=>'option_user_pro',
		'30052'=>'option_box_not_exists',
		'30053'=>'option_pla_not_exists',
		'30054'=>'option_reason_not_empty',
		'30055'=>'option_insert_fail',
		'30056'=>'option_notallow_remark',
	    
	    //1.1版本
	    '30057'=>'option_user_role_empty',
	    '30058'=>'option_user_role_error',
	    '30059'=>'option_task_empty',
	    '30060'=>'option_user_manage_city_err',
	    '30061'=>'option_user_role_null',

		'30100'=>'option_task_execuser_illegal',
		'30101'=>'option_boxid_not_null',
		'30102'=>'option_task_state_error',
		'30103'=>'option_task_upload_pic_error',
		'30104'=>'option_task_record_error',
		'30105'=>'option_task_record_fail',
		'30106'=>'option_task_submit_fail',
		'30107'=>'option_task_infocheck_error',
		'30108'=>'option_task_netmodify_error',
		'30109'=>'option_task_installl_error',
		'30110'=>'option_task_bind_error',
		'30111'=>'option_task_bind_mac_have',
		'30112'=>'option_bind_mac_update_fail',
        '30113'=>'option_task_type_changed',
	    '30114'=>'option_ads_list_err',
	    '30115'=>'option_pro_empty',

	    '30062'=>'option_task_not_new_task',
	    '30063'=>'option_task_refuse_err',
	    '30064'=>'option_task_appoint_err',
	    '30065'=>'option_task_type_empty',
	    '30066'=>'option_task_upload_img_nums_err',
	    '30067'=>'option_task_bind_mac_repeat',
	    '30068'=>'option_task_box_have_repaired',
	    '30069'=>'option_task_have_completed',
	    '30070'=>'option_task_box_have_completed',
	    '30074'=>'option_task_mobile_empty',
	    '30075'=>'option_task_mobile_illegal',
	    '30076'=>'option_task_pub_task_too_often',
	    //2.1.1
	    '30071'=>'box_report_download_empty',
	    '30072'=>'box_report_download_same',

	    '30073'=>'box_report_play_same',
		'30081'=>'box_mac_not_empty',
		'30082'=>'box_mac_illegal',
		'30083'=>'box_memory_state_illegal',
		'30084'=>'boxmem_data_insert_error',
		'30085'=>'boxmem_data_already_insert',
	    //每日知享接口
	    '40001'=>'daily_content_not_exist',
	    '40002'=>'daily_content_collection_err',
	    '40003'=>'daily_content_not_collection_err',
	    '40004'=>'daily_keywords_empty',
		'40005'=>'daily_user_add_fail',
		'40006'=>'daily_user_ptype_notnull',
		'40007'=>'daily_user_update_fail',
		'40008'=>'daily_user_tel_illegal',
		'40009'=>'daily_user_code_notnull',
		'40010'=>'daily_user_code_illegal',
		'40011'=>'daily_user_code_min',
		'40012'=>'daily_code_send_fail',
	    //升级接口
	    '50001'=>'version_device_type_err',

		//餐厅端接口
		'60001'=>'dinner_reportlog_touping_fail',
	    '60002'=>'dinner_mobile_error',
	    '60003'=>'dinner_user_code_min',
	    '60004'=>'dinner_user_code_illegal',
	    '60005'=>'dinner_invite_code_err',
	    '60006'=>'dinner_user_login_err',
	    '60007'=>'dinner_hotel_rec_food_empty',
	    '60008'=>'dinner_hotel_empty',
	    '60009'=>'dinner_hotel_state_err',
	    '60010'=>'dinner_mobile_not_fit_invitecode',
	    '60011'=>'dinner_invite_code_have_used',
	    '60012'=>'dinner_please_input_your_invite_code',
	    '60013'=>'dinner_hotel_adv_list_empty',
	    '60014'=>'dinner_hotel_room_empty',
	    '60015'=>'dinner_mobile_not_bind_code',
	    '60016'=>'dinner_customer_import_err',
	    '60017'=>'dinner_customer_import_empty',
	    '60018'=>'dinner_bind_invite_err',
	    '60019'=>'dinner_bind_mobile_err',
	    '60020'=>'dinner_customer_have_import',
	    '60021'=>'dinner_room_add_failed',
	    '60022'=>'dinner_room_name_repeat',
	    '60023'=>'dinner_order_add_failed',
	    '60024'=>'dinner_order_empty',
	    '60025'=>'dinner_is_welcome_used',
	    '60026'=>'dinner_is_recfood_used',
	    '60027'=>'dinner_ticket_url_have_upload',
	    '60028'=>'dinner_service_update_failed',
	    '60029'=>'dinner_service_ticket_url_empty',
	    '60030'=>'dinner_cannot_del_other_hotel_order',
	    '60031'=>'dinner_order_del_failed',
	    '60032'=>'dinner_order_update_failed',
	    '60033'=>'dinner_order_donot_belong_you',
	    '60034'=>'dinner_customer_mobile_err',
	    '60100'=>'dinner_invite_id_illegal',
		'60101'=>'dinner_customer_insert_fail',
		'60102'=>'dinner_customer_already_exist',
		'60103'=>'dinner_customer_tel_repeat',
		'60104'=>'dinner_customer_tel_empty',
		'60105'=>'dinner_customer_tel1_exist',
		'60106'=>'dinner_customer_tel2_exist',
		'60107'=>'dinner_customer_id_empty',
		'60108'=>'dinner_customer_empty',
		'60109'=>'dinner_customer_label_add_fail',
		'60110'=>'dinner_customer_update_fail',
		'60111'=>'dinner_customer_label_illegal',
		'60112'=>'dinner_customer_label_die_fail',
		'60113'=>'dinner_customer_consume_failed',
		'60114'=>'dinner_order_not_exist',
		'60115'=>'dinner_label_type_error',
		'60116'=>'dinner_customer_label_not_exist',
		'60117'=>'dinner_cosumerecord_type_error',
		'60118'=>'dinner_customer_tel_notsame',
	    '60119'=>'dinner_customer_have_exist',
	    //盒子接口错误码
	    '70001'=>'box_not_exist',
	    '70002'=>'box_mac_error',
	    '70003'=>'box_device_token_report_error',
	    '70004'=>'box_room_empty',
	    '70005'=>'box_device_token_empty',
	    //RTB广告错误码
	    '80001'=>'hotel_attendant_empty',
	    '80002'=>'not_in_rtb_ads_push_time',
	    '80003'=>'rtb_push_ads_list_empty',
	    //聚屏广告
	    '90001'=>'poly_ads_empty',
	    '90002'=>'poly_hotel_not_normal',
	    '90003'=>'poly_box_empty',
	    
	    //4G投屏
	    '91001'=>'for_screen_failed',
	    '91002'=>'stop_screen_failed',
	    //小程序投屏
	    '91010'=>'forscreen_record_failed',
	    '91011'=>'forscreen_push_suggestion_failed',
	    '91012'=>'smallapp_turntable_log_failed',
	    '91013'=>'smallapp_order_time_failed',
	    '91014'=>'smallapp_user_exist',
	    '91015'=>'smallapp_user_add_failed',
	    '90106'=>'small_app_add_failed',
	    '90107'=>'small_app_del_failed',
	    '90108'=>'small_app_breanlink_failed',
	    '90109'=>'netty_box_empty',
	    //小程序游戏
	    '90110'=>'small_app_launch_game_failed',
	    '90111'=>'small_app_game_have_start',
	    '90112'=>'small_app_game_start_failed',
	    '90113'=>'small_app_game_score_report_error',
	    '90114'=>'small_app_game_not_exist',
	    '90115'=>'small_app_game_have_not_lunch',
	    '90116'=>'smallapp_user_notexist',
	    '90117'=>'smallapp_bonus_money_error',
	    '90118'=>'smallapp_surname_error',
	    '90119'=>'smallapp_bonus_range_error',
	    '90120'=>'smallapp_sendtvbonus_error',
	    '90121'=>'smallapp_grabbonus_status_error',
	    '90122'=>'smallapp_bonus_error',

	    '90130'=>'smallapp_redpacket_status_error',



        //小程序餐厅端
        '92001'=>'smallappdinner_mobile_error',
        '92002'=>'smallappdinner_invite_code_error',
        '92003'=>'smallappdinner_invite_code_isuse',
        '92004'=>'smallappdinner_verify_code_hassend',
        '92005'=>'smallappdinner_verify_code_sendfail',
        '92006'=>'smallappdinner_verify_code_checkfail',
        '92007'=>'smallappdinner_login_fail',
        '92008'=>'smallappdinner_invite_code_checkerror',
	    '92009'=>'smallappdinner_bdhotel_dqhotel_error',

	),
);