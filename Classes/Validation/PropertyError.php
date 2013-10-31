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
 * This object holds validation errors for one property.
 *
 * @package Extbase
 * @subpackage Validation
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
 */
class Tx_Extbase_Validation_PropertyError extends Tx_Extbase_Validation_Error {

	/**
	 * @var string The default (english) error message.
	 */
	protected $message = 'Validation errors for property "%s"';

	/**
	 * @var string The error code
	 */
	protected $code = 1242859509;

	/**
	 * @var string The property name
	 */
	protected $propertyName;

	/**
	 * @var array An array of Tx_Extbase_Validation_Error for the property
	 */
	protected $errors = array();

	/**
	 * Create a new property error with the given property name
	 *
	 * @param string $propertyName The property name
	 */
	public function __construct($propertyName) {
		$this->propertyName = $propertyName;
		$this->message = sprintf($this->message, $propertyName);
	}

	/**
	 * Add errors
	 *
	 * @param array $errors Array of Tx_Extbase_Validation_Error for the property
	 * @return void
	 */
	public function addErrors($errors) {
		$this->errors = array_merge($this->errors, $errors);
	}

	/**
	 * Get all errors for the property
	 *
	 * @return array An array of Tx_Extbase_Validation_Error objects or an empty array if no errors occured for the property
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get the property name
	 * @return string The property name for this error
	 */
	public function getPropertyName() {
		return $this->propertyName;
	}
}

?>