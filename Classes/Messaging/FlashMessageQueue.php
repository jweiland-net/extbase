<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Rupert Germann <rupi@gmx.li>
 *  (c) 2013 Alexander Schnitzler <alex.schnitzler@typovision.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A class which collects and renders flash messages.
 *
 * @author Rupert Germann <rupi@gmx.li>
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class Tx_Extbase_Messaging_FlashMessageQueue extends SplQueue {

	/**
	 * A unique identifier for this queue
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * @param string $identifier The unique identifier for this queue
	 */
	public function __construct($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Adds a message either to the BE_USER session (if the $message has the storeInSession flag set)
	 * or it enqueues the message.
	 *
	 * @param Tx_Extbase_Messaging_FlashMessage $message Instance of Tx_Extbase_Messaging_FlashMessage, representing a message
	 * @throws Tx_Extbase_Exception
	 * @return void
	 */
	public function enqueue(Tx_Extbase_Messaging_FlashMessage$message) {
		if (!($message instanceof Tx_Extbase_Messaging_FlashMessage)) {
			throw new Tx_Extbase_Exception(
				'FlashMessageQueue::enqueue() expects an object of type Tx_Extbase_Messaging_FlashMessage but got type "' . (is_object($message) ? get_class($message) : gettype($message)) . '"',
				1376833554
			);
		}
		if ($message->isSessionMessage()) {
			$this->addFlashMessageToSession($message);
		} else {
			parent::enqueue($message);
		}
	}

	/**
	 * @return void
	 */
	public function dequeue() {
		// deliberately empty
	}

	/**
	 * Adds the given flash message to the array of
	 * flash messages that will be stored in the session.
	 *
	 * @param Tx_Extbase_Messaging_FlashMessage $message
	 * @return void
	 */
	protected function addFlashMessageToSession(Tx_Extbase_Messaging_FlashMessage $message) {
		$queuedFlashMessages = $this->getFlashMessagesFromSession();
		$queuedFlashMessages[] = $message;
		$this->storeFlashMessagesInSession($queuedFlashMessages);
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 *
	 * @return array Array of Tx_Extbase_Messaging_FlashMessage objects
	 */
	protected function getAllMessages() {
		// Get messages from user session
		$queuedFlashMessagesFromSession = $this->getFlashMessagesFromSession();
		$queuedFlashMessages = array_merge($queuedFlashMessagesFromSession, $this->toArray());
		return $queuedFlashMessages;
	}

	/**
	 * Returns all messages from the current PHP session and from the current request.
	 * After fetching the messages the internal queue and the message queue in the session
	 * will be emptied.
	 *
	 * @return array Array of Tx_Extbase_Messaging_FlashMessage objects
	 */
	protected function getAllMessagesAndFlush() {
		$queuedFlashMessages = $this->getAllMessages();
		// Reset messages in user session
		$this->removeAllFlashMessagesFromSession();
		// Reset internal messages
		$this->clear();
		return $queuedFlashMessages;
	}

	/**
	 * Stores given flash messages in the session
	 *
	 * @param array $flashMessages Array of Tx_Extbase_Messaging_FlashMessage
	 * @return void
	 */
	protected function storeFlashMessagesInSession(array $flashMessages) {
		$this->getUserByContext()->setAndSaveSessionData($this->identifier, $flashMessages);
	}

	/**
	 * Removes all flash messages from the session
	 *
	 * @return void
	 */
	protected function removeAllFlashMessagesFromSession() {
		$this->getUserByContext()->setAndSaveSessionData($this->identifier, NULL);
	}

	/**
	 * Returns current flash messages from the session, making sure to always
	 * return an array.
	 *
	 * @return array An array of Tx_Extbase_Messaging_FlashMessage flash messages.
	 */
	protected function getFlashMessagesFromSession() {
		$flashMessages = $this->getUserByContext()->getSessionData($this->identifier);
		return is_array($flashMessages) ? $flashMessages : array();
	}

	/**
	 * Gets user object by context
	 *
	 * @return object User object
	 */
	protected function getUserByContext() {
		return TYPO3_MODE === 'BE' ? $GLOBALS['BE_USER'] : $GLOBALS['TSFE']->fe_user;
	}

	/**
	 * Fetches and renders all available flash messages from the queue.
	 *
	 * @return string All flash messages in the queue rendered as HTML.
	 */
	protected function renderFlashMessages() {
		$content = '';
		$flashMessages = $this->getAllMessagesAndFlush();
		if (count($flashMessages)) {
			foreach ($flashMessages as $flashMessage) {
				/** @var $flashMessage Tx_Extbase_Messaging_FlashMessage */
				$content .= $flashMessage->render();
			}
		}
		return $content;
	}

	/**
	 * Returns all items of the queue as array
	 *
	 * @return array
	 */
	public function toArray() {
		$array = array();
		$this->rewind();
		while ($this->valid()) {
			$array[] = $this->current();
			$this->next();
		}
		return $array;
	}

	/**
	 * Removes all items from the queue
	 *
	 * @return void
	 */
	public function clear() {
		$this->rewind();
		while (!$this->isEmpty()) {
			parent::dequeue();
		}
	}

	/**
	 * This method provides a fallback for deprecated static calls like:
	 * FlashMessageQueue::renderFlashMessages,
	 * FlashMessageQueue::getAllMessagesAndFlush,
	 * FlashMessageQueue::getAllMessages and
	 * FlashMessageQueue::addMessage
	 *
	 * From 6.3 on __callStatic and __call will be removed and the
	 * protected non static methods "renderFlashMessages",
	 * "getAllMessagesAndFlush", "getAllMessages" and "addMessage"
	 * will be made public.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @throws RuntimeException
	 * @return void|array|string
	 * @deprecated since 6.1 will be removed in 6.3
	 */
	static public function __callStatic($name, array $arguments) {
		t3lib_div::logDeprecatedFunction();
		/** @var $flashMessageService Tx_Extbase_Messaging_FlashMessageService */
		$flashMessageService = t3lib_div::makeInstance('Tx_Extbase_Messaging_FlashMessageService');
		$identifier = 'core.template.flashMessages';
		switch ($name) {
			case 'renderFlashMessages':
				return $flashMessageService->getMessageQueueByIdentifier($identifier)->renderFlashMessages();
				break;
			case 'getAllMessagesAndFlush':
				return $flashMessageService->getMessageQueueByIdentifier($identifier)->getAllMessagesAndFlush();
				break;
			case 'getAllMessages':
				return $flashMessageService->getMessageQueueByIdentifier($identifier)->getAllMessages();
				break;
			case 'addMessage':
				$flashMessageService->getMessageQueueByIdentifier($identifier)->enqueue(current($arguments));
				break;
			default:
				throw new RuntimeException('The requested method "' . $name . '" cannot be called via __callStatic.', 1363300030);
		}
	}

	/**
	 * This method is deprecated but will not log a deprecation
	 * message because once the here used method names are 'free'
	 * again they will be implemented natively in this class. This
	 * is not possible at the moment because these methods have
	 * been static and need to be statically callable through
	 * __callStatic until 6.3.
	 *
	 * @param string $name
	 * @param array $arguments
	 * @throws RuntimeException
	 * @return void|array|string
	 * @see __callStatic
	 * @deprecated since 6.1 will be removed in 6.3
	 */
	public function __call($name, array $arguments) {
		switch ($name) {
			case 'renderFlashMessages':
				return $this->renderFlashMessages();
				break;
			case 'getAllMessagesAndFlush':
				return $this->getAllMessagesAndFlush();
				break;
			case 'getAllMessages':
				return $this->getAllMessages();
				break;
			case 'addMessage':
				$this->enqueue(current($arguments));
				break;
			default:
				throw new RuntimeException('The requested method "' . $name . '" cannot be called via __call.', 1363300072);
		}
	}
}

?>