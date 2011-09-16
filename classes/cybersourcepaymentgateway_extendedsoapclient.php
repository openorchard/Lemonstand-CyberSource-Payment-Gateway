<?php
	
	class CyberSourcePaymentGateway_ExtendedSoapClient extends SoapClient {
		public $host_obj = null;
		public function __construct($wsdl, $options = null, $host_obj = null) {
			if (!$host_obj) {
				throw new Exception("CyberSource ExtendedSoapClient must be called with a third parameter");
			}
			$this->host_obj = $host_obj;
			parent::__construct($wsdl, $options);
		}


		public function __doRequest($request, $location, $action, $version, $one_way = 0) {
			$user = $this->host_obj->merchant_id;
			$password = $this->host_obj->transaction_key;
			
			$soapHeader = "<SOAP-ENV:Header xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\"><wsse:Security SOAP-ENV:mustUnderstand=\"1\"><wsse:UsernameToken><wsse:Username>$user</wsse:Username><wsse:Password Type=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText\">$password</wsse:Password></wsse:UsernameToken></wsse:Security></SOAP-ENV:Header>";
			
			$requestDOM = new DOMDocument('1.0');
			$soapHeaderDOM = new DOMDocument('1.0');

			try {
				$requestDOM->loadXML($request);
				$soapHeaderDOM->loadXML($soapHeader);

				$node = $requestDOM->importNode($soapHeaderDOM->firstChild, true);
				$requestDOM->firstChild->insertBefore($node, $requestDOM->firstChild->firstChild);
				$request = $requestDOM->saveXML();
			} catch (DOMException $e) {
					throw new Exception('Error adding UsernameToken: ' . $e->code);
			}
			return parent::__doRequest($request, $location, $action, $version, $one_way);
		}
	}