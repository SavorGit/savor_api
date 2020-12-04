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
	                                 'Version','Content','Heartbeat','Heartcalcu',
	                                 'Download','Award','Small','Smalls','Screendistance',
	                                 'APP3','Opclient','Dailyknowledge','Tasksubcontract','Opclient11','Dinnerapp',
	                                 'Dinnerapp2','Box','Opclient20','Forscreen','Smallapp','Smallapp21','Netty',
	                                 'Games','Smallappsimple','Smallapp3','Smalldinnerapp','Payment','Smalldinnerapp11',
	                                 'Smallsale','Smallsale14','Smallsale16','Smallapp4','Smallapp43','Smallsale18',
                                     'Smallsale19','Smallapp44','Smallapp45','Smallapp46','Smallsale20',), //模块配置

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
	'CONFIG_VOLUME'=>array('system_ad_volume'=>'广告音量','system_pro_screen_volume'=>'投屏音量','system_demand_video_volume'=>'点播音量','system_tv_volume'=>'电视音量'),
	'ROOM_TYPE'=> array(1=>'包间',2=>'大厅',3=>'等候区'),
    'ALL_LOTTERY_NUMBER' => 5,
    //热点投屏小程序配置
	'SMALLAPP_CHECK_CODE'=>'smallapp:checkcode:',
    'SMALLAPP_FORSCREEN_ADS'=>'smallapp:forscreen:ads:',


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
        'send_login_merchant'=>'SMS_194920254',
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
    'SAPP_WANT_GAME'      =>'smallapp:wantgame', //想要点开小程序互动游戏
    'SAPP_PLAY_GAME'      =>'smallapp:playgame',
    'SAPP_SUNCODE_LOG'    =>'smallapp:suncodelog:', //电视显示小程序码时长日志
    'SAPP_SCRREN_SHARE'   =>'smallapp:public:forscreen:',
    'SAPP_HISTORY_SCREEN' =>'smallapp:history:forscreen:',
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
    'VSMALL_PREFIX'=>'vsmall:',
    'BOX_TPMEDIA'=>'box:tpmedia:',
    'SAPP_SALE'=>'smallappsale:',
    'SAPP_SALE_ACTIVITYGOODS_PROGRAM'=>'smallappsale:activitygoodsprogram',
    'SAPP_SALE_INVITE_QRCODE'=>'smallappsale:inviteqrcode:',
    'SAPP_SALE_OPGOODS_INTEGRAL'=>'smallappsale:opgoodsintegral:',
    'SAPP_SALE_ACTIVITY_PROMOTE'=>'smallappsale:activitypromote:',
    'SAPP_SALE_COMMENT_PROMOTE'=>'smallappsale:commentpromote:',
    'SAPP_SALE_WELCOME_RESOURCE'=>'smallappsale:welcomeresource',
    'SMALLAPP_HOTEL_RELATION'=>'smallapp:hotelrelation:',
    'SAPP_SELECTCONTENT_PROGRAM'=>'smallapp:selectcontent:program',
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
    'SMALLAPP_DAY_QRCDE'     =>'smallapp:day:qrcode:',

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
	'CONFIG_VOLUME_VAL' => array(
		'system_ad_volume'=>60,
		'system_pro_screen_volume'=>100,
		'system_demand_video_volume'=>90,
		'system_tv_volume'=>100,
		'system_tv_volume'=>100,
		'system_switch_time'=>30,
	),

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
    'BAIDU_GEO_KEY'=>'q1pQnjOG28z8xsCaoby2oqLTLaPgelyq',
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
    'WX_UBLACKLIST'=>array('o9GS-4qaJwiz9uJz2wvVU45eP5-Y','o9GS-4u6wf_l-YQ2jB31juYUfW6c'),
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
       1=>'全网餐厅电视',2=>'当前餐厅所有电视',3=>'当前包间电视',
     ),
    'VIRTUAL_SMALL_SEND_MESSAGE_TYPE'=>array('1'=>'hotel','2'=>'room','3'=>'box','4'=>'tv','5'=>'volume','6'=>'programmenu',
        '7'=>'promotionalvideo','8'=>'adsa','9'=>'adsb','10'=>'adsc','11'=>'demand','12'=>'recommendation',
        '13'=>'apk','14'=>'loading','15'=>'logo','20'=>'bonustomoney'),
    'SMALLAPP_ERWEI_CODE_TYPES'=>array('8'=>'小程序二维码','12'=>'大二维码（节目）','13'=>'小程序呼二维码','15'=>'大二维码（新节目）',
        '29'=>'渠道投屏码','30'=>'投屏帮助视频码','31'=>'活动霸王菜'),
//    type 1:小码2:大码(节目)3:手机小程序呼码5:大码（新节目）6:极简版7:主干版桌牌码8:小程序二维码9:极简版节目大码
//     10:极简版大码11:极简版呼玛12:大二维码（节目）13:小程序呼二维码 15:大二维码（新节目）16：极简版二维码19:极简版节目大二维码
//     20:极简版大二维码21:极简版呼二维码22购物二维码 23销售二维码 24菜品商家 25单个菜品 26海报分销售卖商品 27 商城商家 28商城商品大屏购买
//     29推广渠道投屏码 30投屏帮助视频 31活动霸王菜

    'SMALLAPP_JJ_ERWEI_CODE_TYPES'=>array('16'=>'极简版二维码','19'=>'极简版节目大二维码','20'=>'极简版大二维码','21'=>'极简版呼二维码'),
    'SAPP_FILE_FORSCREEN_TYPES'=>array(
        'xls'=>1,'xlsx'=>1,'csv'=>1,
        'pptx'=>2,'ppt'=>2,
        'doc'=>2,'wps'=>2,'docx'=>2,
        'pdf'=>2,'rtf'=>2,'txt'=>2
    ),
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
        ),


);
