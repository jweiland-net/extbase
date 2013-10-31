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
 * Validator for not empty values
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 */
class Tx_Extbase_Validation_Validator_NotEmptyValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * This validator always needs to be executed even if the given value is empty.
	 * See AbstractValidator::validate()
	 *
	 * @var boolean
	 */
	protected $acceptsEmptyValues = FALSE;

	/**
	 * Checks if the given property ($propertyValue) is not empty (NULL, empty string, empty array or empty object).
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 */
	public function isValid($value) {
		if ($value === NULL) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.notempty.null',
					'extbase'
				), 1221560910);
		}
		if ($value === '') {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.notempty.empty',
					'extbase'
				), 1221560718);
		}
		if (is_array($value) && empty($value)) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.notempty.empty',
					'extbase'
				), 1347992400);
		}
		if (is_object($value) && $value instanceof Countable && $value->count() === 0) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.notempty.empty',
					'extbase'
				), 1347992453);
		}
	}
}

?>