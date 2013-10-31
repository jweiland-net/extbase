<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * The controller context contains information from the controller
 *
 * @api
 */
class Tx_Extbase_MVC_Controller_ControllerContext {

	/**
	 * @var Tx_Extbase_MVC_Request
	 */
	protected $request;

	/**
	 * @var Tx_Extbase_MVC_Response
	 */
	protected $response;

	/**
	 * @var Tx_Extbase_MVC_Controller_Arguments
	 */
	protected $arguments;

	/**
	 * @var Tx_Extbase_Property_MappingResults
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	protected $argumentsMappingResults;

	/**
	 * @var Tx_Extbase_MVC_Web_Routing_UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var Tx_Extbase_MVC_Controller_FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * @var Tx_Extbase_Messaging_FlashMessageQueue
	 */
	protected $flashMessageQueue;

	/**
	 * @var Tx_Extbase_Messaging_FlashMessageService
	 * @inject
	 */
	protected $flashMessageService;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 * @inject
	 */
	protected $extensionService;

	/**
	 * Set the request of the controller
	 *
	 * @param Tx_Extbase_MVC_Request $request
	 * @return void
	 */
	public function setRequest(Tx_Extbase_MVC_Request $request) {
		$this->request = $request;
	}

	/**
	 * Get the request of the controller
	 *
	 * @return Tx_Extbase_MVC_Request
	 * @api
	 */
	public function getRequest() {
		return $this->request;
	}

	/**
	 * Set the response of the controller
	 *
	 * @param Tx_Extbase_MVC_Response $request
	 * @return void
	 */
	public function setResponse(Tx_Extbase_MVC_Response $response) {
		$this->response = $response;
	}

	/**
	 * Get the response of the controller
	 *
	 * @return Tx_Extbase_MVC_Request
	 * @api
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Set the arguments of the controller
	 *
	 * @param Tx_Extbase_MVC_Controller_Arguments $arguments
	 * @return void
	 */
	public function setArguments(Tx_Extbase_MVC_Controller_Arguments $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Get the arguments of the controller
	 *
	 * @return Tx_Extbase_MVC_Controller_Arguments
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Set the arguments mapping results of the controller
	 *
	 * @param Tx_Extbase_Property_MappingResults $argumentsMappingResults
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function setArgumentsMappingResults(Tx_Extbase_Property_MappingResults $argumentsMappingResults) {
		$this->argumentsMappingResults = $argumentsMappingResults;
	}

	/**
	 * Get the arguments mapping results of the controller
	 *
	 * @return Tx_Extbase_Property_MappingResults
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function getArgumentsMappingResults() {
		return $this->argumentsMappingResults;
	}

	/**
	 * Tx_Extbase_MVC_Web_Routing_UriBuilder $uriBuilder
	 * @return void
	 */
	public function setUriBuilder(Tx_Extbase_MVC_Web_Routing_UriBuilder $uriBuilder) {
		$this->uriBuilder = $uriBuilder;
	}

	/**
	 * @return Tx_Extbase_MVC_Web_Routing_UriBuilder
	 * @api
	 */
	public function getUriBuilder() {
		return $this->uriBuilder;
	}

	/**
	 * Set the flash messages
	 *
	 * @param Tx_Extbase_MVC_Controller_FlashMessageContainer $flashMessageContainer
	 * @deprecated since 6.1, will be removed 2 versions later
	 * @return void
	 */
	public function setFlashMessageContainer(Tx_Extbase_MVC_Controller_FlashMessageContainer $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
		$flashMessageContainer->setControllerContext($this);
	}

	/**
	 * Get the flash messages
	 *
	 * @return Tx_Extbase_MVC_Controller_FlashMessageContainer
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getFlashMessageContainer() {
		t3lib_div::logDeprecatedFunction();
		return $this->flashMessageContainer;
	}

	/**
	 * @return Tx_Extbase_Messaging_FlashMessageQueue
	 * @api
	 */
	public function getFlashMessageQueue() {
		if (!$this->flashMessageQueue instanceof Tx_Extbase_Messaging_FlashMessageQueue) {
			if ($this->useLegacyFlashMessageHandling()) {
				$this->flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
			} else {
				$this->flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier(
					'extbase.flashmessages.' . $this->extensionService->getPluginNamespace($this->request->getControllerExtensionName(), $this->request->getPluginName())
				);
			}
		}

		return $this->flashMessageQueue;
	}

	/**
	 * @deprecated since 6.1, will be removed 2 versions later
	 * @return boolean
	 */
	public function useLegacyFlashMessageHandling() {
		return (boolean) Tx_Extbase_Reflection_ObjectAccess::getPropertyPath(
			$this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK),
			'legacy.enableLegacyFlashMessageHandling'
		);
	}
}
?>