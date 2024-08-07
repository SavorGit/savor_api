<?php
//系统及固定配置
$route_rules = array(
	'/^small\/api\/getDownloadList\/(\d{0,10})\/(ads|adv|pro|vod|logo|load)$/'=>'small/api/getDownloadList?hotelid=:1&type=:2',


	'/^small\/api\/getHotel\/(\d{0,10})$/'=>'small/api/getHotel?hotelid=:1',

	'/^small\/api\/getHotel\/(\d{0,10})\/v2$/'=>'small/api/getHotelvb?hotelid=:1',

	'/^small\/api\/getRoom\/(\d{0,10})$/'=>'small/api/getHotelRoom?hotelid=:1',

	'/^small\/api\/getSetTopBox\/(\d{0,10})$/'=>'small/api/getHotelBox?hotelid=:1',
	'/^small\/api\/getTelevision\/(\d{0,10})$/'=>'small/api/getHotelTv?hotelid=:1',
	'/^small\/api\/getUpgradeVersion\/(\d{0,10})\/(wwar|apk)$/' =>'small/api/getUpgradeVersion?hotelId=:1&type=:2',


);
return array(
	//路由配置

	'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES'=>$route_rules,
	'URL_MODEL'				=>2,
	'URL_CASE_INSENSITIVE'  => true, //url支持大小写
    'VAR_MODULE'            =>  'savorm',     // 默认模块获取变量
    'VAR_CONTROLLER'        =>  'savorc',    // 默认控制器获取变量
    'VAR_ACTION'            =>  'savora',    // 默认操作获取变量
    'VAR_PATHINFO'          =>  'savors',    // 兼容模式PATHINFO获取变量例如 ?s=/module/action/id/1 后面的参数取决于URL_PATHINFO_DEPR
    'VAR_TEMPLATE'          =>  'savort',    // 默认模板切换变量
	'MODULE_DENY_LIST'      => array('Common','Runtime'), // 禁止访问的模块列表

	'MODULE_ALLOW_LIST'     => array('Basedata','Feed','Clientstart','Catvideo','H5',
	                                 'Version','Content','Heartbeat','Heartcalcu','Smallappdata',
	                                 'Download','Award','Small','Smalls','Screendistance',
	                                 'Opclient','Dailyknowledge','Tasksubcontract','Opclient11','Smallappops','Dinnerapp',
	                                 'Dinnerapp2','Box','Opclient20','Forscreen','Smallapp','Smallapp21','Netty',
	                                 'Games','Smallappsimple','Smallapp3','Smalldinnerapp','Payment','Smalldinnerapp11',
	                                 'Smallsale','Smallsale14','Smallsale16','Smallapp4','Smallapp43','Smallsale18',
                                     'Smallsale19','Smallapp44','Smallapp45','Smallapp46','Smallsale20','Smallsale21','Smallsale22','Smallsale23'), //模块配置

	'DEFAULT_MODULE'        => 'Small',
	//session cookie配置
	'SESSION_AUTO_START'    =>  true,    // 是否自动开启Session
	'SESSION_OPTIONS'       =>  array(), // session 配置数组 支持type name id path expire domain 等参数
	'SESSION_TYPE'          =>  '', // session hander类型 默认无需设置 除非扩展了session hander驱动
	'SESSION_PREFIX'        =>  'savorapiphp_', // session 前缀
    'COOKIE_DOMAIN'         => '',      // Cookie有效域名
    'COOKIE_PATH'           => '/',     // Cookie路径
    'COOKIE_PREFIX'         => 'savorapi',      // Cookie前缀 避免冲突

	//数据库配置
	'DB_FIELDS_CACHE' 		=> true,
    
    //心跳上报log
    'REPORT_LOG_PATH'       =>'/application_data/app_logs/php/savor_admin',
	//日志配置
	'LOG_RECORD'            =>  false,   // 默认不记录日志
	'LOG_TYPE'              =>  'File', // 日志记录类型 默认为文件方式
	'LOG_LEVEL'             =>  'EMERG,ALERT,CRIT,ERR',// 允许记录的日志级别
	'LOG_EXCEPTION_RECORD'  =>  false,    // 是否记录异常信息日志

    //加载自定义配置
    'LOAD_EXT_CONFIG' => 'interface,error',
    
    //日志目录
    'TC_LOG_PATH'           =>  '/tmp/savorapilog/',//上线需创建目录
    'TC_IMAGE_PATH'         =>  '/opt/web/tmpimg/',
	
	//缓存前缀
	'CACHE_PREFIX'			=>	'NC:savorAPI:',
    //报错页面配置	
    /*'TMPL_ACTION_ERROR'     => 'Public:prompt', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   => 'Public:prompt', // 默认成功跳转对应的模板文件
    'TMPL_TRACE_FILE'       => APP_PATH.'/Admin/View/Public/404.html',    // 页面Trace的模板文件
    'TMPL_EXCEPTION_FILE'   => APP_PATH.'/Admin/View/Public/404.html',    // 异常页面的模板文件
	*/
	'LANG_SWITCH_ON' => true,   // 开启语言包功能
	'LANG_AUTO_DETECT' => false, // 自动侦测语言 开启多语言功能后有效
	'DEFAULT_LANG' => 'zh-cn', // 自动侦测语言 开启多语言功能后有效
	//开发配置
	'SECRET_KEY' => 'w&-ld0n!',//解密接口数据key
	'USER_SECRET_KEY'=>'#@%$&#&!&@!@*!*#',   //用户解密接口数据
    'QRCODE_SECRET_KEY' => 'sw&a-lvd0onr!',//解密接口数据key
	'SIGN_KEY'				=> 'savor4321abcd1234',
	'COMMENT_MD5_KEY'       =>  '#F4$)68!KaMtc^',
    'PWDPRE'                =>'SAVOR@&^2017^2030&*^',
    
	'ORIGINAL_CATCH_TIME'   => '3600',
	//列表缓存时间
	'LIST_CATCH_TIME'       => '7200',
    'OSS_FORSCREEN_ADDR_PATH'=>'forscreen/resource',
	'HOST_NAME'             => 'http://'.$_SERVER['HTTP_HOST'],
	'CLIENT_NAME_ARR'=> array('android'=>3,'ios'=>4),
    'HOTEL_CLIENT_NAME_ARR'=>array('android'=>5,'ios'=>6),
    'OPTION_CLIENT_NAME_ARR'=>array('android'=>7,'ios'=>8),
    'KNOWLEDGE_CLIENT_NAME_ARR'=>array('android'=>9,'ios'=>10),
	'SUBSCONTRACT_CLIENT_NAME_ARR'=>array('android'=>11,'ios'=>12),
	'DOWLOAD_SOURCE_ARR'=>array('office'=>1,'qrcode'=>2,'usershare'=>3,'scan'=>4,'waiter'=>5),
	'DOWNLOAD_HOTEL_INFO_TYPE'=>array('ads'=>1,'adv'=>2,'pro'=>3,'vod'=>4,'logo'=>5,'load'=>6),
	'CONFIG_VOLUME'=>array('system_ad_volume'=>'广告音量','system_pro_screen_volume'=>'投屏音量','system_demand_video_volume'=>'点播音量','system_tv_volume'=>'电视音量','system_for_screen_volume'=>'夏新电视投屏音量',
    'box_carousel_volume'=>'机顶盒轮播音量','box_pro_demand_volume'=>'机顶盒公司节目点播音量','box_content_demand_volume'=>'机顶盒用户内容点播音量','box_video_froscreen_volume'=>'机顶盒视频投屏音量','box_img_froscreen_volume'=>'机顶盒图片投屏音量','box_tv_volume'=>'机顶盒电视音量',
    'tv_carousel_volume'=>'电视轮播音量','tv_pro_demand_volume'=>'电视公司节目点播音量','tv_content_demand_volume'=>'电视用户内容点播音量','tv_video_froscreen_volume'=>'电视视频投屏音量','tv_img_froscreen_volume'=>'电视图片投屏音量',
    ),
    'CONFIG_VOLUME_VAL' => array(
        'system_ad_volume'=>60,
        'system_pro_screen_volume'=>100,
        'system_for_screen_volume'=>10,
        'system_demand_video_volume'=>90,
        'system_tv_volume'=>100,
        'system_tv_volume'=>100,
        'system_switch_time'=>30,
        'box_carousel_volume'=>30,'box_pro_demand_volume'=>40,'box_content_demand_volume'=>40,'box_video_froscreen_volume'=>60,'box_img_froscreen_volume'=>60,'box_img_froscreen_volume'=>60,'box_tv_volume'=>60,
        'tv_carousel_volume'=>6,'tv_pro_demand_volume'=>6,'tv_content_demand_volume'=>6,'tv_video_froscreen_volume'=>12,'tv_img_froscreen_volume'=>12,
    ),
    'ROOM_TYPE'=> array(1=>'包间',2=>'大厅',3=>'等候区'),
    'ALL_LOTTERY_NUMBER' => 5,
    //热点投屏小程序配置
	'SMALLAPP_CHECK_CODE'=>'smallapp:checkcode:',
    'SMALLAPP_FORSCREEN_ADS'=>'smallapp:forscreen:ads:',
    'SMALLAPP_LIFE_ADS'=>'smallapp:life:ads:',
    'SMALLAPP_STORESALE_ADS'=>'smallapp:storesale:ads:',


    'PAYLOGS_PATH'  =>  str_replace('Application/', 'paylogs/', APP_PATH),//支付回调日志目录
    'DADALOGS_PATH'  =>  str_replace('Application/', 'dadalogs/', APP_PATH),//达达快递回调日志目录
    'RESOURCE_TYPEINFO'=>array(
        'mp4'=>1,
        'mov'=>1,
        'jpg'=>2,
        'png'=>2,
        'gif'=>2,
        'jpeg'=>2,
        'bmp'=>2,
    ),
    'HOTEL_KEY' => array(
		'1'=>'重点',
		'2'=>'非重点',
	),
	'HOTEL_LEVEL' => array(
		'3'=>'3A',
		'4'=>'4A',
		'5'=>'5A',
		'6'=>'6A',
	),
	'STATE_REASON' => array(
		'1'=>'正常',
		'2'=>'倒闭',
		'3'=>'装修',
		'4'=>'淘汰',
		'5'=>'放假',
		'6'=>'易主',
		'7'=>'终止合作',
		'8'=>'问题沟通中',
	),
	'HOTEL_STATE' => array(
		'1'=>'正常',
		'2'=>'冻结',
		'3'=>'报损',
	),
    'BOX_STATE'=>array(
        '1'=>'正常',
        '2'=>'冻结',
        '3'=>'报损',
    ),
	'HOTEL_BOX_TYPE' => array(
		'1'=>'一代单机版',
		'2'=>'二代网络版',
		'3'=>'二代5G版',
		'4'=>'二代单机版',
		'5'=>'三代单机版',
		'6'=>'三代网络版',
		'7'=>'互联网电视机',
	),
    'HEART_HOTEL_BOX_TYPE'=>array(
      '2'=>'二代网络版',
      '3'=>'二代5G版',
      '6'=>'三代网络版',
      '7'=>'互联网电视机',
    ),
    
	'HOTEL_DAMAGE_CONFIG' => array(
		'1'=>'电源适配器',
		'2'=>'SD卡损坏',
		'3'=>'HDMI线',
	    '4'=>'信号源错误',
	    '5'=>'5G路由器',
	    '6'=>'遥控器',
	    '7'=>'红外遥控头',
	    '8'=>'机顶盒',
	    '9'=>'小平台',
	    '10'=>'酒楼WIFI',
	    '11'=>'酒楼电视机',
	    '12'=>'未开机',
	    '13'=>'其它',
		'14'=>'SD卡已满',
	),
	'HOTEL_STANDALONE_CONFIG' => array(
		'1'=>'机顶盒坏',
		'2'=>'信号源错误',
		'3'=>'盒子配件故障',
		'4'=>'酒楼配件故障',
		'5'=>'电视机坏',
		'6'=>'盒子系统时间错误',
		'7'=>'线乱',
		'8'=>'天线被拔',
		'9'=>'天线坏',
		'10'=>'无包间',
		'11'=>'无电视',
		'12'=>'无机顶盒',
		'13'=>'无酒楼',
		'14'=>'酒楼装修中',
		'15'=>'死机',
	    '16'=>'其它',
	),

	'SMS_CONFIG' => array(
		'accountsid'=>'6a929755afeded257916ca68518ec1c3',
		'token'     =>'66edd50a46c882a7f4231186c44416d8',
		'appid'     =>'a982fdb55a2441899f2eaa64640477c0',
		'daily_login_templateid'=>'178978',
	    'dinner_login_templateid'=>'238349',
	    'option_repair_done_templateid'=>'322916',
	    'activity_goods_addorder_templateid'=>'489211',
	    'activity_goods_collection_templateid'=>'489216',
	    'send_invoice_addr_templateid'=>'511294',
        'activity_goods_send_salemanager'=>'510315',
	),
    'ALIYUN_SMS_CONFIG' => array(
        'send_invoice_addr_templateid'=>'SMS_176935152',
        'activity_goods_send_salemanager'=>'SMS_176527162',
        'activity_goods_send_salemanager_nolink'=>'SMS_177547510',
        'wx_money_not_enough_templateid'=>'SMS_177256437',
        'dish_send_salemanager'=>'SMS_183267690',
        'dish_send_buyer'=>'SMS_185811967',
        'dish_send_cartsbuyer'=>'SMS_185811876',
        'send_register_merchant'=>'SMS_183762008',
        'send_login_confirm'=>'SMS_176660167',
        'send_login_merchant'=>'SMS_194920254',
        'public_audit_templateid'=>'SMS_216374893',
        'send_laimao_order_templateid'=>'SMS_218284968',
        'send_laimao_orderpay_templateid'=>'SMS_218725485',
        'send_tastewine_user_templateid'=>'SMS_227256415',
        'send_tastewine_sponsor_templateid'=>'SMS_227251496',
        'send_groupbuy_user_templateid'=>'SMS_229638185',
        'send_groupbuy_saleuser_templateid'=>'SMS_229648099',
        'send_invitation_to_user'=>'SMS_241067478',
        'send_invitation_to_user_has_mobile'=>'SMS_247680644',
        'send_invitation_to_user_link'=>'SMS_267410076',
        'send_invitation_to_user_has_mobile_link'=>'SMS_267035538',
    ),
    'WEIXIN_MONEY_NOTICE'=>array(13910825534,13811966726),
	'ONLINE_CONTENT_HOST' => 'http://admin.littlehotspot.com/',
    'OPTION_USER_SKILL_ARR' => array(
        '1'=>'信息检测',
        '8'=>'网络改造',
        '2'=>'安装验收',
        '4'=>'维修',
    ),
    'OPTION_USER_ROLE_ARR' => ARRAY(
        '1'=>'发布者',
        '2'=>'指派者',
        '3'=>'执行者',
        '4'=>'查看',
        '5'=>'外包',
        '6'=>'巡检员',
    ),
    'TASK_EMERGE_ARR'=>array('2'=>'紧急','3'=>'正常'),
    'TASK_STATE_ARR'=>array('1'=>'待指派',2=>'待处理',4=>'已完成',5=>'拒绝'),
    'MAX_ADS_LOCATION_NUMS'=>50,
	'CONSUME_ABILITY' => ARRAY(
		'1'=>'100及以下',
		'2'=>'200',
		'3'=>'300',
		'4'=>'400',
		'5'=>'500',
		'6'=>'600',
		'7'=>'700',
		'8'=>'800',
		'9'=>'900',
		'10'=>'1000',
		'11'=>'1500',
		'12'=>'2000及以上',
	),
    'PROGRAM_ADS_MENU_NUM' =>'program_ads_menu_num',
    'PROGRAM_ADS_CACHE_PRE'=>'program_ads_',
    'PROGRAM_PRO_CACHE_PRE'=>'program_pro_',
    'PROGRAM_ADV_CACHE_PRE'=>'program_adv_',
    'SMALL_ROOM_LIST'     =>'small_room_list_',
    'SMALL_HOTEL_INFO'    =>'small_hotel_info_',
    'SYSTEM_CONFIG'       =>'system_config',
    'SMALL_BOX_LIST'      =>'small_box_list_',
    'SMALL_TV_LIST'       =>'small_tv_list_',
    'HOTEL_BOX_STATE_LIST'=>'hotel_box_state_list_' ,  
    'SAPP_SCRREN'         =>'smallapp:forscreen',  //小程序用户投屏图片
    'SAPP_UPRES_FORSCREEN'=>'smallapp:upresouce',
    'SAPP_UPDOWN_FORSCREEN'=>'smallapp:boxupdown:',
    'SAPP_QRCODE'=>'smallapp:qrcode:',
    'SAPP_FILE_FORSCREEN'=>'smallapp:fileforscreen',
    'SAPP_FILE_DOWNLOAD'=>'smallapp:filedownload',
    'SAPP_WANT_GAME'      =>'smallapp:wantgame', //想要点开小程序互动游戏
    'SAPP_PLAY_GAME'      =>'smallapp:playgame',
    'SAPP_SUNCODE_LOG'    =>'smallapp:suncodelog:', //电视显示小程序码时长日志
    'SAPP_SCRREN_SHARE'   =>'smallapp:public:forscreen:',
    'SAPP_SCRREN_PUBLICDATA'   =>'smallapp:public:forscreendata:',
    'SAPP_HISTORY_SCREEN' =>'smallapp:history:forscreen:',
    'SAPP_FORSCREEN_NUMS' =>'smallapp:interact:nums:',
    'SAPP_PAGEVIEW_LOG'   =>'smallap:pageview:log:',
    'SAPP_CALL_NETY_CMD'=>'call-mini-program',
    'SAPP_BOX_FORSCREEN_NET'=>'smallapp:net:forscreen:',
    'SAPP_CALL_CLIMBTREE'=>'smallapp:callclimbtree:',
    'SAPP_CALL_CLIMBTREE_LOGOUT'=>'smallapp:callclimbtree:logout:',
    'SAPP_REDPACKET'=>'smallapp:redpacket:',
    'SAPP_ORDER_GIFT'=>'smallapp:ordergift:',
    'SAPP_BIRTHDAYDEMAND'=>'smallapp:birthdaydemand',
    'SAPP_REDPACKET_JX'=>'smallapp:redpacket:jx',//抢红包页面精选内容
    'SAPP_FIND_INDEX_RAND'=>'smallapp:find:index:rand:',
    'SAPP_FORMID'=>'smallapp:formid:',
    'SAPP_GUIDE_PROMPT'=>'smallapp:guideprompt:',
    'SAPP_INITDATA'=>'smallapp:initdata:',
    'SAPP_FORSCREENTRACK'=>'smallapp:trackforscreen:',
    'SAPP_SHOPDATA'=>'smallapp:shopdata:',
    'SAPP_EXCHANGE'=>'smallapp:exchange:',
    'VSMALL_PREFIX'=>'vsmall:',
    'BOX_TPMEDIA'=>'box:tpmedia:',
    'SAPP_SALE'=>'smallappsale:',
    'SAPP_OPS'=>'smallappops:',
    'SAPP_DATA'=>'smallappdata:',
    'SAPP_SALE_ACTIVITYGOODS_PROGRAM'=>'smallappsale:activitygoodsprogram',
    'SAPP_SALE_INVITE_QRCODE'=>'smallappsale:inviteqrcode:',
    'SAPP_SALE_OPGOODS_INTEGRAL'=>'smallappsale:opgoodsintegral:',
    'SAPP_SALE_ACTIVITY_PROMOTE'=>'smallappsale:activitypromote:',
    'SAPP_SALE_COMMENT_PROMOTE'=>'smallappsale:commentpromote:',
    'SAPP_SALE_WELCOME_RESOURCE'=>'smallappsale:welcomeresource',
    'SAPP_SALE_TASK_SENDNUM'=>'smallappsale:tasksendnum:',
    'SAPP_SALE_INVITATION_JUMP_URL'=>'smallappsale:invitejumpurl:',
    'SMALLAPP_HOTEL_RELATION'=>'smallapp:hotelrelation:',
    'SAPP_SELECTCONTENT_PROGRAM'=>'smallapp:selectcontent:program',
    'SAPP_ANNUALMEETING_PROGRAM'=>'smallapp:annualmeeting:program:',
    'SAPP_SHOP_PROGRAM'=>'smallapp:shopprogram',
    'SAPP_SELECTCONTENT_CONTENT'=>'smallapp:selectcontent:content',
    'SAPP_SELECTCONTENT_PUSH'=>'smallapp:selectcontent:wxpush',
    'SAPP_FIND_CONTENT'=>'smallapp:findcontent',
    'SAPP_FIND_CONTENTNEW'=>'smallapp:findcontentnew',
    'SAPP_FIND_PUBLICIDS'=>'smallapp:publicids',
    'SAPP_FIND_TOP'=>'smallapp:findtop',
    'SAPP_HAS_FIND'=>'smallapp:hasfind:',
    'SAPP_OPTIMIZE_PROGRAM'=>'smallapp:optimize:program',
    'SAPP_FIND_PROGRAM'=>'smallapp:findprogram',
    'SAPP_SIMPLE_UPLOAD_RESOUCE'=>'smallapp:simple:upload:',
    'SAPP_SIMPLE_UPLOAD_PLAYTIME'=>'smallapp:simple:uploadplaytime:',
    'SMALLAPP_DAY_QRCDE'     =>'smallapp:day:qrcode:',
    'SMALLAPP_LOTTERY'     =>'smallapp:activity:lottery',
    'SAPP_HOTPLAYDEMAND'=>'smallapp:hotplaydemand',
    'SAPP_SCAN_BOX_CODE'=>'smallapp:scanboxcode',
    'SAPP_HOTPLAY_PRONUM'=>'smallapp:hotplaypronum',
    'SAPP_PUBLIC_AUDITNUM'=>'smallapp:public:auditnum:',
    'SAPP_LOTTERY_TASK'=>'smallapp:lotterytask:',
    'SAPP_HELPIMAGE'=>'smallapp:helpimage:',
    'SAPP_CANCEL_FORSCREEN'=>'smallapp:cancelforscreen:',
    'SAPP_NEARBYHOTEL'=>'smallapp:nearbyhotel:',
    'SAPP_LUCKYLOTTERY_POSITION'=>'smallapp:luckylottery:position:',
    'SAPP_LUCKYLOTTERY_USERQUEUE'=>'smallapp:luckylottery:userqueue:',
    'SAPP_LUCKYLOTTERY_SENDCOMMON'=>'smallapp:luckylottery:sendcommon:',
    'SAPP_LUCKYLOTTERY_WINUSER'=>'smallapp:luckylottery:winuser:',
    'SAPP_LUCKYLOTTERY_PRIZEUSER'=>'smallapp:luckylottery:prizeuser:',
    'SAPP_PRIZEPOOL'=>'smallapp:prizepool:',
    'SAPP_PRIZEPOOL_MONEYQUEUE'=>'smallapp:prizepool:moneyqueue:',
    'SAPP_VIP_LEVEL_COUPON'=>'smallapp:viplevelcoupon:',
    'SAPP_SENDSMS'=>'smallapp:sendsms:',
    'FINANCE_HOTELGOODS_PRICE'=>'finance:hotelgoods:price',//酒楼售卖酒商品结算价
    'FINANCE_HOTELSTOCK'=>'finance:hotelstock',
    'FINANCE_GOODSSTOCK'=>'finance:goodsstock',

    'BOX_LANHOTEL_DOWNLOAD'=>'lanhotel:download:',
    'BOX_LANHOTEL_DOWNLOADQUEUE'=>'lanhotel:queuedownload:',
    'BOX_LANHOTEL_DOWNLOAD_FAIL'=>'lanhotel:faildownload:',

    'UMENBAI_API_CONFIG' => array(
        'API_URL'=>'http://msg.umeng.com/api/send',
        'boxclient'=>array(
            'android_appkey'=>'58576b54677baa3b41000809',
            'android_master_secret'=>'v6fr959wpmczeayq34utymxcm7fizufu',
            //'ios_appkey'=>'59b1260a734be41803000022',
            //'ios_master_secret' =>'wgyklqy5uu8dacj9yartpic9xmpkezs4',
        ),
        'optionclient'=>array(
            'android_appkey'=>'59acb7f0f29d98425d000cfa',
            'android_master_secret'=>'75h0agzaqlibje6t2rtph4uuuocjyfse',
            'ios_appkey'=>'59b1260a734be41803000022',
            'ios_master_secret' =>'wgyklqy5uu8dacj9yartpic9xmpkezs4',
        ),
     ),
    'AFTER_APP'=>array(
        0=>"go_app",
        1=>"go_url",
        2=>"go_activity",
        3=>"go_custom",
    ),
    'RTB_TAG_PORTRAYAL_PERCENT'=>'0.3',
    'RTB_ADS_CONFIG_ARR'=>array(
        'minMacNum'=>3,
        'maxAdsNum'=>18,
        'minLineNum'=>5,
        'hotel_meal_time'=>array('lunch_time'=>array('start_time'=>"11:00:00",'end_time'=>"15:00:00"),
                                 'dinner_time'=>array('start_time'=>"17:00:00",'end_time'=>"23:00:00"),   
        )
    ),
    'HEART_LOSS_HOURS'=>'48',
    'NET_REPORT_KEY'=>'net_report_',
    'SMALL_PROGRAM_LIST_KEY'=>'small_program_list_',

	'DEVICE_TYPE' => array(
		'1'=>'小平台',
		'2'=>'机顶盒',
		'3'=>'android',
		'4'=>'ios',
		'5'=>'餐厅端_android',
		'6'=>'餐厅端_ios',
		'7'=>'运维端_android',
		'8'=>'运维端_ios',
		'9'=>'运维-单机版_android',
		'10'=>'运维-单机版_ios',
	),
	'MEMORY_CONDITION' => array(
		'1'=>'内存卡损坏，请及时处理',
		'2'=>'内存卡存储空间不足，请及时处理',
	),
    'SDK_ERROR_REPORT_TIME'=>'10',
    'SAPP_FORSCREEN_VERSION_CODE'=>'2018081404',

    'REDPACKET_GETNUM' => 1,
    'SMALLAPP_REDPACKET_BLESS'=>array(
        1=>'生日快乐',2=>'happy birthday',3=>'福如东海，寿比南山',4=>'大吉大利，今晚吃鸡',
        5=>'貌美如花，人见人夸',6=>'大鹏一日同风起，扶摇直上九万里',7=>'身体健康！茁壮成长！',8=>'新婚快乐，百年好合！'
    ),
    'SMALLAPP_BARRAGES'=>array('生日快乐','happy birthday','祝你生日快乐!愿健康、快乐永远和你相伴!',
        '祝生日快乐！请接受我迟到的祝福。','支支灿烂的烛光，岁岁生日的幸福，幸运的你，明天会更好',
        '愿你生命中的愿望都能得到实现!生日快乐!','祝你天天健康愉快，愿你一切愿望都能实现。',
        '一句问候，一声祝福，一切如愿，一生幸福，一世平安。祝生日快乐!',
        '祝你生日快乐!愿生日带给你的欢乐中蕴涵着一切美好!'
    ),
    'SMALLAPP_TYPE_BARRAGES'=>array(
        6=>array('祝你前程似锦，万事顺心！','愿你仗剑天涯，笑傲人生！','春风得意马蹄疾，一日看尽长安花！','乘风破浪会有时 直挂云帆济沧海！',
            '海阔凭鱼跃，天高任鸟飞！','仰天大笑出门去，我辈岂是蓬蒿人！'),
        7=>array(
            '祝你健康成长，快乐幸福，将来 一定事业有成。','祝你身体健康，平平安安！','祝孩子快乐成长，前途无量！','待长成之时必成栋梁之才！',
            '祝宝宝聪明伶俐，活泼可爱！','祝孩子聪明健康，茁壮成长！'
        ),
        8=>array(
            '祝新人永浴爱河，白头偕老！','祝你们相亲相爱，永结同心！','敬祝婚姻幸福家美满，幸福快乐永相伴！','遥祝相亲相爱到永远，海枯石烂心不变！',
            '愿你们百年好合永结同心！','祝相爱年年岁岁，相知岁岁年年！'
        ),
    ),
    'WX_UBLACKLIST'=>array(
        'o9GS-4qaJwiz9uJz2wvVU45eP5-Y','o9GS-4u6wf_l-YQ2jB31juYUfW6c',
        'o9GS-4g6xM3jhCWUUPnvK5a4sysI','o9GS-4t61F_qSPmwEaAtd9v6f6DY','o9GS-4reX0MCJbXvGamZghvmPk6U',
        'o9GS-4ny76ss08PN9CzrVCDz1ans','o9GS-4qrx2qR_5e0TqpEVCmgC_h8','o9GS-4icfJEZSX8_qDs6pB_nD30o',
        'o9GS-4lGQpR1jntGIX06o5zCakzw','o9GS-4lFWll8oqpXx_k95VIB-RmM','o9GS-4oGSdRGYiNZZ4oKQ9PBm_TI',
    ),
    'COMMENT_CACSI'=>array(
        '1'=>array('name'=>'很糟糕','title'=>'很糟糕，不太满意','desc'=>'本次饭局很糟糕，还需要改善','tv_tips'=>'感谢您的评价！很抱歉本次饭局没有让您满意，我们将针对您提出的意见进行改善。',
            'images'=>array('/images/icon/1_select.png','/images/icon/1_no_select.png'),
            'label'=>array('1001'=>array('id'=>1001,'name'=>'服务不好'),
                            '1002'=>array('id'=>1002,'name'=>'菜品不好'),
                            '1003'=>array('id'=>1003,'name'=>'环境不好'),
                )
        ),
        '2'=>array('name'=>'一般般','title'=>'一般般，还需要改善','desc'=>'本次饭局一般般，还需要改善','tv_tips'=>'感谢您的评价！您的评价是我们前进的动力。',
            'images'=>array('/images/icon/2_select.png','/images/icon/2_no_select.png'),
            'label'=>array('2001'=>array('id'=>2001,'name'=>'服务一般'),
                '2002'=>array('id'=>2002,'name'=>'菜品一般'),
                '2003'=>array('id'=>2003,'name'=>'环境一般'),
            )
        ),
        '3'=>array('name'=>'太赞了','title'=>'太赞了，十分满意','desc'=>'本次饭局太赞了，十分满意','tv_tips'=>'感谢您的评价！您的满意是我们不懈的追求。',
            'images'=>array('/images/icon/3_select.png','/images/icon/3_no_select.png'),
            'label'=>array('3001'=>array('id'=>3001,'name'=>'服务很好'),
                '3002'=>array('id'=>3002,'name'=>'菜品很好'),
                '3003'=>array('id'=>3003,'name'=>'环境很好'),
            )
        ),
    ),

    'SMALLAPP_REDPACKET_SEND_RANGE'=>array(
//       1=>'全网餐厅电视',
       2=>'当前餐厅所有电视',3=>'当前包间电视',
     ),
    'VIRTUAL_SMALL_SEND_MESSAGE_TYPE'=>array('1'=>'hotel','2'=>'room','3'=>'box','4'=>'tv','5'=>'volume','6'=>'programmenu',
        '7'=>'promotionalvideo','8'=>'adsa','9'=>'adsb','10'=>'adsc','11'=>'demand','12'=>'recommendation',
        '13'=>'apk','14'=>'loading','15'=>'logo','20'=>'bonustomoney'),
    'SMALLAPP_ERWEI_CODE_TYPES'=>array('8'=>'小程序二维码','12'=>'大二维码（节目）','13'=>'小程序呼二维码','15'=>'大二维码（新节目）',
        '29'=>'渠道投屏码','30'=>'投屏帮助视频码','31'=>'活动霸王菜','33'=>'手机公众号二维码','38'=>'系统抽奖活动'),
//    type 1:小码2:大码(节目)3:手机小程序呼码5:大码（新节目）6:极简版7:主干版桌牌码8:小程序二维码9:极简版节目大码
//     10:极简版大码11:极简版呼玛12:大二维码（节目）13:小程序呼二维码 15:大二维码（新节目）16：极简版二维码19:极简版节目大二维码
//     20:极简版大二维码21:极简版呼二维码22购物二维码 23销售二维码 24菜品商家 25单个菜品 26海报分销售卖商品 27 商城商家 28商城商品大屏购买
//     29推广渠道投屏码 30投屏帮助视频 31活动霸王菜 32商城商品点播大屏购买 33手机公众号二维码 34分享文件二维码 35活动抽奖 36运营扫码抢红包 37本地生活店铺二维码 38系统抽奖活动 39聚划算活动
//     40极简版二维码连接WiFi 41销售人员发起品鉴酒活动二维码 42销售人员发起抽奖活动二维码 43团购商品销售二维码 44年会参会签到 45售卖抽奖活动 46幸运抽奖(销售端) 47酒楼活动包间二维码 48酒楼活动编号二维码
//     49幸运抽奖核销售卖 50销售端任务邀请会员  101是扑克二维码 102是风扇二维码

    'SMALLAPP_JJ_ERWEI_CODE_TYPES'=>array('16'=>'极简版二维码','19'=>'极简版节目大二维码','20'=>'极简版大二维码','21'=>'极简版呼二维码','40'=>'极简版二维码连接WiFi'),
    'SAPP_FILE_FORSCREEN_TYPES'=>array(
        'xls'=>1,'xlsx'=>1,'csv'=>1,
        'pptx'=>2,'ppt'=>2,
        'doc'=>2,'wps'=>2,'docx'=>2,
        'pdf'=>2,'rtf'=>2,'txt'=>2
    ),
    'SAPP_FILE_FORSCREEN_PLAY_TIMES'=>array(
        array('id'=>1,'name'=>'1分钟','value'=>60,'is_select'=>0),
        array('id'=>3,'name'=>'3分钟','value'=>180,'is_select'=>1),
        array('id'=>5,'name'=>'5分钟','value'=>300,'is_select'=>0),
    ),
    'SAPP_FILE_FORSCREEN_IMAGES'=>array(
        'xls'=>'excel.png','xlsx'=>'excel.png','csv'=>'excel.png',
        'pptx'=>'ppt.png','ppt'=>'ppt.png',
        'doc'=>'doc.png','wps'=>'doc.png','docx'=>'doc.png',
        'pdf'=>'pdf.png','rtf'=>'rtf.png','txt'=>'txt.png'
    ),

    'SHARE_FILE_TYPES'=>array('doc','docx','xls','xlsx','ppt','pptx','pdf'),
    'FEAST_TIME'=>array('lunch'=>array('11:30','14:30'),'dinner'=>array('18:00','21:00')),
    'HASH_IDS_KEY'=>'Q1t80oXSKl',
    'HASH_IDS_KEY_ADMIN'=>'Q1xsCaoby2o',
    'SMALLAPP_JJ_UPLOAD_SIZE'=>'10485760',
    'SHORT_URLS' => array(
        'BOX_QR'=>'http://rd0.cn/p?s=',
        'SIMPLE_BOX_QR'=>'http://rd0.cn/e?s=',
        'SIMPLE_BOX_QRCODE'=>'http://rd0.cn/e?j=',
        'SALE_BOX_QR'=>'http://rd0.cn/ag?g=',
        'SALE_INVITE_QR'=>'http://rd0.cn/sale?p=',
        'SALE_DISH_QR'=>'http://rd0.cn/d?p=',
        'SALE_DISHMERCHANT_QR'=>'http://rd0.cn/dm?p=',
        'SALE_SHOP_GOODS_QR'=>'http://rd0.cn/sg?p=',
        'SALE_SHOP_MERCHANT_QR'=>'http://rd0.cn/sm?p=',
        'SHARE_FILE_QR'=>'http://rd0.cn/sf?p=',
    ),
    'INTEGRAL_TYPES'=>array(
        1=>'开机',
        2=>'互动',
        3=>'销售',
        4=>'兑换',
        5=>'退回',
        6=>'活动促销',
        7=>'评价奖励',
        8=>'评价补贴',
        9=>'分配',
        10=>'领取品鉴',
        11=>'完成品鉴',
        12=>'领取抽奖',
        13=>'完成抽奖',
        14=>'完成销售任务',
        15=>'邀请函发给客人',
//        16=>'邀请函客人扩散',
        17=>'售酒奖励',
        18=>'邀请新会员',
        19=>'会员购买奖励',
        20=>'广告点播',
        21=>'奖励',
        22=>'品鉴酒',
        23=>'物料回收',
        24=>'酒水盘点',
        25=>'开瓶奖励',
        26=>'单品激励',
        27=>'阶梯激励',
    ),
    'SALE_DATE'=>'2019-08',
    'PK_TYPE'=>2,//1走线上原来逻辑 2走新的支付方式
    'service_list'=>array('tv_forscreen'=>'电视投屏','room_signin'=>'包间签到','pro_play'=>'循环播放',
        'activity_pop'=>'活动促销','hotel_activity'=>'餐厅活动','integral_manage'=>'积分收益',
        'integral_shop'=>'积分兑换','goods_manage'=>'活动商品管理','staff_manage'=>'员工管理',
        'task_manage'=>'任务管理'
    ),
    'exchange_tips'=>'%s的“%s”成功兑换了%d元现金',
    'comment_tips'=>'%s包间产生了一条新的评价！',
    'reward_tips'=>'%s包间产生了一条新的打赏！',
    'PAY_TYPES'=>array(
        '10'=>array('id'=>10,'name'=>'微信支付','icon'=>''),
        '20'=>array('id'=>20,'name'=>'线下支付','icon'=>''),
    ),
    'DELIVERY_TYPES'=>array(
        '1'=>array('id'=>1,'name'=>'外卖配送'),
        '2'=>array('id'=>2,'name'=>'到店自取'),
    ),
    'ORDER_STATUS'=>array(
        '1'=>'待处理',
        '2'=>'已完成',
        '10'=>'已下单',
        '11'=>'支付失败',
        '12'=>'支付成功',
        '13'=>'待商家确认',
        '14'=>'待骑手接单',
        '15'=>'待取货',
        '16'=>'配送中',
        '17'=>'已完成',
        '18'=>'商家取消',
        '19'=>'用户取消',
        '51'=>'待处理',
        '52'=>'待发货',
        '53'=>'已派送',
        '61'=>'赠送中',
        '62'=>'已过期',
        '63'=>'获赠',
        '71'=>'转赠中',
    ),
    'ACTIVITY_STATUS'=>array(
        '0'=>'待开始',
        '1'=>'进行中',
        '2'=>'已结束',
        '3'=>'已取消',
    ),
    'REWARD_MONEY_LIST'=>array(
        10001=>array('id'=>10001,'name'=>'¥1','price'=>1,'image'=>'WeChat/resource/reward/kuangquanshui.png'),
        10002=>array('id'=>10002,'name'=>'¥2','price'=>2,'image'=>'WeChat/resource/reward/jitui.png'),
        10003=>array('id'=>10003,'name'=>'¥5','price'=>5,'image'=>'WeChat/resource/reward/hanbao.png'),
        10004=>array('id'=>10004,'name'=>'¥10','price'=>10,'image'=>'WeChat/resource/reward/hongbao.png'),
    ),
    'MESSAGETYPE_LIST'=>array(
        1=>array('type'=>1,'name'=>'赞','image'=>'WeChat/resource/zan.png'),
        2=>array('type'=>2,'name'=>'内容审核','image'=>'WeChat/resource/shenhe.png'),
        3=>array('type'=>3,'name'=>'系统通知','image'=>'WeChat/resource/xiaoxi.png'),
        4=>array('type'=>4,'name'=>'红包领取通知','image'=>'WeChat/resource/hongbao.png'),
        5=>array('type'=>5,'name'=>'购买成功','image'=>'WeChat/resource/goumai.png'),
        6=>array('type'=>6,'name'=>'您的订单已发货','image'=>'WeChat/resource/fahuo.png'),
    ),

    'QUALITY_TYPES'=>array(
        1=>array('name'=>'标清','value'=>'?x-oss-process=image/quality,q_40'),
        2=>array('name'=>'高清','value'=>'?x-oss-process=image/quality,q_80'),
        3=>array('name'=>'原图','value'=>''),
    ),

    'MAP_ORDER_STATUS'=>array(
        '1'=>3,//3普通订单 4分销订单
        '2'=>4,
        '3'=>4,
    ),
    'GIFT_MESSAGE'=>'这份我最爱的礼物，送给与我同行的同路人，愿你万事顺遂～',
    'OFFICIAL_ACCOUNT_ARTICLE_URL'=>'https://mp.weixin.qq.com/s/39w1-K53lT9McBSR59BJcQ',
    'COLLECT_FORSCREEN_OPENIDS' =>array('ofYZG4zmrApmvRSfzeA_mN-pHv2E'=>'郑伟','ofYZG42whtWOvSELbvxvnXHbzty8'=>'黄勇',
        'ofYZG49N0yz-cCTTgfPPEoL1F7l4'=>'鲍强强','ofYZG4xt_03ADzTTtf4QIrA1lt_c'=>'甘顺山','ofYZG43prBncpYjkYq-XaIWRlj6o'=>'吴琳',
        'ofYZG4yZJHaV2h3lJHG5wOB9MzxE'=>'张英涛','ofYZG4-TBnXlWMTGx6afsUrjzXgk'=>'李智','ofYZG4zXTCn52wUjHPeOoNZHFKwo'=>'毕超',
        'ofYZG4ySsM6GN8bF9bw6iWlS9a44'=>'王习宗','ofYZG4-geGG-WO3drWsAZetCghSc'=>'何永锐','ofYZG43zZMAYXbuOiQxIqGfz25aM'=>'玉洁',
        'ofYZG43DyszPj-qwvP5ZutMCGC_c'=>'欧懿','ofYZG4zTOtj9RCaLmDXI0qfY-I34'=>'熊静怡','ofYZG45GWNg7k9CLVHoRdUqQVPJ4'=>'黎晓欣',
        'ofYZG47NzXqDD0lumUkq-it6_mXY'=>'王伟明',
        ),
    'STOCK_MANAGER'=>array(
//        'o9GS-4reX0MCJbXvGamZghvmPk6U'=>'郑伟',
//        'o9GS-4oZfWgjT0lySkJskdlflNrw'=>'黄勇',
//        'o9GS-4g6xM3jhCWUUPnvK5a4sysI'=>'张英涛',
//        'o9GS-4icfJEZSX8_qDs6pB_nD30o'=>'李昭',
//        'o9GS-4t61F_qSPmwEaAtd9v6f6DY'=>'刘斌',
//        'o9GS-4oGSdRGYiNZZ4oKQ9PBm_TI'=>'李丛',
        'o9GS-4iGZE9olTzXTMjon8xDyRpo'=>'黄勇',
        'o9GS-4iinyutBsN73FJFjdZC3rWg'=>'赵翠燕',
        'o9GS-4kpg8khL72nVZKDsgn0ioDM'=>'陈灵玉',
        'o9GS-4mouXnk_WhBAL-Zhsg0YbOE'=>'余穗筠',
        'o9GS-4mTCZvkRCDRnkg77QqohMI4'=>'胡子凤',
        'o9GS-4ix2RgA41QyHjMqAljsvbvY'=>'黎晓欣',
    ),
    'STOCK_RECORD_TYPE'=>array('1'=>'入库','2'=>'出库','3'=>'拆箱','4'=>'领取','5'=>'验收','6'=>'报损','7'=>'核销'),
    'STOCK_REASON'=>array(
        '1'=>array('id'=>1,'name'=>'售卖'),
        '2'=>array('id'=>2,'name'=>'品鉴酒'),
        '3'=>array('id'=>3,'name'=>'活动')
    ),
    'SELL_NOTIN_HOTEL_GOODS'=>array(7,8,27,28,30,42,43,44,45,49,50,54,56,57,58,59,60,62,63,64,66,67),
    'SELL_NOTIN_HOTEL_BRANDS'=>array(10,11,13,14,15,18),
    'FORSCREEN_GUIDE_IMAGE_SWITCH'=>0,
    'LOTTERY_TIMEOUT'=>300,
    'DEFAULT_PAGE_SHOW_TIME'=>2*1000,
    'MEAL_TIME'=>array('lunch'=>array('10:00','15:00'),'dinner'=>array('17:00','23:59')),
    'ACTIVITY_MEAL_TIME'=>array('lunch'=>array('10:00','15:59'),'dinner'=>array('16:00','23:59')),
    'PERSON_PRICE'=>array(
        '1'=>array('id'=>1,'name'=>'100以下','min'=>0,'max'=>100),
        '2'=>array('id'=>2,'name'=>'100-200','min'=>100,'max'=>200),
        '3'=>array('id'=>3,'name'=>'200以上','min'=>200,'max'=>1000000)
    ),
    'REDPACKET_OPERATIONERID'=>42996,
    'PUBLIC_AUDIT_STATUS'=>array(
        '0'=>'删除',
        '1'=>'待审核',
        '2'=>'审核通过',
        '3'=>'审核不通过',
    ),
    'PUBLIC_AUDIT_MOBILE'=>array(
        18910538751
    ),
    'GROUP_BUY_USER_MOBILE'=>array(
        13811966726,
        13810910309,
        13911344326,
    ),
    'EXCLUDE_VIDEOS'=>array(17614,19533,26965),
    'SCENCE_ADV_BIRTHDAY'=>array(
        '1'=>array('ads_img_url'=>'','countdown'=>0),
        '2'=>array('ads_img_url'=>'','countdown'=>0),
        '3'=>array('ads_img_url'=>'','countdown'=>0),
        '4'=>array('ads_img_url'=>'','countdown'=>0),
        '5'=>array('ads_img_url'=>'','countdown'=>0),
        '6'=>array('ads_img_url'=>'','countdown'=>0),
    ),
    'SCENCE_ADV_FILEFORSCREEN'=>array('ads_img_url'=>'','countdown'=>0),
    'SCENCE_ADV_FILEFORSCREEN_NUM'=>0,
    'MEETING_SIGNIN_IMG'=>'WeChat/resource/meeting_img.png',
    'MEETING_SIGNIN_PLAY_TIMES'=>array(
        array('id'=>10,'name'=>'10分钟','value'=>10,'is_select'=>1),
        array('id'=>20,'name'=>'20分钟','value'=>20,'is_select'=>0),
        array('id'=>40,'name'=>'40分钟','value'=>40,'is_select'=>0),
        array('id'=>60,'name'=>'60分钟','value'=>60,'is_select'=>0),
    ),
    'all_forscreen_actions'=>array(
        '1'=>'图片滑动',
        '2'=>'视频投屏',
        '3'=>'切片视频投屏',
        '4'=>'图片投屏',
        '5'=>'节目点播',
        '6'=>'广告跳转',
        '7'=>'点击互动游戏',
        '8'=>'重投',
        '9'=>'手机呼大码',
        '11'=>'发现点播图片',
        '12'=>'发现点播视频',
        '13'=>'点播商城商品',
        '14'=>'点播banner商城商品',
        '16'=>'热播内容点播图片',
        '17'=>'热播内容点播视频',
        '21'=>'查看点播视频',
        '22'=>'查看发现视频',
        '30'=>'投屏文件',
        '31'=>'投屏文件图片',
        '42'=>'用户端投欢迎词',
        '51'=>'扫码抢霸王餐',
        '52'=>'评论',
        '53'=>'点击banner抢霸王餐',
        '54'=>'扫码抽奖',
        '56'=>'生日点播',
        '57'=>'星座点播',
        '58'=>'销售端酒品广告',
        '101'=>'h5互动游戏',
        '120'=>'发红包',
        '121'=>'扫码抢红包'
    ),
    'BONUS_OPERATION_INFO'=>array('nickName'=>'热点酒水超市','avatarUrl'=>'http://oss.littlehotspot.com/media/resource/btCfRRhHkn.jpg'),
    'BONUS_QUESTIONNAIRE'=>array(
        '1'=>array('id'=>1,'name'=>'洋河','image'=>'WeChat/resource/1.jpg'),
        '2'=>array('id'=>2,'name'=>'郎酒','image'=>'WeChat/resource/2.jpg'),
        '3'=>array('id'=>3,'name'=>'剑南春','image'=>'WeChat/resource/3.jpg'),
        '4'=>array('id'=>4,'name'=>'习酒','image'=>'WeChat/resource/4.jpg'),
        '5'=>array('id'=>5,'name'=>'汾酒','image'=>'WeChat/resource/5.jpg'),
        '6'=>array('id'=>6,'name'=>'泸州老窖','image'=>'WeChat/resource/6.jpg'),
        '7'=>array('id'=>7,'name'=>'五粮液','image'=>'WeChat/resource/7.jpg'),
        '8'=>array('id'=>8,'name'=>'茅台','image'=>'WeChat/resource/8.jpg'),
        '9'=>array('id'=>9,'name'=>'其他','image'=>'WeChat/resource/9.jpg'),
//        '10'=>array('id'=>10,'name'=>'百年糊涂','image'=>''),
//        '11'=>array('id'=>11,'name'=>'古井贡','image'=>''),
//        '12'=>array('id'=>12,'name'=>'口子窖','image'=>''),
//        '13'=>array('id'=>13,'name'=>'天之蓝','image'=>''),
//        '14'=>array('id'=>14,'name'=>'梦之蓝','image'=>''),
//        '15'=>array('id'=>15,'name'=>'海之蓝','image'=>''),
    ),
//    'RD_WIFI_HOTEL'=>array('46'=>'辉哥火锅（8号公馆店）','47'=>'孔乙己尚宴(8号公馆店)','48'=>'江仙雅居(东直门店)','55'=>'1949-全鸭季(金宝街店)','85'=>'花家怡园(王府井店)','222'=>'新荣记(银泰店)','395'=>'江山享味酒家(白云万达店)','420'=>'山东老家（科韵分店）','436'=>'江仙雅居（苏州桥店）','787'=>'湘江宴','1007'=>'峨嵋酒家广渠路店','1023'=>'新渝城·川菜·火锅(区庄店)','1038'=>'广东道至正餐厅(保利·时光里店)','1047'=>'四季小馆·北京菜·烤鸭(越秀公园店)','1051'=>'陇上荟·老兰州味道(天河公园店)','1059'=>'御彩酒家(东风东路店)','1064'=>'海门渔港(棠下店)','1065'=>'新泰乐·宴会厅(江南店)','1077'=>'新粤新疆菜(佳兆业广场店)'),
    'TEST_HOTEL'=>'7,482,504,791,508,844,845,597,201,493,883,53,598,1366,1337,925',
    'OPS_STAT_DATE'=>'2022-11-02',
    'RD_WIFI_HOTEL'=>array(),
    'LAIMAO_SALE_HOTELS'=>array('7'=>'永峰写字楼(正式)','28'=>'经易家肴(西单店)','81'=>'海棠居(长椿街店)','787'=>'湘江宴','1007'=>'峨嵋酒家广渠路店'),
    'LAIMAO_SECKILL_GOODS_ID'=>622,
    'RD_TEST_HOTEL' =>
        array (
//            7 =>
//                array (
//                    'hotel_id' => 7,
//                    'hotel_name' => '永峰写字楼(正式)	',
//                    'short_name' => '永峰写字楼',
//                ),
        ),


    'MEMBER_INTEGRAL'=>array(
        'invite_vip_reward_saler'=>5600,
        'buy_reward_saler'=>400,
    ),
    'INVITATION_HOTEL'=>array(
        //'share_img'=>'media/resource/jZW8m7QNNn.jpg',
        'share_img'=>'media/resource/NFs23wnwQa.jpg',
        'bg_img'=>'media/resource/kJxFWZJEDG.jpeg',
        'themeColor'=>'rgb(193,147,166)',
        'themeContrastColor'=>'rgb(255, 255, 255)',
        'painColor'=>'rgb(16, 16, 16)',
        'weakColor'=>'rgb(153, 153, 153)',
        'is_open_sellplatform'=>1,
    ),
    'INVITATION_TASK_INTEGRAL'=>array(
        'send_guest'=>0,
        'guest_to_user'=>0,
        'max_limit'=>66600000,
    ),
    'INVITATION_THEME'=>array(
        '1'=>array(
            'id'=>1,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-1.jpg',
            'themeColor'=>'rgb(255,224,180)',
            'themeContrastColor'=>'rgb(112,13,17)',
            'painColor'=>'rgb(255,224,180)',
            'weakColor'=>'rgb(255,224,180)',
            'is_display'=>0,
        ),
        '2'=>array(
            'id'=>2,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-2.jpg',
            'themeColor'=>'rgb(218,88,142)',
            'themeContrastColor'=>'rgb(255,255,255)',
            'painColor'=>'rgb(218,88,142)',
            'weakColor'=>'rgb(218,88,142)',
            'is_display'=>0,
        ),
        '3'=>array(
            'id'=>3,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-3.jpg',
            'themeColor'=>'rgb(0,71,84)',
            'themeContrastColor'=>'rgb(255,255,255)',
            'painColor'=>'rgb(0,71,84)',
            'weakColor'=>'rgb(0,71,84)',
            'is_display'=>0,
        ),
        '4'=>array(
            'id'=>4,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-4.jpg',
            'themeColor'=>'rgb(255,224,180)',
            'themeContrastColor'=>'rgb(13,42,68)',
            'painColor'=>'rgb(255,224,180)',
            'weakColor'=>'rgb(255,224,180)',
            'is_display'=>0,
        ),
        '5'=>array(
            'id'=>5,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-5.jpg',
            'backgroundColor'=>'#0A2B59',
            'themeColor'=>'#F3D8A1',
            'themeColor2'=>'#F3D8A1',
            'buttonBackgroundColor'=>'#FFE5A3',
            'themeContrastColor'=>'#314865',
            'painColor'=>'#FFFFFF',
            'weakColor'=>'#F4DBA8',
            'is_display'=>1,
        ),
        '6'=>array(
            'id'=>6,
            'bg_img'=>'WeChat/MiniProgram/images/invitation/invitation-6.jpg',
            'backgroundColor'=>'#EEE8DE',
            'themeColor'=>'#171717',
            'themeColor2'=>'#834D01',
            'buttonBackgroundColor'=>'#D5C6BB',
            'themeContrastColor'=>'#171717',
            'painColor'=>'#834D01',
            'weakColor'=>'#304862',
            'is_display'=>1,
        ),
    ),
	'SECKILL_GOODS_CONFIG'=>ARRAY(
		'left_pop_wind' =>1,
		'marquee'       =>1,
	),

    'HOTELQRCODE_JUMP_PAGE'=>array(
        '1'=>array('id'=>1,'name'=>'及时抽奖页面','page'=>'/games/pages/activity/turn_lottery','type'=>'navigate'),
        '2'=>array('id'=>2,'name'=>'本地有售酒水列表页面','page'=>'/mall/pages/wine/index','type'=>'navigate'),
        '3'=>array('id'=>3,'name'=>'小程序首页','page'=>'/pages/index/index','type'=>'tabbar'),
        '4'=>array('id'=>4,'name'=>'邀请会员注册','page'=>'/pages/index/index','type'=>'switchTab'),
    ),
    'QRCODE_MIN_NUM'=>500000,
    'STOCK_AUDIT_STATUS'=>array('1'=>'待审核','2'=>'通过审核','3'=>'审核不通过','4'=>'待补充核销资料'),
    'STOCK_RECYCLE_STATUS'=>array('1'=>'未提交申请','2'=>'审核通过','3'=>'无法收回','4'=>'无需回收','5'=>'审核中','6'=>'审核不通过','7'=>'过期未回收'),
    'STOCK_RECYCLE_ALL_STATUS'=>array('1'=>'未提交申请','2'=>'审核通过','3'=>'无法收回','4'=>'无需回收','5'=>'审核中','6'=>'审核不通过','7'=>'过期未回收'),
    'STOCK_PAY_TYPES'=>array('10'=>'未收款','1'=>'已收款','2'=>'部分收款'),
    'STOCK_SALE_TYPES'=>array('1'=>'餐厅售卖','4'=>'线上团购'),
    'APPROVAL_STATUS'=>array('1'=>'待审批','2'=>'审批不通过','3'=>'待库管接收','4'=>'待出库','5'=>'待运维接收','6'=>'待送货','7'=>'已领取',
        '8'=>'未上传收货单','9'=>'已送达','10'=>'派单待接收','11'=>'待回收瓶盖','12'=>'已回收瓶盖'
    ),
    'ACTIVITY_AWARD_STATUS'=>array('1'=>'发放正常积分','2'=>'发放冻结积分','3'=>'待发放积分'),

    'BBS_CATEGORY'=>array(
        '240301'=>array('id'=>240301,'name'=>'吐槽','icon'=>'WeChat/resource/bbs_icon/tucao.png'),
        '240302'=>array('id'=>240302,'name'=>'分享','icon'=>'WeChat/resource/bbs_icon/fenxiang.png'),
        '240303'=>array('id'=>240303,'name'=>'建议','icon'=>'WeChat/resource/bbs_icon/jianyi.png'),
        '240304'=>array('id'=>240304,'name'=>'举报','icon'=>'WeChat/resource/bbs_icon/jubao.png'),
        '240305'=>array('id'=>240305,'name'=>'其他','icon'=>'WeChat/resource/bbs_icon/qit.png'),
    ),
    'OPS_TASK_SOURCES'=>array(
        '1'=>array('id'=>1,'name'=>'渠道部'),
        '2'=>array('id'=>2,'name'=>'运维部'),
        '3'=>array('id'=>3,'name'=>'系统任务'),
    ),
    'ALL_ALLOT_TYPE'=>array('1'=>'公司配送','2'=>'自己配送'),
    'DATA_GOODS_IDS'=>array(56,62),
    'VIP_3_BUY_WINDE_NUM'=>1200,
    'STAT_TASK_TYPES'=>array(
//        '26'=>'会员邀请(奖券任务)',
        '25'=>'广告点播',
        '6'=>'邀请函',
    ),
    'STAFF_LEVEL'=>array(
        '1'=>'店长',
        '2'=>'销售经理',
        '3'=>'服务员',
    ),
    'UP_STOCK_PRICE_RANGE'=>1.1,
    'SELLWINE_ACTIVITY' =>array(
        'url'=>'media/resource/iWzxhCKCDN.mp4',
        'filename'=>'iWzxhCKCDN.mp4',
        'md5'=>'785c9ed61d2118b46a18028d772d2955',
        'is_offline'=>0,
        'offline_filename'=>''
    ),
    'SELL_TASTE_WINE_ACTIVITY' =>array(
        'url'=>'media/resource/CDeRfecCWd.mp4',
        'filename'=>'CDeRfecCWd.mp4',
        'md5'=>'f4c6c52a45788d7d4349ab55dcb58153',
        'is_offline'=>0,
        'offline_filename'=>''
    ),
    'INIT_WX_USER'=>array(
      'avatarUrl'=>'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132',
      'nickName'=>'微信用户'
    ),
    'SIGN_PROCESS'=>array(
        '1'=>array('id'=>1,'name'=>'陌拜完成','percent'=>0),
        '2'=>array('id'=>2,'name'=>'见到店长或其他关键人','percent'=>10),
        '3'=>array('id'=>3,'name'=>'宣讲演示完成','percent'=>20),
        '4'=>array('id'=>4,'name'=>'对方对平价酒模式无异议','percent'=>30),
        '5'=>array('id'=>5,'name'=>'对方了解并认可价格体系','percent'=>40),
        '6'=>array('id'=>6,'name'=>'认可7天回款','percent'=>70),
        '7'=>array('id'=>7,'name'=>'签合同','percent'=>90),
        '8'=>array('id'=>8,'name'=>'进酒并和驻店人员交接','percent'=>100),
    ),


    'STATSIGNDATA_DESC'=>array(
        '1.权限划分：全国、城市、个人；',
        '2.拜访餐厅数：筛选范围的时间段内总共拜访的餐厅数量，去重；',
        '3.拜访新餐厅数：筛选范围的时间段内有多少餐厅是第一次拜访（同一餐厅不同渠道人员多次拜访，只记一次）；',
        '4.拜访总次数：筛选范围的时间段内总共拜访的次数；',
        '5.单次拜访时长：筛选范围的时间段内平均每次拜访消耗多长时间；',
        '6.拜访频次：平均每人每天拜访几家餐厅（自然日内重复拜访同一家餐厅不去重，保留一位小数）；',
        '7.成功签约数：筛选范围的时间段内总共成功签约多少家餐厅（到第7步）；',
        '8.单店拜访频次：签约餐厅平均几天拜访一次。（如：A餐厅45天内拜访3次成功签约；B餐厅6天内拜访2次签约；（45+6）/（3+2）=10.2，则单店拜访频次为10.2天/次）；',
        '9.签约成功周期：第一次拜访与进店签约之间的间隔为单个店的签约周期。筛选范围的时间段内签约的餐厅，计算平均的签约周期',
    ),
    'REPORT_DESC'=>array(
        '1.财务报告时间段：可以选择自然月（可选多月，不可跨年），选择后下方自动计算出对应的财务报告；',
        '2.销售额：时间段内，销售商品的售价总和；',
        '3.净增利润：时间段内，销售酒水获得了多少利润（售酒毛利+售酒金额*0.1）；',
        '4.销量：时间段内，餐厅内共核销了多少瓶酒水；',
        '5.当前库存：查询财务报告时，餐厅内库存多少瓶酒水；',
        '6.库存价值：查询财务报告时，餐厅内库存酒水合计价值（结算价）；',
        '7.欠款：查询财务报告时，当前餐厅总欠款数数额；',
        '8.超期欠款：查询财务报告时，当前餐厅欠款超过7天的部分；',
    ),
    'STATEMENTS_DESC'=>array(
        '1.对账单时间段：可以选择自然日（可选多月，不可跨年），选择后下方自动计算出对应的对账单；',
        '2.全部销售酒水、未付款酒水：指的是下方售卖明细中的展示什么明细内容，与上方的统计数据无关；',
        '3.销量：时间段内，餐厅内共核销了多少瓶酒水；',
        '4.应结算金额：时间段内，核销酒水的结算价总和；',
        '5.已结算金额：时间段内，已经结算的金额总和；',
        '6.当前库存：查询财务报告时，餐厅内库存多少瓶酒水；',
        '7.库存价值：查询财务报告时，餐厅内库存酒水合计价值（结算价）；',
        '8.欠款：查询财务报告时，当前餐厅总欠款数数额；',
        '9.超期欠款：查询财务报告时，当前餐厅欠款超过7天的部分；',
        '10.当选择时段内售酒明细数据大于30条时，生成的图片不再显示售酒明细，同时提供了售酒明细excel文档下载功能。'
    ),
    'CONTENT_DEFAULT'=>array(
        '1、包间自带酒情况；',
        '2、在餐厅交的店长，你对他的过去、现在和未来都有哪些了解？',
        '3、在餐厅交经理（销售经理、采购经理、财务经理等），你对他的过去、现在和未来都有哪些了解？',
        '4、其他值得公司关注的事情：'
    ),
    'CONTENT_DEFAULT_RESIDENT'=>array(
      '1、你去的目的是什么？' ,
      '2、去店里干了什么？' ,
      '3、是否解决？',
    ),
    'CRM_TASK_TYPES'=> array(
        '1'=>'店长销售端开通',
        '2'=>'配送酒水',
        '3'=>'回款',
        '4'=>'超期欠款',
        '5'=>'完善包间信息',
        '6'=>'推广邀请函',
        '7'=>'推广盘点',
        '8'=>'推广点播',
        '9'=>'开机任务',
        '10'=>'企业微信加餐厅人员',
        '11'=>'自定义'
    ),
    'TASKDATA_DESC'=>array(
        '1.餐厅数：个人权限范围内所有的售酒餐厅；',
        '2.发布任务数：个人权限范围内所有的售酒餐厅在时间段内一共发布了多少任务；',
        '3.处理任务数：发布的任务一共有多少点击了“已处理”操作；',
        '4.完成任务数：点击“已处理”的任务，通过审核的数量；',
        '5.超期未完成任务数：截止到当前时间，所有超期未完成的任务总数（包含跨月的任务，进行时间筛选时数量不变，盘点任务不计入其中）',
        '6.拒绝任务数：驻店人员拒绝任务并且城市经理同意拒绝的任务数；'
    ),
    'HOTEL_TASK_ORDER_DESC'=>array(
        '1.应收账款：超期欠款>应收账款，按照金额从大到小排序；',
        '2.新进酒餐厅排序：最近两周内进酒的餐厅，按照进酒时间，由近及远进行排序；',
        '3.未动销时间：按照未动销时间由长到短进行排序；',
        '4.动销下滑：按照本月销量与上月销量进行对比，下降百分比高的餐厅优先排序；'
    ),
    'TASK_HELP_DESC'=>array(
        1=>array(
            '1、任务解读：每家餐厅至少给三人开通销售端（包含店长）',
            '2、具体任务操作：点击任务处理后，5天内完成开通，完成后不再派发'
        ),
        2=>array(
            '1、任务解读：当餐厅库存酒水低于2瓶，则派发配送酒水任务',
            '2、具体任务操作：点击任务处理后，2天内完成补酒',
            '3、如遇不合作餐厅或准备撤店餐厅，可拒绝配酒任务，则本月不再派发'
        ),  
        3=>array(
            
        ),  
        4=>array(
            '1、任务解读：当餐厅应收账款超过7天，则派发超期欠款任务',
            '2、具体任务操作：点击任务处理后，3天内收回欠款，则完成任务'
        ),  
        5=>array(
            '1、任务解读：新餐厅会派发完善包间信息任务，每个餐厅派发一次',
            '2、具体任务操作：点击任务处理后，5天内通过运维端完善该餐厅包间信息'
        ),  
        6=>array(
            '1、任务解读：当过去7天内，该餐厅邀请函的任务完成率低于10%，则会派发任务',
            '2、具体任务操作：点击任务处理后，7天内让餐厅经理发邀请函；发送次数计算：包间数*1.6*10%=该餐厅每天应发送的次数'
        ),  
        7=>array(
            '1、任务解读：每周二后台下发盘点任务，需让城市经理按时完成盘点任务',
            '2、具体任务操作：如出现盘点任务，点击处理后，5天内让餐厅经理完成本店的酒水盘点'
        ),  
        8=>array(
            '1、任务解读：针对有电视的餐厅，当过去7天内，该餐厅点播任务完成率低于50%，则会派发任务',
            '2、具体任务操作：点击处理任务后，7天内督促餐厅经理，每天至少点播一次，则完成点播任务'
        ),  
        9=>array(
            '1、任务解读：针对有电视的餐厅，当餐厅某台设备失联7天以上，则派发开机任务',
            '2、具体任务操作：点击处理任务后，7天内完成该设备开机',
        ),  
        10=>array(
            '1、任务解读：每家餐厅用企微添加至少3个人的微信（店长、销售经理、财务接口人）',
            '2、具体任务操作：点击处理任务，上传企微截图',
            '3、任务处理时间：1天，1天内未上传截图，第二天会再次派发该任务。如处理完成，则不再派发'
        ),  
        11=>array(
            
        ),  
        12=>array(
            '1、任务解读：新餐厅派发给餐厅制作并摆放标准宣传物料（KT板、桌卡、酒单）任务，拍照上传',
            '2、具体任务操作：点击任务处理后，7天内制作、摆放好相应物料，拍照上传，即完成任务'
        )
        
        
    ),
);
