<?php

class adelyaUtil extends Module {

	/**
	 * Test api function
	 * @return bool|string
	 */
	public function testAPI() {
		$json_request = 'json_header={"action":"connect"}&json_data={}';
		$server_url = Configuration::get('server_url');
		if ($server_url == 0) {
			$server_url = 'https://qa.adelya.com/apiv1/webapi.do';
		} else if ($server_url == 99) {
			$server_url = 'https://localhost/apiv1/webapi.do';
		} else if ($server_url == 90) {
            $server_url = 'https://demo.adelya.com/apiv1/webapi.do';
        } else {
			$server_url = 'https://asp.adelya.com/apiv1/webapi.do';
		}
		$api_key = Configuration::get('api_key');
		$username = Configuration::get('user_login');
		$password = Configuration::get('user_password');
		$connection_timeout = Configuration::get('connection_timeout');
		if (empty($connection_timeout) || !is_numeric($connection_timeout)) {
			$connection_timeout = 7;
		}
		$request_timeout = Configuration::get('request_timeout');
		if (empty($request_timeout) || !is_numeric($request_timeout)) {
			$request_timeout = 7;
		}
		$basicAuth = $api_key . ';' . $username . ':' . $password;
		$basicAuth = 'Basic ' . base64_encode($basicAuth);

		$curl = curl_init($server_url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connection_timeout); //timeout in seconds
		curl_setopt($curl, CURLOPT_TIMEOUT, $request_timeout); //timeout in seconds
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: $basicAuth"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_request);
		//TODO for dev only
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		//If status is not ok (404,500, etc... return null)   0 is a timeout
		if ((!$status || empty($status)) || ($status && $status != '200') || (!$response || empty($response))) {
			$response = 'Error code : ' . $status;
		}
		return $response;
	}

