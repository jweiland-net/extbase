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
 * Validator to chain many validators in a disjunction (logical or). So only one
 * validator has to be valid, to make the whole disjunction valid. Errors are
 * only returned if all validators failed.
 *
 * @package Extbase
 * @subpackage Validation\Validator
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Extbase_Validation_Validator_DisjunctionValidator extends Tx_Extbase_Validation_Validator_AbstractCompositeValidator {

	/**
	 * Checks if the given value is valid according to the validators of the
	 * disjunction.
	 *
	 * So only one validator has to be valid, to make the whole disjunction valid.
	 * Errors are only returned if all validators failed.
	 *
	 * @param mixed $value The value that should be validated
	 * @return Tx_Extbase_Error_Result
	 * @api
	 */
	public function validate($value) {
		$validators = $this->getValidators();
		if ($validators->count() > 0) {
			$result = NULL;
			foreach ($validators as $validator) {
				$validatorResult = $validator->validate($value);
				if ($validatorResult->hasErrors()) {
					if ($result === NULL) {
						$result = $validatorResult;
					} else {
						$result->merge($validatorResult);
					}
				} else {
					if ($result === NULL) {
						$result = $validatorResult;
					} else {
						$result->clear();
					}
					break;
				}
			}
		} else {
			$result = new Tx_Extbase_Error_Result();
		}

		return $result;
	}

	/**
	 * Checks if the given value is valid according to the validators of the conjunction.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $value The value that should be validated
	 * @return boolean TRUE if the value is valid, FALSE if an error occured
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function isValid($value) {
		$result = FALSE;
		foreach ($this->validators as $validator) {
			if ($validator->isValid($value) === FALSE) {
				$this->errors = array_merge($this->errors, $validator->getErrors());
			} else {
				$result = TRUE;
			}
		}
		if ($result === TRUE) {
			$this->errors = array();
		}
		return $result;
	}
}

?>