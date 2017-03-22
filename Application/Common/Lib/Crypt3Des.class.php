<?php
namespace Common\Lib;
class Crypt3Des {
	private $key ; 
	
	function __construct($key){
		$this->key = $key;
	}
	
	/**
	 * 数据加密
	 */
	function encrypt($str){
		$key = str_pad($this->key,8,'0');
		$size = mcrypt_get_block_size(MCRYPT_DES,'ecb');
		$str = $this->pkcs5_pad($str, $size);
		$td = mcrypt_module_open(MCRYPT_DES, '', 'ecb', '');
		$iv = @mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		@mcrypt_generic_init($td, $key, $iv);
		$data = mcrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = bin2hex($data);
		return $data;
	}
	
	function encryptText($str) {
		//（1）用0补全密钥到8位；
		$key = str_pad($this->key,8,'0');
		//（2）用pkcs#5方式对字符串进行填充；
        $padded = $this->pkcs5_pad($str, mcrypt_get_block_size(MCRYPT_DES, MCRYPT_MODE_ECB));
        //（3）用ECB工作模式进行DES加密；
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES,MCRYPT_MODE_ECB), MCRYPT_RAND);	
        $data = mcrypt_encrypt(MCRYPT_DES, $key, $padded, MCRYPT_MODE_ECB, $iv);
        //（4）将加密后的二进制数据转换成十六进制。
        $data = bin2hex($data);
        return $data;  
    }  
  
    function decryptText($str) {
		$key = str_pad($this->key,8,'0');
    	$str = pack('H*', $str);
		$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_DES,MCRYPT_MODE_ECB), MCRYPT_RAND);
        $data = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB, $iv);
        $data = $this->pkcs5_unpad($data);
        return $data;  
    }
	
	/**
	 * 数据解密
	 */
	function decrypt($str){
		$key = str_pad($this->key,8,'0');
		$str = pack('H*', $str);
		$td = mcrypt_module_open(MCRYPT_DES,'','ecb','');
		$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td),MCRYPT_RAND);
		$ks = mcrypt_enc_get_key_size($td);
		@mcrypt_generic_init($td, $key, $iv);
		$data = mdecrypt_generic($td, $str);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$data = $this->pkcs5_unpad($data);
		return $data;
	}

	function pkcs5_pad ($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		$text = $text . str_repeat(chr($pad), $pad);
		return $text;
	}

	function pkcs5_unpad($text){
		$pad = ord($text{strlen($text)-1});
		if ($pad > strlen($text)) {
			return false;
		}
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad){
			return false;
		}
		return substr($text, 0, -1 * $pad);
	}

	function PaddingPKCS7($data) {
		$block_size = mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_CBC);
		$padding_char = $block_size - (strlen($data) % $block_size);
		$data .= str_repeat(chr($padding_char),$padding_char);
		return $data;
	}
}
?>