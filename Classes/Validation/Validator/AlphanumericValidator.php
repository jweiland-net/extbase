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
 * Validator for alphanumeric strings
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_AlphanumericValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * The given $value is valid if it is an alphanumeric string, which is defined as [\pL\d]*.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	public function isValid($value) {
		if (!is_string($value) || preg_match('/^[\pL\d]*$/u', $value) !== 1) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.alphanumeric.notvalid',
					'extbase'
				), 1221551320);
		}
	}
}

?>