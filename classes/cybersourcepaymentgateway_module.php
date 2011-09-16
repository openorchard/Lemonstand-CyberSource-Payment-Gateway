<?php
	require_once dirname(__FILE__) . '/cybersourcepaymentgateway_extendedsoapclient.php';
	
	class CyberSourcePaymentGateway_Module extends Core_ModuleBase {
		/**
		 * Creates the module information object
		 * @return Core_ModuleInfo
		 */
		protected function createModuleInfo() {
			return new Core_ModuleInfo(
				"CyberSource Payment Gateway Module",
				"Adds custom payment type for CyberSource payment gateway processing",
				"Philip Schalm" );
		}
	}