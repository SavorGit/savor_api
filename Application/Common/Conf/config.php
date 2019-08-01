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
	                                 'Smallsale'), //模块配置

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
	/*'SMALLAPP_CONFIG'=>array('cache_key'=>'smallapp_token','appid'=>'wxfdf0346934bb672f','appsecret'=>'b9b93aef8d6609722596e35385ff05c5'),
    'SMALLAPP_SIMPLE_CONFIG'=>array('cache_key'=>'smallapp_simple_token','appid'=>'wxe59b125a3f073901','appsecret'=>'423b1a65b84eacd4c13ab791b3a7edb1'),
    'SMALLAPP_JIJIAN_CONFIG'=>array('cache_key'=>'smallapp_jijian_token','appid'=>'wx7883a4327329a67c','appsecret'=>'da423755cc5b734db6f7a8dd7563a581'),
    'SMALLAPP_DINNER_CONFIG'=>array('cache_key'=>'smallapp_dinner_token','appid'=>'wxc395eb4b44563af1','appsecret'=>'12bdfa28a3a1e842e965034a0a277ed3'),*/
    'SMALLAPP_CHECK_CODE'=>'smallapp:checkcode:',
    'SMALLAPP_FORSCREEN_ADS'=>'smallapp:forscreen:ads:',
    'WX_FWH_CONFIG'=>array('appid'=>'wx7036d73746ff1a14','appsecret'=>'8b658fc90d7105d5cf66cb2193edb7d4'),
    'PAY_WEIXIN_CONFIG'=>array('partner'=>'1513051731','key'=>'A13df3gg45d4fg32F223fgg33GG51112'),
    'PAYLOGS_PATH'  =>  str_replace('Application/', 'paylogs/', APP_PATH),//支付回调日志目录
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
	),
    'HEART_HOTEL_BOX_TYPE'=>array(
      '2'=>'二代网络版',
      '3'=>'二代5G版',
      '6'=>'三代网络版',
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
	),

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
    'SAPP_BIRTHDAYDEMAND'=>'smallapp:birthdaydemand',
    'SAPP_REDPACKET_JX'=>'smallapp:redpacket:jx',//抢红包页面精选内容
    'SAPP_FIND_INDEX_RAND'=>'smallapp:find:index:rand:',
    'VSMALL_PREFIX'=>'vsmall:',
    'BOX_TPMEDIA'=>'box:tpmedia:',
    'SAPP_SALE'=>'smallappsale:',
    'SAPP_SALE_ACTIVITYGOODS_PROGRAM'=>'smallappsale:activitygoodsprogram',

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
        5=>'貌美如花，人见人夸',6=>'健康成长，天天快乐',
    ),
    'SMALLAPP_BARRAGES'=>array('生日快乐','happy birthday','祝你生日快乐!愿健康、快乐永远和你相伴!',
        '祝生日快乐！请接受我迟到的祝福。','支支灿烂的烛光，岁岁生日的幸福，幸运的你，明天会更好',
        '愿你生命中的愿望都能得到实现!生日快乐!','祝你天天健康愉快，愿你一切愿望都能实现。',
        '一句问候，一声祝福，一切如愿，一生幸福，一世平安。祝生日快乐!',
        '祝你生日快乐!愿生日带给你的欢乐中蕴涵着一切美好!'
    ),

    'SMALLAPP_REDPACKET_SEND_RANGE'=>array(
       1=>'全网餐厅电视',2=>'当前餐厅所有电视',3=>'当前包间电视',
     ),
    'VIRTUAL_SMALL_SEND_MESSAGE_TYPE'=>array('1'=>'hotel','2'=>'room','3'=>'box','4'=>'tv','5'=>'volume','6'=>'programmenu',
        '7'=>'promotionalvideo','8'=>'adsa','9'=>'adsb','10'=>'adsc','11'=>'demand','12'=>'recommendation',
        '13'=>'apk','14'=>'loading','15'=>'logo','20'=>'bonustomoney'),
    'SMALLAPP_ERWEI_CODE_TYPES'=>array('8'=>'小程序二维码','12'=>'大二维码（节目）','13'=>'小程序呼二维码','16'=>'大二维码（新节目）'),
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
);
