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
 * Contract for a validator. This interface has drastically changed with Extbase 1.4.0, so that's why this interface does not contain any mandatory methods.
 *
 * For compatibility with Extbase < 1.4.0, the following methods need to exist:
 * - setOptions($options) to set validation options
 * - isValid($object) to check whether an object is valid (returns boolean value)
 * - getErrors() to get errors occuring during validation.
 *
 * For Extbase >= 1.4.0, the following methods need to exist:
 * - __construct($options) to set validation options
 * - validate($object) to check whether the given object is valid. Returns a Tx_Extbase_Error_Result object which can then be checked for validity.
 *
 * Please see the source file for proper documentation of the above methods.
 *
 * @api
 */
interface Tx_Extbase_Validation_Validator_ValidatorInterface {

	/**
	 * Constructs the validator and sets validation options
	 *
	 * @param array $options The validation options
	 * @api
	 */
	//public function __construct(array $options = array());

	/**
	 * Checks if the given value is valid according to the validator, and returns
	 * the Error Messages object which occurred.
	 *
	 * @param mixed $value The value that should be validated
	 * @return Tx_Extbase_Error_Result
	 * @api
	 * @todo: enable once the old property mapper is removed
	 */
	//public function validate($value);

	/**
	 * Returns the options of this validator which can be specified in the constructor
	 *
	 * @return array
	 * @todo: enable once the old property mapper is removed
	 */
	//public function getOptions();
}

?>