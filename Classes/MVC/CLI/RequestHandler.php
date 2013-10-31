<?php
/***************************************************************
*  Copyright notice
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
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Extbase_MVC_CLI_RequestHandler implements Tx_Extbase_MVC_RequestHandlerInterface {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_MVC_Dispatcher
	 * @inject
	 */
	protected $dispatcher;

	/**
	 * @var Tx_Extbase_MVC_CLI_RequestBuilder
	 * @inject
	 */
	protected $requestBuilder;

	/**
	 * @var Tx_Extbase_Service_EnvironmentService
	 * @inject
	 */
	protected $environmentService;

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$request = $this->requestBuilder->build(array_slice($commandLine, 1));
		/** @var $response Tx_Extbase_MVC_CLI_Response */
		$response = $this->objectManager->get('Tx_Extbase_MVC_CLI_Response');
		$this->dispatcher->dispatch($request, $response);
		$response->send();
	}

	/**
	 * This request handler can handle any command line request.
	 *
	 * @return boolean If the request is a command line request, TRUE otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canHandleRequest() {
		return $this->environmentService->isEnvironmentInCliMode();
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 90;
	}
}
?>