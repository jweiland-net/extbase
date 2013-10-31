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
 * Validator based on regular expressions
 *
 * The regular expression is specified in the options by using the array key "regularExpression"
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_RegularExpressionValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'regularExpression' => array('', 'The regular expression to use for validation, used as given', 'string', TRUE)
	);

	/**
	 * Checks if the given value matches the specified regular expression.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws Tx_Extbase_Validation_Exception_InvalidValidationOptionsException
	 * @api
	 */
	public function isValid($value) {
		$result = preg_match($this->options['regularExpression'], $value);
		if ($result === 0) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.regularexpression.nomatch',
					'extbase'
				), 1221565130);
		}
		if ($result === FALSE) {
			throw new Tx_Extbase_Validation_Exception_InvalidValidationOptionsException('regularExpression "' . $this->options['regularExpression'] . '" in RegularExpressionValidator contained an error.', 1298273089);
		}
	}
}

?>