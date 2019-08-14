<?php
class Itembase_Plugin_Block_Registrationlabel extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render element html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
    	foreach(Mage::app()->getStores() as $shop) {
    		if(Mage::getStoreConfig('itembase_section/itembase_group/api_key', $shop->getStoreId()) === NULL) {
    			return sprintf(
    				'<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><h4 id="%s"><a href="%s">%s</a></h4></td></tr>',
    				$element->getHtmlId(),
    				$element->getHtmlId(),
    				Mage::helper('adminhtml')->getUrl('adminhtml/itembase', array('key' => Mage::getSingleton('adminhtml/url')->getSecretKey('itembase', 'index'))),
    				$this->__('If you have no Itembase keys click here!')
    			);
    		}
    	}
		return '';
	}
}
