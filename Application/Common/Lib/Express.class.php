<?php
namespace Common\Lib;
class Express {

    public $company = array (
        'zhongtong' =>
            array (
                'comcode' => 'zhongtong',
                'name' => '中通快递',
                'type' => 1,
            ),
        'yunda' =>
            array (
                'comcode' => 'yunda',
                'name' => '韵达快递',
                'type' => 1,
            ),
        'yuantong' =>
            array (
                'comcode' => 'yuantong',
                'name' => '圆通速递',
                'type' => 1,
            ),
        'youzhengguonei' =>
            array (
                'comcode' => 'youzhengguonei',
                'name' => '邮政快递包裹',
                'type' => 2,
            ),
        'huitongkuaidi' =>
            array (
                'comcode' => 'huitongkuaidi',
                'name' => '百世快递',
                'type' => 1,
            ),
        'shentong' =>
            array (
                'comcode' => 'shentong',
                'name' => '申通快递',
                'type' => 1,
            ),
        'shunfeng' =>
            array (
                'comcode' => 'shunfeng',
                'name' => '顺丰速运',
                'type' => 3,
            ),
        'jd' =>
            array (
                'comcode' => 'jd',
                'name' => '京东物流',
                'type' => 1,
            ),
        'tiantian' =>
            array (
                'comcode' => 'tiantian',
                'name' => '天天快递',
                'type' => 1,
            ),
        'ems' =>
            array (
                'comcode' => 'ems',
                'name' => 'EMS',
                'type' => 2,
            ),
        'youzhengbk' =>
            array (
                'comcode' => 'youzhengbk',
                'name' => '邮政标准快递',
                'type' => 2,
            ),
        'zhaijisong' =>
            array (
                'comcode' => 'zhaijisong',
                'name' => '宅急送',
                'type' => 1,
            ),
        'debangwuliu' =>
            array (
                'comcode' => 'debangwuliu',
                'name' => '德邦',
                'type' => 1,
            ),
        'debangkuaidi' =>
            array (
                'comcode' => 'debangkuaidi',
                'name' => '德邦快递',
                'type' => 1,
            ),
        'zhongtongkuaiyun' =>
            array (
                'comcode' => 'zhongtongkuaiyun',
                'name' => '中通快运',
                'type' => 1,
            ),
        'youshuwuliu' =>
            array (
                'comcode' => 'youshuwuliu',
                'name' => '优速快递',
                'type' => 1,
            ),
        'yundakuaiyun' =>
            array (
                'comcode' => 'yundakuaiyun',
                'name' => '韵达快运',
                'type' => 1,
            ),
        'baishiwuliu' =>
            array (
                'comcode' => 'baishiwuliu',
                'name' => '百世快运',
                'type' => 1,
            ),
        'wanxiangwuliu' =>
            array (
                'comcode' => 'wanxiangwuliu',
                'name' => '万象物流',
                'type' => 1,
            ),
        'annengwuliu' =>
            array (
                'comcode' => 'annengwuliu',
                'name' => '安能快运',
                'type' => 1,
            ),
        'yuantongkuaiyun' =>
            array (
                'comcode' => 'yuantongkuaiyun',
                'name' => '圆通快运',
                'type' => 1,
            ),
        'suning' =>
            array (
                'comcode' => 'suning',
                'name' => '苏宁物流',
                'type' => 1,
            ),
        'dhl' =>
            array (
                'comcode' => 'dhl',
                'name' => 'DHL-中国件',
                'type' => 3,
            ),
        'emsguoji' =>
            array (
                'comcode' => 'emsguoji',
                'name' => 'EMS-国际件',
                'type' => 2,
            ),
        'ewe' =>
            array (
                'comcode' => 'ewe',
                'name' => 'EWE全球快递',
                'type' => 3,
            ),
        'canpost' =>
            array (
                'comcode' => 'canpost',
                'name' => '加拿大(Canada Post)',
                'type' => 2,
            ),
        'danniao' =>
            array (
                'comcode' => 'danniao',
                'name' => '丹鸟',
                'type' => 1,
            ),
        'jtexpress' =>
            array (
                'comcode' => 'jtexpress',
                'name' => '极兔速递',
                'type' => 1,
            ),
        'yuntrack' =>
            array (
                'comcode' => 'yuntrack',
                'name' => 'YUN TRACK',
                'type' => 3,
            ),
        'youzhengguoji' =>
            array (
                'comcode' => 'youzhengguoji',
                'name' => '国际包裹',
                'type' => 3,
            ),
        'auexpress' =>
            array (
                'comcode' => 'auexpress',
                'name' => '澳邮中国快运',
                'type' => 3,
            ),
        'chinaicip' =>
            array (
                'comcode' => 'chinaicip',
                'name' => '卓志速运',
                'type' => 1,
            ),
        'fedex' =>
            array (
                'comcode' => 'fedex',
                'name' => 'FedEx-国际件',
                'type' => 3,
            ),
        'upsen' =>
            array (
                'comcode' => 'upsen',
                'name' => 'UPS-全球件',
                'type' => 3,
            ),
        'suer' =>
            array (
                'comcode' => 'suer',
                'name' => '速尔快递',
                'type' => 1,
            ),
        'shpost' =>
            array (
                'comcode' => 'shpost',
                'name' => '同城快寄',
                'type' => 1,
            ),
        'tnt' =>
            array (
                'comcode' => 'tnt',
                'name' => 'TNT',
                'type' => 3,
            ),
        'zhongyouwuliu' =>
            array (
                'comcode' => 'zhongyouwuliu',
                'name' => '中邮物流',
                'type' => 1,
            ),
        'dhlen' =>
            array (
                'comcode' => 'dhlen',
                'name' => 'DHL-全球件',
                'type' => 3,
            ),
        'rrs' =>
            array (
                'comcode' => 'rrs',
                'name' => '日日顺物流',
                'type' => 1,
            ),
        'longbanwuliu' =>
            array (
                'comcode' => 'longbanwuliu',
                'name' => '龙邦速递',
                'type' => 1,
            ),
        'yuantongguoji' =>
            array (
                'comcode' => 'yuantongguoji',
                'name' => '圆通国际',
                'type' => 3,
            ),
        'kuayue' =>
            array (
                'comcode' => 'kuayue',
                'name' => '跨越速运',
                'type' => 1,
            ),
        'usps' =>
            array (
                'comcode' => 'usps',
                'name' => 'USPS',
                'type' => 2,
            ),
        'yw56' =>
            array (
                'comcode' => 'yw56',
                'name' => '燕文物流',
                'type' => 1,
            ),
        'rlgaus' =>
            array (
                'comcode' => 'rlgaus',
                'name' => '澳洲飞跃物流',
                'type' => 3,
            ),
        'flyway' =>
            array (
                'comcode' => 'flyway',
                'name' => '程光快递',
                'type' => 1,
            ),
        'zhuanyunsifang' =>
            array (
                'comcode' => 'zhuanyunsifang',
                'name' => '转运四方',
                'type' => 3,
            ),
        'yimidida' =>
            array (
                'comcode' => 'yimidida',
                'name' => '壹米滴答',
                'type' => 1,
            ),
        'qianli' =>
            array (
                'comcode' => 'qianli',
                'name' => '千里速递',
                'type' => 1,
            ),
        'btexpress' =>
            array (
                'comcode' => 'btexpress',
                'name' => '邦泰快运',
                'type' => 1,
            ),
        'zhongtongguoji' =>
            array (
                'comcode' => 'zhongtongguoji',
                'name' => '中通国际',
                'type' => 3,
            ),
        'annto' =>
            array (
                'comcode' => 'annto',
                'name' => '安得物流',
                'type' => 1,
            ),
        'jinguangsudikuaijian' =>
            array (
                'comcode' => 'jinguangsudikuaijian',
                'name' => '京广速递',
                'type' => 1,
            ),
        'nsf' =>
            array (
                'comcode' => 'nsf',
                'name' => '新顺丰（NSF）',
                'type' => 3,
            ),
        'jiuyescm' =>
            array (
                'comcode' => 'jiuyescm',
                'name' => '九曳供应链',
                'type' => 1,
            ),
        'kuaijiesudi' =>
            array (
                'comcode' => 'kuaijiesudi',
                'name' => '快捷速递',
                'type' => 1,
            ),
        'gslhkd' =>
            array (
                'comcode' => 'gslhkd',
                'name' => '联合快递',
                'type' => 1,
            ),
        'meiquick' =>
            array (
                'comcode' => 'meiquick',
                'name' => '美快国际物流',
                'type' => 3,
            ),
        'lianbangkuaidi' =>
            array (
                'comcode' => 'lianbangkuaidi',
                'name' => '联邦快递',
                'type' => 1,
            ),
        'lntjs' =>
            array (
                'comcode' => 'lntjs',
                'name' => '特急送',
                'type' => 1,
            ),
        'lianhaowuliu' =>
            array (
                'comcode' => 'lianhaowuliu',
                'name' => '联昊通',
                'type' => 1,
            ),
        'subida' =>
            array (
                'comcode' => 'subida',
                'name' => '速必达',
                'type' => 1,
            ),
        'disifang' =>
            array (
                'comcode' => 'disifang',
                'name' => '递四方',
                'type' => 1,
            ),
        'emsbg' =>
            array (
                'comcode' => 'emsbg',
                'name' => 'EMS包裹',
                'type' => 2,
            ),
        'quanfengkuaidi' =>
            array (
                'comcode' => 'quanfengkuaidi',
                'name' => '全峰快递',
                'type' => 1,
            ),
        'yuxinwuliu' =>
            array (
                'comcode' => 'yuxinwuliu',
                'name' => '宇鑫物流',
                'type' => 1,
            ),
        'banma' =>
            array (
                'comcode' => 'banma',
                'name' => '斑马物流',
                'type' => 1,
            ),
        'zhimakaimen' =>
            array (
                'comcode' => 'zhimakaimen',
                'name' => '芝麻开门',
                'type' => 1,
            ),
        'auspost' =>
            array (
                'comcode' => 'auspost',
                'name' => '澳大利亚(Australia Post)',
                'type' => 2,
            ),
        'guotongkuaidi' =>
            array (
                'comcode' => 'guotongkuaidi',
                'name' => '国通快递',
                'type' => 1,
            ),
        'ups' =>
            array (
                'comcode' => 'ups',
                'name' => 'UPS',
                'type' => 3,
            ),
        'changjiang' =>
            array (
                'comcode' => 'changjiang',
                'name' => '长江国际速递',
                'type' => 3,
            ),
        'dsukuaidi' =>
            array (
                'comcode' => 'dsukuaidi',
                'name' => 'D速快递',
                'type' => 3,
            ),
        'ftd' =>
            array (
                'comcode' => 'ftd',
                'name' => '富腾达国际货运',
                'type' => 3,
            ),
        'zhonghuan' =>
            array (
                'comcode' => 'zhonghuan',
                'name' => '中环快递',
                'type' => 1,
            ),
        'yunexpress' =>
            array (
                'comcode' => 'yunexpress',
                'name' => '德国云快递',
                'type' => 3,
            ),
        'ubonex' =>
            array (
                'comcode' => 'ubonex',
                'name' => '优邦速运',
                'type' => 1,
            ),
        'japanposten' =>
            array (
                'comcode' => 'japanposten',
                'name' => '日本（Japan Post）',
                'type' => 2,
            ),
        'guangdongyouzhengwuliu' =>
            array (
                'comcode' => 'guangdongyouzhengwuliu',
                'name' => '广东邮政',
                'type' => 2,
            ),
        'sdto' =>
            array (
                'comcode' => 'sdto',
                'name' => '速达通',
                'type' => 1,
            ),
        'transrush' =>
            array (
                'comcode' => 'transrush',
                'name' => 'TransRush',
                'type' => 3,
            ),
        'weitepai' =>
            array (
                'comcode' => 'weitepai',
                'name' => '微特派',
                'type' => 1,
            ),
        'arkexpress' =>
            array (
                'comcode' => 'arkexpress',
                'name' => '方舟速递',
                'type' => 3,
            ),
        'zhongtiewuliu' =>
            array (
                'comcode' => 'zhongtiewuliu',
                'name' => '中铁物流',
                'type' => 1,
            ),
        'chszhonghuanguoji' =>
            array (
                'comcode' => 'chszhonghuanguoji',
                'name' => 'CHS中环国际快递',
                'type' => 3,
            ),
        'xdexpress' =>
            array (
                'comcode' => 'xdexpress',
                'name' => '迅达速递',
                'type' => 1,
            ),
        'tiandihuayu' =>
            array (
                'comcode' => 'tiandihuayu',
                'name' => '天地华宇',
                'type' => 1,
            ),
        'ontrac' =>
            array (
                'comcode' => 'ontrac',
                'name' => 'OnTrac',
                'type' => 3,
            ),
        'dpd' =>
            array (
                'comcode' => 'dpd',
                'name' => 'DPD',
                'type' => 3,
            ),
        'xlobo' =>
            array (
                'comcode' => 'xlobo',
                'name' => 'Xlobo贝海国际',
                'type' => 3,
            ),
        'superb' =>
            array (
                'comcode' => 'superb',
                'name' => 'Superb Grace',
                'type' => 3,
            ),
        'bpost' =>
            array (
                'comcode' => 'bpost',
                'name' => '比利时（Bpost）',
                'type' => 2,
            ),
        'sxjdfreight' =>
            array (
                'comcode' => 'sxjdfreight',
                'name' => '顺心捷达',
                'type' => 1,
            ),
        'wjkwl' =>
            array (
                'comcode' => 'wjkwl',
                'name' => '万家康物流',
                'type' => 1,
            ),
        'xinfengwuliu' =>
            array (
                'comcode' => 'xinfengwuliu',
                'name' => '信丰物流',
                'type' => 1,
            ),
        'dhlde' =>
            array (
                'comcode' => 'dhlde',
                'name' => 'DHL-德国件（DHL Deutschland）',
                'type' => 3,
            ),
        'tntau' =>
            array (
                'comcode' => 'tntau',
                'name' => 'TNT Australia',
                'type' => 3,
            ),
        'wanjiawuliu' =>
            array (
                'comcode' => 'wanjiawuliu',
                'name' => '万家物流',
                'type' => 1,
            ),
        'ztky' =>
            array (
                'comcode' => 'ztky',
                'name' => '中铁快运',
                'type' => 1,
            ),
        'unitedex' =>
            array (
                'comcode' => 'unitedex',
                'name' => '联合速运',
                'type' => 1,
            ),
        'sunjex' =>
            array (
                'comcode' => 'sunjex',
                'name' => '新杰物流',
                'type' => 1,
            ),
        'oneexpress' =>
            array (
                'comcode' => 'oneexpress',
                'name' => '一速递',
                'type' => 1,
            ),
        'adapost' =>
            array (
                'comcode' => 'adapost',
                'name' => '安达速递',
                'type' => 1,
            ),
        'polarexpress' =>
            array (
                'comcode' => 'polarexpress',
                'name' => '极地快递',
                'type' => 1,
            ),
        'coe' =>
            array (
                'comcode' => 'coe',
                'name' => 'COE',
                'type' => 3,
            ),
        'shangqiao56' =>
            array (
                'comcode' => 'shangqiao56',
                'name' => '商桥物流',
                'type' => 1,
            ),
        'qexpress' =>
            array (
                'comcode' => 'qexpress',
                'name' => '易达通快递',
                'type' => 1,
            ),
        'topspeedex' =>
            array (
                'comcode' => 'topspeedex',
                'name' => '中运全速',
                'type' => 1,
            ),
        'crazyexpress' =>
            array (
                'comcode' => 'crazyexpress',
                'name' => '疯狂快递',
                'type' => 1,
            ),
        'jiayunmeiwuliu' =>
            array (
                'comcode' => 'jiayunmeiwuliu',
                'name' => '加运美',
                'type' => 1,
            ),
        'quanyikuaidi' =>
            array (
                'comcode' => 'quanyikuaidi',
                'name' => '全一快递',
                'type' => 1,
            ),
        'hkpost' =>
            array (
                'comcode' => 'hkpost',
                'name' => '中国香港(HongKong Post)',
                'type' => 2,
            ),
        'valueway' =>
            array (
                'comcode' => 'valueway',
                'name' => '美通',
                'type' => 1,
            ),
        'zjstky' =>
            array (
                'comcode' => 'zjstky',
                'name' => '苏通快运',
                'type' => 1,
            ),
        'lijisong' =>
            array (
                'comcode' => 'lijisong',
                'name' => '成都立即送',
                'type' => 1,
            ),
        'ucs' =>
            array (
                'comcode' => 'ucs',
                'name' => '合众速递(UCS）',
                'type' => 3,
            ),
        'savor' =>
            array (
                'comcode' => 'savor',
                'name' => '海信物流',
                'type' => 1,
            ),
        'zhongsukuaidi' =>
            array (
                'comcode' => 'zhongsukuaidi',
                'name' => '中速快递',
                'type' => 1,
            ),
        'ane66' =>
            array (
                'comcode' => 'ane66',
                'name' => '安能快递',
                'type' => 1,
            ),
        'chnexp' =>
            array (
                'comcode' => 'chnexp',
                'name' => '中翼国际物流',
                'type' => 3,
            ),
        'kyue' =>
            array (
                'comcode' => 'kyue',
                'name' => '跨跃国际',
                'type' => 3,
            ),
        'wondersyd' =>
            array (
                'comcode' => 'wondersyd',
                'name' => '中邮速递',
                'type' => 1,
            ),
        'aramex' =>
            array (
                'comcode' => 'aramex',
                'name' => 'Aramex',
                'type' => 3,
            ),
        'tnten' =>
            array (
                'comcode' => 'tnten',
                'name' => 'TNT-全球件',
                'type' => 3,
            ),
        'gxwl' =>
            array (
                'comcode' => 'gxwl',
                'name' => '光线速递',
                'type' => 1,
            ),
        'epanex' =>
            array (
                'comcode' => 'epanex',
                'name' => '泛捷国际速递',
                'type' => 3,
            ),
        'tianma' =>
            array (
                'comcode' => 'tianma',
                'name' => '天马迅达',
                'type' => 1,
            ),
        'shenghuiwuliu' =>
            array (
                'comcode' => 'shenghuiwuliu',
                'name' => '盛辉物流',
                'type' => 1,
            ),
        'aae' =>
            array (
                'comcode' => 'aae',
                'name' => 'AAE-中国件',
                'type' => 3,
            ),
        'spring56' =>
            array (
                'comcode' => 'spring56',
                'name' => '春风物流',
                'type' => 1,
            ),
        'exfresh' =>
            array (
                'comcode' => 'exfresh',
                'name' => '安鲜达',
                'type' => 1,
            ),
        'gooday365' =>
            array (
                'comcode' => 'gooday365',
                'name' => '日日顺智慧物联',
                'type' => 1,
            ),
        'hengluwuliu' =>
            array (
                'comcode' => 'hengluwuliu',
                'name' => '恒路物流',
                'type' => 1,
            ),
        'chinasqk' =>
            array (
                'comcode' => 'chinasqk',
                'name' => 'SQK国际速递',
                'type' => 3,
            ),
        'stoexpress' =>
            array (
                'comcode' => 'stoexpress',
                'name' => '美国申通',
                'type' => 3,
            ),
        'singpost' =>
            array (
                'comcode' => 'singpost',
                'name' => '新加坡小包(Singapore Post)',
                'type' => 2,
            ),
        'hexinexpress' =>
            array (
                'comcode' => 'hexinexpress',
                'name' => '合心速递',
                'type' => 1,
            ),
        'emsen' =>
            array (
                'comcode' => 'emsen',
                'name' => 'EMS-英文',
                'type' => 2,
            ),
        'jingshun' =>
            array (
                'comcode' => 'jingshun',
                'name' => '景顺物流',
                'type' => 1,
            ),
        'globaltracktrace' =>
            array (
                'comcode' => 'globaltracktrace',
                'name' => 'globaltracktrace',
                'type' => 3,
            ),
        'emsinten' =>
            array (
                'comcode' => 'emsinten',
                'name' => 'EMS-国际件-英文',
                'type' => 2,
            ),
        'doortodoor' =>
            array (
                'comcode' => 'doortodoor',
                'name' => 'CJ物流',
                'type' => 3,
            ),
        'huangmajia' =>
            array (
                'comcode' => 'huangmajia',
                'name' => '黄马甲',
                'type' => 1,
            ),
        'uszcn' =>
            array (
                'comcode' => 'uszcn',
                'name' => '转运中国',
                'type' => 3,
            ),
        'meixi' =>
            array (
                'comcode' => 'meixi',
                'name' => '美西快递',
                'type' => 3,
            ),
        'baifudongfang' =>
            array (
                'comcode' => 'baifudongfang',
                'name' => '百福东方',
                'type' => 3,
            ),
        'nanjingshengbang' =>
            array (
                'comcode' => 'nanjingshengbang',
                'name' => '晟邦物流',
                'type' => 1,
            ),
        'tmwexpress' =>
            array (
                'comcode' => 'tmwexpress',
                'name' => '明达国际速递',
                'type' => 3,
            ),
        'ueq' =>
            array (
                'comcode' => 'ueq',
                'name' => 'UEQ快递',
                'type' => 3,
            ),
        'dpexen' =>
            array (
                'comcode' => 'dpexen',
                'name' => 'Toll',
                'type' => 3,
            ),
        'huanqiu' =>
            array (
                'comcode' => 'huanqiu',
                'name' => '环球速运',
                'type' => 3,
            ),
        'ocs' =>
            array (
                'comcode' => 'ocs',
                'name' => 'OCS',
                'type' => 3,
            ),
        'tstexp' =>
            array (
                'comcode' => 'tstexp',
                'name' => 'TST速运通',
                'type' => 3,
            ),
        'zhongtongphone' =>
            array (
                'comcode' => 'zhongtongphone',
                'name' => '中通（带电话）',
                'type' => 1,
            ),
        'newzealand' =>
            array (
                'comcode' => 'newzealand',
                'name' => '新西兰（New Zealand Post）',
                'type' => 2,
            ),
        'lianbangkuaidien' =>
            array (
                'comcode' => 'lianbangkuaidien',
                'name' => '联邦快递-英文',
                'type' => 1,
            ),
        'aotsd' =>
            array (
                'comcode' => 'aotsd',
                'name' => '澳天速运',
                'type' => 3,
            ),
        'datianwuliu' =>
            array (
                'comcode' => 'datianwuliu',
                'name' => '大田物流',
                'type' => 1,
            ),
        'ytkd' =>
            array (
                'comcode' => 'ytkd',
                'name' => '运通中港快递',
                'type' => 1,
            ),
        'yaofeikuaidi' =>
            array (
                'comcode' => 'yaofeikuaidi',
                'name' => '耀飞同城快递',
                'type' => 1,
            ),
        'sfwl' =>
            array (
                'comcode' => 'sfwl',
                'name' => '盛丰物流',
                'type' => 1,
            ),
        'euasia' =>
            array (
                'comcode' => 'euasia',
                'name' => '欧亚专线',
                'type' => 3,
            ),
        'bazirim' =>
            array (
                'comcode' => 'bazirim',
                'name' => '皮牙子快递',
                'type' => 1,
            ),
        'la911' =>
            array (
                'comcode' => 'la911',
                'name' => '鼎润物流',
                'type' => 1,
            ),
        'dpex' =>
            array (
                'comcode' => 'dpex',
                'name' => 'DPEX',
                'type' => 3,
            ),
        'suteng' =>
            array (
                'comcode' => 'suteng',
                'name' => '广东速腾物流',
                'type' => 1,
            ),
        'fastgo' =>
            array (
                'comcode' => 'fastgo',
                'name' => '速派快递(FastGo)',
                'type' => 3,
            ),
        'anxl' =>
            array (
                'comcode' => 'anxl',
                'name' => '安迅物流',
                'type' => 1,
            ),
        'xunsuexpress' =>
            array (
                'comcode' => 'xunsuexpress',
                'name' => '迅速快递',
                'type' => 1,
            ),
        'zhongchuan' =>
            array (
                'comcode' => 'zhongchuan',
                'name' => '众川国际',
                'type' => 3,
            ),
        'hd' =>
            array (
                'comcode' => 'hd',
                'name' => '宏递快运',
                'type' => 1,
            ),
        'hnht56' =>
            array (
                'comcode' => 'hnht56',
                'name' => '鸿泰物流',
                'type' => 1,
            ),
        'fedexcn' =>
            array (
                'comcode' => 'fedexcn',
                'name' => 'Fedex-国际件-中文',
                'type' => 3,
            ),
        'westwing' =>
            array (
                'comcode' => 'westwing',
                'name' => '西翼物流',
                'type' => 1,
            ),
        'yafengsudi' =>
            array (
                'comcode' => 'yafengsudi',
                'name' => '亚风速递',
                'type' => 1,
            ),
        'jiayiwuliu' =>
            array (
                'comcode' => 'jiayiwuliu',
                'name' => '佳怡物流',
                'type' => 1,
            ),
        'synship' =>
            array (
                'comcode' => 'synship',
                'name' => 'SYNSHIP快递',
                'type' => 3,
            ),
        'dekuncn' =>
            array (
                'comcode' => 'dekuncn',
                'name' => '德坤物流',
                'type' => 1,
            ),
        'hrex' =>
            array (
                'comcode' => 'hrex',
                'name' => '锦程快递',
                'type' => 1,
            ),
        'ecmscn' =>
            array (
                'comcode' => 'ecmscn',
                'name' => '易客满',
                'type' => 3,
            ),
        'bflg' =>
            array (
                'comcode' => 'bflg',
                'name' => '上海缤纷物流',
                'type' => 1,
            ),
        'auod' =>
            array (
                'comcode' => 'auod',
                'name' => '澳德物流',
                'type' => 3,
            ),
        'tcat' =>
            array (
                'comcode' => 'tcat',
                'name' => '黑猫宅急便',
                'type' => 3,
            ),
        'guexp' =>
            array (
                'comcode' => 'guexp',
                'name' => '全联速运',
                'type' => 1,
            ),
        'sundarexpress' =>
            array (
                'comcode' => 'sundarexpress',
                'name' => '顺达快递',
                'type' => 1,
            ),
        'sut56' =>
            array (
                'comcode' => 'sut56',
                'name' => '速通物流',
                'type' => 1,
            ),
        'stosolution' =>
            array (
                'comcode' => 'stosolution',
                'name' => '申通国际',
                'type' => 3,
            ),
        'lbex' =>
            array (
                'comcode' => 'lbex',
                'name' => '龙邦物流',
                'type' => 1,
            ),
        'mosuda' =>
            array (
                'comcode' => 'mosuda',
                'name' => '魔速达',
                'type' => 1,
            ),
        'est365' =>
            array (
                'comcode' => 'est365',
                'name' => '东方汇',
                'type' => 1,
            ),
        'efs' =>
            array (
                'comcode' => 'efs',
                'name' => 'EFS Post（平安快递）',
                'type' => 2,
            ),
        'jgwl' =>
            array (
                'comcode' => 'jgwl',
                'name' => '景光物流',
                'type' => 1,
            ),
        'yzswuliu' =>
            array (
                'comcode' => 'yzswuliu',
                'name' => '亚洲顺物流',
                'type' => 3,
            ),
        'zhaijibian' =>
            array (
                'comcode' => 'zhaijibian',
                'name' => '宅急便',
                'type' => 3,
            ),
        'pjbest' =>
            array (
                'comcode' => 'pjbest',
                'name' => '品骏快递',
                'type' => 1,
            ),
        'deutschepost' =>
            array (
                'comcode' => 'deutschepost',
                'name' => '德国(Deutsche Post)',
                'type' => 2,
            ),
        'yunfeng56' =>
            array (
                'comcode' => 'yunfeng56',
                'name' => '韵丰物流',
                'type' => 1,
            ),
        'cht361' =>
            array (
                'comcode' => 'cht361',
                'name' => '诚和通',
                'type' => 1,
            ),
        'cnpex' =>
            array (
                'comcode' => 'cnpex',
                'name' => 'CNPEX中邮快递',
                'type' => 3,
            ),
        'mmlogi' =>
            array (
                'comcode' => 'mmlogi',
                'name' => '猛犸速递',
                'type' => 3,
            ),
        'xynyc' =>
            array (
                'comcode' => 'xynyc',
                'name' => '新元国际',
                'type' => 3,
            ),
        'dhlbenelux' =>
            array (
                'comcode' => 'dhlbenelux',
                'name' => 'DHL Benelux',
                'type' => 3,
            ),
        'qinyuan' =>
            array (
                'comcode' => 'qinyuan',
                'name' => '秦远物流',
                'type' => 1,
            ),
        'runhengfeng' =>
            array (
                'comcode' => 'runhengfeng',
                'name' => '全时速运',
                'type' => 1,
            ),
        'shipgce' =>
            array (
                'comcode' => 'shipgce',
                'name' => '飞洋快递',
                'type' => 3,
            ),
        'pingandatengfei' =>
            array (
                'comcode' => 'pingandatengfei',
                'name' => '平安达腾飞',
                'type' => 1,
            ),
        'haidaibao' =>
            array (
                'comcode' => 'haidaibao',
                'name' => '海带宝',
                'type' => 3,
            ),
        'ukrpostcn' =>
            array (
                'comcode' => 'ukrpostcn',
                'name' => '乌克兰邮政包裹',
                'type' => 2,
            ),
        'fastgoexpress' =>
            array (
                'comcode' => 'fastgoexpress',
                'name' => '速派快递',
                'type' => 1,
            ),
        'tywl99' =>
            array (
                'comcode' => 'tywl99',
                'name' => '天翼物流',
                'type' => 1,
            ),
        'bsht' =>
            array (
                'comcode' => 'bsht',
                'name' => '百事亨通',
                'type' => 1,
            ),
        'ubuy' =>
            array (
                'comcode' => 'ubuy',
                'name' => '德国优拜物流',
                'type' => 3,
            ),
        'feiyuanvipshop' =>
            array (
                'comcode' => 'feiyuanvipshop',
                'name' => '飞远配送',
                'type' => 1,
            ),
        'kejie' =>
            array (
                'comcode' => 'kejie',
                'name' => '科捷物流',
                'type' => 1,
            ),
        'rufengda' =>
            array (
                'comcode' => 'rufengda',
                'name' => '如风达',
                'type' => 1,
            ),
        'dayangwuliu' =>
            array (
                'comcode' => 'dayangwuliu',
                'name' => '大洋物流',
                'type' => 1,
            ),
        'sendtochina' =>
            array (
                'comcode' => 'sendtochina',
                'name' => '速递中国',
                'type' => 3,
            ),
        'koreapost' =>
            array (
                'comcode' => 'koreapost',
                'name' => '韩国（Korea Post）',
                'type' => 2,
            ),
        'ytchengnuoda' =>
            array (
                'comcode' => 'ytchengnuoda',
                'name' => '承诺达',
                'type' => 1,
            ),
        'yangbaoguo' =>
            array (
                'comcode' => 'yangbaoguo',
                'name' => '洋包裹',
                'type' => 3,
            ),
        'jxfex' =>
            array (
                'comcode' => 'jxfex',
                'name' => '集先锋快递',
                'type' => 1,
            ),
        'italiane' =>
            array (
                'comcode' => 'italiane',
                'name' => '意大利(Poste Italiane)',
                'type' => 2,
            ),
        'cosco' =>
            array (
                'comcode' => 'cosco',
                'name' => '中远e环球',
                'type' => 3,
            ),
        'hangrui' =>
            array (
                'comcode' => 'hangrui',
                'name' => '上海航瑞货运',
                'type' => 1,
            ),
        'postnlcn' =>
            array (
                'comcode' => 'postnlcn',
                'name' => '荷兰邮政-中文(PostNL international registered mail)',
                'type' => 2,
            ),
        'zlink' =>
            array (
                'comcode' => 'zlink',
                'name' => '三真驿道',
                'type' => 1,
            ),
        'jym56' =>
            array (
                'comcode' => 'jym56',
                'name' => '加运美速递',
                'type' => 1,
            ),
        'correosdees' =>
            array (
                'comcode' => 'correosdees',
                'name' => '西班牙(Correos de Espa?a)',
                'type' => 2,
            ),
        'jiujiuwl' =>
            array (
                'comcode' => 'jiujiuwl',
                'name' => '久久物流',
                'type' => 1,
            ),
        'jiajiwuliu' =>
            array (
                'comcode' => 'jiajiwuliu',
                'name' => '佳吉快运',
                'type' => 1,
            ),
        'cccc58' =>
            array (
                'comcode' => 'cccc58',
                'name' => '中集冷云',
                'type' => 1,
            ),
        'sagawa' =>
            array (
                'comcode' => 'sagawa',
                'name' => '佐川急便',
                'type' => 3,
            ),
        'lineone' =>
            array (
                'comcode' => 'lineone',
                'name' => '一号线',
                'type' => 3,
            ),
        'yousutongda' =>
            array (
                'comcode' => 'yousutongda',
                'name' => '优速通达',
                'type' => 1,
            ),
        'anxindakuaixi' =>
            array (
                'comcode' => 'anxindakuaixi',
                'name' => '安信达',
                'type' => 1,
            ),
        'etong' =>
            array (
                'comcode' => 'etong',
                'name' => 'E通速递',
                'type' => 3,
            ),
        'sd138' =>
            array (
                'comcode' => 'sd138',
                'name' => '泰国138国际物流',
                'type' => 3,
            ),
        'kingfreight' =>
            array (
                'comcode' => 'kingfreight',
                'name' => '货运皇',
                'type' => 3,
            ),
        'cnausu' =>
            array (
                'comcode' => 'cnausu',
                'name' => '中澳速递',
                'type' => 3,
            ),
        'chronopostfra' =>
            array (
                'comcode' => 'chronopostfra',
                'name' => '法国大包、EMS-法文（Chronopost France）',
                'type' => 2,
            ),
        'jinan' =>
            array (
                'comcode' => 'jinan',
                'name' => '金岸物流',
                'type' => 1,
            ),
        'blueskyexpress' =>
            array (
                'comcode' => 'blueskyexpress',
                'name' => '蓝天快递',
                'type' => 1,
            ),
        'jialidatong' =>
            array (
                'comcode' => 'jialidatong',
                'name' => '嘉里大通',
                'type' => 1,
            ),
        'gdkd' =>
            array (
                'comcode' => 'gdkd',
                'name' => '港快速递',
                'type' => 1,
            ),
        'jiajikuaidi' =>
            array (
                'comcode' => 'jiajikuaidi',
                'name' => '佳吉快递',
                'type' => 1,
            ),
        'shangda' =>
            array (
                'comcode' => 'shangda',
                'name' => '上大物流',
                'type' => 1,
            ),
        'wherexpess' =>
            array (
                'comcode' => 'wherexpess',
                'name' => '威盛快递',
                'type' => 1,
            ),
        'zengyisudi' =>
            array (
                'comcode' => 'zengyisudi',
                'name' => '增益速递',
                'type' => 1,
            ),
        'jiacheng' =>
            array (
                'comcode' => 'jiacheng',
                'name' => '佳成快递 ',
                'type' => 1,
            ),
        'wto56kj' =>
            array (
                'comcode' => 'wto56kj',
                'name' => '臣邦同城',
                'type' => 1,
            ),
        'ztong' =>
            array (
                'comcode' => 'ztong',
                'name' => '智通物流',
                'type' => 1,
            ),
        'hac56' =>
            array (
                'comcode' => 'hac56',
                'name' => '瀚朝物流',
                'type' => 1,
            ),
        'cameroon' =>
            array (
                'comcode' => 'cameroon',
                'name' => '喀麦隆(CAMPOST)',
                'type' => 2,
            ),
        'japanpost' =>
            array (
                'comcode' => 'japanpost',
                'name' => '日本郵便',
                'type' => 3,
            ),
        'wtdchina' =>
            array (
                'comcode' => 'wtdchina',
                'name' => '威时沛运货运',
                'type' => 1,
            ),
        'ganzhongnengda' =>
            array (
                'comcode' => 'ganzhongnengda',
                'name' => '能达速递',
                'type' => 1,
            ),
        'zyzoom' =>
            array (
                'comcode' => 'zyzoom',
                'name' => '增速跨境 ',
                'type' => 3,
            ),
        'taoplus' =>
            array (
                'comcode' => 'taoplus',
                'name' => '淘布斯国际物流',
                'type' => 3,
            ),
        'jieanda' =>
            array (
                'comcode' => 'jieanda',
                'name' => '捷安达',
                'type' => 1,
            ),
        'emswuliu' =>
            array (
                'comcode' => 'emswuliu',
                'name' => 'EMS物流',
                'type' => 1,
            ),
        'com1express' =>
            array (
                'comcode' => 'com1express',
                'name' => '商壹国际物流',
                'type' => 3,
            ),
        'hlyex' =>
            array (
                'comcode' => 'hlyex',
                'name' => '好来运',
                'type' => 1,
            ),
        'nell' =>
            array (
                'comcode' => 'nell',
                'name' => '尼尔快递',
                'type' => 1,
            ),
        'yuanchengwuliu' =>
            array (
                'comcode' => 'yuanchengwuliu',
                'name' => '远成物流',
                'type' => 1,
            ),
        'thailand' =>
            array (
                'comcode' => 'thailand',
                'name' => '泰国（Thailand Thai Post）',
                'type' => 2,
            ),
        'boyol' =>
            array (
                'comcode' => 'boyol',
                'name' => '贝业物流',
                'type' => 1,
            ),
        'signedexpress' =>
            array (
                'comcode' => 'signedexpress',
                'name' => '签收快递',
                'type' => 1,
            ),
        'yizhengdasuyun' =>
            array (
                'comcode' => 'yizhengdasuyun',
                'name' => '一正达速运',
                'type' => 1,
            ),
        'ruidianyouzheng' =>
            array (
                'comcode' => 'ruidianyouzheng',
                'name' => '瑞典（Sweden Post）',
                'type' => 2,
            ),
        'farlogistis' =>
            array (
                'comcode' => 'farlogistis',
                'name' => '泛远国际物流',
                'type' => 3,
            ),
        'nebuex' =>
            array (
                'comcode' => 'nebuex',
                'name' => '星云速递',
                'type' => 1,
            ),
        'amazoncnorder' =>
            array (
                'comcode' => 'amazoncnorder',
                'name' => '亚马逊中国订单',
                'type' => 3,
            ),
        'nzzto' =>
            array (
                'comcode' => 'nzzto',
                'name' => '新西兰中通',
                'type' => 3,
            ),
        'minghangkuaidi' =>
            array (
                'comcode' => 'minghangkuaidi',
                'name' => '民航快递',
                'type' => 1,
            ),
        'postserv' =>
            array (
                'comcode' => 'postserv',
                'name' => '台湾（中华邮政）',
                'type' => 2,
            ),
        'youban' =>
            array (
                'comcode' => 'youban',
                'name' => '邮邦国际',
                'type' => 3,
            ),
        'yue777' =>
            array (
                'comcode' => 'yue777',
                'name' => '玥玛速运',
                'type' => 1,
            ),
        'fedexus' =>
            array (
                'comcode' => 'fedexus',
                'name' => 'FedEx-美国件',
                'type' => 3,
            ),
        'koalaexp' =>
            array (
                'comcode' => 'koalaexp',
                'name' => '考拉速递',
                'type' => 1,
            ),
        'newsway' =>
            array (
                'comcode' => 'newsway',
                'name' => '家家通快递',
                'type' => 1,
            ),
        'qpost' =>
            array (
                'comcode' => 'qpost',
                'name' => '卡塔尔（Qatar Post）',
                'type' => 2,
            ),
        'parcelforce' =>
            array (
                'comcode' => 'parcelforce',
                'name' => '英国大包、EMS（Parcel Force）',
                'type' => 2,
            ),
        'tzky' =>
            array (
                'comcode' => 'tzky',
                'name' => '铁中快运',
                'type' => 1,
            ),
        'colissimo' =>
            array (
                'comcode' => 'colissimo',
                'name' => '法国小包（colissimo）',
                'type' => 3,
            ),
        'wuliuky' =>
            array (
                'comcode' => 'wuliuky',
                'name' => '五六快运',
                'type' => 1,
            ),
        'shangcheng' =>
            array (
                'comcode' => 'shangcheng',
                'name' => '尚橙物流',
                'type' => 1,
            ),
        'ftky365' =>
            array (
                'comcode' => 'ftky365',
                'name' => '丰通快运',
                'type' => 1,
            ),
        'jiazhoumao' =>
            array (
                'comcode' => 'jiazhoumao',
                'name' => '加州猫速递',
                'type' => 1,
            ),
        'wlwex' =>
            array (
                'comcode' => 'wlwex',
                'name' => '星空国际',
                'type' => 3,
            ),
        'httx56' =>
            array (
                'comcode' => 'httx56',
                'name' => '汇通天下物流',
                'type' => 1,
            ),
        'huanqiuabc' =>
            array (
                'comcode' => 'huanqiuabc',
                'name' => '中国香港环球快运',
                'type' => 3,
            ),
        'easyexpress' =>
            array (
                'comcode' => 'easyexpress',
                'name' => 'EASY EXPRESS',
                'type' => 3,
            ),
        'hotwms' =>
            array (
                'comcode' => 'hotwms',
                'name' => '皇家云仓',
                'type' => 1,
            ),
        'ndwl' =>
            array (
                'comcode' => 'ndwl',
                'name' => '南方传媒物流',
                'type' => 1,
            ),
        'zteexpress' =>
            array (
                'comcode' => 'zteexpress',
                'name' => 'ZTE中兴物流',
                'type' => 3,
            ),
        'speedpost' =>
            array (
                'comcode' => 'speedpost',
                'name' => '新加坡EMS、大包(Singapore Speedpost)',
                'type' => 2,
            ),
        'asendiausa' =>
            array (
                'comcode' => 'asendiausa',
                'name' => 'Asendia USA',
                'type' => 3,
            ),
        'lfexpress' =>
            array (
                'comcode' => 'lfexpress',
                'name' => '龙枫国际快递',
                'type' => 3,
            ),
        'youyou' =>
            array (
                'comcode' => 'youyou',
                'name' => '优优速递',
                'type' => 1,
            ),
        'trakpak' =>
            array (
                'comcode' => 'trakpak',
                'name' => 'TRAKPAK',
                'type' => 3,
            ),
        'baitengwuliu' =>
            array (
                'comcode' => 'baitengwuliu',
                'name' => '百腾物流',
                'type' => 1,
            ),
        'csuivi' =>
            array (
                'comcode' => 'csuivi',
                'name' => '法国(La Poste)',
                'type' => 2,
            ),
        'yuananda' =>
            array (
                'comcode' => 'yuananda',
                'name' => '源安达',
                'type' => 1,
            ),
        'tykd' =>
            array (
                'comcode' => 'tykd',
                'name' => '天翼快递',
                'type' => 1,
            ),
        'meiguokuaidi' =>
            array (
                'comcode' => 'meiguokuaidi',
                'name' => '美国快递',
                'type' => 3,
            ),
        'postnl' =>
            array (
                'comcode' => 'postnl',
                'name' => '荷兰邮政(PostNL international registered mail)',
                'type' => 2,
            ),
        'lasership' =>
            array (
                'comcode' => 'lasership',
                'name' => 'LaserShip',
                'type' => 3,
            ),
        'yujiawl' =>
            array (
                'comcode' => 'yujiawl',
                'name' => '宇佳物流',
                'type' => 1,
            ),
        'haizhongzhuanyun' =>
            array (
                'comcode' => 'haizhongzhuanyun',
                'name' => '海中转运',
                'type' => 1,
            ),
        'kenya' =>
            array (
                'comcode' => 'kenya',
                'name' => '肯尼亚(POSTA KENYA)',
                'type' => 2,
            ),
        'vps' =>
            array (
                'comcode' => 'vps',
                'name' => '维普恩物流',
                'type' => 1,
            ),
        'eupackage' =>
            array (
                'comcode' => 'eupackage',
                'name' => '易优包裹',
                'type' => 1,
            ),
        'goldjet' =>
            array (
                'comcode' => 'goldjet',
                'name' => '高捷快运',
                'type' => 1,
            ),
        'landmarkglobal' =>
            array (
                'comcode' => 'landmarkglobal',
                'name' => 'Landmark Global',
                'type' => 3,
            ),
        'longfx' =>
            array (
                'comcode' => 'longfx',
                'name' => 'LUCFLOW EXPRESS',
                'type' => 1,
            ),
        'bht' =>
            array (
                'comcode' => 'bht',
                'name' => 'BHT',
                'type' => 3,
            ),
        'gsm' =>
            array (
                'comcode' => 'gsm',
                'name' => 'GSM',
                'type' => 3,
            ),
        'homecourier' =>
            array (
                'comcode' => 'homecourier',
                'name' => '如家国际快递',
                'type' => 3,
            ),
        'ht22' =>
            array (
                'comcode' => 'ht22',
                'name' => '海淘物流',
                'type' => 1,
            ),
        'nederlandpost' =>
            array (
                'comcode' => 'nederlandpost',
                'name' => '荷兰速递(Nederland Post)',
                'type' => 2,
            ),
        'chuangyi' =>
            array (
                'comcode' => 'chuangyi',
                'name' => '创一快递',
                'type' => 1,
            ),
        'chengtong' =>
            array (
                'comcode' => 'chengtong',
                'name' => '城通物流',
                'type' => 1,
            ),
        'yongchangwuliu' =>
            array (
                'comcode' => 'yongchangwuliu',
                'name' => '永昌物流',
                'type' => 1,
            ),
        'anjie88' =>
            array (
                'comcode' => 'anjie88',
                'name' => '安捷物流',
                'type' => 1,
            ),
        'tcxbthai' =>
            array (
                'comcode' => 'tcxbthai',
                'name' => 'TCXB国际物流',
                'type' => 3,
            ),
        'pfcexpress' =>
            array (
                'comcode' => 'pfcexpress',
                'name' => '皇家物流',
                'type' => 1,
            ),
        'dechuangwuliu' =>
            array (
                'comcode' => 'dechuangwuliu',
                'name' => '深圳德创物流',
                'type' => 1,
            ),
        'uhi' =>
            array (
                'comcode' => 'uhi',
                'name' => '优海国际速递',
                'type' => 3,
            ),
        'chukou1' =>
            array (
                'comcode' => 'chukou1',
                'name' => '出口易',
                'type' => 3,
            ),
        'sccod' =>
            array (
                'comcode' => 'sccod',
                'name' => '丰程物流',
                'type' => 1,
            ),
        'uscbexpress' =>
            array (
                'comcode' => 'uscbexpress',
                'name' => '易境达国际物流',
                'type' => 3,
            ),
        'hermes' =>
            array (
                'comcode' => 'hermes',
                'name' => 'Hermes',
                'type' => 3,
            ),
        'astexpress' =>
            array (
                'comcode' => 'astexpress',
                'name' => '安世通快递',
                'type' => 1,
            ),
        'ausexpress' =>
            array (
                'comcode' => 'ausexpress',
                'name' => '澳世速递',
                'type' => 3,
            ),
        'chuanxiwuliu' =>
            array (
                'comcode' => 'chuanxiwuliu',
                'name' => '传喜物流',
                'type' => 1,
            ),
        'citylink' =>
            array (
                'comcode' => 'citylink',
                'name' => 'City-Link',
                'type' => 3,
            ),
        'yingchao' =>
            array (
                'comcode' => 'yingchao',
                'name' => '英超物流',
                'type' => 3,
            ),
        'zlxdjwl' =>
            array (
                'comcode' => 'zlxdjwl',
                'name' => '中粮鲜到家物流',
                'type' => 1,
            ),
        'swisspost' =>
            array (
                'comcode' => 'swisspost',
                'name' => '瑞士(Swiss Post)',
                'type' => 2,
            ),
        'cnws' =>
            array (
                'comcode' => 'cnws',
                'name' => '中国翼',
                'type' => 3,
            ),
        'qichen' =>
            array (
                'comcode' => 'qichen',
                'name' => '启辰国际速递',
                'type' => 3,
            ),
        'guoeryue' =>
            array (
                'comcode' => 'guoeryue',
                'name' => '天天快物流',
                'type' => 1,
            ),
        'wandougongzhu' =>
            array (
                'comcode' => 'wandougongzhu',
                'name' => '豌豆物流',
                'type' => 1,
            ),
        'saiaodimmb' =>
            array (
                'comcode' => 'saiaodimmb',
                'name' => '赛澳递for买卖宝',
                'type' => 3,
            ),
        'ctoexp' =>
            array (
                'comcode' => 'ctoexp',
                'name' => '泰国中通CTO',
                'type' => 3,
            ),
        'postdanmarken' =>
            array (
                'comcode' => 'postdanmarken',
                'name' => '丹麦(Post Denmark)',
                'type' => 2,
            ),
        'jumstc' =>
            array (
                'comcode' => 'jumstc',
                'name' => '聚盟共建',
                'type' => 1,
            ),
        'buytong' =>
            array (
                'comcode' => 'buytong',
                'name' => '百通物流',
                'type' => 1,
            ),
        'tnjex' =>
            array (
                'comcode' => 'tnjex',
                'name' => '明通国际快递',
                'type' => 3,
            ),
        'jcex' =>
            array (
                'comcode' => 'jcex',
                'name' => 'jcex',
                'type' => 3,
            ),
        'quansutong' =>
            array (
                'comcode' => 'quansutong',
                'name' => '全速通',
                'type' => 1,
            ),
        'sunspeedy' =>
            array (
                'comcode' => 'sunspeedy',
                'name' => '新速航',
                'type' => 1,
            ),
        'chengpei' =>
            array (
                'comcode' => 'chengpei',
                'name' => '河北橙配',
                'type' => 1,
            ),
        'humpline' =>
            array (
                'comcode' => 'humpline',
                'name' => '驼峰国际',
                'type' => 3,
            ),
        'vnpost' =>
            array (
                'comcode' => 'vnpost',
                'name' => '越南EMS(VNPost Express)',
                'type' => 2,
            ),
        'dfwl' =>
            array (
                'comcode' => 'dfwl',
                'name' => '达发物流',
                'type' => 1,
            ),
        'onehcang' =>
            array (
                'comcode' => 'onehcang',
                'name' => '一号仓',
                'type' => 3,
            ),
        'shenganwuliu' =>
            array (
                'comcode' => 'shenganwuliu',
                'name' => '圣安物流',
                'type' => 1,
            ),
        'benteng' =>
            array (
                'comcode' => 'benteng',
                'name' => '奔腾物流',
                'type' => 1,
            ),
        'highsince' =>
            array (
                'comcode' => 'highsince',
                'name' => 'Highsince',
                'type' => 3,
            ),
        'beckygo' =>
            array (
                'comcode' => 'beckygo',
                'name' => '佰麒快递',
                'type' => 1,
            ),
        'byht' =>
            array (
                'comcode' => 'byht',
                'name' => '展勤快递',
                'type' => 1,
            ),
        'malaysiapost' =>
            array (
                'comcode' => 'malaysiapost',
                'name' => '马来西亚小包（Malaysia Post(Registered)）',
                'type' => 2,
            ),
        'pochta' =>
            array (
                'comcode' => 'pochta',
                'name' => '俄罗斯邮政(Russian Post)',
                'type' => 2,
            ),
        'postnlchina' =>
            array (
                'comcode' => 'postnlchina',
                'name' => '荷兰邮政-中国件',
                'type' => 2,
            ),
        'shunjieda' =>
            array (
                'comcode' => 'shunjieda',
                'name' => '顺捷达',
                'type' => 1,
            ),
        'hyytes' =>
            array (
                'comcode' => 'hyytes',
                'name' => '恒宇运通',
                'type' => 1,
            ),
        'zhonghuanus' =>
            array (
                'comcode' => 'zhonghuanus',
                'name' => '中环转运',
                'type' => 3,
            ),
        'anjiekuaidi' =>
            array (
                'comcode' => 'anjiekuaidi',
                'name' => '青岛安捷快递',
                'type' => 1,
            ),
        'vangenexpress' =>
            array (
                'comcode' => 'vangenexpress',
                'name' => '万庚国际速递',
                'type' => 3,
            ),
        'gongsuda' =>
            array (
                'comcode' => 'gongsuda',
                'name' => '共速达',
                'type' => 1,
            ),
        'haihongwangsong' =>
            array (
                'comcode' => 'haihongwangsong',
                'name' => '海红网送',
                'type' => 1,
            ),
        'qbexpress' =>
            array (
                'comcode' => 'qbexpress',
                'name' => '秦邦快运',
                'type' => 1,
            ),
        'anposten' =>
            array (
                'comcode' => 'anposten',
                'name' => '爱尔兰(An Post)',
                'type' => 2,
            ),
        'beebird' =>
            array (
                'comcode' => 'beebird',
                'name' => '锋鸟物流',
                'type' => 1,
            ),
        'gotoubi' =>
            array (
                'comcode' => 'gotoubi',
                'name' => 'UBI Australia',
                'type' => 3,
            ),
        'zdepost' =>
            array (
                'comcode' => 'zdepost',
                'name' => '直德邮',
                'type' => 3,
            ),
        'zsky123' =>
            array (
                'comcode' => 'zsky123',
                'name' => '准实快运',
                'type' => 1,
            ),
        'kerrytj' =>
            array (
                'comcode' => 'kerrytj',
                'name' => '嘉里大荣物流',
                'type' => 1,
            ),
        'macao' =>
            array (
                'comcode' => 'macao',
                'name' => '中国澳门(Macau Post)',
                'type' => 2,
            ),
        'zhongjiwuliu' =>
            array (
                'comcode' => 'zhongjiwuliu',
                'name' => '中技物流',
                'type' => 1,
            ),
        'dongjun' =>
            array (
                'comcode' => 'dongjun',
                'name' => '成都东骏物流',
                'type' => 1,
            ),
        'shunfenghk' =>
            array (
                'comcode' => 'shunfenghk',
                'name' => '顺丰-繁体',
                'type' => 1,
            ),
        'bpostinter' =>
            array (
                'comcode' => 'bpostinter',
                'name' => '比利时国际(Bpost international)',
                'type' => 2,
            ),
        'cllexpress' =>
            array (
                'comcode' => 'cllexpress',
                'name' => '澳通华人物流',
                'type' => 3,
            ),
        'dhlecommerce' =>
            array (
                'comcode' => 'dhlecommerce',
                'name' => 'dhl小包',
                'type' => 3,
            ),
        'disifangau' =>
            array (
                'comcode' => 'disifangau',
                'name' => '递四方澳洲',
                'type' => 3,
            ),
        'feibaokuaidi' =>
            array (
                'comcode' => 'feibaokuaidi',
                'name' => '飞豹快递',
                'type' => 1,
            ),
        'fyex' =>
            array (
                'comcode' => 'fyex',
                'name' => '飞云快递系统',
                'type' => 1,
            ),
        'jcsuda' =>
            array (
                'comcode' => 'jcsuda',
                'name' => '嘉诚速达',
                'type' => 1,
            ),
        'koreapostcn' =>
            array (
                'comcode' => 'koreapostcn',
                'name' => '韩国邮政',
                'type' => 2,
            ),
        'shlindao' =>
            array (
                'comcode' => 'shlindao',
                'name' => '林道国际快递',
                'type' => 3,
            ),
        'xiyoug' =>
            array (
                'comcode' => 'xiyoug',
                'name' => '西游寄',
                'type' => 3,
            ),
        'yuefengwuliu' =>
            array (
                'comcode' => 'yuefengwuliu',
                'name' => '越丰物流',
                'type' => 1,
            ),
        'ztjieda' =>
            array (
                'comcode' => 'ztjieda',
                'name' => '泰捷达国际物流',
                'type' => 3,
            ),
        'adp' =>
            array (
                'comcode' => 'adp',
                'name' => 'ADP国际快递',
                'type' => 3,
            ),
        'baishiyp' =>
            array (
                'comcode' => 'baishiyp',
                'name' => '百世云配',
                'type' => 1,
            ),
        'bjemstckj' =>
            array (
                'comcode' => 'bjemstckj',
                'name' => '北京EMS',
                'type' => 2,
            ),
        'chunfai' =>
            array (
                'comcode' => 'chunfai',
                'name' => '中国香港骏辉物流',
                'type' => 3,
            ),
        'cnspeedster' =>
            array (
                'comcode' => 'cnspeedster',
                'name' => '速舟物流',
                'type' => 1,
            ),
        'efspost' =>
            array (
                'comcode' => 'efspost',
                'name' => 'EFSPOST',
                'type' => 1,
            ),
        'eshunda' =>
            array (
                'comcode' => 'eshunda',
                'name' => '俄顺达',
                'type' => 1,
            ),
        'exsuda' =>
            array (
                'comcode' => 'exsuda',
                'name' => 'E速达',
                'type' => 1,
            ),
        'newgistics' =>
            array (
                'comcode' => 'newgistics',
                'name' => 'Newgistics',
                'type' => 3,
            ),
        'quansu' =>
            array (
                'comcode' => 'quansu',
                'name' => '全速物流',
                'type' => 1,
            ),
        'ydglobe' =>
            array (
                'comcode' => 'ydglobe',
                'name' => '云达通',
                'type' => 1,
            ),
        'airpak' =>
            array (
                'comcode' => 'airpak',
                'name' => 'airpak expresss',
                'type' => 3,
            ),
        'ynztsy' =>
            array (
                'comcode' => 'ynztsy',
                'name' => '纵通速运',
                'type' => 1,
            ),
        'alog' =>
            array (
                'comcode' => 'alog',
                'name' => '心怡物流',
                'type' => 1,
            ),
        'gslexpress' =>
            array (
                'comcode' => 'gslexpress',
                'name' => '德尚国际速递',
                'type' => 3,
            ),
        'jieborne' =>
            array (
                'comcode' => 'jieborne',
                'name' => '捷邦物流',
                'type' => 1,
            ),
        'yuntongkuaidi' =>
            array (
                'comcode' => 'yuntongkuaidi',
                'name' => '运通中港',
                'type' => 1,
            ),
        'zhongtianwanyun' =>
            array (
                'comcode' => 'zhongtianwanyun',
                'name' => '中天万运',
                'type' => 1,
            ),
        'abf' =>
            array (
                'comcode' => 'abf',
                'name' => 'ABF',
                'type' => 3,
            ),
        'ksudi' =>
            array (
                'comcode' => 'ksudi',
                'name' => '快速递',
                'type' => 1,
            ),
        'ltx' =>
            array (
                'comcode' => 'ltx',
                'name' => '蓝天国际快递',
                'type' => 3,
            ),
        'luben' =>
            array (
                'comcode' => 'luben',
                'name' => '陆本速递 LUBEN EXPRESS',
                'type' => 1,
            ),
        'tntuk' =>
            array (
                'comcode' => 'tntuk',
                'name' => 'TNT UK',
                'type' => 3,
            ),
        'uspscn' =>
            array (
                'comcode' => 'uspscn',
                'name' => 'USPSCN',
                'type' => 3,
            ),
        'zsda56' =>
            array (
                'comcode' => 'zsda56',
                'name' => '转瞬达集运',
                'type' => 1,
            ),
        'zsmhwl' =>
            array (
                'comcode' => 'zsmhwl',
                'name' => '明辉物流',
                'type' => 1,
            ),
        'aolau' =>
            array (
                'comcode' => 'aolau',
                'name' => 'AOL澳通速递',
                'type' => 3,
            ),
        'bqcwl' =>
            array (
                'comcode' => 'bqcwl',
                'name' => '百千诚物流',
                'type' => 1,
            ),
        'hhair56' =>
            array (
                'comcode' => 'hhair56',
                'name' => '华瀚快递',
                'type' => 1,
            ),
        'mingliangwuliu' =>
            array (
                'comcode' => 'mingliangwuliu',
                'name' => '明亮物流',
                'type' => 1,
            ),
        'pengyuanexpress' =>
            array (
                'comcode' => 'pengyuanexpress',
                'name' => '鹏远国际速递',
                'type' => 3,
            ),
        '1ziton' =>
            array (
                'comcode' => '1ziton',
                'name' => '一智通',
                'type' => 1,
            ),
        'ausbondexpress' =>
            array (
                'comcode' => 'ausbondexpress',
                'name' => '澳邦国际物流',
                'type' => 1,
            ),
        'bee001' =>
            array (
                'comcode' => 'bee001',
                'name' => '蜜蜂速递',
                'type' => 1,
            ),
        'excocotree' =>
            array (
                'comcode' => 'excocotree',
                'name' => '可可树美中速运',
                'type' => 1,
            ),
        'idamalu' =>
            array (
                'comcode' => 'idamalu',
                'name' => '大马鹿',
                'type' => 1,
            ),
        'shunjiefengda' =>
            array (
                'comcode' => 'shunjiefengda',
                'name' => '顺捷丰达',
                'type' => 1,
            ),
        'slovenia' =>
            array (
                'comcode' => 'slovenia',
                'name' => '斯洛文尼亚(Slovenia Post)',
                'type' => 2,
            ),
        'trackparcel' =>
            array (
                'comcode' => 'trackparcel',
                'name' => 'track-parcel',
                'type' => 3,
            ),
        'yidatong' =>
            array (
                'comcode' => 'yidatong',
                'name' => '易达通',
                'type' => 1,
            ),
        'yiouzhou' =>
            array (
                'comcode' => 'yiouzhou',
                'name' => '易欧洲国际物流',
                'type' => 3,
            ),
        'fanyukuaidi' =>
            array (
                'comcode' => 'fanyukuaidi',
                'name' => '凡宇快递',
                'type' => 1,
            ),
        'fastontime' =>
            array (
                'comcode' => 'fastontime',
                'name' => '加拿大联通快运',
                'type' => 3,
            ),
        'feikangda' =>
            array (
                'comcode' => 'feikangda',
                'name' => '飞康达',
                'type' => 1,
            ),
        'quanritongkuaidi' =>
            array (
                'comcode' => 'quanritongkuaidi',
                'name' => '全日通',
                'type' => 1,
            ),
        'zjgj56' =>
            array (
                'comcode' => 'zjgj56',
                'name' => '振捷国际货运',
                'type' => 3,
            ),
        'bangsongwuliu' =>
            array (
                'comcode' => 'bangsongwuliu',
                'name' => '邦送物流',
                'type' => 1,
            ),
        'bdatong' =>
            array (
                'comcode' => 'bdatong',
                'name' => '八达通',
                'type' => 1,
            ),
        'belgiumpost' =>
            array (
                'comcode' => 'belgiumpost',
                'name' => '比利时(Belgium Post)',
                'type' => 2,
            ),
        'bulgarian' =>
            array (
                'comcode' => 'bulgarian',
                'name' => '保加利亚（Bulgarian Posts）',
                'type' => 2,
            ),
        'esinotrans' =>
            array (
                'comcode' => 'esinotrans',
                'name' => '中外运',
                'type' => 3,
            ),
        'fox' =>
            array (
                'comcode' => 'fox',
                'name' => 'FOX国际快递',
                'type' => 3,
            ),
        'gttexpress' =>
            array (
                'comcode' => 'gttexpress',
                'name' => 'GTT EXPRESS快递',
                'type' => 1,
            ),
        'hmus' =>
            array (
                'comcode' => 'hmus',
                'name' => '华美快递',
                'type' => 1,
            ),
        'kuai8' =>
            array (
                'comcode' => 'kuai8',
                'name' => '快8速运',
                'type' => 1,
            ),
        'malaysiaems' =>
            array (
                'comcode' => 'malaysiaems',
                'name' => '马来西亚大包、EMS（Malaysia Post(parcel,EMS)）',
                'type' => 2,
            ),
        'mchy' =>
            array (
                'comcode' => 'mchy',
                'name' => '木春货运',
                'type' => 1,
            ),
        'nntengda' =>
            array (
                'comcode' => 'nntengda',
                'name' => '腾达速递',
                'type' => 1,
            ),
        'rpx' =>
            array (
                'comcode' => 'rpx',
                'name' => 'rpx',
                'type' => 3,
            ),
        'saiaodi' =>
            array (
                'comcode' => 'saiaodi',
                'name' => '赛澳递',
                'type' => 3,
            ),
        'szuem' =>
            array (
                'comcode' => 'szuem',
                'name' => '联运通物流',
                'type' => 1,
            ),
        'taijin' =>
            array (
                'comcode' => 'taijin',
                'name' => '泰进物流',
                'type' => 1,
            ),
        'xianglongyuntong' =>
            array (
                'comcode' => 'xianglongyuntong',
                'name' => '祥龙运通物流',
                'type' => 1,
            ),
        'yinjiesudi' =>
            array (
                'comcode' => 'yinjiesudi',
                'name' => '银捷速递',
                'type' => 1,
            ),
        'a2u' =>
            array (
                'comcode' => 'a2u',
                'name' => 'A2U速递',
                'type' => 3,
            ),
        'ahdf' =>
            array (
                'comcode' => 'ahdf',
                'name' => '德方物流',
                'type' => 1,
            ),
        'ajexpress' =>
            array (
                'comcode' => 'ajexpress',
                'name' => '捷记方舟',
                'type' => 3,
            ),
        'bangbangpost' =>
            array (
                'comcode' => 'bangbangpost',
                'name' => '帮帮发',
                'type' => 3,
            ),
        'chronopostfren' =>
            array (
                'comcode' => 'chronopostfren',
                'name' => '法国大包、EMS-英文(Chronopost France)',
                'type' => 2,
            ),
        'dadaoex' =>
            array (
                'comcode' => 'dadaoex',
                'name' => '大道物流',
                'type' => 1,
            ),
        'flowerkd' =>
            array (
                'comcode' => 'flowerkd',
                'name' => '花瓣转运',
                'type' => 3,
            ),
        'ftlexpress' =>
            array (
                'comcode' => 'ftlexpress',
                'name' => '法翔速运',
                'type' => 1,
            ),
        'hkems' =>
            array (
                'comcode' => 'hkems',
                'name' => '云邮跨境快递',
                'type' => 3,
            ),
        'jamaicapost' =>
            array (
                'comcode' => 'jamaicapost',
                'name' => '牙买加（Jamaica Post）',
                'type' => 2,
            ),
        'jiguang' =>
            array (
                'comcode' => 'jiguang',
                'name' => '极光转运',
                'type' => 3,
            ),
        'koali' =>
            array (
                'comcode' => 'koali',
                'name' => '番薯国际货运',
                'type' => 3,
            ),
        'lesotho' =>
            array (
                'comcode' => 'lesotho',
                'name' => '莱索托(Lesotho Post)',
                'type' => 2,
            ),
        'minbangsudi' =>
            array (
                'comcode' => 'minbangsudi',
                'name' => '民邦速递',
                'type' => 1,
            ),
        'postnlpacle' =>
            array (
                'comcode' => 'postnlpacle',
                'name' => '荷兰包裹(PostNL International Parcels)',
                'type' => 2,
            ),
        'riyuwuliu' =>
            array (
                'comcode' => 'riyuwuliu',
                'name' => '日昱物流',
                'type' => 1,
            ),
        'shipbyace' =>
            array (
                'comcode' => 'shipbyace',
                'name' => '王牌快递',
                'type' => 1,
            ),
        'ycgky' =>
            array (
                'comcode' => 'ycgky',
                'name' => '远成快运',
                'type' => 1,
            ),
        'zhongwaiyun' =>
            array (
                'comcode' => 'zhongwaiyun',
                'name' => '中外运速递',
                'type' => 3,
            ),
        'zhpex' =>
            array (
                'comcode' => 'zhpex',
                'name' => '众派速递',
                'type' => 1,
            ),
        'zhuoshikuaiyun' =>
            array (
                'comcode' => 'zhuoshikuaiyun',
                'name' => '卓实快运',
                'type' => 1,
            ),
        'bester' =>
            array (
                'comcode' => 'bester',
                'name' => '飛斯特',
                'type' => 3,
            ),
        'ccd' =>
            array (
                'comcode' => 'ccd',
                'name' => '河南次晨达',
                'type' => 1,
            ),
        'cces' =>
            array (
                'comcode' => 'cces',
                'name' => 'CCES/国通快递',
                'type' => 3,
            ),
        'comexpress' =>
            array (
                'comcode' => 'comexpress',
                'name' => '邦通国际',
                'type' => 3,
            ),
        'diantongkuaidi' =>
            array (
                'comcode' => 'diantongkuaidi',
                'name' => '店通快递',
                'type' => 1,
            ),
        'eucnrail' =>
            array (
                'comcode' => 'eucnrail',
                'name' => '中欧国际物流',
                'type' => 3,
            ),
        'fastway' =>
            array (
                'comcode' => 'fastway',
                'name' => 'Fastway Ireland',
                'type' => 3,
            ),
        'fbkd' =>
            array (
                'comcode' => 'fbkd',
                'name' => '飞邦快递',
                'type' => 1,
            ),
        'ghtexpress' =>
            array (
                'comcode' => 'ghtexpress',
                'name' => 'GHT物流',
                'type' => 3,
            ),
        'jixianda' =>
            array (
                'comcode' => 'jixianda',
                'name' => '急先达',
                'type' => 1,
            ),
        'kcs' =>
            array (
                'comcode' => 'kcs',
                'name' => 'KCS',
                'type' => 3,
            ),
        'khzto' =>
            array (
                'comcode' => 'khzto',
                'name' => '柬埔寨中通',
                'type' => 1,
            ),
        'maxeedexpress' =>
            array (
                'comcode' => 'maxeedexpress',
                'name' => '澳洲迈速快递',
                'type' => 3,
            ),
        'novaposhta' =>
            array (
                'comcode' => 'novaposhta',
                'name' => 'Nova Poshta',
                'type' => 3,
            ),
        'pakistan' =>
            array (
                'comcode' => 'pakistan',
                'name' => '巴基斯坦(Pakistan Post)',
                'type' => 2,
            ),
        'pcaexpress' =>
            array (
                'comcode' => 'pcaexpress',
                'name' => 'PCA Express',
                'type' => 3,
            ),
        'uzbekistan' =>
            array (
                'comcode' => 'uzbekistan',
                'name' => '乌兹别克斯坦(Post of Uzbekistan)',
                'type' => 2,
            ),
        'whgjkd' =>
            array (
                'comcode' => 'whgjkd',
                'name' => '香港伟豪国际物流',
                'type' => 3,
            ),
        'yundaexus' =>
            array (
                'comcode' => 'yundaexus',
                'name' => '美国云达',
                'type' => 3,
            ),
        'zhongxinda' =>
            array (
                'comcode' => 'zhongxinda',
                'name' => '忠信达',
                'type' => 1,
            ),
        'advancing' =>
            array (
                'comcode' => 'advancing',
                'name' => '安达信',
                'type' => 1,
            ),
        'anlexpress' =>
            array (
                'comcode' => 'anlexpress',
                'name' => '新干线快递',
                'type' => 1,
            ),
        'chinastarlogistics' =>
            array (
                'comcode' => 'chinastarlogistics',
                'name' => '华欣物流',
                'type' => 1,
            ),
        'cnair' =>
            array (
                'comcode' => 'cnair',
                'name' => 'CNAIR',
                'type' => 3,
            ),
        'dpdgermany' =>
            array (
                'comcode' => 'dpdgermany',
                'name' => 'DPD Germany',
                'type' => 3,
            ),
        'england' =>
            array (
                'comcode' => 'england',
                'name' => '英国(大包,EMS)',
                'type' => 2,
            ),
        'eta100' =>
            array (
                'comcode' => 'eta100',
                'name' => '易达国际速递',
                'type' => 3,
            ),
        'express7th' =>
            array (
                'comcode' => 'express7th',
                'name' => '7号速递',
                'type' => 1,
            ),
        'fedroad' =>
            array (
                'comcode' => 'fedroad',
                'name' => 'FedRoad 联邦转运',
                'type' => 3,
            ),
        'flysman' =>
            array (
                'comcode' => 'flysman',
                'name' => '飞力士物流',
                'type' => 1,
            ),
        'freakyquick' =>
            array (
                'comcode' => 'freakyquick',
                'name' => 'FQ狂派速递',
                'type' => 3,
            ),
        'georgianpost' =>
            array (
                'comcode' => 'georgianpost',
                'name' => '格鲁吉亚(Georgian Pos）',
                'type' => 3,
            ),
        'hltop' =>
            array (
                'comcode' => 'hltop',
                'name' => '海联快递',
                'type' => 1,
            ),
        'huaqikuaiyun' =>
            array (
                'comcode' => 'huaqikuaiyun',
                'name' => '华企快运',
                'type' => 1,
            ),
        'jdiex' =>
            array (
                'comcode' => 'jdiex',
                'name' => 'JDIEX',
                'type' => 3,
            ),
        'jindawuliu' =>
            array (
                'comcode' => 'jindawuliu',
                'name' => '金大物流',
                'type' => 1,
            ),
        'junfengguoji' =>
            array (
                'comcode' => 'junfengguoji',
                'name' => '骏丰国际速递',
                'type' => 3,
            ),
        'lutong' =>
            array (
                'comcode' => 'lutong',
                'name' => '鲁通快运',
                'type' => 1,
            ),
        'polarisexpress' =>
            array (
                'comcode' => 'polarisexpress',
                'name' => '北极星快运',
                'type' => 1,
            ),
        'portugalctt' =>
            array (
                'comcode' => 'portugalctt',
                'name' => '葡萄牙（Portugal CTT）',
                'type' => 3,
            ),
        'qesd' =>
            array (
                'comcode' => 'qesd',
                'name' => '7E速递',
                'type' => 3,
            ),
        'quanchuan56' =>
            array (
                'comcode' => 'quanchuan56',
                'name' => '全川物流',
                'type' => 1,
            ),
        'quantwl' =>
            array (
                'comcode' => 'quantwl',
                'name' => '全通快运',
                'type' => 1,
            ),
        'santaisudi' =>
            array (
                'comcode' => 'santaisudi',
                'name' => '三态速递',
                'type' => 1,
            ),
        'sdsy888' =>
            array (
                'comcode' => 'sdsy888',
                'name' => '首达速运',
                'type' => 1,
            ),
        'shiningexpress' =>
            array (
                'comcode' => 'shiningexpress',
                'name' => '阳光快递',
                'type' => 1,
            ),
        'tollpriority' =>
            array (
                'comcode' => 'tollpriority',
                'name' => 'Toll Priority(Toll Online)',
                'type' => 3,
            ),
        'yiex' =>
            array (
                'comcode' => 'yiex',
                'name' => '宜送物流',
                'type' => 1,
            ),
        'yitongda' =>
            array (
                'comcode' => 'yitongda',
                'name' => '易通达',
                'type' => 1,
            ),
        'yuanhhk' =>
            array (
                'comcode' => 'yuanhhk',
                'name' => '远航国际快运',
                'type' => 3,
            ),
        'yuanzhijiecheng' =>
            array (
                'comcode' => 'yuanzhijiecheng',
                'name' => '元智捷诚',
                'type' => 1,
            ),
        'auvanda' =>
            array (
                'comcode' => 'auvanda',
                'name' => '中联速递',
                'type' => 1,
            ),
        'baotongkd' =>
            array (
                'comcode' => 'baotongkd',
                'name' => '宝通快递',
                'type' => 1,
            ),
        'biaojikuaidi' =>
            array (
                'comcode' => 'biaojikuaidi',
                'name' => '彪记快递',
                'type' => 1,
            ),
        'canpostfr' =>
            array (
                'comcode' => 'canpostfr',
                'name' => '加拿大邮政',
                'type' => 2,
            ),
        'chengda' =>
            array (
                'comcode' => 'chengda',
                'name' => '成达国际速递',
                'type' => 3,
            ),
        'ckeex' =>
            array (
                'comcode' => 'ckeex',
                'name' => '城晓国际快递',
                'type' => 3,
            ),
        'cloudexpress' =>
            array (
                'comcode' => 'cloudexpress',
                'name' => 'CE易欧通国际速递',
                'type' => 3,
            ),
        'cncexp' =>
            array (
                'comcode' => 'cncexp',
                'name' => 'C&C国际速递',
                'type' => 3,
            ),
        'ecallturn' =>
            array (
                'comcode' => 'ecallturn',
                'name' => 'E跨通',
                'type' => 3,
            ),
        'emms' =>
            array (
                'comcode' => 'emms',
                'name' => '澳州顺风快递',
                'type' => 3,
            ),
        'gswtkd' =>
            array (
                'comcode' => 'gswtkd',
                'name' => '万通快递',
                'type' => 1,
            ),
        'gts' =>
            array (
                'comcode' => 'gts',
                'name' => 'GTS快递',
                'type' => 3,
            ),
        'hgy56' =>
            array (
                'comcode' => 'hgy56',
                'name' => '环国运物流',
                'type' => 1,
            ),
        'hkposten' =>
            array (
                'comcode' => 'hkposten',
                'name' => '中国香港(HongKong Post)英文',
                'type' => 2,
            ),
        'hungary' =>
            array (
                'comcode' => 'hungary',
                'name' => '匈牙利（Magyar Posta）',
                'type' => 2,
            ),
        'jdexpressusa' =>
            array (
                'comcode' => 'jdexpressusa',
                'name' => '骏达快递',
                'type' => 1,
            ),
        'jixiangyouau' =>
            array (
                'comcode' => 'jixiangyouau',
                'name' => '吉祥邮（澳洲）',
                'type' => 3,
            ),
        'kxda' =>
            array (
                'comcode' => 'kxda',
                'name' => '凯信达',
                'type' => 1,
            ),
        'latvia' =>
            array (
                'comcode' => 'latvia',
                'name' => '拉脱维亚(Latvijas Pasts)',
                'type' => 3,
            ),
        'mrw' =>
            array (
                'comcode' => 'mrw',
                'name' => 'MRW',
                'type' => 3,
            ),
        'ocaargen' =>
            array (
                'comcode' => 'ocaargen',
                'name' => 'OCA Argentina',
                'type' => 3,
            ),
        'quanjitong' =>
            array (
                'comcode' => 'quanjitong',
                'name' => '全际通',
                'type' => 1,
            ),
        'sanshengco' =>
            array (
                'comcode' => 'sanshengco',
                'name' => '三盛快递',
                'type' => 1,
            ),
        'shunbang' =>
            array (
                'comcode' => 'shunbang',
                'name' => '顺邦国际物流',
                'type' => 3,
            ),
        'sinoairinex' =>
            array (
                'comcode' => 'sinoairinex',
                'name' => '中外运空运',
                'type' => 3,
            ),
        'stkd' =>
            array (
                'comcode' => 'stkd',
                'name' => '顺通快递',
                'type' => 1,
            ),
        'sxhongmajia' =>
            array (
                'comcode' => 'sxhongmajia',
                'name' => '红马甲物流',
                'type' => 1,
            ),
        'uganda' =>
            array (
                'comcode' => 'uganda',
                'name' => '乌干达(Posta Uganda)',
                'type' => 2,
            ),
        'wykjt' =>
            array (
                'comcode' => 'wykjt',
                'name' => '51跨境通',
                'type' => 3,
            ),
        'xingyuankuaidi' =>
            array (
                'comcode' => 'xingyuankuaidi',
                'name' => '新元快递',
                'type' => 1,
            ),
        'yamaxunwuliu' =>
            array (
                'comcode' => 'yamaxunwuliu',
                'name' => '亚马逊中国',
                'type' => 3,
            ),
        'yibangwuliu' =>
            array (
                'comcode' => 'yibangwuliu',
                'name' => '一邦速递',
                'type' => 1,
            ),
        'yuntong' =>
            array (
                'comcode' => 'yuntong',
                'name' => '运通速运',
                'type' => 1,
            ),
        'abcglobal' =>
            array (
                'comcode' => 'abcglobal',
                'name' => '全球快运',
                'type' => 3,
            ),
        'adlerlogi' =>
            array (
                'comcode' => 'adlerlogi',
                'name' => '德国雄鹰速递',
                'type' => 3,
            ),
        'airgtc' =>
            array (
                'comcode' => 'airgtc',
                'name' => '航空快递',
                'type' => 3,
            ),
        'aosu' =>
            array (
                'comcode' => 'aosu',
                'name' => '澳速物流',
                'type' => 3,
            ),
        'aplusex' =>
            array (
                'comcode' => 'aplusex',
                'name' => 'Aplus物流',
                'type' => 3,
            ),
        'axexpress' =>
            array (
                'comcode' => 'axexpress',
                'name' => '澳新物流',
                'type' => 3,
            ),
        'baoxianda' =>
            array (
                'comcode' => 'baoxianda',
                'name' => '报通快递',
                'type' => 1,
            ),
        'barbados' =>
            array (
                'comcode' => 'barbados',
                'name' => '巴巴多斯(Barbados Post)',
                'type' => 2,
            ),
        'cex' =>
            array (
                'comcode' => 'cex',
                'name' => '城铁速递',
                'type' => 1,
            ),
        'chengji' =>
            array (
                'comcode' => 'chengji',
                'name' => '城际快递',
                'type' => 1,
            ),
        'chinatzx' =>
            array (
                'comcode' => 'chinatzx',
                'name' => '同舟行物流',
                'type' => 1,
            ),
        'chllog' =>
            array (
                'comcode' => 'chllog',
                'name' => '嘉荣物流',
                'type' => 1,
            ),
        'chunghwa56' =>
            array (
                'comcode' => 'chunghwa56',
                'name' => '中骅物流',
                'type' => 1,
            ),
        'cqxingcheng' =>
            array (
                'comcode' => 'cqxingcheng',
                'name' => '重庆星程快递',
                'type' => 1,
            ),
        'dfkuaidi' =>
            array (
                'comcode' => 'dfkuaidi',
                'name' => '东风快递',
                'type' => 1,
            ),
        'dfpost' =>
            array (
                'comcode' => 'dfpost',
                'name' => '达方物流',
                'type' => 1,
            ),
        'djy56' =>
            array (
                'comcode' => 'djy56',
                'name' => '天翔东捷运',
                'type' => 1,
            ),
        'edragon' =>
            array (
                'comcode' => 'edragon',
                'name' => '龙象国际物流',
                'type' => 3,
            ),
        'el56' =>
            array (
                'comcode' => 'el56',
                'name' => '易联通达',
                'type' => 1,
            ),
        'euexpress' =>
            array (
                'comcode' => 'euexpress',
                'name' => 'EU-EXPRESS',
                'type' => 3,
            ),
        'euguoji' =>
            array (
                'comcode' => 'euguoji',
                'name' => '易邮国际',
                'type' => 3,
            ),
        'fedexukcn' =>
            array (
                'comcode' => 'fedexukcn',
                'name' => 'FedEx-英国件',
                'type' => 3,
            ),
        'feihukuaidi' =>
            array (
                'comcode' => 'feihukuaidi',
                'name' => '飞狐快递',
                'type' => 1,
            ),
        'finland' =>
            array (
                'comcode' => 'finland',
                'name' => '芬兰(Itella Posti Oy)',
                'type' => 2,
            ),
        'gda' =>
            array (
                'comcode' => 'gda',
                'name' => '安的快递',
                'type' => 1,
            ),
        'ghl' =>
            array (
                'comcode' => 'ghl',
                'name' => '环创物流',
                'type' => 1,
            ),
        'haimengsudi' =>
            array (
                'comcode' => 'haimengsudi',
                'name' => '海盟速递',
                'type' => 1,
            ),
        'haoyoukuai' =>
            array (
                'comcode' => 'haoyoukuai',
                'name' => '好又快物流',
                'type' => 1,
            ),
        'hitaoe' =>
            array (
                'comcode' => 'hitaoe',
                'name' => 'Hi淘易快递',
                'type' => 3,
            ),
        'hnfy' =>
            array (
                'comcode' => 'hnfy',
                'name' => '飞鹰物流',
                'type' => 1,
            ),
        'hnqst' =>
            array (
                'comcode' => 'hnqst',
                'name' => '河南全速通',
                'type' => 1,
            ),
        'hongywl' =>
            array (
                'comcode' => 'hongywl',
                'name' => '红远物流',
                'type' => 1,
            ),
        'huada' =>
            array (
                'comcode' => 'huada',
                'name' => '华达快运',
                'type' => 1,
            ),
        'huandonglg' =>
            array (
                'comcode' => 'huandonglg',
                'name' => '环东物流',
                'type' => 1,
            ),
        'iceland' =>
            array (
                'comcode' => 'iceland',
                'name' => '冰岛(Iceland Post)',
                'type' => 2,
            ),
        'india' =>
            array (
                'comcode' => 'india',
                'name' => '印度(India Post)',
                'type' => 2,
            ),
        'israelpost' =>
            array (
                'comcode' => 'israelpost',
                'name' => '以色列(Israel Post)',
                'type' => 2,
            ),
        'italysad' =>
            array (
                'comcode' => 'italysad',
                'name' => 'Italy SDA',
                'type' => 3,
            ),
        'jintongkd' =>
            array (
                'comcode' => 'jintongkd',
                'name' => '劲通快递',
                'type' => 1,
            ),
        'jinyuekuaidi' =>
            array (
                'comcode' => 'jinyuekuaidi',
                'name' => '晋越快递',
                'type' => 1,
            ),
        'jordan' =>
            array (
                'comcode' => 'jordan',
                'name' => '约旦(Jordan Post)',
                'type' => 2,
            ),
        'kazpost' =>
            array (
                'comcode' => 'kazpost',
                'name' => '哈萨克斯坦(Kazpost)',
                'type' => 2,
            ),
        'kfwnet' =>
            array (
                'comcode' => 'kfwnet',
                'name' => '快服务',
                'type' => 1,
            ),
        'lanbiaokuaidi' =>
            array (
                'comcode' => 'lanbiaokuaidi',
                'name' => '蓝镖快递',
                'type' => 1,
            ),
        'lishi' =>
            array (
                'comcode' => 'lishi',
                'name' => '丽狮物流',
                'type' => 1,
            ),
        'longlangkuaidi' =>
            array (
                'comcode' => 'longlangkuaidi',
                'name' => '隆浪快递',
                'type' => 1,
            ),
        'mailongdy' =>
            array (
                'comcode' => 'mailongdy',
                'name' => '迈隆递运',
                'type' => 1,
            ),
        'milkyway' =>
            array (
                'comcode' => 'milkyway',
                'name' => '银河物流',
                'type' => 1,
            ),
        'overseaex' =>
            array (
                'comcode' => 'overseaex',
                'name' => '波音速递',
                'type' => 1,
            ),
        'parcelforcecn' =>
            array (
                'comcode' => 'parcelforcecn',
                'name' => '英国邮政大包EMS',
                'type' => 2,
            ),
        'peisihuoyunkuaidi' =>
            array (
                'comcode' => 'peisihuoyunkuaidi',
                'name' => '配思货运',
                'type' => 1,
            ),
        'posta' =>
            array (
                'comcode' => 'posta',
                'name' => '坦桑尼亚（Tanzania Posts Corporation）',
                'type' => 2,
            ),
        'postpng' =>
            array (
                'comcode' => 'postpng',
                'name' => '巴布亚新几内亚(PNG Post)',
                'type' => 2,
            ),
        'rokin' =>
            array (
                'comcode' => 'rokin',
                'name' => '荣庆物流',
                'type' => 1,
            ),
        'romanian' =>
            array (
                'comcode' => 'romanian',
                'name' => '罗马尼亚（Posta Romanian）',
                'type' => 2,
            ),
        'rrskx' =>
            array (
                'comcode' => 'rrskx',
                'name' => '日日顺快线',
                'type' => 1,
            ),
        'safexpress' =>
            array (
                'comcode' => 'safexpress',
                'name' => 'Safexpress',
                'type' => 3,
            ),
        'sczpds' =>
            array (
                'comcode' => 'sczpds',
                'name' => '速呈',
                'type' => 1,
            ),
        'serbia' =>
            array (
                'comcode' => 'serbia',
                'name' => '塞尔维亚(PE Post of Serbia)',
                'type' => 2,
            ),
        'sfjhd' =>
            array (
                'comcode' => 'sfjhd',
                'name' => '圣飞捷快递',
                'type' => 1,
            ),
        'shengtongscm' =>
            array (
                'comcode' => 'shengtongscm',
                'name' => '盛通快递',
                'type' => 1,
            ),
        'shunshid' =>
            array (
                'comcode' => 'shunshid',
                'name' => '顺士达速运',
                'type' => 1,
            ),
        'siodemka' =>
            array (
                'comcode' => 'siodemka',
                'name' => 'Siodemka',
                'type' => 3,
            ),
        'sofast56' =>
            array (
                'comcode' => 'sofast56',
                'name' => '嗖一下同城快递',
                'type' => 1,
            ),
        'staryvr' =>
            array (
                'comcode' => 'staryvr',
                'name' => '星运快递',
                'type' => 1,
            ),
        'suijiawuliu' =>
            array (
                'comcode' => 'suijiawuliu',
                'name' => '穗佳物流',
                'type' => 1,
            ),
        'superoz' =>
            array (
                'comcode' => 'superoz',
                'name' => '速配欧翼',
                'type' => 3,
            ),
        'swisspostcn' =>
            array (
                'comcode' => 'swisspostcn',
                'name' => '瑞士邮政',
                'type' => 2,
            ),
        'sxexpress' =>
            array (
                'comcode' => 'sxexpress',
                'name' => '三象速递',
                'type' => 1,
            ),
        'szyouzheng' =>
            array (
                'comcode' => 'szyouzheng',
                'name' => '深圳邮政',
                'type' => 2,
            ),
        'tdcargo' =>
            array (
                'comcode' => 'tdcargo',
                'name' => 'TD Cargo',
                'type' => 3,
            ),
        'thaizto' =>
            array (
                'comcode' => 'thaizto',
                'name' => '泰国中通ZTO',
                'type' => 3,
            ),
        'tlky' =>
            array (
                'comcode' => 'tlky',
                'name' => '天联快运',
                'type' => 1,
            ),
        'tonghetianxia' =>
            array (
                'comcode' => 'tonghetianxia',
                'name' => '通和天下',
                'type' => 1,
            ),
        'turtle' =>
            array (
                'comcode' => 'turtle',
                'name' => '海龟国际快递',
                'type' => 3,
            ),
        'uexiex' =>
            array (
                'comcode' => 'uexiex',
                'name' => '欧洲UEX',
                'type' => 2,
            ),
        'vipexpress' =>
            array (
                'comcode' => 'vipexpress',
                'name' => '鹰运国际速递',
                'type' => 3,
            ),
        'wotu' =>
            array (
                'comcode' => 'wotu',
                'name' => '渥途国际速运',
                'type' => 3,
            ),
        'wuyuansudi' =>
            array (
                'comcode' => 'wuyuansudi',
                'name' => '伍圆速递',
                'type' => 1,
            ),
        'xiangdawuliu' =>
            array (
                'comcode' => 'xiangdawuliu',
                'name' => '湘达物流',
                'type' => 1,
            ),
        'xsrd' =>
            array (
                'comcode' => 'xsrd',
                'name' => '鑫世锐达',
                'type' => 1,
            ),
        'xyd666' =>
            array (
                'comcode' => 'xyd666',
                'name' => '鑫远东速运',
                'type' => 1,
            ),
        'ydhex' =>
            array (
                'comcode' => 'ydhex',
                'name' => 'YDH',
                'type' => 1,
            ),
        'yemen' =>
            array (
                'comcode' => 'yemen',
                'name' => '也门(Yemen Post)',
                'type' => 2,
            ),
        'yifankd' =>
            array (
                'comcode' => 'yifankd',
                'name' => '艺凡快递',
                'type' => 1,
            ),
        'yongbangwuliu' =>
            array (
                'comcode' => 'yongbangwuliu',
                'name' => '永邦国际物流',
                'type' => 3,
            ),
        'yourscm' =>
            array (
                'comcode' => 'yourscm',
                'name' => '雅澳物流',
                'type' => 3,
            ),
        'ywexpress' =>
            array (
                'comcode' => 'ywexpress',
                'name' => '远为快递',
                'type' => 1,
            ),
        'yyqc56' =>
            array (
                'comcode' => 'yyqc56',
                'name' => '一运全成物流',
                'type' => 1,
            ),
        'zf365' =>
            array (
                'comcode' => 'zf365',
                'name' => '珠峰速运',
                'type' => 1,
            ),
        'adaexpress' =>
            array (
                'comcode' => 'adaexpress',
                'name' => '明大快递',
                'type' => 1,
            ),
        'adiexpress' =>
            array (
                'comcode' => 'adiexpress',
                'name' => '安达易国际速递',
                'type' => 3,
            ),
        'afghan' =>
            array (
                'comcode' => 'afghan',
                'name' => '阿富汗(Afghan Post)',
                'type' => 2,
            ),
        'afl' =>
            array (
                'comcode' => 'afl',
                'name' => 'AFL',
                'type' => 3,
            ),
        'agopost' =>
            array (
                'comcode' => 'agopost',
                'name' => '全程快递',
                'type' => 1,
            ),
        'ahkbps' =>
            array (
                'comcode' => 'ahkbps',
                'name' => '卡邦配送',
                'type' => 1,
            ),
        'albania' =>
            array (
                'comcode' => 'albania',
                'name' => '阿尔巴尼亚(Posta shqipatre)',
                'type' => 2,
            ),
        'aliexpress' =>
            array (
                'comcode' => 'aliexpress',
                'name' => '无忧物流',
                'type' => 1,
            ),
        'amcnorder' =>
            array (
                'comcode' => 'amcnorder',
                'name' => 'amazon-国内订单',
                'type' => 3,
            ),
        'amusorder' =>
            array (
                'comcode' => 'amusorder',
                'name' => 'amazon-国际订单',
                'type' => 3,
            ),
        'apgecommerce' =>
            array (
                'comcode' => 'apgecommerce',
                'name' => 'apgecommerce',
                'type' => 1,
            ),
        'aplus100' =>
            array (
                'comcode' => 'aplus100',
                'name' => '美国汉邦快递',
                'type' => 3,
            ),
        'ariesfar' =>
            array (
                'comcode' => 'ariesfar',
                'name' => '艾瑞斯远',
                'type' => 1,
            ),
        'aruba' =>
            array (
                'comcode' => 'aruba',
                'name' => '阿鲁巴[荷兰]（Post Aruba）',
                'type' => 2,
            ),
        'auex' =>
            array (
                'comcode' => 'auex',
                'name' => '澳货通',
                'type' => 3,
            ),
        'austria' =>
            array (
                'comcode' => 'austria',
                'name' => '奥地利(Austrian Post)',
                'type' => 2,
            ),
        'auvexpress' =>
            array (
                'comcode' => 'auvexpress',
                'name' => 'AUV国际快递',
                'type' => 3,
            ),
        'azerbaijan' =>
            array (
                'comcode' => 'azerbaijan',
                'name' => '阿塞拜疆EMS(EMS AzerExpressPost)',
                'type' => 2,
            ),
        'bahrain' =>
            array (
                'comcode' => 'bahrain',
                'name' => '巴林(Bahrain Post)',
                'type' => 2,
            ),
        'bangladesh' =>
            array (
                'comcode' => 'bangladesh',
                'name' => '孟加拉国(EMS)',
                'type' => 2,
            ),
        'bcwelt' =>
            array (
                'comcode' => 'bcwelt',
                'name' => 'BCWELT',
                'type' => 3,
            ),
        'belize' =>
            array (
                'comcode' => 'belize',
                'name' => '伯利兹(Belize Postal)',
                'type' => 2,
            ),
        'belpost' =>
            array (
                'comcode' => 'belpost',
                'name' => '白俄罗斯(Belpochta)',
                'type' => 3,
            ),
        'benniao' =>
            array (
                'comcode' => 'benniao',
                'name' => '笨鸟国际',
                'type' => 3,
            ),
        'bjqywl' =>
            array (
                'comcode' => 'bjqywl',
                'name' => '青云物流',
                'type' => 1,
            ),
        'bjxsrd' =>
            array (
                'comcode' => 'bjxsrd',
                'name' => '鑫锐达',
                'type' => 1,
            ),
        'bluedart' =>
            array (
                'comcode' => 'bluedart',
                'name' => 'BlueDart',
                'type' => 3,
            ),
        'bmlchina' =>
            array (
                'comcode' => 'bmlchina',
                'name' => '标杆物流',
                'type' => 1,
            ),
        'bohei' =>
            array (
                'comcode' => 'bohei',
                'name' => '波黑(JP BH Posta)',
                'type' => 2,
            ),
        'bolivia' =>
            array (
                'comcode' => 'bolivia',
                'name' => '玻利维亚',
                'type' => 3,
            ),
        'borderguru' =>
            array (
                'comcode' => 'borderguru',
                'name' => 'BorderGuru',
                'type' => 2,
            ),
        'bosind' =>
            array (
                'comcode' => 'bosind',
                'name' => '堡昕德速递',
                'type' => 1,
            ),
        'botspost' =>
            array (
                'comcode' => 'botspost',
                'name' => '博茨瓦纳',
                'type' => 2,
            ),
        'bphchina' =>
            array (
                'comcode' => 'bphchina',
                'name' => '速方(Sufast)',
                'type' => 3,
            ),
        'brazilposten' =>
            array (
                'comcode' => 'brazilposten',
                'name' => '巴西(Brazil Post/Correios)',
                'type' => 2,
            ),
        'brunei' =>
            array (
                'comcode' => 'brunei',
                'name' => '文莱(Brunei Postal)',
                'type' => 2,
            ),
        'caledonia' =>
            array (
                'comcode' => 'caledonia',
                'name' => '新喀里多尼亚[法国](New Caledonia)',
                'type' => 3,
            ),
        'cambodia' =>
            array (
                'comcode' => 'cambodia',
                'name' => '柬埔寨(Cambodia Post)',
                'type' => 2,
            ),
        'camekong' =>
            array (
                'comcode' => 'camekong',
                'name' => '到了港',
                'type' => 3,
            ),
        'campbellsexpress' =>
            array (
                'comcode' => 'campbellsexpress',
                'name' => 'Campbell’s Express',
                'type' => 3,
            ),
        'canhold' =>
            array (
                'comcode' => 'canhold',
                'name' => '能装能送',
                'type' => 1,
            ),
        'canpar' =>
            array (
                'comcode' => 'canpar',
                'name' => 'Canpar',
                'type' => 3,
            ),
        'cargolux' =>
            array (
                'comcode' => 'cargolux',
                'name' => '卢森堡航空',
                'type' => 3,
            ),
        'cbo56' =>
            array (
                'comcode' => 'cbo56',
                'name' => '钏博物流',
                'type' => 1,
            ),
        'cdek' =>
            array (
                'comcode' => 'cdek',
                'name' => 'CDEK',
                'type' => 3,
            ),
        'ceskaposta' =>
            array (
                'comcode' => 'ceskaposta',
                'name' => '捷克（?eská po?ta）',
                'type' => 3,
            ),
        'cevalogistics' =>
            array (
                'comcode' => 'cevalogistics',
                'name' => 'CEVA Logistic',
                'type' => 3,
            ),
        'cfss' =>
            array (
                'comcode' => 'cfss',
                'name' => '银雁专送',
                'type' => 1,
            ),
        'changwooair' =>
            array (
                'comcode' => 'changwooair',
                'name' => '昌宇国际',
                'type' => 3,
            ),
        'changyuwuliu' =>
            array (
                'comcode' => 'changyuwuliu',
                'name' => '长宇物流',
                'type' => 1,
            ),
        'chile' =>
            array (
                'comcode' => 'chile',
                'name' => '智利(Correos Chile)',
                'type' => 2,
            ),
        'chinapostcb' =>
            array (
                'comcode' => 'chinapostcb',
                'name' => '中邮电商',
                'type' => 1,
            ),
        'chronopostport' =>
            array (
                'comcode' => 'chronopostport',
                'name' => 'Chronopost Portugal',
                'type' => 2,
            ),
        'city56' =>
            array (
                'comcode' => 'city56',
                'name' => '城市映急',
                'type' => 1,
            ),
        'citysprint' =>
            array (
                'comcode' => 'citysprint',
                'name' => 'citysprint',
                'type' => 3,
            ),
        'cjkoreaexpress' =>
            array (
                'comcode' => 'cjkoreaexpress',
                'name' => '大韩通运',
                'type' => 1,
            ),
        'clsp' =>
            array (
                'comcode' => 'clsp',
                'name' => 'CL日中速运',
                'type' => 3,
            ),
        'cneulogistics' =>
            array (
                'comcode' => 'cneulogistics',
                'name' => '中欧物流',
                'type' => 1,
            ),
        'cnexps' =>
            array (
                'comcode' => 'cnexps',
                'name' => 'CNE',
                'type' => 3,
            ),
        'cnup' =>
            array (
                'comcode' => 'cnup',
                'name' => 'CNUP 中联邮',
                'type' => 3,
            ),
        'colombia' =>
            array (
                'comcode' => 'colombia',
                'name' => '哥伦比亚(4-72 La Red Postal de Colombia)',
                'type' => 2,
            ),
        'correios' =>
            array (
                'comcode' => 'correios',
                'name' => '莫桑比克（Correios de Moçambique）',
                'type' => 3,
            ),
        'correo' =>
            array (
                'comcode' => 'correo',
                'name' => '乌拉圭（Correo Uruguayo）',
                'type' => 3,
            ),
        'correoargentino' =>
            array (
                'comcode' => 'correoargentino',
                'name' => '阿根廷(Correo Argentina)',
                'type' => 3,
            ),
        'correos' =>
            array (
                'comcode' => 'correos',
                'name' => '哥斯达黎加(Correos de Costa Rica)',
                'type' => 2,
            ),
        'cpsair' =>
            array (
                'comcode' => 'cpsair',
                'name' => '华中快递',
                'type' => 1,
            ),
        'crossbox' =>
            array (
                'comcode' => 'crossbox',
                'name' => '环旅快运',
                'type' => 1,
            ),
        'csxss' =>
            array (
                'comcode' => 'csxss',
                'name' => '新时速物流',
                'type' => 1,
            ),
        'cypruspost' =>
            array (
                'comcode' => 'cypruspost',
                'name' => '塞浦路斯(Cyprus Post)',
                'type' => 2,
            ),
        'czwlyn' =>
            array (
                'comcode' => 'czwlyn',
                'name' => '云南诚中物流',
                'type' => 1,
            ),
        'dasu' =>
            array (
                'comcode' => 'dasu',
                'name' => '达速物流',
                'type' => 1,
            ),
        'dcs' =>
            array (
                'comcode' => 'dcs',
                'name' => 'DCS',
                'type' => 3,
            ),
        'decnlh' =>
            array (
                'comcode' => 'decnlh',
                'name' => '德中快递',
                'type' => 1,
            ),
        'deltec' =>
            array (
                'comcode' => 'deltec',
                'name' => 'Deltec Courier',
                'type' => 3,
            ),
        'desworks' =>
            array (
                'comcode' => 'desworks',
                'name' => '澳行快递',
                'type' => 3,
            ),
        'dhlhk' =>
            array (
                'comcode' => 'dhlhk',
                'name' => 'DHL HK',
                'type' => 3,
            ),
        'dhlnetherlands' =>
            array (
                'comcode' => 'dhlnetherlands',
                'name' => 'DHL-荷兰（DHL Netherlands）',
                'type' => 3,
            ),
        'dhlpoland' =>
            array (
                'comcode' => 'dhlpoland',
                'name' => 'DHL-波兰（DHL Poland）',
                'type' => 3,
            ),
        'di5pll' =>
            array (
                'comcode' => 'di5pll',
                'name' => '递五方云仓',
                'type' => 1,
            ),
        'dianyi' =>
            array (
                'comcode' => 'dianyi',
                'name' => '云南滇驿物流',
                'type' => 1,
            ),
        'didasuyun' =>
            array (
                'comcode' => 'didasuyun',
                'name' => '递达速运',
                'type' => 1,
            ),
        'dindon' =>
            array (
                'comcode' => 'dindon',
                'name' => '叮咚澳洲转运',
                'type' => 3,
            ),
        'dingdong' =>
            array (
                'comcode' => 'dingdong',
                'name' => '叮咚快递',
                'type' => 1,
            ),
        'directlink' =>
            array (
                'comcode' => 'directlink',
                'name' => 'Direct Link',
                'type' => 2,
            ),
        'disifangus' =>
            array (
                'comcode' => 'disifangus',
                'name' => '递四方美国',
                'type' => 3,
            ),
        'donghanwl' =>
            array (
                'comcode' => 'donghanwl',
                'name' => '东瀚物流',
                'type' => 1,
            ),
        'donghong' =>
            array (
                'comcode' => 'donghong',
                'name' => '东红物流',
                'type' => 1,
            ),
        'dpdpoland' =>
            array (
                'comcode' => 'dpdpoland',
                'name' => 'DPD Poland',
                'type' => 3,
            ),
        'dpduk' =>
            array (
                'comcode' => 'dpduk',
                'name' => 'DPD UK',
                'type' => 3,
            ),
        'dtdcindia' =>
            array (
                'comcode' => 'dtdcindia',
                'name' => 'DTDC India',
                'type' => 3,
            ),
        'duodao56' =>
            array (
                'comcode' => 'duodao56',
                'name' => 'duodao56',
                'type' => 1,
            ),
        'ealceair' =>
            array (
                'comcode' => 'ealceair',
                'name' => '东方航空物流',
                'type' => 3,
            ),
        'ecfirstclass' =>
            array (
                'comcode' => 'ecfirstclass',
                'name' => 'EC-Firstclass',
                'type' => 3,
            ),
        'ecmsglobal' =>
            array (
                'comcode' => 'ecmsglobal',
                'name' => 'ECMS Express',
                'type' => 3,
            ),
        'ecotransite' =>
            array (
                'comcode' => 'ecotransite',
                'name' => '东西E全运',
                'type' => 3,
            ),
        'ecuador' =>
            array (
                'comcode' => 'ecuador',
                'name' => '厄瓜多尔(Correos del Ecuador)',
                'type' => 2,
            ),
        'edaeuexpress' =>
            array (
                'comcode' => 'edaeuexpress',
                'name' => '易达快运',
                'type' => 1,
            ),
        'edtexpress' =>
            array (
                'comcode' => 'edtexpress',
                'name' => 'e直运',
                'type' => 3,
            ),
        'egypt' =>
            array (
                'comcode' => 'egypt',
                'name' => '埃及（Egypt Post）',
                'type' => 2,
            ),
        'eiffel' =>
            array (
                'comcode' => 'eiffel',
                'name' => '艾菲尔国际速递',
                'type' => 3,
            ),
        'elta' =>
            array (
                'comcode' => 'elta',
                'name' => '希腊包裹（ELTA Hellenic Post）',
                'type' => 2,
            ),
        'eltahell' =>
            array (
                'comcode' => 'eltahell',
                'name' => '希腊EMS（ELTA Courier）',
                'type' => 2,
            ),
        'emirates' =>
            array (
                'comcode' => 'emirates',
                'name' => '阿联酋(Emirates Post)',
                'type' => 2,
            ),
        'emonitoring' =>
            array (
                'comcode' => 'emonitoring',
                'name' => '波兰小包(Poczta Polska)',
                'type' => 3,
            ),
        'emssouthafrica' =>
            array (
                'comcode' => 'emssouthafrica',
                'name' => '南非EMS',
                'type' => 2,
            ),
        'emsukraine' =>
            array (
                'comcode' => 'emsukraine',
                'name' => '乌克兰EMS(EMS Ukraine)',
                'type' => 2,
            ),
        'emsukrainecn' =>
            array (
                'comcode' => 'emsukrainecn',
                'name' => '乌克兰EMS-中文(EMS Ukraine)',
                'type' => 2,
            ),
        'epspost' =>
            array (
                'comcode' => 'epspost',
                'name' => '联众国际',
                'type' => 3,
            ),
        'estafeta' =>
            array (
                'comcode' => 'estafeta',
                'name' => 'Estafeta',
                'type' => 3,
            ),
        'estes' =>
            array (
                'comcode' => 'estes',
                'name' => 'Estes',
                'type' => 3,
            ),
        'ethiopia' =>
            array (
                'comcode' => 'ethiopia',
                'name' => '埃塞俄比亚(Ethiopian postal)',
                'type' => 2,
            ),
        'eucpost' =>
            array (
                'comcode' => 'eucpost',
                'name' => '德国 EUC POST',
                'type' => 2,
            ),
        'europe8' =>
            array (
                'comcode' => 'europe8',
                'name' => '败欧洲',
                'type' => 3,
            ),
        'europeanecom' =>
            array (
                'comcode' => 'europeanecom',
                'name' => 'europeanecom',
                'type' => 3,
            ),
        'eusacn' =>
            array (
                'comcode' => 'eusacn',
                'name' => '优莎速运',
                'type' => 1,
            ),
        'expressplus' =>
            array (
                'comcode' => 'expressplus',
                'name' => '澳洲新干线快递',
                'type' => 3,
            ),
        'ezhuanyuan' =>
            array (
                'comcode' => 'ezhuanyuan',
                'name' => '易转运',
                'type' => 3,
            ),
        'fandaguoji' =>
            array (
                'comcode' => 'fandaguoji',
                'name' => '颿达国际快递-英文',
                'type' => 3,
            ),
        'fardarww' =>
            array (
                'comcode' => 'fardarww',
                'name' => '颿达国际快递',
                'type' => 3,
            ),
        'fastzt' =>
            array (
                'comcode' => 'fastzt',
                'name' => '正途供应链',
                'type' => 1,
            ),
        'fedexuk' =>
            array (
                'comcode' => 'fedexuk',
                'name' => 'FedEx-英国件（FedEx UK)',
                'type' => 3,
            ),
        'feikuaida' =>
            array (
                'comcode' => 'feikuaida',
                'name' => '飞快达',
                'type' => 1,
            ),
        'fenghuangkuaidi' =>
            array (
                'comcode' => 'fenghuangkuaidi',
                'name' => '凤凰快递',
                'type' => 1,
            ),
        'fiji' =>
            array (
                'comcode' => 'fiji',
                'name' => '斐济(Fiji Post)',
                'type' => 2,
            ),
        'fsexp' =>
            array (
                'comcode' => 'fsexp',
                'name' => '全速快递',
                'type' => 1,
            ),
        'gaotieex' =>
            array (
                'comcode' => 'gaotieex',
                'name' => '高铁快运',
                'type' => 1,
            ),
        'gaticn' =>
            array (
                'comcode' => 'gaticn',
                'name' => 'Gati-中文',
                'type' => 3,
            ),
        'gatien' =>
            array (
                'comcode' => 'gatien',
                'name' => 'Gati-英文',
                'type' => 3,
            ),
        'gatikwe' =>
            array (
                'comcode' => 'gatikwe',
                'name' => 'Gati-KWE',
                'type' => 3,
            ),
        'gdct56' =>
            array (
                'comcode' => 'gdct56',
                'name' => '广东诚通物流',
                'type' => 1,
            ),
        'gdqwwl' =>
            array (
                'comcode' => 'gdqwwl',
                'name' => '全网物流',
                'type' => 1,
            ),
        'gdrz58' =>
            array (
                'comcode' => 'gdrz58',
                'name' => '容智快运',
                'type' => 1,
            ),
        'gdxp' =>
            array (
                'comcode' => 'gdxp',
                'name' => '新鹏快递',
                'type' => 1,
            ),
        'ge2d' =>
            array (
                'comcode' => 'ge2d',
                'name' => 'GE2D跨境物流',
                'type' => 3,
            ),
        'gibraltar' =>
            array (
                'comcode' => 'gibraltar',
                'name' => '直布罗陀[英国]( Royal Gibraltar Post)',
                'type' => 2,
            ),
        'gjwl' =>
            array (
                'comcode' => 'gjwl',
                'name' => '冠捷物流 ',
                'type' => 1,
            ),
        'gls' =>
            array (
                'comcode' => 'gls',
                'name' => 'GLS',
                'type' => 3,
            ),
        'gml' =>
            array (
                'comcode' => 'gml',
                'name' => '英脉物流',
                'type' => 1,
            ),
        'greenland' =>
            array (
                'comcode' => 'greenland',
                'name' => '格陵兰[丹麦]（TELE Greenland A/S）',
                'type' => 3,
            ),
        'grivertek' =>
            array (
                'comcode' => 'grivertek',
                'name' => '潍鸿',
                'type' => 3,
            ),
        'gscq365' =>
            array (
                'comcode' => 'gscq365',
                'name' => '哥士传奇速递',
                'type' => 1,
            ),
        'gtgogo' =>
            array (
                'comcode' => 'gtgogo',
                'name' => 'GT国际快运',
                'type' => 3,
            ),
        'gtongsudi' =>
            array (
                'comcode' => 'gtongsudi',
                'name' => '广通速递',
                'type' => 1,
            ),
        'guangdongtonglu' =>
            array (
                'comcode' => 'guangdongtonglu',
                'name' => '广东通路',
                'type' => 1,
            ),
        'guanting' =>
            array (
                'comcode' => 'guanting',
                'name' => '冠庭国际物流',
                'type' => 3,
            ),
        'guosong' =>
            array (
                'comcode' => 'guosong',
                'name' => '国送快运',
                'type' => 1,
            ),
        'gvpexpress' =>
            array (
                'comcode' => 'gvpexpress',
                'name' => '宏观国际快递',
                'type' => 3,
            ),
        'gzxingcheng' =>
            array (
                'comcode' => 'gzxingcheng',
                'name' => '贵州星程快递',
                'type' => 1,
            ),
        'haihongmmb' =>
            array (
                'comcode' => 'haihongmmb',
                'name' => '海红for买卖宝',
                'type' => 3,
            ),
        'haiwaihuanqiu' =>
            array (
                'comcode' => 'haiwaihuanqiu',
                'name' => '海外环球',
                'type' => 3,
            ),
        'haixingqiao' =>
            array (
                'comcode' => 'haixingqiao',
                'name' => '海星桥快递',
                'type' => 1,
            ),
        'handboy' =>
            array (
                'comcode' => 'handboy',
                'name' => '汉邦国际速递',
                'type' => 3,
            ),
        'hanfengjl' =>
            array (
                'comcode' => 'hanfengjl',
                'name' => '翰丰快递',
                'type' => 1,
            ),
        'hangyu' =>
            array (
                'comcode' => 'hangyu',
                'name' => '航宇快递',
                'type' => 1,
            ),
        'happylink' =>
            array (
                'comcode' => 'happylink',
                'name' => '开心快递',
                'type' => 1,
            ),
        'haypost' =>
            array (
                'comcode' => 'haypost',
                'name' => '亚美尼亚(Haypost-Armenian Postal)',
                'type' => 2,
            ),
        'heimao56' =>
            array (
                'comcode' => 'heimao56',
                'name' => '黑猫速运',
                'type' => 1,
            ),
        'hengrui56' =>
            array (
                'comcode' => 'hengrui56',
                'name' => '恒瑞物流',
                'type' => 1,
            ),
        'hjs' =>
            array (
                'comcode' => 'hjs',
                'name' => '猴急送',
                'type' => 1,
            ),
        'hlkytj' =>
            array (
                'comcode' => 'hlkytj',
                'name' => '互联快运',
                'type' => 1,
            ),
        'hlpgyl' =>
            array (
                'comcode' => 'hlpgyl',
                'name' => '共联配',
                'type' => 1,
            ),
        'hnssd56' =>
            array (
                'comcode' => 'hnssd56',
                'name' => '顺时达物流',
                'type' => 1,
            ),
        'homexpress' =>
            array (
                'comcode' => 'homexpress',
                'name' => '居家通',
                'type' => 1,
            ),
        'hongbeixin' =>
            array (
                'comcode' => 'hongbeixin',
                'name' => '红背心',
                'type' => 1,
            ),
        'hongjie' =>
            array (
                'comcode' => 'hongjie',
                'name' => '宏捷国际物流',
                'type' => 3,
            ),
        'hongpinwuliu' =>
            array (
                'comcode' => 'hongpinwuliu',
                'name' => '宏品物流',
                'type' => 1,
            ),
        'hqtd' =>
            array (
                'comcode' => 'hqtd',
                'name' => '环球通达 ',
                'type' => 3,
            ),
        'hrbzykd' =>
            array (
                'comcode' => 'hrbzykd',
                'name' => '卓烨快递',
                'type' => 1,
            ),
        'hre' =>
            array (
                'comcode' => 'hre',
                'name' => '高铁速递',
                'type' => 1,
            ),
        'hrvatska' =>
            array (
                'comcode' => 'hrvatska',
                'name' => '克罗地亚（Hrvatska Posta）',
                'type' => 2,
            ),
        'hsgtsd' =>
            array (
                'comcode' => 'hsgtsd',
                'name' => '海硕高铁速递',
                'type' => 1,
            ),
        'htongexpress' =>
            array (
                'comcode' => 'htongexpress',
                'name' => '华通快运',
                'type' => 1,
            ),
        'htwd' =>
            array (
                'comcode' => 'htwd',
                'name' => '华通务达物流',
                'type' => 1,
            ),
        'huaxiahuoyun' =>
            array (
                'comcode' => 'huaxiahuoyun',
                'name' => '华夏货运',
                'type' => 1,
            ),
        'huiqiangkuaidi' =>
            array (
                'comcode' => 'huiqiangkuaidi',
                'name' => '汇强快递',
                'type' => 1,
            ),
        'huoban' =>
            array (
                'comcode' => 'huoban',
                'name' => '兰州伙伴物流',
                'type' => 1,
            ),
        'hutongwuliu' =>
            array (
                'comcode' => 'hutongwuliu',
                'name' => '户通物流',
                'type' => 1,
            ),
        'hyeship' =>
            array (
                'comcode' => 'hyeship',
                'name' => '鸿远物流',
                'type' => 1,
            ),
        'hyk' =>
            array (
                'comcode' => 'hyk',
                'name' => '上海昊宏国际货物',
                'type' => 3,
            ),
        'hzpl' =>
            array (
                'comcode' => 'hzpl',
                'name' => '华航快递',
                'type' => 1,
            ),
        'idada' =>
            array (
                'comcode' => 'idada',
                'name' => '大达物流',
                'type' => 1,
            ),
        'iexpress' =>
            array (
                'comcode' => 'iexpress',
                'name' => 'iExpress',
                'type' => 3,
            ),
        'ilogen' =>
            array (
                'comcode' => 'ilogen',
                'name' => 'logen路坚',
                'type' => 3,
            ),
        'ilyang' =>
            array (
                'comcode' => 'ilyang',
                'name' => 'ILYANG',
                'type' => 3,
            ),
        'imlb2c' =>
            array (
                'comcode' => 'imlb2c',
                'name' => '艾姆勒',
                'type' => 3,
            ),
        'indonesia' =>
            array (
                'comcode' => 'indonesia',
                'name' => '印度尼西亚EMS(Pos Indonesia-EMS)',
                'type' => 2,
            ),
        'inposdom' =>
            array (
                'comcode' => 'inposdom',
                'name' => '多米尼加（INPOSDOM – Instituto Postal Dominicano）',
                'type' => 2,
            ),
        'interlink' =>
            array (
                'comcode' => 'interlink',
                'name' => 'Interlink Express',
                'type' => 3,
            ),
        'iparcel' =>
            array (
                'comcode' => 'iparcel',
                'name' => 'UPS i-parcel',
                'type' => 3,
            ),
        'iran' =>
            array (
                'comcode' => 'iran',
                'name' => '伊朗（Iran Post）',
                'type' => 2,
            ),
        'iyoungspeed' =>
            array (
                'comcode' => 'iyoungspeed',
                'name' => '驿扬国际速运',
                'type' => 3,
            ),
        'jdpplus' =>
            array (
                'comcode' => 'jdpplus',
                'name' => '急递',
                'type' => 1,
            ),
        'jerseypost' =>
            array (
                'comcode' => 'jerseypost',
                'name' => '泽西岛',
                'type' => 3,
            ),
        'jetexpressgroup' =>
            array (
                'comcode' => 'jetexpressgroup',
                'name' => '澳速通国际速递',
                'type' => 3,
            ),
        'jiajiatong56' =>
            array (
                'comcode' => 'jiajiatong56',
                'name' => '佳家通货运',
                'type' => 1,
            ),
        'jinchengwuliu' =>
            array (
                'comcode' => 'jinchengwuliu',
                'name' => '锦程物流',
                'type' => 1,
            ),
        'jiugong' =>
            array (
                'comcode' => 'jiugong',
                'name' => '九宫物流',
                'type' => 1,
            ),
        'jiuyicn' =>
            array (
                'comcode' => 'jiuyicn',
                'name' => '久易快递',
                'type' => 1,
            ),
        'jjx888' =>
            array (
                'comcode' => 'jjx888',
                'name' => '佳捷翔物流',
                'type' => 1,
            ),
        'jsexpress' =>
            array (
                'comcode' => 'jsexpress',
                'name' => '骏绅物流',
                'type' => 1,
            ),
        'juwu' =>
            array (
                'comcode' => 'juwu',
                'name' => '聚物物流',
                'type' => 1,
            ),
        'juzhongda' =>
            array (
                'comcode' => 'juzhongda',
                'name' => '聚中大',
                'type' => 1,
            ),
        'kaolaexpress' =>
            array (
                'comcode' => 'kaolaexpress',
                'name' => '考拉国际速递',
                'type' => 3,
            ),
        'kjde' =>
            array (
                'comcode' => 'kjde',
                'name' => '跨境直邮通',
                'type' => 3,
            ),
        'koreapostkr' =>
            array (
                'comcode' => 'koreapostkr',
                'name' => '韩国邮政韩文',
                'type' => 2,
            ),
        'krtao' =>
            array (
                'comcode' => 'krtao',
                'name' => '淘韩国际快递',
                'type' => 3,
            ),
        'kuaidawuliu' =>
            array (
                'comcode' => 'kuaidawuliu',
                'name' => '快达物流',
                'type' => 1,
            ),
        'kuaitao' =>
            array (
                'comcode' => 'kuaitao',
                'name' => '快淘快递',
                'type' => 1,
            ),
        'kuaiyouda' =>
            array (
                'comcode' => 'kuaiyouda',
                'name' => '四川快优达速递',
                'type' => 1,
            ),
        'kyrgyzpost' =>
            array (
                'comcode' => 'kyrgyzpost',
                'name' => '吉尔吉斯斯坦(Kyrgyz Post)',
                'type' => 2,
            ),
        'lanhukuaidi' =>
            array (
                'comcode' => 'lanhukuaidi',
                'name' => '蓝弧快递',
                'type' => 1,
            ),
        'lao' =>
            array (
                'comcode' => 'lao',
                'name' => '老挝(Lao Express) ',
                'type' => 3,
            ),
        'laposte' =>
            array (
                'comcode' => 'laposte',
                'name' => '塞内加尔',
                'type' => 2,
            ),
        'lasy56' =>
            array (
                'comcode' => 'lasy56',
                'name' => '林安物流',
                'type' => 1,
            ),
        'lbbk' =>
            array (
                'comcode' => 'lbbk',
                'name' => '立白宝凯物流',
                'type' => 1,
            ),
        'ldxpres' =>
            array (
                'comcode' => 'ldxpres',
                'name' => '林道国际快递-英文',
                'type' => 3,
            ),
        'ledii' =>
            array (
                'comcode' => 'ledii',
                'name' => '乐递供应链',
                'type' => 1,
            ),
        'leopard' =>
            array (
                'comcode' => 'leopard',
                'name' => '云豹国际货运',
                'type' => 3,
            ),
        'letseml' =>
            array (
                'comcode' => 'letseml',
                'name' => '美联快递',
                'type' => 1,
            ),
        'lgs' =>
            array (
                'comcode' => 'lgs',
                'name' => 'lazada',
                'type' => 3,
            ),
        'lianyun' =>
            array (
                'comcode' => 'lianyun',
                'name' => '联运快递',
                'type' => 1,
            ),
        'libanpost' =>
            array (
                'comcode' => 'libanpost',
                'name' => '黎巴嫩(Liban Post)',
                'type' => 2,
            ),
        'linex' =>
            array (
                'comcode' => 'linex',
                'name' => 'Linex',
                'type' => 3,
            ),
        'lithuania' =>
            array (
                'comcode' => 'lithuania',
                'name' => '立陶宛（Lietuvos pa?tas）',
                'type' => 3,
            ),
        'littlebearbear' =>
            array (
                'comcode' => 'littlebearbear',
                'name' => '小熊物流',
                'type' => 1,
            ),
        'lmfex' =>
            array (
                'comcode' => 'lmfex',
                'name' => '良藤国际速递',
                'type' => 3,
            ),
        'logistics' =>
            array (
                'comcode' => 'logistics',
                'name' => '華信物流WTO',
                'type' => 3,
            ),
        'longvast' =>
            array (
                'comcode' => 'longvast',
                'name' => '长风物流',
                'type' => 1,
            ),
        'lqht' =>
            array (
                'comcode' => 'lqht',
                'name' => '恒通快递',
                'type' => 1,
            ),
        'lsexpress' =>
            array (
                'comcode' => 'lsexpress',
                'name' => '6LS EXPRESS',
                'type' => 3,
            ),
        'ltexp' =>
            array (
                'comcode' => 'ltexp',
                'name' => '乐天速递',
                'type' => 1,
            ),
        'ltparcel' =>
            array (
                'comcode' => 'ltparcel',
                'name' => '联通快递',
                'type' => 3,
            ),
        'lundao' =>
            array (
                'comcode' => 'lundao',
                'name' => '论道国际物流',
                'type' => 3,
            ),
        'luxembourg' =>
            array (
                'comcode' => 'luxembourg',
                'name' => '卢森堡(Luxembourg Post)',
                'type' => 2,
            ),
        'lwe' =>
            array (
                'comcode' => 'lwe',
                'name' => 'LWE',
                'type' => 3,
            ),
        'macedonia' =>
            array (
                'comcode' => 'macedonia',
                'name' => '马其顿(Macedonian Post)',
                'type' => 2,
            ),
        'mailikuaidi' =>
            array (
                'comcode' => 'mailikuaidi',
                'name' => '麦力快递',
                'type' => 1,
            ),
        'maldives' =>
            array (
                'comcode' => 'maldives',
                'name' => '马尔代夫(Maldives Post)',
                'type' => 2,
            ),
        'malta' =>
            array (
                'comcode' => 'malta',
                'name' => '马耳他（Malta Post）',
                'type' => 2,
            ),
        'mangguo' =>
            array (
                'comcode' => 'mangguo',
                'name' => '芒果速递',
                'type' => 1,
            ),
        'mapleexpress' =>
            array (
                'comcode' => 'mapleexpress',
                'name' => '今枫国际快运',
                'type' => 3,
            ),
        'mauritius' =>
            array (
                'comcode' => 'mauritius',
                'name' => '毛里求斯(Mauritius Post)',
                'type' => 2,
            ),
        'meibang' =>
            array (
                'comcode' => 'meibang',
                'name' => '美邦国际快递',
                'type' => 3,
            ),
        'meidaexpress' =>
            array (
                'comcode' => 'meidaexpress',
                'name' => '美达快递',
                'type' => 1,
            ),
        'meitai' =>
            array (
                'comcode' => 'meitai',
                'name' => '美泰物流',
                'type' => 1,
            ),
        'mexico' =>
            array (
                'comcode' => 'mexico',
                'name' => '墨西哥（Correos de Mexico）',
                'type' => 2,
            ),
        'mexicodenda' =>
            array (
                'comcode' => 'mexicodenda',
                'name' => 'Mexico Senda Express',
                'type' => 3,
            ),
        'mjexp' =>
            array (
                'comcode' => 'mjexp',
                'name' => '美龙快递',
                'type' => 1,
            ),
        'moldova' =>
            array (
                'comcode' => 'moldova',
                'name' => '摩尔多瓦(Posta Moldovei)',
                'type' => 2,
            ),
        'mongolpost' =>
            array (
                'comcode' => 'mongolpost',
                'name' => '蒙古国(Mongol Post) ',
                'type' => 2,
            ),
        'montenegro' =>
            array (
                'comcode' => 'montenegro',
                'name' => '黑山(Posta Crne Gore)',
                'type' => 2,
            ),
        'morocco' =>
            array (
                'comcode' => 'morocco',
                'name' => '摩洛哥 ( Morocco Post )',
                'type' => 2,
            ),
        'multipack' =>
            array (
                'comcode' => 'multipack',
                'name' => 'Mexico Multipack',
                'type' => 3,
            ),
        'mxe56' =>
            array (
                'comcode' => 'mxe56',
                'name' => '中俄速通（淼信）',
                'type' => 1,
            ),
        'myhermes' =>
            array (
                'comcode' => 'myhermes',
                'name' => 'MyHermes',
                'type' => 3,
            ),
        'nalexpress' =>
            array (
                'comcode' => 'nalexpress',
                'name' => '新亚物流',
                'type' => 1,
            ),
        'namibia' =>
            array (
                'comcode' => 'namibia',
                'name' => '纳米比亚(NamPost)',
                'type' => 2,
            ),
        'nedahm' =>
            array (
                'comcode' => 'nedahm',
                'name' => '红马速递',
                'type' => 1,
            ),
        'nepalpost' =>
            array (
                'comcode' => 'nepalpost',
                'name' => '尼泊尔（Nepal Postal Services）',
                'type' => 2,
            ),
        'nigerianpost' =>
            array (
                'comcode' => 'nigerianpost',
                'name' => '尼日利亚(Nigerian Postal)',
                'type' => 2,
            ),
        'njhaobo' =>
            array (
                'comcode' => 'njhaobo',
                'name' => '浩博物流',
                'type' => 1,
            ),
        'nle' =>
            array (
                'comcode' => 'nle',
                'name' => 'NLE',
                'type' => 3,
            ),
        'nlebv' =>
            array (
                'comcode' => 'nlebv',
                'name' => '亚欧专线',
                'type' => 3,
            ),
        'nmhuahe' =>
            array (
                'comcode' => 'nmhuahe',
                'name' => '华赫物流',
                'type' => 1,
            ),
        'nuoer' =>
            array (
                'comcode' => 'nuoer',
                'name' => '诺尔国际物流',
                'type' => 3,
            ),
        'nuoyaao' =>
            array (
                'comcode' => 'nuoyaao',
                'name' => '偌亚奥国际快递',
                'type' => 3,
            ),
        'oman' =>
            array (
                'comcode' => 'oman',
                'name' => '阿曼(Oman Post)',
                'type' => 2,
            ),
        'omniva' =>
            array (
                'comcode' => 'omniva',
                'name' => '爱沙尼亚(Eesti Post)',
                'type' => 2,
            ),
        'onway' =>
            array (
                'comcode' => 'onway',
                'name' => '昂威物流',
                'type' => 1,
            ),
        'opek' =>
            array (
                'comcode' => 'opek',
                'name' => 'OPEK',
                'type' => 3,
            ),
        'paraguay' =>
            array (
                'comcode' => 'paraguay',
                'name' => '巴拉圭(Correo Paraguayo)',
                'type' => 3,
            ),
        'parcelchina' =>
            array (
                'comcode' => 'parcelchina',
                'name' => '诚一物流',
                'type' => 3,
            ),
        'pdstow' =>
            array (
                'comcode' => 'pdstow',
                'name' => '全球速递',
                'type' => 3,
            ),
        'peex' =>
            array (
                'comcode' => 'peex',
                'name' => '派尔快递',
                'type' => 1,
            ),
        'peixingwuliu' =>
            array (
                'comcode' => 'peixingwuliu',
                'name' => '陪行物流',
                'type' => 1,
            ),
        'pengcheng' =>
            array (
                'comcode' => 'pengcheng',
                'name' => '鹏程快递',
                'type' => 1,
            ),
        'peru' =>
            array (
                'comcode' => 'peru',
                'name' => '秘鲁(SERPOST)',
                'type' => 2,
            ),
        'phlpost' =>
            array (
                'comcode' => 'phlpost',
                'name' => '菲律宾（Philippine Postal）',
                'type' => 2,
            ),
        'pinsuxinda' =>
            array (
                'comcode' => 'pinsuxinda',
                'name' => '品速心达快递',
                'type' => 1,
            ),
        'pinxinkuaidi' =>
            array (
                'comcode' => 'pinxinkuaidi',
                'name' => '品信快递',
                'type' => 1,
            ),
        'pioneer' =>
            array (
                'comcode' => 'pioneer',
                'name' => '先锋国际快递',
                'type' => 3,
            ),
        'portugalseur' =>
            array (
                'comcode' => 'portugalseur',
                'name' => 'Portugal Seur',
                'type' => 3,
            ),
        'postelbe' =>
            array (
                'comcode' => 'postelbe',
                'name' => 'PostElbe',
                'type' => 2,
            ),
        'postenab' =>
            array (
                'comcode' => 'postenab',
                'name' => 'PostNord(Posten AB)',
                'type' => 2,
            ),
        'postennorge' =>
            array (
                'comcode' => 'postennorge',
                'name' => '挪威（Posten Norge）',
                'type' => 2,
            ),
        'ptt' =>
            array (
                'comcode' => 'ptt',
                'name' => '土耳其',
                'type' => 2,
            ),
        'purolator' =>
            array (
                'comcode' => 'purolator',
                'name' => 'Purolator',
                'type' => 3,
            ),
        'pzhjst' =>
            array (
                'comcode' => 'pzhjst',
                'name' => '急顺通',
                'type' => 1,
            ),
        'qdants' =>
            array (
                'comcode' => 'qdants',
                'name' => 'ANTS EXPRESS',
                'type' => 3,
            ),
        'qhxykd' =>
            array (
                'comcode' => 'qhxykd',
                'name' => '雪域快递',
                'type' => 1,
            ),
        'qskdyxgs' =>
            array (
                'comcode' => 'qskdyxgs',
                'name' => '千顺快递',
                'type' => 1,
            ),
        'quantium' =>
            array (
                'comcode' => 'quantium',
                'name' => 'Quantium',
                'type' => 3,
            ),
        'quanxintong' =>
            array (
                'comcode' => 'quanxintong',
                'name' => '全信通快递',
                'type' => 1,
            ),
        'qzx56' =>
            array (
                'comcode' => 'qzx56',
                'name' => '全之鑫物流',
                'type' => 1,
            ),
        'redexpress' =>
            array (
                'comcode' => 'redexpress',
                'name' => 'Red Express',
                'type' => 3,
            ),
        'republic' =>
            array (
                'comcode' => 'republic',
                'name' => '叙利亚(Syrian Post)',
                'type' => 2,
            ),
        'rhtexpress' =>
            array (
                'comcode' => 'rhtexpress',
                'name' => '睿和泰速运',
                'type' => 1,
            ),
        'rrthk' =>
            array (
                'comcode' => 'rrthk',
                'name' => '日日通国际',
                'type' => 3,
            ),
        'rwanda' =>
            array (
                'comcode' => 'rwanda',
                'name' => '卢旺达(Rwanda i-posita)',
                'type' => 3,
            ),
        's2c' =>
            array (
                'comcode' => 's2c',
                'name' => 'S2C',
                'type' => 3,
            ),
        'samoa' =>
            array (
                'comcode' => 'samoa',
                'name' => '萨摩亚(Samoa Post)',
                'type' => 2,
            ),
        'saudipost' =>
            array (
                'comcode' => 'saudipost',
                'name' => '沙特阿拉伯(Saudi Post)',
                'type' => 2,
            ),
        'scic' =>
            array (
                'comcode' => 'scic',
                'name' => '中加国际快递',
                'type' => 3,
            ),
        'scxingcheng' =>
            array (
                'comcode' => 'scxingcheng',
                'name' => '四川星程快递',
                'type' => 1,
            ),
        'selektvracht' =>
            array (
                'comcode' => 'selektvracht',
                'name' => 'Selektvracht',
                'type' => 3,
            ),
        'seur' =>
            array (
                'comcode' => 'seur',
                'name' => 'International Seur',
                'type' => 3,
            ),
        'sfau' =>
            array (
                'comcode' => 'sfau',
                'name' => '澳丰速递',
                'type' => 3,
            ),
        'sfift' =>
            array (
                'comcode' => 'sfift',
                'name' => '十方通物流',
                'type' => 1,
            ),
        'sfpost' =>
            array (
                'comcode' => 'sfpost',
                'name' => '曹操到',
                'type' => 3,
            ),
        'shanda56' =>
            array (
                'comcode' => 'shanda56',
                'name' => '衫达快运',
                'type' => 1,
            ),
        'shanghaikuaitong' =>
            array (
                'comcode' => 'shanghaikuaitong',
                'name' => '上海快通',
                'type' => 1,
            ),
        'shanghaiwujiangmmb' =>
            array (
                'comcode' => 'shanghaiwujiangmmb',
                'name' => '上海无疆for买卖宝',
                'type' => 3,
            ),
        'shangtuguoji' =>
            array (
                'comcode' => 'shangtuguoji',
                'name' => '尚途国际货运',
                'type' => 1,
            ),
        'shaoke' =>
            array (
                'comcode' => 'shaoke',
                'name' => '捎客物流',
                'type' => 1,
            ),
        'shd56' =>
            array (
                'comcode' => 'shd56',
                'name' => '商海德物流',
                'type' => 1,
            ),
        'shenma' =>
            array (
                'comcode' => 'shenma',
                'name' => '神马快递',
                'type' => 1,
            ),
        'shipsoho' =>
            array (
                'comcode' => 'shipsoho',
                'name' => '苏豪快递',
                'type' => 1,
            ),
        'shiyunkuaidi' =>
            array (
                'comcode' => 'shiyunkuaidi',
                'name' => '世运快递',
                'type' => 1,
            ),
        'shlexp' =>
            array (
                'comcode' => 'shlexp',
                'name' => 'SHL畅灵国际物流',
                'type' => 3,
            ),
        'shpostwish' =>
            array (
                'comcode' => 'shpostwish',
                'name' => 'wish邮',
                'type' => 3,
            ),
        'sihaiet' =>
            array (
                'comcode' => 'sihaiet',
                'name' => '四海快递',
                'type' => 1,
            ),
        'sihiexpress' =>
            array (
                'comcode' => 'sihiexpress',
                'name' => '四海捷运',
                'type' => 1,
            ),
        'sinoex' =>
            array (
                'comcode' => 'sinoex',
                'name' => '中外运速递-中文',
                'type' => 3,
            ),
        'sixroad' =>
            array (
                'comcode' => 'sixroad',
                'name' => '易普递',
                'type' => 1,
            ),
        'skynet' =>
            array (
                'comcode' => 'skynet',
                'name' => 'skynet',
                'type' => 3,
            ),
        'skynetmalaysia' =>
            array (
                'comcode' => 'skynetmalaysia',
                'name' => 'SkyNet Malaysia',
                'type' => 3,
            ),
        'skynetworldwide' =>
            array (
                'comcode' => 'skynetworldwide',
                'name' => 'skynetworldwide',
                'type' => 3,
            ),
        'skypost' =>
            array (
                'comcode' => 'skypost',
                'name' => '荷兰Sky Post',
                'type' => 2,
            ),
        'slovak' =>
            array (
                'comcode' => 'slovak',
                'name' => '斯洛伐克(Slovenská Posta)',
                'type' => 2,
            ),
        'slpost' =>
            array (
                'comcode' => 'slpost',
                'name' => '斯里兰卡(Sri Lanka Post)',
                'type' => 2,
            ),
        'southafrican' =>
            array (
                'comcode' => 'southafrican',
                'name' => '南非（South African Post Office）',
                'type' => 2,
            ),
        'speeda' =>
            array (
                'comcode' => 'speeda',
                'name' => '行必达',
                'type' => 1,
            ),
        'speedoex' =>
            array (
                'comcode' => 'speedoex',
                'name' => '申必达',
                'type' => 1,
            ),
        'staky' =>
            array (
                'comcode' => 'staky',
                'name' => '首通快运',
                'type' => 1,
            ),
        'starex' =>
            array (
                'comcode' => 'starex',
                'name' => '星速递',
                'type' => 1,
            ),
        'subaoex' =>
            array (
                'comcode' => 'subaoex',
                'name' => '速豹',
                'type' => 1,
            ),
        'sucheng' =>
            array (
                'comcode' => 'sucheng',
                'name' => '速呈宅配',
                'type' => 1,
            ),
        'sucmj' =>
            array (
                'comcode' => 'sucmj',
                'name' => '特急便物流',
                'type' => 1,
            ),
        'sudapost' =>
            array (
                'comcode' => 'sudapost',
                'name' => '苏丹（Sudapost）',
                'type' => 2,
            ),
        'sujievip' =>
            array (
                'comcode' => 'sujievip',
                'name' => '郑州速捷',
                'type' => 1,
            ),
        'supinexpress' =>
            array (
                'comcode' => 'supinexpress',
                'name' => '速品快递',
                'type' => 1,
            ),
        'szdpex' =>
            array (
                'comcode' => 'szdpex',
                'name' => '深圳DPEX',
                'type' => 3,
            ),
        'taimek' =>
            array (
                'comcode' => 'taimek',
                'name' => '天美快递',
                'type' => 1,
            ),
        'tanzania' =>
            array (
                'comcode' => 'tanzania',
                'name' => '坦桑尼亚(Tanzania Posts)',
                'type' => 2,
            ),
        'tcixps' =>
            array (
                'comcode' => 'tcixps',
                'name' => 'TCI XPS',
                'type' => 3,
            ),
        'thunderexpress' =>
            array (
                'comcode' => 'thunderexpress',
                'name' => '加拿大雷霆快递',
                'type' => 3,
            ),
        'tianxiang' =>
            array (
                'comcode' => 'tianxiang',
                'name' => '天翔快递',
                'type' => 1,
            ),
        'tianzong' =>
            array (
                'comcode' => 'tianzong',
                'name' => '天纵物流',
                'type' => 1,
            ),
        'timedg' =>
            array (
                'comcode' => 'timedg',
                'name' => '万家通快递',
                'type' => 1,
            ),
        'tmg' =>
            array (
                'comcode' => 'tmg',
                'name' => '株式会社T.M.G',
                'type' => 1,
            ),
        'tntitaly' =>
            array (
                'comcode' => 'tntitaly',
                'name' => 'TNT Italy',
                'type' => 3,
            ),
        'tntpostcn' =>
            array (
                'comcode' => 'tntpostcn',
                'name' => 'TNT Post',
                'type' => 2,
            ),
        'tny' =>
            array (
                'comcode' => 'tny',
                'name' => 'TNY物流',
                'type' => 3,
            ),
        'tongdaxing' =>
            array (
                'comcode' => 'tongdaxing',
                'name' => '通达兴物流',
                'type' => 1,
            ),
        'topshey' =>
            array (
                'comcode' => 'topshey',
                'name' => '顶世国际物流',
                'type' => 3,
            ),
        'tunisia' =>
            array (
                'comcode' => 'tunisia',
                'name' => '突尼斯EMS(Rapid-Poste)',
                'type' => 2,
            ),
        'uex' =>
            array (
                'comcode' => 'uex',
                'name' => 'UEX国际物流',
                'type' => 3,
            ),
        'ugoexpress' =>
            array (
                'comcode' => 'ugoexpress',
                'name' => '邮鸽速运',
                'type' => 1,
            ),
        'ukraine' =>
            array (
                'comcode' => 'ukraine',
                'name' => '乌克兰小包、大包(UkrPoshta)',
                'type' => 3,
            ),
        'ukrpost' =>
            array (
                'comcode' => 'ukrpost',
                'name' => '乌克兰小包、大包(UkrPost)',
                'type' => 2,
            ),
        'uluckex' =>
            array (
                'comcode' => 'uluckex',
                'name' => '优联吉运',
                'type' => 1,
            ),
        'upsfreight' =>
            array (
                'comcode' => 'upsfreight',
                'name' => 'UPS Freight',
                'type' => 3,
            ),
        'upsmailinno' =>
            array (
                'comcode' => 'upsmailinno',
                'name' => 'UPS Mail Innovations',
                'type' => 3,
            ),
        'uschuaxia' =>
            array (
                'comcode' => 'uschuaxia',
                'name' => '华夏国际速递',
                'type' => 3,
            ),
        'utaoscm' =>
            array (
                'comcode' => 'utaoscm',
                'name' => 'UTAO优到',
                'type' => 3,
            ),
        'vanuatu' =>
            array (
                'comcode' => 'vanuatu',
                'name' => '瓦努阿图(Vanuatu Post)',
                'type' => 2,
            ),
        'vctrans' =>
            array (
                'comcode' => 'vctrans',
                'name' => '越中国际物流',
                'type' => 3,
            ),
        'vietnam' =>
            array (
                'comcode' => 'vietnam',
                'name' => '越南小包(Vietnam Posts)',
                'type' => 2,
            ),
        'wanboex' =>
            array (
                'comcode' => 'wanboex',
                'name' => '万博快递',
                'type' => 1,
            ),
        'wanjiatong' =>
            array (
                'comcode' => 'wanjiatong',
                'name' => '宁夏万家通',
                'type' => 1,
            ),
        'wdm' =>
            array (
                'comcode' => 'wdm',
                'name' => '万达美',
                'type' => 1,
            ),
        'wenjiesudi' =>
            array (
                'comcode' => 'wenjiesudi',
                'name' => '文捷航空',
                'type' => 3,
            ),
        'winit' =>
            array (
                'comcode' => 'winit',
                'name' => '万邑通',
                'type' => 1,
            ),
        'wlfast' =>
            array (
                'comcode' => 'wlfast',
                'name' => '凡仕特物流',
                'type' => 1,
            ),
        'wowvip' =>
            array (
                'comcode' => 'wowvip',
                'name' => '沃埃家',
                'type' => 1,
            ),
        'wtdex' =>
            array (
                'comcode' => 'wtdex',
                'name' => 'WTD海外通',
                'type' => 3,
            ),
        'wygj168' =>
            array (
                'comcode' => 'wygj168',
                'name' => '万运国际快递',
                'type' => 3,
            ),
        'wzhaunyun' =>
            array (
                'comcode' => 'wzhaunyun',
                'name' => '微转运',
                'type' => 3,
            ),
        'xaetc' =>
            array (
                'comcode' => 'xaetc',
                'name' => '西安胜峰',
                'type' => 1,
            ),
        'xdshipping' =>
            array (
                'comcode' => 'xdshipping',
                'name' => '国晶物流',
                'type' => 1,
            ),
        'xianchengliansudi' =>
            array (
                'comcode' => 'xianchengliansudi',
                'name' => '西安城联速递',
                'type' => 1,
            ),
        'xiangteng' =>
            array (
                'comcode' => 'xiangteng',
                'name' => '翔腾物流',
                'type' => 1,
            ),
        'xiaocex' =>
            array (
                'comcode' => 'xiaocex',
                'name' => '小C海淘',
                'type' => 3,
            ),
        'xilaikd' =>
            array (
                'comcode' => 'xilaikd',
                'name' => '西安喜来快递',
                'type' => 1,
            ),
        'xinning' =>
            array (
                'comcode' => 'xinning',
                'name' => '新宁物流',
                'type' => 1,
            ),
        'xipost' =>
            array (
                'comcode' => 'xipost',
                'name' => '西邮寄',
                'type' => 3,
            ),
        'xtb' =>
            array (
                'comcode' => 'xtb',
                'name' => '鑫通宝物流',
                'type' => 1,
            ),
        'yatfai' =>
            array (
                'comcode' => 'yatfai',
                'name' => '一辉物流',
                'type' => 1,
            ),
        'ycgglobal' =>
            array (
                'comcode' => 'ycgglobal',
                'name' => 'YCG物流',
                'type' => 3,
            ),
        'yhtlogistics' =>
            array (
                'comcode' => 'yhtlogistics',
                'name' => '宇航通物流',
                'type' => 1,
            ),
        'yidihui' =>
            array (
                'comcode' => 'yidihui',
                'name' => '驿递汇速递',
                'type' => 1,
            ),
        'yihangmall' =>
            array (
                'comcode' => 'yihangmall',
                'name' => '易航物流',
                'type' => 1,
            ),
        'yilingsuyun' =>
            array (
                'comcode' => 'yilingsuyun',
                'name' => '亿领速运',
                'type' => 1,
            ),
        'yiqiguojiwuliu' =>
            array (
                'comcode' => 'yiqiguojiwuliu',
                'name' => '一柒国际物流',
                'type' => 3,
            ),
        'yiqisong' =>
            array (
                'comcode' => 'yiqisong',
                'name' => '一起送',
                'type' => 1,
            ),
        'yishunhang' =>
            array (
                'comcode' => 'yishunhang',
                'name' => '亿顺航',
                'type' => 3,
            ),
        'yisong' =>
            array (
                'comcode' => 'yisong',
                'name' => '宜送',
                'type' => 1,
            ),
        'yiyou' =>
            array (
                'comcode' => 'yiyou',
                'name' => '易邮速运',
                'type' => 1,
            ),
        'yjhgo' =>
            array (
                'comcode' => 'yjhgo',
                'name' => '武汉优进汇',
                'type' => 1,
            ),
        'ykouan' =>
            array (
                'comcode' => 'ykouan',
                'name' => '洋口岸',
                'type' => 3,
            ),
        'yodel' =>
            array (
                'comcode' => 'yodel',
                'name' => 'YODEL',
                'type' => 3,
            ),
        'youjia' =>
            array (
                'comcode' => 'youjia',
                'name' => '友家速递',
                'type' => 1,
            ),
        'youlai' =>
            array (
                'comcode' => 'youlai',
                'name' => '邮来速递',
                'type' => 1,
            ),
        'ypsd' =>
            array (
                'comcode' => 'ypsd',
                'name' => '壹品速递',
                'type' => 1,
            ),
        'ytky168' =>
            array (
                'comcode' => 'ytky168',
                'name' => '运通快运',
                'type' => 1,
            ),
        'yuandun' =>
            array (
                'comcode' => 'yuandun',
                'name' => '远盾物流',
                'type' => 1,
            ),
        'yuezhongsh' =>
            array (
                'comcode' => 'yuezhongsh',
                'name' => '粤中国际货运代理（上海）有限公司',
                'type' => 3,
            ),
        'yufeng' =>
            array (
                'comcode' => 'yufeng',
                'name' => '御风速运',
                'type' => 1,
            ),
        'yyexp' =>
            array (
                'comcode' => 'yyexp',
                'name' => '西安运逸快递',
                'type' => 1,
            ),
        'zenzen' =>
            array (
                'comcode' => 'zenzen',
                'name' => '三三国际物流',
                'type' => 3,
            ),
        'zhdwl' =>
            array (
                'comcode' => 'zhdwl',
                'name' => '众辉达物流',
                'type' => 1,
            ),
        'zhitengwuliu' =>
            array (
                'comcode' => 'zhitengwuliu',
                'name' => '志腾物流',
                'type' => 1,
            ),
    );

    public function getCompany(){
        return $this->company;
    }

}
?>