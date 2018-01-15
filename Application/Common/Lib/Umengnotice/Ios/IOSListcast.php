<?php

namespace Umengnotice\Ios;

use Umengnotice\IOSNotification;


class IOSListcast extends IOSNotification {
	function __construct() {
		parent::__construct();
		$this->data["type"] = "listcast";
		$this->data["device_tokens"] = NULL;
	}

	function sendIOSListcast($pam) {
		try {
			$this->setAppMasterSecret($this->umeng_ios_master);
			$this->setPredefinedKeyValue("appkey",           $this->umeng_ios_key);
			$this->setPredefinedKeyValue("timestamp",        $pam['time']);
			// Set your device tokens here
			$this->setPredefinedKeyValue("device_tokens",    $pam['device_tokens']);
			$this->setPredefinedKeyValue("alert", $pam['alert']);
			$this->setPredefinedKeyValue("badge", $pam['badge']);
			$this->setPredefinedKeyValue("sound", $pam['sound']);
			$this->setPredefinedKeyValue("production_mode", $pam['production_mode']);
			// Set customized fields
			$this->setCustomizedField($pam['customm']);
			print("Sending unicast notification, please wait...\r\n");
			$this->send();
			print("Sent SUCCESS\r\n");
		} catch (Exception $e) {
			print("Caught exception: " . $e->getMessage());
		}
	}

}