	/**
	 * Add a new customer in adelya if necessary
	 * @param $customer
	 */
	public function addNewCustomerData($customer) {
		if ($this->pluginIsActive() == true) {
			try {
				$idExternal = 'PRESTASHOP:' . $customer->id;
				//If customer already exists in adelya, do nothing
				$already_a_member = $this->callAPI('json_header={action:"show"}&json_data={CompactFidelityMember:{idExternal:"' . $idExternal . '"}}');
				if ($already_a_member && !empty($already_a_member)) {
					$already_a_member = json_decode($already_a_member, true);
					if (array_key_exists('CompactFidelityMember', $already_a_member) || array_key_exists('list', $already_a_member)) {
						$this->adelyalog('Member ' . $idExternal . ' already exist in adelya, doing update', 1);
						$this->syncCustomerData($customer);
					} else {
						$src = 'WEB';
						$gender = '';
						if ($customer->id_gender == 1) {
							$gender = 'M';
						} else if ($customer->id_gender == 2) {
							$gender = 'MME';
						} else if ($customer->id_gender == 3) {
							$gender = 'MELLE';
						}
						$firstname = $customer->firstname;
						if (!isset($firstname)) {
							$firstname = '';
						}
						$name = $customer->lastname;
						if (!isset($name)) {
							$name = '';
						}
						$email = $customer->email;
						if (!isset($email)) {
							$email = '';
						}
						$birthday = $customer->birthday;
						if (!isset($birthday)) {
							$birthday = '';
						}
						$optin = $customer->newsletter;
						if (!isset($optin)) {
							$optin = false;
						}
						$partneroptin = $customer->optin;
						if (!isset($partneroptin)) {
							$partneroptin = false;
						}
						//Before creation, we look for someone with same email and name as this person
						$member = $this->callAPI('json_header={action:"show"}&json_data={CompactFidelityMember:{actif_ge:1,email:"' . $email . '"}}');
						if ($member && !empty($member)) {
							$member = json_decode($member, true);
							$idMember = null;
							if (array_key_exists('CompactFidelityMember', $member)) {
								$idMember = $member['CompactFidelityMember']['id'];
							} else if (array_key_exists('list', $member)) {
								$idMember = $member['list'][0]['id'];
							}
							//If a member is found,  we add the idExternal to him and we do nothing else
							if ($idMember != null) {
								$this->callAPI('json_header={action:"persist"}&json_data={FidelityMember:{id:' . $idMember . ',idExternal:"' . $idExternal . '"}}');
							} else {
								//We add a new member
								$callpersist = 'json_header={action:"persist"}&json_data=
				{FidelityMember:{
					src:"' . $src . '",
					idExternal:"' . $idExternal . '",
					gender:"' . $gender . '",
					firstname:"' . $firstname . '",
					name:"' . $name . '",
                            ';
								if ($birthday != null && $birthday != '0000-00-00') {
									$callpersist = $callpersist . 'birthday:"' . $birthday . '",';
								}
								if ($optin == true) {
									$callpersist = $callpersist . 'emailoptin:"1",';
								} else {
									$callpersist = $callpersist . 'emailoptin:"0",';
								}
								if ($partneroptin == true) {
									$callpersist = $callpersist . 'partneroptin:"1",';
								} else {
									$callpersist = $callpersist . 'partneroptin:"0",';
								}
								$callpersist = $callpersist . 'email:"' . $email . '"}}';
								$this->callAPI($callpersist);
							}
						}
					}
				}
			} catch (Exception $e) {
				$this->adelyalog("Error while adelya customer creation " . $e->getMessage(), 2);
			}
		}
	}

	public function pluginIsActive() {
		return (bool)Tools::getValue('adelyaapi_status', Configuration::get('adelyaapi_status'));
	}

	/**
	 * Generic method for apiCall
	 * @param $json_request
	 * @return bool|string|null
	 */
	public function callAPI($json_request) {
		$server_url = Configuration::get('server_url');
		if ($server_url == 0) {
			$server_url = 'https://qa.adelya.com/apiv1/webapi.do';
		} else if ($server_url == 99) {
            $server_url = 'https://localhost/apiv1/webapi.do';
        } else if ($server_url == 90) {
            $server_url = 'https://demo.adelya.com/apiv1/webapi.do';
        } else {
			$server_url = 'https://asp.adelya.com/apiv1/webapi.do';
		}
		$api_key = Configuration::get('api_key');
		$username = Configuration::get('user_login');
		$password = Configuration::get('user_password');
		$connection_timeout = Configuration::get('connection_timeout');
		if (empty($connection_timeout) || !is_numeric($connection_timeout)) {
			$connection_timeout = 7;
		}
		$request_timeout = Configuration::get('request_timeout');
		if (empty($request_timeout) || !is_numeric($request_timeout)) {
			$request_timeout = 7;
		}
		$basicAuth = $api_key . ';' . $username . ':' . $password;
		$basicAuth = 'Basic ' . base64_encode($basicAuth);
		$curl = curl_init($server_url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connection_timeout); //timeout in seconds
		curl_setopt($curl, CURLOPT_TIMEOUT, $request_timeout); //timeout in seconds
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: $basicAuth"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $json_request);
		//TODO for dev only
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		//If status is not ok (404,500, etc... return null)   0 is a timeout
		if ((!$status || empty($status)) || ($status && $status != '200') || (!$response || empty($response))) {
			$this->adelyalog('Appel API échoué (' . $status . ', ' . $response . ')', 'ERROR');
			//Log de l'appel api manqué sur un fichier dans le module
			$logger = new FileLogger(0); //0 == debug level, logDebug() won’t work without this.
			$logger->setFilename(_PS_ROOT_DIR_ . '/log/adelyaapi.log');
			$logger->logDebug('|' . $status . '|' . $json_request);
			$response = null;
		}

		return $response;
	}

	//Sync data using the most recent data as a reference if the option is selected

	public function adelyalog($log, $severity = 1) {
		// 1 = INFO 2 = WARNING  3 = ERROR
		if ($log && $severity && !empty($log) && !empty($severity)) {
			if ($severity == 'ERROR') {
				PrestaShopLogger::addLog('[ADELYA MODULE] ' . date('Y-m-d H:i:s', time()) . ' : ' . $log, 3);
			} else if ($severity == 'INFO') {
				PrestaShopLogger::addLog('[ADELYA MODULE] ' . date('Y-m-d H:i:s', time()) . ' : ' . $log, 1);
			} else {
				PrestaShopLogger::addLog('[ADELYA MODULE] ' . date('Y-m-d H:i:s', time()) . ' : ' . $log, $severity);
			}
		}
	}

	//Sync coupons and use them if necessary

	/**
	 * Send request to adelya to "remove" customer data from Adelya DB
	 * @param $customer    the cutomer object to remove
	 */
	public function removeCustomerData($customer) {
		if ($this->pluginIsActive() == true) {
			$this->adelyalog('Customer deletion send apiCall for ' . $customer->id, 1);
			try {
				$idExternal = 'PRESTASHOP:' . $customer->id;
				//persist status change into adelya
				$obj = new stdClass();
                $loadFromKeysConstraint = new stdClass();
                $loadFromKeysConstraint->idExternal = $idExternal;
				$obj->loadFromKeys = $loadFromKeysConstraint;
				$obj->actif = 0;
				$this->callAPI('json_header={action:"persist"}&json_data={FidelityMember:' . json_encode($obj) . '}');
			} catch (Exception $ex) {
				$this->adelyalog('error while sending apiCall ' . $ex->getMessage(), 3);
			}
		}
	}
	
	/**
	 * Send request to adelya to deactivate a customer
	 * @param $customer    the cutomer object to deactivate
	 */
	public function deactiveCustomer($customer) {
		if ($this->pluginIsActive() == true) {
			$this->adelyalog('Customer deactivation send apiCall for ' . $customer->id, 1);
			try {
				$idExternal = 'PRESTASHOP:' . $customer->id;
				//persist status change into adelya
				$obj = new stdClass();
                $loadFromKeysConstraint = new stdClass();
                $loadFromKeysConstraint->idExternal = $idExternal;
				$obj->loadFromKeys = $loadFromKeysConstraint;
				$obj->actif = 0;
				$this->callAPI('json_header={action:"persist"}&json_data={FidelityMember:' . json_encode($obj) . '}');
			} catch (Exception $ex) {
				$this->adelyalog('error while sending apiCall ' . $ex->getMessage(), 3);
			}
		}
	}
	

	public function syncCustomerData($customer) {
		if ($this->pluginIsActive() == true) {
			try {
				if ($customer) {
					$idExternal = 'PRESTASHOP:' . $customer->id;
					$date_update_presta = $customer->date_upd;
					if (!$date_update_presta || empty($date_update_presta)) {
						$date_update_presta = '1990-01-01 00:00:00';
					}
					$member = $this->callAPI('json_header={action:"show"}&json_data={CompactFidelityMember:{idExternal:"' . $idExternal . '"}}');
					if ($member && !empty($member)) {
						$member = json_decode($member, true);
						if (array_key_exists('CompactFidelityMember', $member)) {
							$date_update_adelya = $member['CompactFidelityMember']['dateUpdate'];
							$inactif = isset($member['CompactFidelityMember']['actif']) && $member['CompactFidelityMember']['actif'] == '0';
								
							if (!$date_update_adelya || empty($date_update_adelya)) {
								$date_update_adelya = '1995-01-01 00:00:00';
							}
							$date_update_presta = strtotime($date_update_presta);
							$date_update_adelya = strtotime($date_update_adelya);
							
							// Case Presta values are the latest, we want to update Adelya
							if ($date_update_presta > $date_update_adelya) {
								if ($customer->id_gender == 1) {
									$gender = 'M';
								} else if ($customer->id_gender == 2) {
									$gender = 'MME';
								} else if ($customer->id_gender == 3) {
									$gender = 'MELLE';
								}
								$firstname = $customer->firstname;
								$name = $customer->lastname;
								$email = $customer->email;
								$birthday = $customer->birthday;
								$optin = $customer->newsletter;
								$partneroptin = $customer->optin;
								$addresses = $customer->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
								foreach ($addresses as $address) {
									$obj = new Address((int)$address['id_address']);
									$line1 = $obj->address1;
									$line2 = $obj->address2;
									$zip = $obj->postcode;
									$town = $obj->city;
									$country = $obj->country;
									$tel = $obj->phone;
									$mobile = $obj->phone_mobile;
									break;
								}
								//Mise à jour des info vers adelya
								$idMember = $member['CompactFidelityMember']['id'];
								if ($idMember && !empty($idMember)) {
									$callpersist = 'json_header={action:"persist"}&json_data={FidelityMember:{id:' . $idMember . ',';
									if ($gender && !empty($gender)) {
										$callpersist = $callpersist . 'gender:"' . $gender . '",';
									}
									if ($firstname && !empty($firstname)) {
										$callpersist = $callpersist . 'firstname:"' . $firstname . '",';
									}
									if ($name && !empty($name)) {
										$callpersist = $callpersist . 'name:"' . $name . '",';
									}
									if ($birthday && !empty($birthday) && $birthday != '0000-00-00') {
										$callpersist = $callpersist . 'birthday:"' . $birthday . '",';
									}
									if (isset($tel) && !empty($tel)) {
										$callpersist = $callpersist . 'tel:"' . $tel . '",';
									}
									if (isset($mobile) && !empty($mobile)) {
										$callpersist = $callpersist . 'mobile:"' . $mobile . '",';
									}
									// Activate if it was deactivated
									if ($customer->fid_program_membership == '1' && $inactif) {
										$callpersist = $callpersist . 'actif:1,';
									}
									if ($optin == 1) {
										if (($email && !empty($email)) || (isset($member['CompactFidelityMember']['email']) && !empty($member['CompactFidelityMember']['email']))) {
											$callpersist = $callpersist . 'emailoptin:"1",';
										}
										if ((isset($mobile) && !empty($mobile)) || (isset($member['CompactFidelityMember']['mobile']) && !empty($member['CompactFidelityMember']['mobile']))) {
											$callpersist = $callpersist . 'smsoptin:"1",';
										}
									} else {
										$callpersist = $callpersist . 'emailoptin:"0",';
										$callpersist = $callpersist . 'smsoptin:"0",';
									}
									if ($partneroptin == true) {
										$callpersist = $callpersist . 'partneroptin:"1",';
									} else {
										$callpersist = $callpersist . 'partneroptin:"0",';
									}
									//Address
									if (isset($line1) && !empty($line1)) {
										$callpersist = $callpersist . 'address:{line1:"' . $line1 . '"';
										if (isset($line2) && !empty($line2)) {
											$callpersist = $callpersist . ',line2:"' . $line2 . '"';
										}
										if (isset($zip) && !empty($zip)) {
											$callpersist = $callpersist . ',zip:"' . $zip . '"';
										}
										if (isset($town) && !empty($town)) {
											$callpersist = $callpersist . ',town:"' . $town . '"';
										}
										if (isset($country) && !empty($country)) {
											$callpersist = $callpersist . ',country:"' . $country . '"},';
										} else {
											$callpersist = $callpersist . '},';
										}
									}
									$callpersist = $callpersist . 'email:"' . $email . '"}}';
									
									$this->adelyalog('Customer update Presta -> Adelya ' . $customer->id, 1);
									$this->callAPI($callpersist);
								}
							} else if (Tools::getValue('customer_sync', Configuration::get('customer_sync')) == 0) {
								//Mise à jour des infos prestashop  note : on ne touche pas à l'adresse car livraison ni à l'email
								$gender = isset($member['CompactFidelityMember']['gender']) ? $member['CompactFidelityMember']['gender'] : NULL;
								$name = isset($member['CompactFidelityMember']['name']) ? $member['CompactFidelityMember']['name'] : NULL;
								$firstname = isset($member['CompactFidelityMember']['firstname']) ? $member['CompactFidelityMember']['firstname'] : NULL;
								$birthday = isset($member['CompactFidelityMember']['birthday']) ? $member['CompactFidelityMember']['birthday'] : NULL;
								$emailoptin = isset($member['CompactFidelityMember']['emailoptin']) ? $member['CompactFidelityMember']['emailoptin'] : NULL;
								$smsoptin = isset($member['CompactFidelityMember']['smsoptin']) ? $member['CompactFidelityMember']['smsoptin'] : NULL;
								$partneroptin = isset($member['CompactFidelityMember']['partneroptin']) ? $member['CompactFidelityMember']['partneroptin'] : NULL;
								$actif = isset($member['CompactFidelityMember']['actif']) ? $member['CompactFidelityMember']['actif'] : NULL;

								// Si le client est actif sur Adelya, on active la fid dans Presta
//								if ($actif && !empty($actif) && $actif >= 1) {
//									$customer->fid_program_membership = '1';
//									$date = new DateTime();
//									$customer->fid_program_membership_date = $date->format('Y-m-d H:i:s');
//								}
								
								if (!empty($gender)) {
									if ($gender == 'M') {
										$customer->id_gender = 1;
									} else if ($gender == 'MME') {
										$customer->id_gender = 2;
									}
								}
								if ($name && !empty($name)) {
									$customer->lastname = $name;
								}
								if ($firstname && !empty($firstname)) {
									$customer->firstname = $firstname;
								}
								if ($birthday && !empty($birthday)) {
									$customer->birthday = date('Y-m-d', strtotime($birthday));
								}
								if ($emailoptin && !empty($emailoptin) && $emailoptin == 1) {
									$customer->newsletter = 1;
								} else if ($smsoptin && !empty($smsoptin) && $smsoptin == 1) {
									$customer->newsletter = 1;
								} else {
									$customer->newsletter = 0;
								}
								if ($partneroptin && !empty($partneroptin)) {
									$customer->optin = $partneroptin;
								}
								
								$this->adelyalog('Customer update Adelya -> Presta ' . $customer->id, 1);
								$customer->save();
							}
						}
					}
				}
			} catch (Exception $e) {
				$this->adelyalog("Error while adelya synch " . $e->getMessage(), 2);
			}
		}
	}

	public function syncCoupons($customer_id, $language_id) {
		if ($this->pluginIsActive() == true && Tools::getValue('voucher_sync', Configuration::get('voucher_sync')) == 0
				&&  $this->context->customer->fid_program_membership == '1') {
			$idExternal = 'PRESTASHOP:' . $customer_id;
			$coupons = $this->callAPI('json_header={action:"show"}&json_data={"Sent":{"type":"COUPON","member":{"idExternal":"' . $idExternal . '"}}}');
			if ($coupons && !empty($coupons)) {
				$coupons = json_decode($coupons, true);
				$couponlist = null;
				if (array_key_exists('Sent', $coupons)) {
					$couponlist = $coupons;
				} else if (array_key_exists('list', $coupons)) {
					$couponlist = $coupons['list'];
				}
				if ($couponlist != null) {
					foreach ($couponlist as &$coupon) {
						$id = $coupon['id'];
						$trackcode = $coupon['trackCode'];
						$fvalue = $coupon['fvalue'];
						$startDate = $coupon['startDate'];
						$endDate = $coupon['endDate'];
						$idCampaign = $coupon['campaign'];
						$status1 = 0;
						if (array_key_exists('status1', $coupon)) {
							$status1 = $coupon['status1'];
						}
						$status2 = 0;
						if (array_key_exists('status2', $coupon)) {
							$status2 = $coupon['status2'];
						}
						$now = date('Y-m-d H:i:s');
						$endDate = strtotime($endDate);
						//Si le coupon est valide et non utilisé
						if ($status1 == 0 && $status2 == 0 && $endDate > time()) {
							$cart_rules = CartRule::getCartsRuleByCode($trackcode, (int)$language_id, true);
							//S'il n'existe pas dans prestashop, le créer
							if (!$cart_rules || empty($cart_rules)) {
								//Load des détails de la campagne pour créer le bon type de coupon
								$campaign = $this->callAPI('json_header={action:"show"}&json_data={"Campaign":{"id":' . $idCampaign . '}}');
								if ($campaign && !empty($campaign)) {
									$campaign = json_decode($campaign, true);
									if (array_key_exists('Sms', $campaign)) {
										$voucher = new CartRule();
										$campdescr = $campaign['Sms']['descr'];
										$campcouponValue = $campaign['Sms']['couponValue'];
										$campcouponUnit = $campaign['Sms']['couponUnit'];
										$campidExternal = $campaign['Sms']['idExternal'];
										$proceed = true;
										//Si complété, ne sont crées que les coupons de la liste
										if (Configuration::get('specific_vouchers') && trim(Configuration::get('specific_vouchers')) != false && $campidExternal && trim($campidExternal) != false) {
											$specific_vouchers_list = explode(",", Configuration::get('specific_vouchers'));
											if ($specific_vouchers_list && !empty($specific_vouchers_list) && !in_array($campidExternal, $specific_vouchers_list)) {
												$proceed = false;
											}
										}
										if ($proceed) {
											$languages = Language::getLanguages();
											foreach ($languages as $key => $language) {
												$names[$language['id_lang']] = $campdescr;
											}
											if ($fvalue && !empty($fvalue)) {
												$total = $fvalue;
											} else if ($campcouponValue && !empty($campcouponValue)) {
												$total = $campcouponValue;
											}
											$voucher->reduction_currency = 1;
											//Si tout est ok au niveau de l'initialisation, on peut créer le coupon sans risque
											if ($campcouponUnit && !empty($campcouponUnit) && $total && !empty($total)) {
												$voucher->name = $names;
												$voucher->description = $campdescr;
												$voucher->code = $trackcode;
												$voucher->highlight = 0;
												$voucher->partial_use = 0; //No partial use allowed
												$voucher->priority = 1;
												$voucher->active = 1;
												$voucher->id_customer = (int)($customer_id);
												$voucher->date_from = $coupon['startDate'];
												if ($endDate && !empty($endDate)) {
													$voucher->date_to = $coupon['endDate'];
												} else {
													$voucher->date_to = date('Y-m-d H:i:s', time() + (3600 * 24 * 365.25 * 100));
												}
												if ($campcouponUnit && !empty($campcouponUnit)) {
													if ($campcouponUnit == 'addCA') {
														$voucher->reduction_amount = (float)($total);
													} else if ($campcouponUnit == 'percent') {
														$voucher->reduction_percent = (float)($total);
													}
												}
												if ($campcouponUnit == 'addCA') {
													$voucher->minimal = (float)($voucher->reduction_amount);
												}
												$voucher->quantity = 1;
												$voucher->quantity_per_user = 1;
												$voucher->cumulable = 1;
												$voucher->cumulable_reduction = 1;
												$voucher->cart_display = 1;
												$voucher->cart_rule_restriction = 0;
												$voucher->minimum_amount_tax = 1;
												$voucher->minimum_amount_shipping = 0;
												$voucher->reduction_tax = 1;
												if ($voucher->validateFieldsLang()) {
													$voucher->add();
												}
											}
										}
									}
								}
							} else {
								//S'il existe, vérifier s'il n'est pas utilisé ou périmé dans prestashop
								foreach ($cart_rules as &$discount) {
									if ($this->usedByCustomer($discount['id_cart_rule'], $customer_id) == true && $this->isValid($discount['id_cart_rule'], $customer_id) == false) {
										//S'il est utilisé, mettre à jour le coupon coté adelya
										$this->callAPI('json_header={action:"persist"}&json_data={"Sent":{"loadFromKeys": { "trackCode":"' . $trackcode . '"},"status2":true,"dateUpdateStatus2":"' . date('Y-m-d H:i:s') . '"}}');
										$this->callAPI('json_header={action:"persist"}&json_data={"AdEvent":{"type":"usedcoupon","comment":"' . $trackcode . '","fvalue":' . $fvalue . ',"member":{"loadFromKeys": { "idExternal":"' . $idExternal . '"}},"cause":{"loadFromKeys": { "id":"' . $id . '"}}}}');
									}
								}
							}
						} else {
							//Si le coupon n'est plus valide, verifier dans prestashop, si il existe le désactiver
							$cart_rules = CartRule::getCartsRuleByCode($trackcode, (int)$language_id, true);
							if ($cart_rules) {
								foreach ($cart_rules as &$discount) {
									if ($this->isValid($discount['id_cart_rule'], $customer_id) == true) {
										$this->deactivateVoucher($discount['id_cart_rule'], $customer_id);
									}
								}
							}
						}
					}
				}
			}
		}
	}

	public function usedByCustomer($id_cart_rule, $id_customer) {
		return (bool)Db::getInstance()->getValue('
		SELECT id_cart_rule
		FROM `' . _DB_PREFIX_ . 'order_cart_rule` ocr
		LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON ocr.`id_order` = o.`id_order`
		WHERE ocr.`id_cart_rule` = ' . (int)$id_cart_rule . '
		AND o.`id_customer` = ' . (int)$id_customer);
	}

	public function isValid($id_cart_rule, $id_customer) {
		return (bool)Db::getInstance()->getValue('
		SELECT id_cart_rule
		FROM `' . _DB_PREFIX_ . 'cart_rule` psc 
		WHERE date_to>now() and psc.`id_cart_rule` = ' . (int)$id_cart_rule . '
		AND psc.`id_customer` = ' . (int)$id_customer);
	}

	public function deactivateVoucher($id_cart_rule, $id_customer) {
		if ($id_cart_rule && $id_customer && !empty($id_cart_rule) && !empty($id_customer)) {
			Db::getInstance()->execute('
		UPDATE `' . _DB_PREFIX_ . 'cart_rule` psc
                SET psc.quantity=0, psc.quantity_per_user=0      
		WHERE psc.`id_cart_rule` = ' . (int)$id_cart_rule . '
		AND psc.`id_customer` = ' . (int)$id_customer);
		}
	}

	public function addCA($params) {
		$addCA = $params['order'];
		if ($this->pluginIsActive() == true && $addCA && !empty($addCA)) {
			$idExternalCustomer = 'PRESTASHOP:' . $addCA->id_customer;
			$fvalue = $addCA->total_paid;
			//Check if not exists first
			$searchevent_return = $this->callAPI('json_header={action:"search"}&json_data={"AdEvent":{"type":"addCA","idExternal":"' . $idExternalCustomer . ':' . $addCA->id . '","member":{"idExternal":"' . $idExternalCustomer . '"}}}');
			if ($searchevent_return && !empty($searchevent_return)) {
				//If no object found we can continue
				$searcheventreturn = json_decode($searchevent_return, true);
				if (array_key_exists('ApiReturn', $searcheventreturn) && $searcheventreturn['ApiReturn']['code'] == 'ERROR' && strpos($searcheventreturn['ApiReturn']['message'], 'No object found') !== false) {
					//Create AdEvent
					$adEvent = 'json_header={action:"persist"}&json_data={
                        "AdEvent":{
                                "type":"addCA",
                                "member":{
                                        loadFromKeys: { idExternal: "' . $idExternalCustomer . '"}	  
                                },
                                "idExternal":"PRESTASHOP:' . $addCA->id_customer . ':' . $addCA->id . '",
                                "fvalue":' . $fvalue . ',
                                "details":[
                ';
					$isFirst = true;
					foreach ($addCA->getProductsDetail() as $product) {
						//Quantity
						$unitValue = floatval($product['unit_price_tax_incl']);
						$finalValue = floatval($product['total_price_tax_incl']);
						$quantity = $product['product_quantity'];
						$class10 = $product['reference'];
						if (!$class10 || empty($class10)) {
							$class10 = 'NOREF';
						}

						if ($isFirst == true) {
							$adEvent = $adEvent . '{
                        "AdEventDetail":{
                        "class10":"' . $class10 . '",
                        "quantity":' . $quantity . ',
                        "unitValue":' . $unitValue . ',
                        "finalValue":' . $finalValue . '
                        }}';
							$isFirst = false;
						} else {
							$adEvent = $adEvent . ',{
                        "AdEventDetail":{
                        "class10":"' . $class10 . '",
                        "quantity":' . $quantity . ',
                        "unitValue":' . $unitValue . ',
                        "finalValue":' . $finalValue . '
                        }}';
						}
					}
					//Ajout des coupons éventuellement utilisés
					foreach ($addCA->getCartRules() as $rule) {
						$id_cart_rule = $rule['id_cart_rule'];
						if ($id_cart_rule && !empty($id_cart_rule)) {
							$class10 = $this->getTrackcode($id_cart_rule);
							$finalValue = floatval($rule['value']);
							if (!$class10 || empty($class10)) {
								$class10 = $id_cart_rule;
							} else {
								$this->callAPI('json_header={action:"persist"}&json_data={"Sent":{"loadFromKeys": { "trackCode":"' . $class10 . '"},"status2":true,"dateUpdateStatus2":"' . date('Y-m-d H:i:s') . '"}}');
								$this->callAPI('json_header={action:"persist"}&json_data={"AdEvent":{"type":"usedcoupon","comment":"' . $class10 . '","fvalue":' . $finalValue . ',"member":{"loadFromKeys": { "idExternal":"' . $idExternalCustomer . '"}},"cause":{"loadFromKeys": { "trackcode":"' . $class10 . '"}}}}');
							}
							if ($isFirst == true) {
								$adEvent = $adEvent . '{
                                "AdEventDetail":{
                                "class10":"' . $class10 . '",
                                "detailType":"C",
                                "finalValue":-' . $finalValue . '
                            }}';
								$isFirst = false;
							} else {
								$adEvent = $adEvent . ',{
                                "AdEventDetail":{
                                "class10":"' . $class10 . '",
                                "detailType":"C",
                                "finalValue":-' . $finalValue . '
                            }}';
							}
						}
					}
					$adEvent = $adEvent . ']}}';
					$this->callAPI($adEvent);
					//Maj ca non indispensable, préfèrer utiliser le param cascadeOnEvent
				}
			}
		}
	}

	public function getTrackcode($id_cart_rule) {
		return Db::getInstance()->getValue('select code from ' . _DB_PREFIX_ . 'cart_rule where id_cart_rule=' . (int)$id_cart_rule);
	}

	public function cancelCA($order) {
		if ($this->pluginIsActive() == true) {
			$id_order = (int)$order->id;
			$id_customer = (int)$order->id_customer;
			if ($id_order && !empty($id_order) && $id_customer && !empty($id_customer)) {
				$idExternalCustomer = 'PRESTASHOP:' . $id_customer;
				$idExternal = 'PRESTASHOP:' . $id_customer . ':' . $id_order;
				$addCA = $this->callAPI('json_header={action:"show"}&json_data={CompactAdEvent:{"member":{"idExternal":"' . $idExternalCustomer . '"},idExternal:"' . $idExternal . '",status1:false}}');
				if ($addCA && !empty($addCA)) {
					$addCA = json_decode($addCA, true);
					if (array_key_exists('CompactAdEvent', $addCA)) {
						$idEvent = $addCA['CompactAdEvent']['id'];
						$this->callAPI('json_header={action:"delete"}&json_data={AdEvent:{"id":' . $idEvent . '}}');
					}
				}
			}
		}
	}

	public function getFidData() {
		$nbPoint = '';
		$nbCredit = '';
		$idExternal = 'PRESTASHOP:' . $this->context->customer->id;
		$member = $this->callAPI('json_header={action:"show"}&json_data={CompactFidelityMember:{idExternal:"' . $idExternal . '"}}');
		if ($member && !empty($member)) {
			$member = json_decode($member, true);
			if (array_key_exists('CompactFidelityMember', $member)) {
				$counter_setting = Configuration::get('fidelity_counter');
				if ($counter_setting == 1) {
					$nbPoint = $member['CompactFidelityMember']['nbPoint'];
				} else if ($counter_setting == 2) {
					$nbCredit = $member['CompactFidelityMember']['nbCredit'];
				} else if ($counter_setting == 3) {
					$nbPoint = $member['CompactFidelityMember']['nbPoint'];
					$nbCredit = $member['CompactFidelityMember']['nbCredit'];
				}
			}
		}
		return array(
			'FRONT_LOYALTYSUBMENU_HTMLTEXT' => Tools::getValue('fidelity_rules_text', Configuration::get('fidelity_rules_text')),
			'FRONT_LOYALTYSUBMENU_NBPOINT' => $nbPoint,
			'FRONT_LOYALTYSUBMENU_NBCREDIT' => $nbCredit
		);
	}

}

?>