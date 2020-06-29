<?php
/**
 * redis缓存
 */
namespace Common\Lib;

class SavorRedis {

    private static $_instance;
    // 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;

    // Slave 句柄标记
    private $_sn = 0;

    // 服务器连接句柄
    private $_linkHandle = array(
        'master'=>null,// 只支持一台 Master
        'slave'=>array(),// 可以有多台 Slave
    );

    /**
     * 构造函数
     *
     * @param boolean $isUseCluster 是否采用 M/S 方案
     */
    public function __construct($isUseCluster=true){
        $this->_isUseCluster = $isUseCluster;
        $this->connect();
    }

    public static function getInstance(){
        if(!(self::$_instance instanceof self)){
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     *
     * @param array $config Redis服务器配置
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($config="db1",$select="0"){

    	//$CI =& get_instance();
    	$redisconfig=C('REDIS_CONFIG');

    	if (!isset($redisconfig[$config]))
    	{
    		//show_error("获取不到配置信息：".$config);
    		//exit;
    		return false;
    	}else
    	{
    		$redisconfig=$redisconfig[$config];
    	}
    	foreach ($redisconfig as $val)
    	{
    		$config=array();
    		$isMaster=$val['isMaster'];
    		$config['host']=$val['host'];
    		$config['port']=$val['port'];
    		$config['password'] = $val['password'];
	        // 设置 Master 连接
	        if($isMaster){
	            $this->_linkHandle['master'] = new \Redis();
	            $ret[] = $this->_linkHandle['master']->connect($config['host'],$config['port']);
	            $ret[] = $this->_linkHandle['master']->auth($config['password']);
	            $this->_linkHandle['master']->select($select);
	        }else{
	            // 多个 Slave 连接
	            $this->_linkHandle['slave'][$this->_sn] = new \Redis;
	            $ret[]= $this->_linkHandle['slave'][$this->_sn]->connect($config['host'],$config['port']);
	            $ret[]= $this->_linkHandle['slave'][$this->_sn]->auth($config['password']);
	            $this->_linkHandle['slave'][$this->_sn]->select($select);
	            ++$this->_sn;
	        }
    	}
        return $ret;
    }
	function select($select=0)
	{
		$this->getRedis()->select($select);
		foreach($this->_linkHandle['slave'] as $key=>$val)
		{
			$val->select($select);
		}

	}
    function flushadb()
    {
        $this->getRedis()->flushdb();

    }

    /**
     * 关闭连接
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag=2){
        switch($flag){
            // 关闭 Master
            case 0:
            	$obj_redis = $this->getRedis();
                if(!empty($obj_redis)){
                	$obj_redis->close();
                }
            break;
            // 关闭 Slave
            case 1:
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
            break;
            // 关闭所有
            case 2:
            	$obj_redis = $this->getRedis();
                if(!empty($obj_redis)){
                	$obj_redis->close();
                }
                for($i=0; $i<$this->_sn; ++$i){
                    $this->_linkHandle['slave'][$i]->close();
                }
            break;
        }
        return true;
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster=true,$slaveOne=true){
        // 只返回 Master
        if($isMaster){
            return $this->_linkHandle['master'];
        }else{
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
    }

    /**
     * 写缓存
     *
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function set($key, $value, $expire=0){
        // 永不超时
        if($expire == 0){
            $ret = $this->getRedis()->set($key, $value);
        }else{
            $ret = $this->getRedis()->setex($key, $expire, $value);
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function get($key){
        // 是否一次取多个值
        $func = is_array($key) ? 'mGet' : 'get';
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->{$func}($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($key);
    }

    /**
     * 读缓存 hash
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function hget($key,$find){
    	// 是否一次取多个值
    	if (!$find)
    	{
    		return "";
    	}
    	$func ='hget';
    	// 没有使用M/S
    	if(! $this->_isUseCluster){
    		return $this->getRedis()->{$func}($key,$find);
    	}
    	// 使用了 M/S
    	return $this->_getSlaveRedis()->{$func}($key,$find);
    }

    /**
     * 读缓存 hash
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function hmGet($key,$find){
    	// 是否一次取多个值
    	if (!is_array($find))
    	{
    		return "";
    	}
    	$func ='hmGet';
    	// 没有使用M/S
    	if(! $this->_isUseCluster){
    		return $this->getRedis()->{$func}($key,$find);
    	}
    	// 使用了 M/S
    	return $this->_getSlaveRedis()->{$func}($key,$find);
    }


    /**
     * 读缓存 hash all
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function hgetall($key){

    	$func ='hgetall';
    	// 没有使用M/S
    	if(! $this->_isUseCluster){
    		return $this->getRedis()->{$func}($key);
    	}
    	// 使用了 M/S
    	return $this->_getSlaveRedis()->{$func}($key);
    }


    public function sort($key,$array){

        $func ='sort';
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->{$func}($key,$array);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($key,$array);
    }
    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function setnx($key, $value){

        return $this->getRedis()->setnx($key, $value);
    }
    /**
     * 设置缓存 hash
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function hSet($key,$find, $value){
    	return $this->getRedis()->hset($key,$find, $value);
    }
    /**
     * 批量设置缓存 hash
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function hMset($key,$findarray){
    	return $this->getRedis()->hMset($key,$findarray);
    }

    public function hincrby($key, $member, $val){
    	return $this->getRedis()->hincrby($key, $member, $val);
    }

    /**
     * Sets an expiration date (a timeout) on an item.
     *
     * @param   string  $key    The key that will disappear.
     * @param   int     $ttl    The key's remaining Time To Live, in seconds.
     * @return  bool:   TRUE in case of success, FALSE in case of failure.
     * @link    http://redis.io/commands/expire
     * @example
     * <pre>
     * $redis->set('x', '42');
     * $redis->setTimeout('x', 3);  // x will disappear in 3 seconds.
     * sleep(5);                    // wait 5 seconds
     * $redis->get('x');            // will return `FALSE`, as 'x' has expired.
     * </pre>
     */
    public function expire( $key, $ttl ) {
        return $this->getRedis()->expire($key,$ttl);
    }
    /**
     * 查找key是否存在
     *
     * @param string || array $key
     */
    public function keys($key)
    {
    	return $this->getRedis()->keys($key);
    }

    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function remove($key){
        // $key => "key1" || array('key1','key2')
        return $this->getRedis()->delete($key);
    }


	/**
     * 删除哈希缓存
     *
     * @param string || array $key 缓存KEY
     * @param string $find
     * @return int 删除的健的数量
     */
    public function hdel($key,$find){
        return $this->getRedis()->hdel($key,$find);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function incr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->incr($key);
        }else{
            return $this->getRedis()->incrBy($key, $default);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function decr($key,$default=1){
        if($default == 1){
            return $this->getRedis()->decr($key);
        }else{
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    public function sadd($key, $val){
    	$ret = $this->getRedis()->sadd($key,$val);
    	return $ret;
    }

    public function sRem($key, $member){
    	return $this->getRedis()->sRem($key, $member);
    }

    public function sMembers($key){
    	if(! $this->_isUseCluster){
    		return $this->getRedis()->sMembers($key);
    	}
    	// 使用了 M/S
    	return $this->_getSlaveRedis()->sMembers($key);
    }
    /**
     * 添空当前数据库
     *
     * @return boolean
     */
    public function clear(){
        return $this->getRedis()->flushDB();
    }
    public function zAdd($key, $score ,$member ){
    	if(empty($member)){
    		return false;
    	}
    	$ret = $this->getRedis()->zAdd($key,$score,$member);
    	return $ret;
    }
    public function zDelete($key,$value){
    	$ret = $this->getRedis()->zDelete($key,$value);
    	return $ret;
    }
    public function zRange($key,$star,$end,$score= false){
    	// 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->zRange($key,$star,$end,$score);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->zRange($key,$star,$end,$score);
    }

    public function zRank($key,$member){
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->zRank($key,$member);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->zRank($key,$member);
    }

    public function zRangeByScore($key, $star, $end,$condition = array())
    {
    	// 没有使用M/S
        if(! $this->_isUseCluster){
       		return $this->getRedis()->zRangeByScore($key,$star,$end,$condition);
        }
        // 使用了 M/S
       	return $this->_getSlaveRedis()->zRangeByScore($key,$star,$end,$condition);
    }
    public function zScore($key, $member){
    	// 没有使用M/S
    	if(! $this->_isUseCluster){
    		return $this->getRedis()->zScore($key, $member);
    	}
    	// 使用了 M/S
    	return $this->_getSlaveRedis()->zScore($key, $member);
    }

    public function zremrangebyrank ($key, $star, $end){
        return $this->getRedis()->zremrangebyrank($key, $star, $end);
    }

    public function zRem($key, $member){
        return $this->getRedis()->zRem($key, $member);
    }

    public function zIncrby($key, $member, $num){
    	if(empty($member)){
    		return false;
    	}
    	$ret = $this->getRedis()->zIncrby($key, $num, $member);
    	return $ret;
    }

    public function multi(){
        $ret = $this->getRedis()->multi();
        return $ret;
    }

    public function exec(){
        $ret = $this->getRedis()->exec();
        return $ret;
    }

    public function watch($key){
        return $this->getRedis()->watch($key);
    }

    public function unwatch(){
        $ret = $this->getRedis()->unwatch();
        return $ret;
    }

    public function zunionstore($newKey, $keyArray){
        $ret = $this->getRedis()->zunionstore($newKey, $keyArray);
        return $ret;
    }

    public function zinterstore($newKey, $keyArray){
        $ret = $this->getRedis()->zinterstore($newKey, $keyArray);
        return $ret;
    }

    public function zcard($key){
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->zcard($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->zcard($key);
    }

    /*=====================GEO相关方法 start=======================*/
    protected function minVersionCheck($version) {
        return version_compare($this->version, $version, "ge");
    }

    protected function _checkGeoUnit($unit){
        $unitArray = ['m','km','mi','ft']; //米，千米，英里，英尺
        if(!in_array($unit, $unitArray)){
            return false;
        }
        return true;
    }

    /**
     *添加geo位置信息
     * @param unknown_type $key
     * @param unknown_type $member ['memeber'=>location]
     * @return boolean
     */
    public function geoAdd($key, $members = []){
        if(empty($key) || empty($members)){
            return false;
        }

        $args[] = $key;
        foreach ($members as $member => $location) {
            if(!is_array($location)){
                $location = explode(",", $location);
            }
            list($lng, $lat) = $location;
            $args = array_merge($args, Array($lng, $lat, $member));
        }
        return call_user_func_array(Array($this->getRedis(), 'geoadd'), $args);
    }

    /**
     * 获取指定的成员位置信息
     * @param unknown_type $key
     * @param unknown_type $member
     */
    public function geoGet($key, $members){
        if(empty($key) || empty($members)){
            return false;
        }
        $args[] = $key;
        if(!is_array($members)){
            $args[] = $members;
        }else{
            $args = array_merge($args, $members);
        }
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return call_user_func_array(Array($this->getRedis(), 'geopos'), $args);
        }
        // 使用了 M/S
        return call_user_func_array(Array($this->_getSlaveRedis(), 'geopos'), $args);
    }

    /**
     * 获取两个成员直接的距离
     * @param unknown_type $key
     * @param unknown_type $memberOne
     * @param unknown_type $memberTwo
     * @param unknown_type $unit
     * @return boolean
     */
    public function geoDist($key, $memberOne, $memberTwo, $unit='m'){
        if($this->_checkGeoUnit($unit) === false){
            return false;
        }

        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->geodist($key, $memberOne, $memberTwo, $unit);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->geodist($key, $memberOne, $memberTwo, $unit);
    }

    /**
     *以给定的经纬度为中心， 返回键包含的位置元素当中， 与中心的距离不超过给定最大距离的所有位置元素
     * @param unknown_type $key
     * @param unknown_type $location
     * @param unknown_type $unit
     * $opts = Array('WITHCOORD', 'WITHDIST', 'WITHHASH', ASC|DESC, COUNT=>count);
     */
    public function geoRadius($key, $location, $radius, $unit='m', $opts=''){
        if($this->_checkGeoUnit($unit) === false){
            return false;
        }
        if(!is_array($location)){
            $location = explode(",", $location);
        }
        list($lng, $lat) = $location;

        $args = [];
        if(!is_array($opts)){
            $args[] = $opts;
        }else{
            $args = $opts;
        }

        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->georadius($key, $lng, $lat, $radius, $unit, $args);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->georadius($key, $lng, $lat, $radius, $unit, $args);
    }

    /**
     *以给定的位置元素为中心， 返回键包含的位置元素当中， 与中心的距离不超过给定最大距离的所有位置元素
     * @param unknown_type $key
     * @param unknown_type $location
     * @param unknown_type $unit
     * $opts = Array('WITHCOORD', 'WITHDIST', 'WITHHASH', ASC|DESC, COUNT=>count);
     */
    public function geoRadiusByMember($key, $member, $radius, $unit='m', $opts=''){
        if($this->_checkGeoUnit($unit) === false){
            return false;
        }

        $args = [];
        if(!is_array($opts)){
            $args[] = $opts;
        }else{
            $args = $opts;
        }

        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->georadiusbymember($key, $member, $radius, $unit, $args);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->georadiusbymember($key, $member, $radius, $unit, $args);
    }

    /**
     *查找一个或多个元素的 Geohash
     * @param unknown_type $key
     * @param unknown_type $members
     * @return boolean|mixed
     */
    public function geoHash($key, $members = []){
        if(empty($key) || empty($members)){
            return false;
        }
        $args[] = $key;
        if(!is_array($members)){
            $args[] = $members;
        }else{
            $args = array_merge($args, $members);
        }

        // 没有使用M/S
        if(! $this->_isUseCluster){
            return call_user_func_array(Array($this->getRedis(), 'geohash'), $args);
        }
        // 使用了 M/S
        return call_user_func_array(Array($this->_getSlaveRedis(), 'geohash'), $args);
    }
    /*=====================GEO相关方法 end=======================*/

    /* =================== 以下私有方法 =================== */

    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     *
     * @return redis object
     */
    private function _getSlaveRedis(){
        // 就一台 Slave 机直接返回
        if($this->_sn <= 1){
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id,$m=10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for($i=0;$i<$l;$i++)
        {
            //相加模式HASH
            $h += substr($b,$i*2,2);
        }
        $hash = ($h*1)%$m;
        return $hash;
    }
    public function rpush($key,$value){
        return $this->getRedis()->rpush($key,$value);
    }
    public function lgetrange($key, $start, $end){
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->lgetrange($key,$start,$end);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->lgetrange($key,$start,$end);
    }
    public function lsize($key){
        // 没有使用M/S
        if(! $this->_isUseCluster){
            return $this->getRedis()->lsize($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->lsize($key);
    }
    public function lpop($key){
        return $this->getRedis()->lpop($key);
    }
    function __destruct()
	{
		$this->close();
	}
}// End Class
