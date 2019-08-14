<?php
class Itembase_Plugin_IndexController extends Mage_Core_Controller_Front_Action
{
	public function indexAction()
	{
		include('Itembase/Plugin/plugindata.php');

		$language = substr(Mage::app()->getLocale()->getDefaultLocale(), 0 , 2);
		$opts = array('http' =>
			array(
				'ignore_errors' => true,
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query(array_merge($_GET, array(
					'lang' => $language,
					'api_key' => Mage::getStoreConfig('itembase_section/itembase_group/api_key'),
				)))
			)
		);
		$context = stream_context_create($opts);
		$html = file_get_contents(ITEMBASE_SERVER_EMBED.'/embed/publicpage', false, $context);
		
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('core/text', 'itembase/page')->setText($html));
		$this->renderLayout();
	}
}