<?php
/**
 * @package plugins.reach
 * @subpackage Admin
 */
class CatalogItemExportAction extends KalturaApplicationPlugin
{
	/**
	 * @return string - absolute file path of the phtml template
	 */
	public function getTemplatePath()
	{
		return realpath(dirname(__FILE__));
	}

	public function doAction(Zend_Controller_Action $action)
	{
		$action->getHelper('layout')->disableLayout();
		$request = $action->getRequest();
		$vendorPartnerId = $this->_getParam('filter_input') ? $this->_getParam('filter_input') : $request->getParam('partner_id');

		$client = Infra_ClientHelper::getClient();
		$reachPluginClient = Kaltura_Client_Reach_Plugin::get($client);
		$exportUrl = $reachPluginClient->vendorCatalogItem->getServeUrl($vendorPartnerId);

		$ch = curl_init($exportUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$servedContent = curl_exec($ch);
		curl_close($ch);

		if($servedContent)
		{
			$action->view->servedContent = $servedContent;
		}
	}
}
