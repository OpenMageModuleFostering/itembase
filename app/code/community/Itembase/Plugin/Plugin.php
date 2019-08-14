<?php

class Itembase_Plugin_Plugin
{
	public function displayPlugin(Varien_Event_Observer $observer)
	{
		$observerData = $observer->getData();
		$order = Mage::getModel('sales/order');
		$order->load(is_array($observerData['order_ids']) ? $observerData['order_ids'][0] : $observerData['order_ids']);
		
		eval('function itembaseErrorHandler($errno, $errstr, $errfile, $errline) {
'.((bool)Mage::getStoreConfig('itembase_section/itembase_group/debug') ? 'echo "
<!--ITEMBASE
".print_r(array($errno, $errstr, $errfile, $errline), true)."ITEMBASE-->
";' : '').'
	return true;
}');
		set_error_handler('itembaseErrorHandler', E_ALL);
		
		try {
			include('Itembase/Plugin/plugindata.php');
			include('Itembase/Plugin/oauth.php');
			
			$responseArray = json_decode(authenticateClient(Mage::getStoreConfig('itembase_section/itembase_group/api_key'), Mage::getStoreConfig('itembase_section/itembase_group/secret')), true);
			if(!isset($responseArray['access_token'])) itembaseErrorHandler(0, 'no access_token for '.Mage::getStoreConfig('itembase_section/itembase_group/api_key').' '.substr(Mage::getStoreConfig('itembase_section/itembase_group/secret'), 0, 4).'... '.ITEMBASE_SERVER_OAUTH.' '.print_r($responseArray, true), __FILE__, __LINE__ - 1);
			
			$allProducts = array();
			foreach ($order->getAllItems() as $item) {
				$product = $item->getProduct();
				$category = null;
				if (is_array($categoryIds = $product->getCategoryIds())) {
					$category = Mage::getModel('catalog/category');
					$category->load($categoryIds[0]);
				}
				
				$allProducts [] = array(
					'id' => $product->getId(),
					'category' => $category ? $category->getName() : '',
					'name' => $product->getName(),
					'quantity' => $item->getQtyOrdered(),
					'price' => $item->getPriceInclTax(),
					'ean' => '',
					'isbn' => '',
					'asin' => '',
					'description' => $product->getDescription(),
					'pic_thumb' => Mage::app()->getLayout()->helper('catalog/image')->init($product, 'thumbnail')->__toString(),
					'pic_medium' => Mage::app()->getLayout()->helper('catalog/image')->init($product, 'small_image')->__toString(),
					'pic_large' => Mage::app()->getLayout()->helper('catalog/image')->init($product, 'image')->__toString(),
					'url' => $product->getProductUrl(),
				);
			}
			
			$dataForItembase = array(
				'access_token' => $responseArray['access_token'],
				'email' => $order->getCustomerEmail(),
				'firstname' => $order->getCustomerFirstname(),
				'lastname' => $order->getCustomerLastname(),
				'street' => implode(' ', $order->getBillingAddress()->getStreet()),
				'zip' => $order->getBillingAddress()->getPostcode(),
				'city' => $order->getBillingAddress()->getCity(),
				'country' => Mage::getModel('directory/country')->load($order->getBillingAddress()->getCountryId())->getIso2Code(),
				'phone' => $order->getBillingAddress()->getTelephone(),
				'lang' => substr(Mage::app()->getLocale()->getDefaultLocale(), 0 , 2),
				'purchase_date' => $order->getCreatedAt(),
				'currency' => $order->getOrderCurrencyCode(),
				'total' => $order->getGrandTotal(),
				'order_number' => $order->getId(),
				'customer_id' => $order->getCustomerId(),
				'shipping_cost' => $order->getShippingAmount(),
				'shipping_method' => $order->getShippingDescription(),
				'shop_name' => Mage::app()->getStore()->getName(),
				'products' => $allProducts,
			);
			
			utf8EncodeRecursive($dataForItembase);
			if(is_callable('json_last_error')) {
				json_encode($dataForItembase);
				if(json_last_error() != JSON_ERROR_NONE) itembaseErrorHandler(0, 'json_encode error '.json_last_error(), __FILE__, __LINE__ - 1);
			}
			
			$block = Mage::app()->getLayout()->createBlock('itembase/plugin')
				->setTemplate('itembase/checkout_success.phtml')
				->assign('ibdata', $dataForItembase)
				->assign('ibembedserver', ITEMBASE_SERVER_EMBED)
				->assign('ibhostserver', ITEMBASE_SERVER_HOST)
				->assign('ibpluginversion', ITEMBASE_PLUGIN_VERSION);
			Mage::app()->getLayout()->getBlock('content')->append($block);
		} catch(Exception $e) {
			itembaseErrorHandler($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
		}
		
		restore_error_handler();
	}
}
