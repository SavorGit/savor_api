<?php
namespace Umengnotice\Android;

use Umengnotice\AndroidNotification;



class AndroidListcast extends AndroidNotification {
	function __construct() {
		parent::__construct();
		$this->data["type"] = "listcast";
		$this->data["device_tokens"] = NULL;
	}


	public function sendAndroidListcast($pam) {
		try {

			$this->setAppMasterSecret($this->umeng_appmaster);
			$this->setPredefinedKeyValue("appkey",           $this->umeng_appkey);
			$this->setPredefinedKeyValue("timestamp",        $pam['time']);
			// Set your device tokens here
			$this->setPredefinedKeyValue("device_tokens",    $pam['device_tokens']);
			$this->setPredefinedKeyValue("ticker",           $pam['ticker']);

			$this->setPredefinedKeyValue("title",            $pam['title']);
			$this->setPredefinedKeyValue("text",             $pam['text']);
			$this->setPredefinedKeyValue("after_open",       $pam['after_open']);
			$this->setPredefinedKeyValue("custom",           $pam['custom']);
			// Set 'production_mode' to 'false' if it's a test device.
			// For how to register a test device, please see the developer doc.
			$this->setPredefinedKeyValue("production_mode", $pam['production_mode']);
			$this->setPredefinedKeyValue("display_type", $pam['display_type']);

			// Set extra fields
			$this->setExtraField($pam['extra']);

			//print("Sending unicast notification, please wait...\r\n");

			$this->send();
			//print("Sent SUCCESS\r\n");
		} catch (\Exception $e) {
			//print("Caught exception: " . $e->getMessage());
		}
	}

}