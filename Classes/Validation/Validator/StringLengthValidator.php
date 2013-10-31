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
 * Validator for string length
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_StringLengthValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'minimum' => array(0, 'Minimum length for a valid string', 'integer'),
		'maximum' => array(PHP_INT_MAX, 'Maximum length for a valid string', 'integer')
	);

	/**
	 * Checks if the given value is a valid string (or can be cast to a string
	 * if an object is given) and its length is between minimum and maximum
	 * specified in the validation options.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @throws Tx_Extbase_Validation_Exception_InvalidValidationOptionsException
	 * @api
	 */
	public function isValid($value) {
		if ($this->options['maximum'] < $this->options['minimum']) {
			throw new Tx_Extbase_Validation_Exception_InvalidValidationOptionsException('The \'maximum\' is shorter than the \'minimum\' in the StringLengthValidator.', 1238107096);
		}

		if (is_object($value)) {
			if (!method_exists($value, '__toString')) {
				$this->addError('The given object could not be converted to a string.', 1238110957);
				return;
			}
		} elseif (!is_string($value)) {
			$this->addError('The given value was not a valid string.', 1269883975);
			return;
		}

		// TODO Use \TYPO3\CMS\Core\Charset\CharsetConverter::strlen() instead; How do we get the charset?
		$stringLength = strlen($value);
		$isValid = TRUE;
		if ($stringLength < $this->options['minimum']) {
			$isValid = FALSE;
		}
		if ($stringLength > $this->options['maximum']) {
			$isValid = FALSE;
		}

		if ($isValid === FALSE) {
			if ($this->options['minimum'] > 0 && $this->options['maximum'] < PHP_INT_MAX) {
				$this->addError(
					Tx_Extbase_Utility_Localization::translate(
						'validator.stringlength.between',
						'extbase',
						array (
							$this->options['minimum'],
							$this->options['maximum']
						)
					), 1238108067, array($this->options['minimum'], $this->options['maximum']));
			} elseif ($this->options['minimum'] > 0) {
				$this->addError(
					Tx_Extbase_Utility_Localization::translate(
						'validator.stringlength.less',
						'extbase',
						array(
							$this->options['minimum']
						)
					), 1238108068, array($this->options['minimum']));
			} else {
				$this->addError(
					Tx_Extbase_Utility_Localization::translate(
						'validator.stringlength.exceed',
						'extbase',
						array(
							$this->options['maximum']
						)
					), 1238108069, array($this->options['maximum']));
			}
		}
	}
}

?>