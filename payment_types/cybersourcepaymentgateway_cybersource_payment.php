<?

	class CyberSourcePaymentGateway_CyberSource_Payment extends Shop_PaymentType
	{
		
		const WSDL_URL_TEST = 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl';
		const WSDL_URL_LIVE = 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl';
		
		const RESPONSE_CODE_SUCCESS = 100;
		
		/**
		 * Returns information about the payment type
		 * Must return array: array(
		 *		'name'=>'Authorize.net', 
		 *		'custom_payment_form'=>false,
		 *		'offline'=>false,
		 *		'pay_offline_message'=>null
		 * ).
		 * Use custom_paymen_form key to specify a name of a partial to use for building a back-end
		 * payment form. Usually it is needed for forms which ACTION refer outside web services, 
		 * like PayPal Standard. Otherwise override build_payment_form method to build back-end payment
		 * forms.
		 * If the payment type provides a front-end partial (containing the payment form), 
		 * it should be called in following way: payment:name, in lower case, e.g. payment:authorize.net
		 *
		 * Set index 'offline' to true to specify that the payments of this type cannot be processed online 
		 * and thus they have no payment form. You may specify a message to display on the payment page
		 * for offline payment type, using 'pay_offline_message' index.
		 *
		 * @return array
		 */
		public function get_info()
		{
			return array(
				'name'=>'CyberSource',
				'custom_payment_form'=>'backend_payment_form.htm',
				'description'=>'CyberSource payment method'
			);
		}

		/**
		 * Builds the payment type administration user interface 
		 * For drop-down and radio fields you should also add methods returning 
		 * options. For example, of you want to have Sizes drop-down:
		 * public function get_sizes_options();
		 * This method should return array with keys corresponding your option identifiers
		 * and values corresponding its titles.
		 * 
		 * @param $host_obj ActiveRecord object to add fields to
		 * @param string $context Form context. In preview mode its value is 'preview'
		 */
		public function build_config_ui($host_obj, $context = null)
		{
			$host_obj->add_field('test_mode', 'Test Mode')->tab('Configuration')->renderAs(frm_onoffswitcher)->comment('Use the CyberSource Test Environment to try out Website Payments. This will use the test URL for the gateway.', 'above');
			if ($context !== 'preview')
			{
				$host_obj->add_field('merchant_id', 'Merchant ID', 'left')->tab('Configuration')->renderAs(frm_text)->comment('Please provide your Merchant ID', 'above')->validation()->fn('trim')->required('Please provide merchant ID.');
				$host_obj->add_field('transaction_key', 'Transaction Key', 'left')->tab('Configuration')->renderAs(frm_text)->comment('Please provide your SOAP Toolkit Seacurity Key', 'above')->validation()->fn('trim')->required('Please provide SOAP Toolkit Security Key.');
				


				$host_obj->add_field('order_status', 'Order Status', 'left')->tab('Configuration')->renderAs(frm_dropdown)->comment('Select status to assign the order in case of successful payment.', 'above');
				$host_obj->add_field('cancelled_order_status', 'Cancelled Order Status', 'right')->tab('Configuration')->renderAs(frm_dropdown)->comment('Select status to assign the order in case of unsuccessful payment.', 'above');
			}
			
			$host_obj->add_field('cancel_page', 'Cancel Page', 'full')->tab('Configuration')->renderAs(frm_dropdown)->comment('Page to which the customer’s browser is redirected upon unsuccessful payment.', 'above')->tab('General Parameters')->previewNoRelation()->referenceSort('title');
		}
		
		public function get_order_status_options($current_key_value = -1)
		{
			if (-1 == $current_key_value)
				return Shop_OrderStatus::create()->order('name')->find_all()->as_array('name', 'id');

			return Shop_OrderStatus::create()->find($current_key_value)->name;
		}
		
		public function get_cancelled_order_status_options($current_key_value = -1) {
			return $this->get_order_status_options($current_key_value);
		}

		/**
		 * Validates configuration data before it is saved to database
		 * Use host object field_error method to report about errors in data:
		 * $host_obj->field_error('max_weight', 'Max weight should not be less than Min weight');
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 */
		public function validate_config_on_save($host_obj)
		{

		}
		
		/**
		 * Validates configuration data after it is loaded from database
		 * Use host object to access fields previously added with build_config_ui method.
		 * You can alter field values if you need
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 */
		public function validate_config_on_load($host_obj)
		{

		}

		/**
		 * Initializes configuration data when the payment method is first created
		 * Use host object to access and set fields previously added with build_config_ui method.
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 */
		public function init_config_data($host_obj)
		{
			$host_obj->test_mode = 1;
		}
		
		public function get_cancel_page_options($current_key_value = -1)
		{
			if ($current_key_value == -1)
				return Cms_Page::create()->order('title')->find_all()->as_array('title', 'id');

			return Cms_Page::create()->find($current_key_value)->title;
		}
		
		public function get_hidden_fields($host_obj, $order, $backend = false)
		{
			$result['order_id'] = $order->id;
			return $result;
		}
		
		protected function init_validation_obj()
		{
			$validation = new Phpr_Validation();
			$validation->add('FIRSTNAME', 'Cardholder first name')->fn('trim')->required('Please specify a cardholder first name.');
			$validation->add('LASTNAME', 'Cardholder last name')->fn('trim')->required('Please specify a cardholder last name.');
			$validation->add('EXPDATE_MONTH', 'Expiration month')->fn('trim')->required('Please specify a card expiration month.')->regexp('/^[0-9]*$/', 'Credit card expiration month can contain only digits.');
			$validation->add('EXPDATE_YEAR', 'Expiration year')->fn('trim')->required('Please specify a card expiration year.')->regexp('/^[0-9]*$/', 'Credit card expiration year can contain only digits.');

			$validation->add('ACCT', 'Credit card number')->fn('trim')->required('Please specify a credit card number.')->regexp('/^[0-9]*$/', 'Please specify a valid credit card number. Credit card number can contain only digits.')->minLength(13, "Invalid credit card number")->maxLength(16, "Invalid credit card number");
			$validation->add('CVV2', 'CVV2')->fn('trim')->required('Please specify CVV2 value.')->regexp('/^[0-9]*$/', 'Please specify a CVV2 number. CVV2 can contain only digits.')->minLength(3, "Invalid credit card code (CVV2)")->maxLength(4, "Invalid credit card code (CVV2)");

			return $validation;
		}
		
		protected function get_wsdl($host_obj) {
			return $host_obj->test_mode ? self::WSDL_URL_TEST : self::WSDL_URL_LIVE;
		}
		
		/**
		 * Processes payment using passed data
		 * @param array $data Posted payment form data
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 * @param $order Order object
		 */
		public function process_payment_form($data, $host_obj, $order, $back_end = false)
		{
			/*
			 * Validate input data
			 */
			$validation = $this->init_validation_obj();

			try
			{
				if (!$validation->validate($data))
					$validation->throwException();
			} catch (Exception $ex)
			{
				$this->log_payment_attempt($order, $ex->getMessage(), 0, array(), array(), null);
				throw $ex;
			}
			
			$soap = new CyberSourcePaymentGateway_ExtendedSoapClient($this->get_wsdl($host_obj), array('trace' => true), $host_obj);
			$request = (object)(array(
				'merchantID' => $host_obj->merchant_id,
				'merchantReferenceCode' => md5(microtime() . rand(0, time())),
				'clientLibrary' => 'PHP',
				'clientLibraryVersion' => phpversion(),
				'clientEnvironment' => php_uname(),
				'ccAuthService' => array('run' => 'true'),
				'ccCaptureService' => array('run' => 'true')
			));
			
			$bill_to = array(
				'firstName' => $validation->fieldValues['FIRSTNAME'],
				'lastName' => $validation->fieldValues['LASTNAME'],
				'street1' => $order->billing_street_addr,
				'city' => $order->billing_city,
				'postalCode' => $order->billing_zip,
				'country' => $order->billing_country->code,
				'email' => $order->billing_email,
				'phoneNumber' => $order->billing_phone,
				'company' => $order->billing_company,
				'ipaddress' => $_SERVER['REMOTE_ADDR']
			);
			if ($order->billing_state)
				$bill_to['state'] = $order->billing_state->code;
			
			$request->billTo = (object)(array_filter($bill_to));
			
			$ship_to = array(
				'firstName' => $order->shipping_first_name,
				'lastName' => $order->shipping_last_name,
				'street1' => $order->shipping_street_addr,
				'city' => $order->shipping_city,
				'postalCode' => $order->shipping_zip,
				'country' => $order->shipping_country->code,
				'email' => $order->shipping_email,
				'phoneNumber' => $order->shipping_phone,
				'company' => $order->shipping_company
			);
			
			if ($order->shipping_state)
				$ship_to['state'] = $order->shipping_state->code;
			
			$request->shipTo = (object)(array_filter($ship_to));
			
			$card_array = array(
				'fullName' => $validation->fieldValues['FIRSTNAME'] . ' ' . $validation->fieldValues['LASTNAME'],
				'accountNumber' => $validation->fieldValues['ACCT'],
				'expirationMonth' => $validation->fieldValues['EXPDATE_MONTH'],
				'expirationYear' => $validation->fieldValues['EXPDATE_YEAR'],
				'cvNumber' => $validation->fieldValues['CVV2'],
			);
			
			$request->card = (object)array_filter($card_array);
			
			$converter = Shop_CurrencyConverter::create();
			
			$purchase = (object)(array(
				'currency' => 'USD',
				'grandTotalAmount' => $converter->convert(3025, Shop_CurrencySettings::get()->code, 'USD')
			));
			
			$request->purchaseTotals = $purchase;
			
			$log_fields = array(
				'message' => 'Unsuccessful payment',
				'status' => 0,
				'request_array' => array(),
				'response_array' => array(),
				'response_text' => '',
				'cvv_response_code' => -1,
				'cvv_response_text' => '',
				'avs_response_code' => -1,
				'avs_response_text' => ''
			);
			
			try {
				$response = $soap->runTransaction($request);
				
				$xml = new DOMDocument();
				$xml->loadxml($soap->__getLastRequest());
				// Remove CC information
				$xpath = new DOMXpath($xml);
				foreach ($xpath->query('//ns1:accountNumber') as $node) {
					$text = str_pad('', strlen($node->textContent)-4, '*') . substr($node->textContent, -4);
					$node->firstChild->replaceData(0,9999,$text);
				}

				foreach ($xpath->query('//ns1:cvNumber') as $node)
					$node->parentNode->removeChild($node);
				
				
				$log_fields['request_array'] = array(
					'full_request' => $xml->saveXml()
				);
				
				if (count($response->ccAuthReply)) {
					$codes = array(
						'cvv_response_code' => 'cvCode',
						'cvv_response_text' => 'cvCodeRaw',
						'avs_response_code' => 'avsCode',
						'avs_response_text' => 'avsCodeRaw'
					);
					foreach ($codes as $key => $field)
						if (isset($response->ccAuthReply->$field))
							$log_fields[$key] = $response->ccAuthReply->$field;
				}
				
				$log_fields['response_text'] = $soap->__getLastResponse();
				$log_fields['message'] = $response->decision;
				
				if ($response->reasonCode==self::RESPONSE_CODE_SUCCESS) {
					$log_fields['message'] = 'Successful payment';
					$this->update_transaction_status($host_obj, $order, (string)$response->requestID, (string)$response->decision, (string)$response->reasonCode);
					Shop_OrderStatusLog::create_record($host_obj->order_status, $order);
					$order->set_payment_processed();
				}
				
				$log_fields['response_array']['decision'] = $response->decision;
				$log_fields['response_array']['reasonCode'] = $response->reasonCode;
				
				$fields = array(
					'reasonCode', 'avsCode', 'avsCodeRaw', 'cvCode', 'cvCodeRaw', 'processorResponse'
				);
				if (isset($response->ccAuthReply))
					foreach ($fields as $field)
						if (isset($response->ccAuthReply->$field))
							$log_fields['response_array'][$field] = $response->ccAuthReply->$field;
				
			} catch (Exception $e) {
				$log_fields['message'] = $e->getMessage();
			}
			
			$this->log_payment_attempt(
				$order, 
				$log_fields['message'],
				$log_fields['status'],
				$log_fields['request_array'],
				$log_fields['response_array'], 
				$log_fields['response_text'],
				$log_fields['cvv_response_code'],
				$log_fields['cvv_response_text'],
				$log_fields['avs_response_code'],
				$log_fields['avs_response_text']
			);
			
			
		}

		public function _log_payment_attempt() {
			call_user_func_array(array($this, 'log_payment_attempt'), func_get_args());
		}

		/**
		 * This function is called before a CMS page deletion.
		 * Use this method to check whether the payment method
		 * references a page. If so, throw Phpr_ApplicationException 
		 * with explanation why the page cannot be deleted.
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 * @param Cms_Page $page Specifies a page to be deleted
		 */
		public function page_deletion_check($host_obj, $page)
		{
			if ($host_obj->cancel_page == $page->id)
				throw new Phpr_ApplicationException('Page cannot be deleted because it is used in CyberSource payment method as a cancel page.');
		}
		
		/**
		 * This function is called before an order status deletion.
		 * Use this method to check whether the payment method
		 * references an order status. If so, throw Phpr_ApplicationException 
		 * with explanation why the status cannot be deleted.
		 * @param $host_obj ActiveRecord object containing configuration fields values
		 * @param Shop_OrderStatus $status Specifies a status to be deleted
		 */
		public function status_deletion_check($host_obj, $status)
		{
			if ($host_obj->order_status == $status->id)
				throw new Phpr_ApplicationException('Status cannot be deleted because it is used in CyberSource payment method.');
		}
	}

?>