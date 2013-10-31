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
 * An abstract composite validator with consisting of other validators
 *
 * @api
 */
abstract class Tx_Extbase_Validation_Validator_AbstractCompositeValidator implements Tx_Extbase_Validation_Validator_ObjectValidatorInterface, Countable {

	/**
	 * This contains the supported options, their default values and descriptions.
	 *
	 * @var array
	 */
	protected $supportedOptions = array();

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var SplObjectStorage
	 */
	protected $validators;

	/**
	 * @var SplObjectStorage
	 */
	protected $validatedInstancesContainer;

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructs the composite validator and sets validation options
	 *
	 * @param array $options Options for the validator
	 * @api
	 */
	public function __construct(array $options = array()) {
		// check for options given but not supported
		if (($unsupportedOptions = array_diff_key($options, $this->supportedOptions)) !== array()) {
			throw new Tx_Extbase_Validation_Exception_InvalidValidationOptionsException('Unsupported validation option(s) found: ' . implode(', ', array_keys($unsupportedOptions)), 1339079804);
		}

		// check for required options being set
		array_walk(
			$this->supportedOptions,
			function($supportedOptionData, $supportedOptionName, $options) {
				if (isset($supportedOptionData[3]) && !array_key_exists($supportedOptionName, $options)) {
					throw new Tx_Extbase_Validation_Exception_InvalidValidationOptionsException('Required validation option not set: ' . $supportedOptionName, 1339163922);
				}
			},
			$options
		);

		// merge with default values
		$this->options = array_merge(
			array_map(
				function ($value) {
					return $value[0];
				},
				$this->supportedOptions
			),
			$options
		);
		$this->validators = new SplObjectStorage();
	}

	/**
	 * Does nothing.
	 *
	 * @param array $options Not used
	 * @return void
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function setOptions(array $options) {
	}

	/**
	 * Returns an array of errors which occurred during the last isValid() call.
	 *
	 * @return array An array of Tx_Extbase_Validation_Error objects or an empty array if no errors occurred.
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds a new validator to the conjunction.
	 *
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The validator that should be added
	 * @return void
	 * @api
	 */
	public function addValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		if ($validator instanceof Tx_Extbase_Validation_Validator_ObjectValidatorInterface) {
			// @todo: provide bugfix as soon as it is fixed in TYPO3.Flow (http://forge.typo3.org/issues/48093)
			$validator->setValidatedInstancesContainer = $this->validatedInstancesContainer;
		}
		$this->validators->attach($validator);
	}

	/**
	 * Removes the specified validator.
	 *
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The validator to remove
	 * @throws Tx_Extbase_Validation_Exception_NoSuchValidatorException
	 * @return void
	 */
	public function removeValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		if (!$this->validators->contains($validator)) {
			throw new Tx_Extbase_Validation_Exception_NoSuchValidatorException('Cannot remove validator because its not in the conjunction.', 1207020177);
		}
		$this->validators->detach($validator);
	}

	/**
	 * Returns the number of validators contained in this conjunction.
	 *
	 * @return integer The number of validators
	 * @api
	 */
	public function count() {
		return count($this->validators);
	}

	/**
	 * Returns the child validators of this Composite Validator
	 *
	 * @return SplObjectStorage
	 */
	public function getValidators() {
		return $this->validators;
	}

	/**
	 * Returns the options for this validator
	 *
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Allows to set a container to keep track of validated instances.
	 *
	 * @param SplObjectStorage $validatedInstancesContainer A container to keep track of validated instances
	 * @return void
	 * @api
	 */
	public function setValidatedInstancesContainer(SplObjectStorage $validatedInstancesContainer) {
		$this->validatedInstancesContainer = $validatedInstancesContainer;
	}

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if this validator can validate instances of the given object or FALSE if it can't
	 *
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function canValidate($object) {
		// deliberately empty
	}

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 *
	 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
	 */
	public function isPropertyValid($object, $propertyName) {
		// deliberately empty
	}
}

?>