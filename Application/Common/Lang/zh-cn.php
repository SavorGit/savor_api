<?php

/**
 * ThinkPHP 简体中文语言包
 */
return array(
	'parmas_not_null'=>'网络异常，请稍后再试。',
	'parmas_null'=>'参数为空',
	'traceinfo_not_null'=>'traceinfo不能为空',
	'mobile_deviceid_not_match'=>'用户手机设备不一致',
	'token_not_null'=>'令牌不能为空',
	'token_has_expired'=>'token已失效',
	'sign_error'=>'签名错误',
	'success'=>'成功',
    'wechat_encrypts_data_decrypts_failed'=>'微信加密数据,解密失败',
    
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
    'have_not_this_box'=>'设备异常，请稍后再试。',
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
    'stas_time_illegal'=>'非标准时间戳格式',
    'stas_time_insert_fail'=>'存入redis失败',
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
    'option_user_not_exist'=>'登录失败,没有登录权限',
    'option_user_pwd_error'=>'登录失败,账号密码错误',
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
    'option_task_state_error'=>'该任务为非处理中任务',
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
    'option_task_bind_error'=>'绑定机顶盒参数传递错误',
    'option_task_bind_mac_have'=>'该机顶盒mac地址已存在',
    'option_bind_mac_update_fail'=>'机顶盒mac更新失败',
    'option_user_role_null'=>'登录失败，没有登录权限',
    'option_task_type_empty'=>'任务类型错误',
    'option_task_type_changed'=>'任务类型不一致',
    'option_task_upload_img_nums_err'=>'上传照片数量错误',
    'option_task_bind_mac_repeat'=>'绑定MAC与机顶盒MAC相同',
    'option_ads_list_err'=>'由于正在下载历史数据，无法查看列表',
    'option_pro_empty'=>'该节目单不存在',
    'option_task_box_have_repaired'=>'该版位已经维修处理过,请勿重复操作',
    'option_task_have_completed'=>'该任务已经由#处理为完成状态',
    'option_task_box_have_completed'=>'该版位已经由#维修，请处理其它版位',
    'option_task_mobile_empty'=>'酒楼联系人电话不能为空',
    'option_task_mobile_illegal'=>'酒楼联系电话非法',
    //每日知享接口
    'daily_content_not_exist'=>'文章不存在',
    'daily_content_collection_err'=>'收藏失败，网络异常，请稍后再试。',
    'daily_content_not_collection_err'=>'取消收藏失败，网络异常，请稍后再试。',
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
    //餐厅端接口
    'dinner_reportlog_touping_fail'=>'餐厅端酒楼投屏日志上传失败',
    'dinner_mobile_error'=>'手机号输入有误',
    'dinner_user_code_min'=>'一分钟内请勿重复获取验证码',
    'dinner_user_code_illegal'=>'验证码错误或已过期',
    'dinner_invite_code_err'=>'您输入的邀请码不正确,请重新输入',
    'dinner_user_login_err'=>'登录失败',
    'dinner_hotel_rec_food_empty'=>'当前酒楼没有推荐菜，请联系酒楼维护人员添加',
    'dinner_hotel_empty'=>'邀请码对应酒楼不存在',
    'dinner_hotel_state_err'=>'邀请码对应的酒楼状态异常',
    'dinner_mobile_not_fit_invitecode'=>'该手机号与邀请码绑定手机号不一致',
    'dinner_invite_code_have_used'=>'该邀请码已经被使用,请更换',
    'dinner_please_input_your_invite_code'=>'您已经绑定邀请码，请输入正确邀请码',
    'dinner_hotel_adv_list_empty'=>'该酒楼没有宣传片，请联系酒楼维护人员添加',
    'dinner_hotel_room_empty'=>'该酒楼没有包间，请联系酒楼维护人员添加',
    'dinner_invite_id_illegal'=>'传参邀请id应为整数',

    'dinner_mobile_not_bind_code'=>'该手机号还未绑定酒楼邀请码',
    'dinner_customer_import_err'=>'通讯录导入失败',
    'dinner_customer_import_empty'=>'导入通讯录不能为空',
    'dinner_bind_invite_err'=>'用户绑定关系不存在',
    'dinner_bind_mobile_err'=>'绑定手机号与该手机号不一致',
    'dinner_customer_have_import'=>'该账号已经导入过通讯录',


    'dinner_customer_insert_fail'=>'添加客户失败请重试',
    'dinner_customer_already_exist'=>'该客户联系方式已经添加过',
    'dinner_customer_tel_repeat'=>'客户电话请勿重复',
    'dinner_customer_tel_empty'=>'客户两个电话不可都为空',
    'dinner_customer_tel1_exist'=>'手机号1已经存在',
    'dinner_customer_tel2_exist'=>'手机号2已经存在',
    'dinner_customer_id_empty'=>'客户id不可为空',
    'dinner_customer_empty'=>'该客户不存在',
    'dinner_customer_label_add_fail'=>'客户标签添加失败',
    'dinner_customer_update_fail'=>'客户更新失败',
    'dinner_customer_label_illegal'=>'客户标签id不可为空',
    'dinner_customer_label_not_exist'=>'客户标签id不存在',
    'dinner_customer_label_die_fail'=>'客户标签熄灭失败',
    'dinner_customer_consume_failed'=>'消费记录添加失败',
    'dinner_label_type_error'=>'点亮熄灭标签类型传参错误',
    'dinner_order_not_exist'=>'预订信息不存在',
    'dinner_cosumerecord_type_error'=>'添加消费记录类型传参错误',
    'dinner_customer_tel_notsame'=>'客户ID与电话得到ID不一致',

    'dinner_room_add_failed'=>'包间添加失败',
    'dinner_room_name_repeat'=>'包间名称已存在，请重新输入',
    'dinner_order_add_failed'=>'预订添加失败',
    'dinner_order_empty'=>'该预订信息不存在',
    'dinner_is_welcome_used'=>'该预订信息的欢迎词功能已经使用过',
    'dinner_is_recfood_used'=>'该预订信息的推荐菜功能已经使用过',
    'dinner_ticket_url_have_upload'=>'该预订信息的消费小票已经上传过',
    'dinner_service_update_failed'=>'更新失败',
    'dinner_service_ticket_url_empty'=>'请上传消费小票',
    'dinner_cannot_del_other_hotel_order'=>'不可删除其它酒楼的预订信息',
    'dinner_order_del_failed'=>'预订信息删除失败',
    'dinner_order_update_failed'=>'预订信息更新失败',
    'dinner_order_donot_belong_you'=>'该预订不属于你',
    'dinner_customer_mobile_err'=>'请输入正确的客户手机',
    'dinner_customer_have_exist'=>'导入失败，该用户已经存在',
    
    'box_not_exist'=>'设备异常，请稍后再试。',
    'box_mac_error'=>'mac与机顶盒mac不一致',
    'box_device_token_report_error'=>'机顶盒device_token上报失败',
    'box_room_empty' =>'包间不存在',
    'box_device_token_empty'=>'机顶盒设备号为空',
    
    'hotel_attendant_empty'=>'酒楼工作人员mac为空',
    'not_in_rtb_ads_push_time'=>'不在推送时间范围内',
    'rtb_push_ads_list_empty'=>'推送RTB广告列表为空',
    'box_report_download_empty'=>'上报下载资源为空',
    'box_report_download_same'=>'上报下载资源和当前记录下载资源相同',
    'box_report_play_same'=>'上报播放资源和当前记录播放资源相同',

    'box_mac_not_empty'=>'设备异常，请稍后再试。',
    'box_mac_illegal'=>'机顶盒MAC不存在',
    'box_memory_state_illegal'=>'机顶盒内存状态上报值错误',
    'boxmem_data_insert_error'=>'数据入库失败',
    'boxmem_data_already_insert'=>'内存卡信息已经上报',
    'poly_ads_empty'=>'聚屏广告列表为空',
    'poly_hotel_not_normal'=>'酒楼不存在或者为非正常酒楼',
    'poly_box_empty'=>'该酒楼下无聚屏广告的盒子',
    'for_screen_failed'=>'投屏失败',
    'stop_screen_failed'=>'结束投屏失败',
    'option_task_pub_task_too_often'=>'您发布任务太过频繁，请10秒后再发',
    'forscreen_record_failed'=>'投屏记录失败',
    'forscreen_push_suggestion_failed'=>'反馈意见失败',
    'smallapp_turntable_log_failed'=>'网络异常，请稍后再试。',
    'smallapp_order_time_failed'=>'网络异常，请稍后再试。',
    'smallapp_user_exist'=>'用户已存在',
    'smallapp_user_add_failed'=>'用户添加失败',
    'small_app_addcollect_failed'=>'收藏失败，请稍后重试',
    'small_app_del_failed'=>'删除失败',
    'small_app_breanlink_failed'=>'断开连接失败',

    'smallapp_pushnetty_msg_error'=>'您选择的包间设备异常，请稍后再试',

    'smallapp_netty_position_requestid_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_position_mac_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_position_location_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_position_mac_notregister'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_position_other_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_position_timeout'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_requestid_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_pushbox_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_requestid_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_pushcmd_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_box_notexist'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_box_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_content_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_nobox_register'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_box_notregister'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_other_error'=>'您选择的包间设备异常，请稍后再试',
    'smallapp_netty_push_timeout'=>'您选择的包间设备异常，请稍后再试',



    'small_app_launch_game_failed'=>'网络异常，发起游戏失败，请稍后再试',
    'small_app_game_have_start'=>'游戏已开始',
    'small_app_game_start_failed'=>'网络异常，游戏开始失败，请稍后再试',
    'small_app_game_score_report_error'=>'游戏数据上报失败',
    'small_app_game_not_exist'=>'该游戏不存在或已下线',
    'small_app_game_have_not_lunch'=>'游戏尚未发起，请等待',
    'smallapp_forscreen_size_not_exist'=>'投屏文件大小不存在',
    'smallapp_forscreen_md5_not_exist'=>'投屏文件md5不存在',
    'smallapp_forscreen_record_not_exist'=>'投屏记录不存在',
    'smallapp_forscreen_has_addhelpplay'=>'请勿重复添加助力',
    'smallapp_forscreen_has_addhelp'=>'请勿重复助力',
    'smallapp_resource_foul'=>'投屏内容涉嫌违规',
    'smallapp_address_not_you'=>'该收货地址不属于你',
    'smallapp_cart_not_you'=>'此购物车不属于你',
    'smallapp_order_not_you'=>'此订单不属于你',
    'smallapp_order_choose_paytype_error'=>'请选择正确的支付方式',
    'smallapp_order_has_cancel'=>'订单已取消,请勿重复取消',
    'smallapp_order_not_support_cancel'=>'此订单不支持取消',
    'smallapp_order_has_receive_error'=>'订单接收失败',
    'smallapp_order_receive_delivery_error'=>'订单配送失败',
    'smallapp_comment_err'=>'评价失败，请稍后再试',
    'smallapp_comment_score_err'=>'评分参数异常',
    'smallapp_comment_staff_empty'=>'该服务人员不存在',
    

    'send_sms_error'=>'短信发送失败，请稍后再试',
    'smallappdinner_mobile_error'=>'请输入正确的手机号码',
    'smallappdinner_invite_code_error'=>'邀请码输入错误',
    'smallappdinner_invite_code_isuse'=>'邀请码已被使用',
    'smallappdinner_verify_code_hassend'=>'验证码已发送',
    'smallappdinner_verify_code_sendfail'=>'验证码发送失败,请重试',
    'smallappdinner_verify_code_checkfail'=>'验证码输入错误',
    'smallappdinner_login_fail'=>'登录失败',
    'smallappdinner_invite_code_checkerror'=>'邀请码错误',
    'smallapp_user_notexist'=>'获取用户信息失败，请稍后再试。',
    'smallapp_bonus_money_error'=>'单个红包额度过低',
    'smallapp_bonus_money_max_error'=>'红包额度过大',
    'smallapp_surname_error'=>'请输入百家姓中的姓氏',
    'smallapp_bonus_range_error'=>'红包发送不在范围之内',
    'smallapp_sendtvbonus_error'=>'发送电视红包失败',
    'smallapp_redpacket_status_error'=>'亲，红包已抢完。',
    'smallapp_grabbonus_status_error'=>'亲，红包已抢完。',
    'smallapp_bonus_error'=>'该红包不存在',
    'smallapp_qrcode_type_error'=>'设备异常，请稍后再试。',
    'smallapp_qrcode_content_error'=>'设备异常，请稍后再试。',
    'smallapp_qrcode_has_expire'=>'设备异常，请稍后再试。',
    'smallappdinner_bdhotel_dqhotel_error'=>'邀请码为非本酒楼邀请码，请核实后重试',
    'virtual_small_data_erro'=>'虚拟小平台推送数据错误',
    'virtual_small_code_erro'=>'虚拟小平台推送数据已处理',
    'smallapp_forscreen_file_not_exist'=>'投屏文件已过期',
    'smallappdinner_user_openid_error'=>'获取用户信息失败，请稍后再试。',
    'smallappdinner_box_signin_error'=>'当前盒子签到失败',
    'smallappdinner_addactivitygoods_time_error'=>'盒子添加商品活动时间错误',
    'smallappdinner_addactivitygoods_has_repeat'=>'请勿重复添加活动商品',
    'smallappdinner_activitygoods_status_error'=>'活动商品状态错误',
    'smallappdinner_program_play_error'=>'节目单播放失败',
    'smallappdinner_activity_has_upper_limit'=>'活动已达上限',
    'smallappdinner_upload_oss_md5_error'=>'请重新选择上传图片或视频',
    'smallappdinner_hotel_nothas_goods'=>'该酒楼无此活动商品',
    'smallappdinner_hotel_nothas_playprogram'=>'暂无节目单循环播放内容',
    'smallappdinner_goods_not_approved'=>'商品未审核通过',
    'smallappdinner_addorder_upperlimit'=>'下单次数已达上限',
    'smallappdinner_collection_upperlimit'=>'收藏次数已达上限',
    'smallappdinner_sendsms_repeatsend'=>'请勿重复发送短信',
    'smallappdinner_addorder_repeat'=>'您已下单，60秒内请勿重复下单。',
    'smallappsale_role_error'=>'当前用户已冻结',
    'smallappsale_user_has_remove'=>'用户已经移除',
    'smallappsale_qrcode_decode_error'=>'二维码解码错误',
    'smallappsale_qrcode_has_expired'=>'二维码已过期',
    'smallappsale_invite_code_not_exists'=>'邀请码不存在',
    'smallapp_have_collect'=>'您已经收藏过了',
    'smallappsale_addorder_phone_error'=>'请输入正确的手机号码',
    'smallappsale_addorder_integral_not_enough'=>'您的兑换积分不够',
    'smallappsale_connect_box'=>'请先连接包间电视,再进行购买',
    'smallappsale_bindmobile_verify_err'=>'手机验证码错误或失效',
    'smallappsale_bindmobile_fail'=>'绑定手机号失败',
    'smallappsale_verify_code_sendfail'=>'验证码发送失败,请重试',
    'smallappsale_openid_empty'=>'该用户唯一标识错误',
    'smallappsale_mobile_have_exist'=>'该手机号已被绑定,请使用其他手机号',
    'smallappsale_staff_empty'=>'该酒楼不存在该用户',
    'smallappsale_merchant_empty'=>'该商家不存在或已下线',
    'smallappsale_exchange_money_error'=>'兑换金额有误',
    'smallappsale_exchange_integral_not_enough'=>'积分不足，请尝试提现其他金额～',
    'smallappsale_exchange_num_has_upper_limit'=>'用户提现机会已达上限',
    'smallappsale_welcome_has_begin_start'=>'欢迎词已开始播放',
    'smallappsale_welcome_user_not_match'=>'欢迎词用户不匹配',
    'smallappsale_welcome_has_stop'=>'欢迎词已停止播放',
    'smallappsale_welcome_has_remove'=>'欢迎词已删除',
    'smallappsale_welcome_had_play'=>'欢迎词正在播放,不能删除',
    'smallappsale_welcome_choose_data_error'=>'请你选择正确的时间',
    'smallappsale_addgoods_had_over_max'=>'添加餐厅活动已达上限',
    'smallappsale_update_taskshareprofit_had_over_max'=>'本月修改分润设置已达上限',
    'smallappsale_taskshareprofit_update_no_permission'=>'你无此权限进行修改',
    'smallappsale_set_taskshareprofit_error'=>'分润比例设置有误',
    'smallappsale_choose_room_error'=>'当前选择包间有误',
    'smallappsale_staff_not_exist_inhotel'=>'当前服务员不属于此酒楼',
    'smallappsale_not_have_permission'=>'无此权限进行操作',
    'smallappsale_staff_not_support_set_permission'=>'当前员工不支持设置权限',
    'smallappsale_staff_has_set_permission'=>'当前员工已设置过此权限',
    'smallappsale_dishgoods_not_exist'=>'获取菜品失败，请稍后重试',
    'smallappsale_merchant_not_exist'=>'获取商品列表失败，请稍后重试',
    'smallappsale_order_not_exist'=>'订单不存在',
    'smallappsale_dishgoods_has_down'=>'菜品已下架,不能进行此操作.',
    'smallappsale_adddishorder_time_error'=>'请核对你的送达时间',
    'smallappsale_register_sendtime_error'=>'60秒内请勿重复发送',
    'smallappsale_register_verify_code_error'=>'注册验证码错误',
    'smallappsale_register_user_not_ok'=>'当前商家未通过审核',
    'smallappsale_dishgoods_name_error'=>'菜品名称不能重复',
    'smallappsale_payee_openid_has_check'=>'当前重置的收款人正在审核中,请勿重复重置',
    'smallappsale_self_time_error'=>'请选择正确的自提时间',
    'smallappsale_set_payee_repeat'=>'当前重置的收款人已通过审核,请勿重复重置',
    'smallapp_order_address_error'=>'该地址不在商家的配送范围，请选择同城市的收货地址。',
    'smallappsale_add_dishgoods_amount_error'=>'库存输入有误',
    'smallappsale_add_orderexpress_error'=>'请勿重复录入快递单号',
    'smallappsale_order_status_not_express'=>'当前订单状态不能进行发货',
    'smallappsale_exchange_day_money_upper_limit'=>'单日提现金额已达上限',
    'smallappsale_exchange_money_error'=>'提现金额有误',
    'smallapp_order_addshop_order_area_error'=>'订单中包含限售区域的商品',
    'smallapp_order_addshop_order_totalmoney_error'=>'订单金额有误',
    'smallapp_order_amount_error'=>'当前购买数量有误,无法购买',
    'smallappsale_goods_amount_gt_zero'=>'请输入大于0的整数',
    'smallapp_goods_sale_out'=>'商品已售空下架',
    'smallapp_order_upnum_gt_buynum'=>'限领份数不能大于购买数量',
    'smallapp_order_receivenum_gt_hasnum'=>'当前数量不足，请重新选择数量',
    'smallapp_order_receive_over'=>'当前礼品已领完',
    'smallapp_order_had_give'=>'当前礼品已转赠',
    'smallapp_order_gift_had_expire'=>'当前礼品已领取',
    'smallapp_order_gift_had_receive'=>'当前礼品已领取',
    'smallapp_order_givegift_had_receive'=>'当前转赠礼品已被领取',
    'smallapp_order_givegift_num_error'=>'您需要将%d件商品进行分配',
    'smallapp_order_givegift_success'=>'该商品已转增成功，无法自己领取',
    'smallappsale_signin_num_upper_limit'=>'当天签到次数已达上限',
    'smallapp_activity_not_exist'=>'已过本轮抽奖时间，请等待下一轮抽奖',
    'smallapp_reward_money_not_exist'=>'请输入正确的打赏金额',
    'smallappsale_lottery_time_error'=>'请选择正确的开奖时间',
    'smallappsale_lottery_time_repeat'=>'相同时间内不能存在多个活动',
    'smallappsale_lotteryactivity_cancel_error'=>'当前活动不能取消',
    'smallappsale_input_assign_moneyorintegral'=>'请输入要分配的金额或积分',
    'smallappsale_input_assign_money_error'=>'输入分配的金额有误',
    'smallappsale_merchant_assign_money_error'=>'分配系统正在维护中，请明天再试。',
    'smallappsale_input_assign_integral_error'=>'输入分配的积分有误',
    'smallapp_add_sharefile_uplimit'=>'当前分享的文件已达上限',
    'smallapp_del_sharefile_error'=>'删除分享文件失败，请稍后重试',
    'smallapp_sharefile_not_exist'=>'分享文件失败，请稍后重试',
    'smallapp_lottery_not_exist'=>'抽奖活动正在维护中，请稍后再试。',
    'smallapp_lottery_not_config'=>'请先配置抽奖活动',
    'smallapp_lottery_not_start'=>'请先开始抽奖',
    'smallapp_lottery_not_finish'=>'抽奖活动还未结束',
    'smallapp_lottery_has_exist_noopen'=>'当前包间存在未开奖的抽奖活动，请稍后重试',
    'smallapp_lottery_join_people_num_error'=>'大于2人才可开始抽奖，快来邀请好友加入吧',
    'smallapp_lottery_timeout_error'=>'活动已超时结束，请重新发起。',
    'smallapp_turntable_game_has_exist'=>'活动已超时结束，请重新发起。',
    'smallappsale_not_has_cash_task'=>'请先领取现金红包任务',
    'smallappsale_not_has_task'=>'请先领取任务',
    'smallappsale_cash_task_had_expire'=>'任务已超时结束，请重新领取现金任务',
    'smallappsale_task_had_expire'=>'任务已超时结束，请重新领取任务',
    'smallappsale_cash_task_had_withdraw'=>'现金已领取，请再次领取现金任务',
    'smallappsale_cash_task_had_recevie'=>'现金任务已领取，请完成后再次领取',
    'smallappsale_task_had_recevie'=>'任务已领取，请完成后再次领取',
    'smallappsale_claim_income_error'=>'收益已被其他人领取，请选取其他包间',
    'smallappsale_notcash_task_claim_error'=>'请先领取现金红包任务',
    'smallappsale_cash_task_not_finish'=>'请先完成现金红包任务',
    'smallapp_lottery_has_expire'=>'抽奖活动已过期',
    'smallapp_lottery_prize_not_exist'=>'抽奖活动奖品不存在，请重新进行抽奖',
    'smallapp_lottery_prize_task_not_finish'=>'请先完成抽奖活动奖品任务',
    'smallappsale_initiatelottery_time_error'=>'请选择正确的发起时间',
    'box_not_5g_network'=>'当前酒楼非5G网络环境',
    'box_download_in_progress'=>'当前盒子正在下载中',
    'box_download_finish'=>'当前盒子已下载完成',
    'box_otherdownload_wait'=>'当前无空闲局域网机顶盒,请等待',
    'smallapp_tastwine_not_exist'=>'品鉴酒活动正在维护中，请稍后再试。',
    'smallapp_tasklottery_not_exist'=>'抽奖活动正在维护中，请稍后再试。',
    'smallapp_tastwine_not_meal_time'=>'请在饭局时间内参加品鉴酒活动',
    'smallapp_tastwine_room_join_uplimit'=>'免费品鉴酒已领完，您下次要快点哦～',
    'smallapp_tastwine_join_uplimit'=>'您已参与过此活动，不可重复参与',
    'smallapp_tastwine_had_join'=>'当前饭局已参加过品鉴酒活动',
    'smallapp_tasklottery_had_join'=>'当前饭局已参加过抽奖活动',
    'smallapp_tastwine_join_first'=>'请先参加品鉴酒活动',
    'smallapp_tastwine_invalid_user'=>'无法领取，请联系管理人员',
    'smallappsale_login_had_register'=>'请勿重复注册',
    'smallappsale_notenough_inventory_to_launch_event'=>'库存不足，请联系管理员',
    'smallappsale_please_get_task_first'=>'请先领取任务',
    'smallapp_tastwine_has_expire'=>'品鉴酒活动已过期',
    'smallapp_tasklottery_has_expire'=>'抽奖活动已过期',
    'smallappsale_activity_boot_num_error'=>'当前电视开机数不足%d个，无法发起抽奖活动',
    'smallappsale_activity_lottery_time_error'=>'请选择正确的时间',
    'smallappsale_activity_lottery_has_begin'=>'活动已开始,请勿进行修改',
    'smallappops_contact_admin_createuser'=>'请联系管理员创建用户',
    'smallappops_login_had_register'=>'请勿重复注册',
    'smallappops_not_entity_platform'=>'该酒楼非实体小平台',
    'smallappops_clean_resource_error'=>'清除资源失败,请稍后重试',
    'smallapp_meeting_not_exist'=>'加入年会失败,请稍后重试',
    'smallapp_start_singin'=>'请先发起参会签到',
    'smallapp_singin_nouser'=>'当前无签到人员,请重新发起参会签到',
    'smallapp_choose_date_notday'=>'请选择同一天的日期',
    'smallapp_singin_time_error'=>'请签到结束后，再发起抽奖',
    'smallapp_choose_enddate_error'=>'请选择大于当前时间的结束时间',
    'smallappsale_invitation_hotel_error'=>'当前酒楼暂未开通邀请函,请联系管理员',
    'smallappsale_finance_qrcode_type_error'=>'二维码单位和商品单位不一致',
    'smallappsale_finance_qrcode_error'=>'请扫酒水二维码',
    'smallappsale_finance_vqrcode_error'=>'扫码错误,请扫酒商码',
    'smallappsale_finance_vintner_code_hadrecord'=>'此商品已有扫码记录,请勿重复扫码',
    'smallappsale_finance_stock_goods_hasin_exist'=>'扫码商品已入库',
    'smallappsale_finance_stock_goods_hasout_exist'=>'扫码商品已出库',
    'smallappsale_finance_stock_goods_hasin_noexist'=>'扫码商品未入库',
    'smallappsale_finance_stock_goods_out_not_matchup'=>'商品不一致',
    'smallappsale_finance_stock_goods_not_exist'=>'商品不存在',
    'smallappsale_finance_stock_donot_restock_in'=>'请勿重复入库',
    'smallappsale_finance_cant_receive_more_stock'=>'不能同时领取多个出库单的商品',
    'smallappsale_finance_cant_outstock_error'=>'当前出库单还未完成出库',
    'smallappsale_finance_outstock_has_receive_first'=>'请先领取当前出库单',
    'smallappsale_finance_outstock_has_receive'=>'当前出库单已领取',
    'smallappsale_finance_outstock_has_check'=>'当前出库单已验收',
    'smallappsale_finance_stock_recevie_num_not_eq'=>'领取数量不一致',
    'smallappsale_finance_stock_check_num_not_eq'=>'验收数量不一致',
    'smallappsale_finance_goods_writeoff_error'=>'当前商品不支持核销',
    'smallappsale_finance_goods_had_writeoff'=>'当前商品已完成核销',
    'smallappsale_finance_goods_had_submit_writeoff'=>'当前商品已申请核销',
    'smallappsale_finance_goods_had_reportedloss'=>'当前商品已报损',
    'smallappsale_finance_goods_check_error'=>'当前商品未验收',
    'smallappsale_finance_goods_differentwrittenoff_sametime'=>'不能同时核销不同的商品',
    'smallappsale_finance_goods_had_submit_reportedloss'=>'当前商品已申请报损',
    'smallappsale_finance_unpack_repeat'=>'请勿重复拆箱',
    'smallappsale_finance_stock_goods_hastaste_writeoff_error'=>'品鉴酒未用完,不能进行核销',
    'smallappsale_invitation_not_exist'=>'邀请函正在维护中，请稍后再试。',
    'smallappsale_invitation_had_expire'=>'请在预定时间内接收邀请',
    'smallappsale_finance_qrcode_had_use'=>'当前二维码已使用',
    'smallappsale_finance_qrcode_has_nouse'=>'当前二维码还未使用',
    'smallappsale_goods_not_support_lottery'=>'当前酒水不可发起抽奖活动',
    'smallappsale_qrcode_type_error'=>'二维码类型错误',
    'smallappsale_wo_coupon_has_used'=>'当前优惠券已使用',
    'smallappsale_wo_coupon_not_intime'=>'当前优惠券不在使用范围',
    'smallappsale_qrcode_not_support_coupon_writeoff'=>'此码无法优惠券核销',
    'smallappsale_qrcode_not_support_goods_writeoff'=>'此码无法商品核销',
    'smallappsale_qrcode_not_support_lottery'=>'此码无法发起售酒抽奖',
    'smallappsale_hotel_nothave_sell_lottery'=>'当前酒楼暂未配置售卖抽奖,请联系管理员',
    'smallappsale_hotel_nothave_sell_lottery'=>'当前酒楼暂未配置售卖抽奖,请联系管理员',
    'smallappsale_sellgoods_has_sell_lottery'=>'当前核销商品已发起过售酒抽奖',
    'smallappsale_lotterygoods_has_writeoff'=>'当前奖品已核销',
    'smallappsale_qrcode_not_support_lotterygoods_writeoff'=>'此码无法实物奖品核销',
    'smallappsale_goods_had_bind_coupon'=>'无法使用，此商品已经被其他优惠券绑定',
    'smallappsale_goods_has_notin_coupon_use_range'=>'此商品不在当前优惠券使用范围',
    'smallappsale_ads_notin_demand_task'=>'请选择点播任务的广告',
    'smallappsale_demand_task_notin_meal'=>'点播无效，请在就餐时间重试',
    'smallappsale_demand_task_box_had_finish'=>'当前电视此任务已被完成，请使用其他电视。',
    'smallappsale_demand_task_box_has_finish_plase_next'=>'本次任务已被xxxx完成，新任务xx分钟后开始',
    'smallappsale_forscreenuser_in_selluser'=>'请勿使用相同用户进行操作',
    'smallappsale_task_notin_get_time'=>'请在每天上午六点后领取任务',
    'smallappops_add_hotel_name_repeat'=>'餐厅名称不能重复',
    'smallappops_salerecord_sign_inout_hotel_error'=>'签到签退的酒楼必须为同一酒楼',
    'smallapp_sellwine_activity_choose_same_goods'=>'请选择同一商品',
    'smallapp_sellwine_activity_get_money_limit'=>'领取红包金额已达上限',
    'smallapp_sellwine_activity_get_dailymoney_limit'=>'领取每日红包已达上限',
    'smallapp_sellwine_activity_order_has_get_money'=>'当前订单已领取过红包',
    'smallapp_sellwine_activity_idcode_has_bind'=>'商品码不能重复使用',
    'smallapp_hotel_staff_not_join_activity'=>'餐厅人员不可参与此活动',
    'smallappsale_task_had_other_people_recevie'=>'当前任务已被其他人领取',
    'smallappsale_task_had_finish_scan_tastewine'=>'请勿重复倒酒',
    'smallappsale_task_tastewine_notin_meal'=>'请在饭局时间内给客人倒酒',
    'smallappsale_task_tastewine_goods_error'=>'请选择活动用的品鉴酒',
    'smallappsale_task_tastewine_hotel_had_finish'=>'当前酒楼品鉴酒已消耗完毕',
    'smallappsale_task_tastewine_not_use_openbottle'=>'扫码失败，请使用已经开瓶的品鉴酒进行品鉴。',
    'smallappsale_task_tastewine_use_tastewine_over'=>'当前品鉴酒已用完',
    'smallappsale_task_tastewine_use_tastewine_hotel_error'=>'请使用当前酒楼的品鉴酒',
    'smallapp_use_edit_error'=>'请完善个人信息',
    'smallapp_invite_sale_user_time_error'=>'邀请链接已失效',
    'smallapp_invite_user_disable'=>'邀请人已失效',
    'smallappops_stock_check_hotel_error'=>'扫码酒楼不一致',
    'smallappops_stock_check_local_error'=>'定位失败，请在餐厅200m范围内操作',
    'smallappops_stock_check_month_finish'=>'扫码所在酒楼当前月份已盘点完成',
    'smallappsale_add_customer_mobile_repeat'=>'手机号码已重复',
    'smallappsale_finance_stock_writeoff_hotel_error'=>'酒水所在酒楼不一致,无法核销',
    'smallappsale_finance_create_data_error'=>'所选时间段内无数据,无法生成',
    'smallappsale_finance_threshold_error'=>'暂时无法兑换，请联系热点工作人员',
    'smallappsale_exchange_spacetime_error'=>'一分钟内只能兑换一次',
    'smallappsale_finance_writeoff_bottle_error'=>'单次核销不能超过6瓶',
    'smallappdata_mobile_not_open_account'=>'当前手机号没有开通账号',
    'smallappdata_user_had_register'=>'当前用户已注册账号',
    'smallappdata_user_not_open_account'=>'当前用户没有开通账号',
    'smallapp_exchange_spacetime_error'=>'一分钟内只能提现一次',
    'smallapp_exchange_num_has_upper_limit'=>'提现机会已达上限',
    'smallapp_exchange_money_greater_last'=>'提现金额大于剩余金额',
    'smallapp_exchange_money_greater_500'=>'单次提现金额不能大于500元',
    'smallappsale_exchange_not_support'=>'系统维护中',
    'smallappsale_finance_writeoff_not_support'=>'系统维护中',
    'smallappops_bbs_nick_name_error'=>'昵称已被占用,请重新输入',
    'smallappops_bbs_add_comment_error'=>'每个话题每人每天只能评论一次',
    'smallappsale_finance_winecode_association_error'=>'关联失败，此酒商码已使用过',
    'smallappsale_finance_idcode_association_error'=>'关联失败，此热点码已使用过',
    'smallappsale_finance_use_bind_idcode_error'=>'请使用瓶码进行绑定',
    'smallappops_finance_threshold_error'=>'当前酒楼有欠款,无法进行送酒',





);