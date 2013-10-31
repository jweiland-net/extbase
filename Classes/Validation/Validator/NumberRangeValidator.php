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
 * Validator for general numbers
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_NumberRangeValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'minimum' => array(0, 'The minimum value to accept', 'integer'),
		'maximum' => array(PHP_INT_MAX, 'The maximum value to accept', 'integer')
	);

	/**
	 * The given value is valid if it is a number in the specified range.
	 *
	 * @param mixed $value The value that should be validated
	 * @return void
	 * @api
	 */
	public function isValid($value) {
		if (!is_numeric($value)) {
			$this->addError(
				Tx_Extbase_Utility_Localization::translate(
					'validator.numberrange.notvalid',
					'extbase'
				), 1221563685);
			return;
		}

		$minimum = $this->options['minimum'];
		$maximum = $this->options['maximum'];

		/**
		 * @todo: remove this fallback in 6.3
		 * @deprecated since Extbase 1.4, will be removed two versions after Extbase 6.1
		 */
		if ($minimum === NULL && isset($this->options['startRange'])) {
			$minimum = $this->options['startRange'];
		}

		if ($maximum === NULL && isset($this->options['endRange'])) {
			$maximum = $this->options['endRange'];
		}

		if ($minimum > $maximum) {
			$x = $minimum;
			$minimum = $maximum;
			$maximum = $x;
		}
		if ($value < $minimum || $value > $maximum) {
			$this->addError(Tx_Extbase_Utility_Localization::translate(
				'validator.numberrange.range',
				'extbase',
				array(
					$minimum,
					$maximum
				)
			), 1221561046, array($minimum, $maximum));
		}
	}
}

?>