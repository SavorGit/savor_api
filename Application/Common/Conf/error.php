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

	    //小程序投屏
	    '90100'=>'smallapp_qrcode_type_error',
	    '90101'=>'smallapp_qrcode_content_error',
	    '90102'=>'smallapp_forscreen_file_not_exist',
	    '90103'=>'smallapp_forscreen_size_not_exist',
	    '90104'=>'smallapp_forscreen_md5_not_exist',
	    '90105'=>'smallapp_forscreen_record_not_exist',
	    '90106'=>'smallapp_forscreen_has_addhelpplay',
	    '90107'=>'smallapp_forscreen_has_addhelp',
	    '90108'=>'smallapp_resource_foul',
        '90109'=>'smallapp_pushnetty_box_empty',

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
	    '90123'=>'smallapp_bonus_money_max_error',

	    '90130'=>'smallapp_redpacket_status_error',
	    '90131'=>'smallapp_have_collect',
	    '90132'=>'smallapp_address_not_you',
	    '90133'=>'smallapp_cart_not_you',
	    '90134'=>'smallapp_order_not_you',
	    '90135'=>'smallapp_order_choose_paytype_error',
	    '90136'=>'smallapp_order_has_cancel',
	    '90137'=>'smallapp_order_not_support_cancel',
	    '90138'=>'smallapp_order_has_receive_error',
	    '90139'=>'smallapp_order_receive_delivery_error',
	    '90140'=>'smallapp_order_address_error',
	    '90141'=>'smallapp_order_addshop_order_area_error',
	    '90142'=>'smallapp_order_addshop_order_totalmoney_error',
	    '90143'=>'smallapp_order_amount_error',
	    '90144'=>'smallapp_goods_sale_out',
	    '90145'=>'smallapp_order_upnum_gt_buynum',
	    '90146'=>'smallapp_order_receivenum_gt_hasnum',
	    '90147'=>'smallapp_order_receive_over',
	    '90148'=>'smallapp_order_had_give',
	    '90149'=>'smallapp_order_gift_had_expire',
	    '90150'=>'smallapp_order_gift_had_receive',
	    '90151'=>'smallapp_order_givegift_had_receive',
	    '90152'=>'smallapp_order_givegift_num_error',
	    '90153'=>'smallapp_order_givegift_success',
	    '90154'=>'smallapp_comment_err',
	    '90155'=>'smallapp_comment_score_err',
	    '90156'=>'smallapp_comment_staff_empty',
	    '90157'=>'smallapp_activity_not_exist',
	    '90158'=>'smallapp_reward_money_not_exist',
	    '90159'=>'smallapp_add_sharefile_uplimit',
	    '90160'=>'smallapp_del_sharefile_error',
	    '90161'=>'smallapp_sharefile_not_exist',
	    '90162'=>'smallapp_lottery_not_exist',
	    '90163'=>'smallapp_lottery_not_config',
	    '90164'=>'smallapp_lottery_not_start',
	    '90165'=>'smallapp_lottery_not_finish',
        '90166'=>'smallapp_qrcode_has_expire',
        '90167'=>'smallapp_lottery_has_exist_noopen',
        '90168'=>'smallapp_lottery_join_people_num_error',
        '90169'=>'smallapp_lottery_timeout_error',
        '90170'=>'smallapp_turntable_game_has_exist',
        '90171'=>'smallapp_lottery_not_exist',
        '90172'=>'smallapp_lottery_has_expire',
        '90173'=>'smallapp_lottery_prize_not_exist',
        '90174'=>'smallapp_lottery_prize_task_not_finish',


        '91015'=>'smallapp_user_add_failed',
        '91016'=>'small_app_addcollect_failed',

        '91001'=>'smallapp_pushnetty_msg_error',
        '91002'=>'smallapp_netty_position_requestid_notexist',
        '91003'=>'smallapp_netty_position_mac_notexist',
        '91004'=>'smallapp_netty_position_location_error',
        '91005'=>'smallapp_netty_position_mac_notregister',
        '91006'=>'smallapp_netty_position_other_error',
        '91007'=>'smallapp_netty_position_timeout',
        '91008'=>'smallapp_netty_push_requestid_notexist',
        '91009'=>'smallapp_netty_push_pushbox_error',
        '91010'=>'smallapp_netty_push_requestid_notexist',
        '91011'=>'smallapp_netty_push_pushcmd_notexist',
        '91012'=>'smallapp_netty_push_box_notexist',
        '91013'=>'smallapp_netty_push_box_error',
        '91014'=>'smallapp_netty_push_content_error',
        '91017'=>'smallapp_netty_push_nobox_register',
        '91018'=>'smallapp_netty_push_box_notregister',
        '91019'=>'smallapp_netty_push_other_error',
        '91020'=>'smallapp_netty_push_timeout',

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
        '92010'=>'smallappdinner_user_openid_error',
        '92011'=>'smallappdinner_box_signin_error',
        '92012'=>'smallappdinner_addactivitygoods_time_error',
        '92013'=>'smallappdinner_addactivitygoods_has_repeat',
        '92014'=>'smallappdinner_activitygoods_status_error',
        '92015'=>'smallappdinner_program_play_error',
        '92016'=>'smallappdinner_activity_has_upper_limit',
        '92017'=>'smallappdinner_upload_oss_md5_error',
        '92018'=>'smallappdinner_hotel_nothas_goods',
        '92019'=>'smallappdinner_hotel_nothas_playprogram',
        '92020'=>'smallappdinner_goods_not_approved',
        '92021'=>'smallappdinner_addorder_upperlimit',
        '92022'=>'smallappdinner_collection_upperlimit',
        '92023'=>'smallappdinner_sendsms_repeatsend',
        '92024'=>'smallappdinner_addorder_repeat',

        //小程序销售端
        '93001'=>'smallappsale_role_error',
        '93002'=>'smallappsale_user_has_remove',
        '93003'=>'smallappsale_qrcode_decode_error',
        '93004'=>'smallappsale_qrcode_has_expired',
        '93005'=>'smallappsale_invite_code_not_exists',
        '93006'=>'smallappsale_addorder_phone_error',
        '93007'=>'smallappsale_addorder_integral_not_enough',
        '93008'=>'smallappsale_connect_box',
	    '93009'=>'smallappsale_bindmobile_verify_err',
	    '93010'=>'smallappsale_bindmobile_fail',
	    '93011'=>'smallappsale_verify_code_sendfail',
	    '93012'=>'smallappsale_openid_empty',
	    '93013'=>'smallappsale_mobile_have_exist',
	    '93014'=>'smallappsale_staff_empty',
	    '93015'=>'smallappsale_merchant_empty',
	    '93016'=>'smallappsale_exchange_money_error',
	    '93017'=>'smallappsale_exchange_integral_not_enough',
	    '93018'=>'smallappsale_exchange_num_has_upper_limit',
	    '93019'=>'smallappsale_welcome_has_begin_start',
	    '93020'=>'smallappsale_welcome_user_not_match',
	    '93021'=>'smallappsale_welcome_has_stop',
	    '93022'=>'smallappsale_welcome_has_remove',
	    '93023'=>'smallappsale_welcome_had_play',
	    '93024'=>'smallappsale_welcome_choose_data_error',
	    '93025'=>'smallappsale_addgoods_had_over_max',
	    '93026'=>'smallappsale_update_taskshareprofit_had_over_max',
	    '93027'=>'smallappsale_taskshareprofit_update_no_permission',
	    '93028'=>'smallappsale_set_taskshareprofit_error',
	    '93029'=>'smallappsale_choose_room_error',
	    '93030'=>'smallappsale_staff_not_exist_inhotel',
	    '93031'=>'smallappsale_not_have_permission',
	    '93032'=>'smallappsale_staff_not_support_set_permission',
	    '93033'=>'smallappsale_staff_has_set_permission',
	    '93034'=>'smallappsale_dishgoods_not_exist',
	    '93035'=>'smallappsale_merchant_not_exist',
	    '93036'=>'smallappsale_order_not_exist',
	    '93037'=>'smallappsale_dishgoods_has_down',
	    '93038'=>'smallappsale_adddishorder_time_error',
	    '93039'=>'smallappsale_register_sendtime_error',
	    '93040'=>'smallappsale_register_verify_code_error',
	    '93041'=>'smallappsale_register_user_not_ok',
	    '93042'=>'smallappsale_dishgoods_name_error',
	    '93043'=>'smallappsale_payee_openid_has_check',
	    '93044'=>'smallappsale_self_time_error',
	    '93045'=>'smallappsale_set_payee_repeat',
	    '93046'=>'smallappsale_add_dishgoods_amount_error',
	    '93047'=>'smallappsale_add_orderexpress_error',
	    '93048'=>'smallappsale_order_status_not_express',
	    '93049'=>'smallappsale_exchange_day_money_upper_limit',
	    '93050'=>'smallappsale_exchange_money_error',
	    '93051'=>'smallappsale_goods_amount_gt_zero',
	    '93052'=>'smallappsale_signin_num_upper_limit',
	    '93053'=>'smallappsale_lottery_time_error',
	    '93054'=>'smallappsale_lottery_time_repeat',
	    '93055'=>'smallappsale_lotteryactivity_cancel_error',
	    '93056'=>'smallappsale_input_assign_moneyorintegral',
	    '93057'=>'smallappsale_input_assign_money_error',
	    '93058'=>'smallappsale_merchant_assign_money_error',
	    '93059'=>'smallappsale_input_assign_integral_error',
	    '93060'=>'smallappsale_not_has_cash_task',
	    '93061'=>'smallappsale_cash_task_had_expire',
	    '93062'=>'smallappsale_cash_task_had_withdraw',
	    '93063'=>'smallappsale_cash_task_had_recevie',
	    '93064'=>'smallappsale_claim_income_error',
	    '93065'=>'smallappsale_notcash_task_claim_error',
	    '93066'=>'smallappsale_cash_task_not_finish',
	    '93067'=>'smallappsale_initiatelottery_time_error',


	),
);