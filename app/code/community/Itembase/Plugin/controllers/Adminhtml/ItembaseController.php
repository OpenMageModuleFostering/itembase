<?php

class Itembase_Plugin_Adminhtml_ItembaseController extends Mage_Adminhtml_Controller_Action
{
	public function indexAction()
	{
		$session = Mage::getSingleton('adminhtml/session');
		include('Itembase/Plugin/plugindata.php');
		$language = substr(Mage::app()->getLocale()->getDefaultLocale(), 0 , 2);
		
		$html = '';
		
		// registration data gathering
		$employee = Mage::getSingleton('admin/session')->getUser();
		$user = array(
				'email' => $employee->getEmail(),
				'firstname' => $employee->getFirstname(),
				'lastname' => $employee->getLastname(),
				'street' => Mage::getStoreConfig('shipping/origin/street_line1').(Mage::getStoreConfig('shipping/origin/street_line2') ? ' '.Mage::getStoreConfig('shipping/origin/street_line2') : ''),
				'zip' => Mage::getStoreConfig('shipping/origin/postcode'),
				'town' => Mage::getStoreConfig('shipping/origin/city'),
				'state' => '',
				'country' => Mage::getStoreConfig('general/store_information/merchant_country'),
				'telephone' => Mage::getStoreConfig('general/store_information/phone'),
				'fax' => '',
		);
		$shops = array();
		foreach(Mage::app()->getStores() as $shop) {
			if(Mage::getStoreConfig('itembase_section/itembase_group/api_key', $shop->getStoreId()) === NULL) {
				$shops[] = array(
					'shop_id' => $shop->getStoreId(),
					'shop_name' => Mage::getStoreConfig('general/store_information/name', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('general/store_information/name', $shop->getStoreId()) : Mage::getStoreConfig('general/store_information/name'),
					'shop_url' => Mage::getStoreConfig('web/unsecure/base_url', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('web/unsecure/base_url', $shop->getStoreId()) : Mage::getStoreConfig('web/unsecure/base_url'),
					'register' => 1,
					'street' => (Mage::getStoreConfig('shipping/origin/street_line1', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('shipping/origin/street_line1', $shop->getStoreId()) : Mage::getStoreConfig('shipping/origin/street_line1')).(Mage::getStoreConfig('shipping/origin/street_line2', $shop->getStoreId()) !== NULL ? ' '.Mage::getStoreConfig('shipping/origin/street_line2', $shop->getStoreId()): (Mage::getStoreConfig('shipping/origin/street_line2') !== NULL ? ' '.Mage::getStoreConfig('shipping/origin/street_line2') : '')),
					'zip' => Mage::getStoreConfig('shipping/origin/postcode', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('shipping/origin/postcode', $shop->getStoreId()) : Mage::getStoreConfig('shipping/origin/postcode'),
					'town' => Mage::getStoreConfig('shipping/origin/city', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('shipping/origin/city', $shop->getStoreId()) : Mage::getStoreConfig('shipping/origin/city'),
					'state' => '',
					'country' => Mage::getStoreConfig('general/store_information/merchant_country', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('general/store_information/merchant_country', $shop->getStoreId()) : Mage::getStoreConfig('general/store_information/merchant_country'),
					'telephone' => Mage::getStoreConfig('general/store_information/phone', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('general/store_information/phone', $shop->getStoreId()) : Mage::getStoreConfig('general/store_information/phone'),
					'fax' => '',
					'email' => Mage::getStoreConfig('trans_email/ident_general/email', $shop->getStoreId()) !== NULL ? Mage::getStoreConfig('trans_email/ident_general/email', $shop->getStoreId()) : Mage::getStoreConfig('trans_email/ident_general/email'),
				);
			}
		}
		
		// registration data saving
		if($this->getRequest()->getParam('itembaseRegistration')) {
			$responseData = json_decode(base64_decode($this->getRequest()->getParam('itembaseRegistration')), true);
			if(isset($responseData['errors'])) {
				$session->addError($this->__('Registration error.').'<br />'.implode('<br />', $responseData['errors']));
				$user['email'] = $responseData['user']['email'];
				$user['firstname'] = $responseData['user']['firstname'];
				$user['lastname'] = $responseData['user']['lastname'];
			} else {
				$this->saveConfiguration($responseData);
				$session->addSuccess($this->__('Registration completed.').'<br />'.$responseData['success'].' <a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'itembase_section', 'key' => Mage::getSingleton('adminhtml/url')->getSecretKey('system_config','edit'))).'">'.$this->__('Click here to continue.').'</a>');
				$this->loadLayout()->renderLayout();
				return;
			}
		}
		
		if($shops) {
			// registration data sending
			if($this->getRequest()->getParam('submitItembaseRegistration')) {
				$data = array(
					'user' => $user = $this->getRequest()->getParam('user'),
					'shops' => $shops = $this->getRequest()->getParam('shops'),
					'shop_software' => $this->getRequest()->getParam('shop_software'),
					'return' => 'json',
					'lang' => $language,
				);
				$header[] = 'Authorization: OAuth Content-Type: application/x-www-form-urlencoded';
				$ibCurl = curl_init();
				curl_setopt($ibCurl, CURLOPT_HEADER, false);
				curl_setopt($ibCurl, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ibCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ibCurl, CURLOPT_SSL_VERIFYHOST, 2);
				curl_setopt($ibCurl, CURLOPT_URL, ITEMBASE_SERVER_HOST.'/api/register_retailer');
				curl_setopt($ibCurl, CURLOPT_POST, true);
				curl_setopt($ibCurl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ibCurl, CURLOPT_POSTFIELDS, http_build_query($data));
				$jsonResponse = curl_exec($ibCurl);
				if($jsonResponse === FALSE) {
					$session->addError($this->__('Curl error.').'<br />'.curl_error($ibCurl));
				} else {
					$responseData = json_decode($jsonResponse, true);
					if(isset($responseData['errors'])) {
						$session->addError($this->__('Registration error.').'<br />'.implode('<br />', $responseData['errors']));
					} else {
						$this->saveConfiguration($responseData);
						$session->addSuccess($this->__('Registration completed.').'<br />'.$responseData['success'].' <a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit", array('section' => 'itembase_section', 'key' => Mage::getSingleton('adminhtml/url')->getSecretKey('system_config','edit'))).'">'.$this->__('Click here to continue.').'</a>');
						$this->loadLayout()->renderLayout();
						return;
					}
				}
				curl_close($ibCurl);
			}
			
			// registration data output
			$registrationHtml .= '
			<form action="'.(extension_loaded('curl') ? preg_replace('/(\&|\?)itembaseRegistration=[a-z0-9]*/i', '', $_SERVER['REQUEST_URI']) : ITEMBASE_SERVER_HOST.'/api/register_retailer').'" method="post">';
			if(Mage::getStoreConfig('itembase_section/itembase_group/email') === NULL) {
				$registrationHtml .= '
				<div class="itembase-reg-left">
					<label>'.$this->__('Email').'</label>
					<input type="text" name="user[email]" value="'.$user['email'].'" />
					<label>'.$this->__('Firstname').'</label>
					<input type="text" name="user[firstname]" value="'.$user['firstname'].'" />
					<label>'.$this->__('Lastname').'</label>
					<input type="text" name="user[lastname]" value="'.$user['lastname'].'" />
					<input type="hidden" name="user[street]" value="'.$user['street'].'" />
					<input type="hidden" name="user[zip]" value="'.$user['zip'].'" />
					<input type="hidden" name="user[town]" value="'.$user['town'].'" />
					<input type="hidden" name="user[state]" value="'.$user['state'].'" />
					<input type="hidden" name="user[country]" value="'.$user['country'].'" />
					<input type="hidden" name="user[telephone]" value="'.$user['telephone'].'" />
					<input type="hidden" name="user[fax]" value="'.$user['fax'].'" />
				</div>';
			} else {
				$registrationHtml .= '
				<div class="itembase-reg-left">
					<label>'.$this->__('Email').'</label>
					<input type="text" value="'.Mage::getStoreConfig('itembase_section/itembase_group/email').'" disabled="disabled" />
					<input type="hidden" name="user[email]" value="'.Mage::getStoreConfig('itembase_section/itembase_group/email').'" />
				</div>';
			}
			$registrationHtml .= '
				<div class="itembase-reg-right">
					<label class="itembase-form-label-reg">'.$this->__('Register shop').'</label>';
			foreach($shops as $shop) {
				$registrationHtml .= '
					<div class="itembase-shop">
						<input class="input-check" type="checkbox" name="shops['.$shop['shop_id'].'][register]" '.($shop['register'] ? ' checked="checked"' : '').' /><span class="itembase-shopname">'.$shop['shop_name'].'</span>
						<input type="hidden" name="shops['.$shop['shop_id'].'][shop_id]" value="'.$shop['shop_id'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][shop_name]" value="'.$shop['shop_name'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][shop_url]" value="'.$shop['shop_url'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][street]" value="'.$shop['street'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][zip]" value="'.$shop['zip'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][town]" value="'.$shop['town'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][state]" value="'.$shop['state'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][country]" value="'.$shop['country'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][telephone]" value="'.$shop['telephone'].'" />
						<input type="hidden" name="shops['.$shop['shop_id'].'][fax]" value="'.$shop['fax'].'" />
					</div>';
			}
			$registrationHtml .= '
					<input type="hidden" name="shop_software" value="TWFnZW50bzFfN18w" />
					<input type="hidden" name="lang" value="'.$language.'" />
				</div>
				<div class="itembase-reg-final">
					<input type="checkbox" id="confirmItembaseTAC" /><span class="itembase-shopname">'.$this->__("I accept itembase <a href='http://partners.itembase.com/docs/tac.pdf' target='_blank' style='text-decoration:underline;'>Terms</a>").'</span>
					<input class="itembase-button-green" type="submit" name="submitItembaseRegistration" value="'.$this->__('Register').'" onclick="if(!document.getElementById(\'confirmItembaseTAC\').checked){ alert(\''.$this->__('Accept itembase Terms first').'\'); return false; }" />
				</div>
				<input name="form_key" type="hidden" value="'.Mage::getSingleton('core/session')->getFormKey().'" /> 
			</form>';
			$html .= str_replace('[form]', $registrationHtml, file_get_contents(ITEMBASE_SERVER_EMBED.'/embed/registration?shop_software=TWFnZW50bzFfN18w&lang='.$language, false, stream_context_create(array('http' => array('ignore_errors' => true)))));
		}
		
		$this->loadLayout()->_addContent($this->getLayout()->createBlock('core/text', 'registration')->setText($html))->renderLayout();
	}

	private function saveConfiguration($responseData) {
		$config = Mage::getConfig();
		if(Mage::getStoreConfig('itembase_section/itembase_group/email') === NULL) {
			$config->saveConfig('itembase_section/itembase_group/email', $responseData['user']['email']);
		}
		foreach($responseData['shops'] as $shop) {
			$config->saveConfig('itembase_section/itembase_group/api_key', $shop['api_key'], 'stores', $shop['shop_id']);
			$config->saveConfig('itembase_section/itembase_group/secret', $shop['secret'], 'stores', $shop['shop_id']);
		}
		$config->reinit();
	}
}