<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2009 Sebastian Kurfürst <sebastian@typo3.org>
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
 * This is a container for all Flash Messages. It is of scope session, but as Extbase
 * has no session scope, we need to save it manually.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope session
 * @api
 */
class Tx_Extbase_MVC_Controller_FlashMessageContainer implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_MVC_Controller_ControllerContext
	 */
	protected $controllerContext;

	/**
	 * @param Tx_Extbase_MVC_Controller_ControllerContext $controllerContext
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function setControllerContext(Tx_Extbase_MVC_Controller_ControllerContext $controllerContext) {
		$this->controllerContext = $controllerContext;
	}

	/**
	 * Add another flash message.
	 * Severity can be specified and must be one of
	 * Tx_Extbase_Messaging_FlashMessage::NOTICE,
	 * Tx_Extbase_Messaging_FlashMessage::INFO,
	 * Tx_Extbase_Messaging_FlashMessage::OK,
	 * Tx_Extbase_Messaging_FlashMessage::WARNING,
	 * Tx_Extbase_Messaging_FlashMessage::ERROR
	 *
	 * @param string $message
	 * @param string $title optional message title
	 * @param integer $severity optional severity code. One of the Tx_Extbase_Messaging_FlashMessage constants
	 * @throws InvalidArgumentException
	 * @return void
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function add($message, $title = '', $severity = Tx_Extbase_Messaging_FlashMessage::OK) {
		t3lib_div::logDeprecatedFunction();
		if (!is_string($message)) {
			throw new InvalidArgumentException(
				'The flash message must be string, ' . gettype($message) . ' given.',
				1243258395
			);
		}
		/** @var $flashMessage Tx_Extbase_Messaging_FlashMessage */
		$flashMessage = t3lib_div::makeInstance(
			'Tx_Extbase_Messaging_FlashMessage', $message, $title, $severity, TRUE
		);
		$this->controllerContext->getFlashMessageQueue()->addMessage($flashMessage);
	}

	/**
	 * @return array An array of flash messages: array<Tx_Extbase_Messaging_FlashMessage>
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getAllMessages() {
		t3lib_div::logDeprecatedFunction();
		return $this->controllerContext->getFlashMessageQueue()->getAllMessages();
	}

	/**
	 * @return void
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function flush() {
		t3lib_div::logDeprecatedFunction();
		$this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
	}

	/**
	 * @return array An array of flash messages: array<Tx_Extbase_Messaging_FlashMessage>
	 * @deprecated since 6.1, will be removed 2 versions later
	 */
	public function getAllMessagesAndFlush() {
		t3lib_div::logDeprecatedFunction();
		return $this->controllerContext->getFlashMessageQueue()->getAllMessagesAndFlush();
	}

}
?>