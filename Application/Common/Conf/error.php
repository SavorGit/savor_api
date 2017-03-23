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
		'10000'=>'success',
	    
		//用户操作报错
		'12001'=>'mobile_illegal',
		'12002'=>'user_not_exist',
		'12003' =>'user_login_err',
	    
	    
	    //记录用手首次使用app
	    '20001'=>'first_use_have_data',
	    '20002'=>'first_use_push_err',
	),
);