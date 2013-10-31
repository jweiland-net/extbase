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
 * A controller argument
 *
 * @api
 */
class Tx_Extbase_MVC_Controller_Argument {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_Persistence_QueryFactory
	 * @inject
	 */
	protected $queryFactory;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * This is the old property mapper, which has been completely rewritten for 1.4.
	 * @inject
	 *
	 * @var Tx_Extbase_Property_Mapper
	 */
	protected $deprecatedPropertyMapper;

	/**
	 * The new, completely rewritten property mapper since Extbase 1.4.
	 *
	 * @var Tx_Extbase_Property_PropertyMapper
	 * @inject
	 */
	protected $propertyMapper;

	/**
	 * @var Tx_Extbase_MVC_Controller_MvcPropertyMappingConfiguration
	 * @inject
	 */
	protected $propertyMappingConfiguration;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Service_TypeHandlingService
	 */
	protected $typeHandlingService;

	/**
	 * Name of this argument
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Short name of this argument
	 *
	 * @var string
	 */
	protected $shortName = NULL;

	/**
	 * Data type of this argument's value
	 *
	 * @var string
	 */
	protected $dataType = NULL;

	/**
	 * If the data type is an object, the class schema of the data type class is resolved
	 *
	 * @var Tx_Extbase_Reflection_ClassSchema
	 */
	protected $dataTypeClassSchema;

	/**
	 * TRUE if this argument is required
	 *
	 * @var boolean
	 */
	protected $isRequired = FALSE;

	/**
	 * Actual value of this argument
	 *
	 * @var object
	 */
	protected $value = NULL;

	/**
	 * Default value. Used if argument is optional.
	 *
	 * @var mixed
	 */
	protected $defaultValue = NULL;

	/**
	 * A custom validator, used supplementary to the base validation
	 *
	 * @var Tx_Extbase_Validation_Validator_ValidatorInterface
	 */
	protected $validator = NULL;

	/**
	 * The validation results. This can be asked if the argument has errors.
	 *
	 * @var Tx_Extbase_Error_Result
	 */
	protected $validationResults = NULL;

	/**
	 * Uid for the argument, if it has one
	 * @var string
	 */
	protected $uid = NULL;

	const ORIGIN_CLIENT = 0;
	const ORIGIN_PERSISTENCE = 1;
	const ORIGIN_PERSISTENCE_AND_MODIFIED = 2;
	const ORIGIN_NEWLY_CREATED = 3;

	/**
	 * The origin of the argument value. This is only meaningful after argument mapping.
	 *
	 * One of the ORIGIN_* constants above
	 * @var integer
	 */
	protected $origin = 0;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 * @inject
	 */
	protected $persistenceManager;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @throws InvalidArgumentException if $name is not a string or empty
	 * @api
	 */
	public function __construct($name, $dataType) {
		if (!is_string($name)) {
			throw new \InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		}
		if (strlen($name) === 0) {
			throw new \InvalidArgumentException('$name must be a non-empty string, ' . strlen($name) . ' characters given.', 1232551853);
		}
		$this->name = $name;
		$this->dataType = $dataType;
	}

	/**
	 * @param Tx_Extbase_Reflection_Service $reflectionService
	 * @return void
	 */
	public function injectReflectionService(Tx_Extbase_Reflection_Service $reflectionService) {
		$this->reflectionService = $reflectionService;
		// Check for classnames (which have at least one underscore or backslash)
		$this->dataTypeClassSchema = strpbrk($this->dataType, '_\\') !== FALSE ? $this->reflectionService->getClassSchema($this->dataType) : NULL;
	}

	/**
	 * @param Tx_Extbase_Service_TypeHandlingService $typeHandlingService
	 * @return void
	 */
	public function injectTypeHandlingService(Tx_Extbase_Service_TypeHandlingService $typeHandlingService) {
		$this->typeHandlingService = $typeHandlingService;
		$this->dataType = $this->typeHandlingService->normalizeType($this->dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @api
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the short name of this argument.
	 *
	 * @param string $shortName A "short name" - a single character
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 * @api
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) !== 1)) {
			throw new \InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		}
		$this->shortName = $shortName;
		return $this;	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 * @api
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType The data type. Can be either a built-in type such as "Text" or "Integer" or a fully qualified object name
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @api
	 */
	public function setDataType($dataType) {
		$this->dataType = $dataType;
		$this->dataTypeClassSchema = $this->reflectionService->getClassSchema($dataType);
		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @api
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Marks this argument to be required
	 *
	 * @param boolean $required TRUE if this argument should be required
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @api
	 */
	public function setRequired($required) {
		$this->isRequired = (boolean)$required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 * @api
	 */
	public function isRequired() {
		return $this->isRequired;
	}

	/**
	 * Sets the default value of the argument
	 *
	 * @param mixed $defaultValue Default value
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @api
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
		return $this;
	}

	/**
	 * Returns the default value of this argument
	 *
	 * @return mixed The default value
	 * @api
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}

	/**
	 * Sets a custom validator which is used supplementary to the base validation
	 *
	 * @param Tx_Extbase_Validation_Validator_ValidatorInterface $validator The actual validator object
	 * @return Tx_Extbase_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @api
	 */
	public function setValidator(Tx_Extbase_Validation_Validator_ValidatorInterface $validator) {
		$this->validator = $validator;
		return $this;
	}

	/**
	 * Create and set a validator chain
	 *
	 * @param array Object names of the validators
	 * @return Tx_Extbase_MVC_Controller_Argument Returns $this (used for fluent interface)
	 * @api
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function setNewValidatorConjunction(array $objectNames) {
		if ($this->validator === NULL) {
			$this->validator = $this->objectManager->create('Tx_Extbase_Validation_Validator_ConjunctionValidator');
		}
		foreach ($objectNames as $objectName) {
			if (!class_exists($objectName)) {
				$objectName = 'Tx_Extbase_Validation_Validator_' . $objectName;
			}
			$this->validator->addValidator($this->objectManager->get($objectName));
		}
		return $this;
	}

	/**
	 * Returns the set validator
	 *
	 * @return Tx_Extbase_Validation_Validator_ValidatorInterface The set validator, NULL if none was set
	 * @api
	 */
	public function getValidator() {
		return $this->validator;
	}

	/**
	 * Get the origin of the argument value. This is only meaningful after argument mapping.
	 *
	 * @return integer one of the ORIGIN_* constants
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function getOrigin() {
		return $this->origin;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $rawValue: The value of this argument
	 * @return Tx_Extbase_MVC_Controller_Argument $this
	 * @throws Tx_Extbase_MVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 */
	public function setValue($rawValue) {
		if ($this->configurationManager->isFeatureEnabled('rewrittenPropertyMapper')) {
			if ($rawValue === NULL) {
				$this->value = NULL;
				return $this;
			}
			if (is_object($rawValue) && $rawValue instanceof $this->dataType) {
				$this->value = $rawValue;
				return $this;
			}
			$this->value = $this->propertyMapper->convert($rawValue, $this->dataType, $this->propertyMappingConfiguration);
			$this->validationResults = $this->propertyMapper->getMessages();
			if ($this->validator !== NULL) {
				// TODO: Validation API has also changed!!!
				$validationMessages = $this->validator->validate($this->value);
				$this->validationResults->merge($validationMessages);
			}
			return $this;
		} else {
			if ($rawValue === NULL || (is_object($rawValue) && $rawValue instanceof $this->dataType)) {
				$this->value = $rawValue;
			} else {
				$this->value = $this->transformValue($rawValue);
			}
			return $this;
		}
	}

	/**
	 * Checks if the value is a UUID or an array but should be an object, i.e.
	 * the argument's data type class schema is set. If that is the case, this
	 * method tries to look up the corresponding object instead.
	 *
	 * Additionally, it maps arrays to objects in case it is a normal object.
	 *
	 * @param mixed $value The value of an argument
	 * @return mixed
	 * @throws Tx_Extbase_MVC_Exception_InvalidArgumentValue
	 * @deprecated since Extbase 1.4, will be removed in Extbase 6.0
	 */
	protected function transformValue($value) {
		if (!class_exists($this->dataType)) {
			return $value;
		}
		$transformedValue = NULL;
		if ($this->dataTypeClassSchema !== NULL) {
			// The target object is an Entity or ValueObject.
			if (is_numeric($value)) {
				$this->origin = self::ORIGIN_PERSISTENCE;
				$transformedValue = $this->findObjectByUid($value);
			} elseif (is_array($value)) {
				$this->origin = self::ORIGIN_PERSISTENCE_AND_MODIFIED;
				$transformedValue = $this->deprecatedPropertyMapper->map(array_keys($value), $value, $this->dataType);
			}
		} else {
			if (!is_array($value)) {
				throw new Tx_Extbase_MVC_Exception_InvalidArgumentValue('The value was a simple type, so we could not map it to an object. Maybe the @entity or @valueobject annotations are missing?', 1251730701);
			}
			$this->origin = self::ORIGIN_NEWLY_CREATED;
			$transformedValue = $this->deprecatedPropertyMapper->map(array_keys($value), $value, $this->dataType);
		}

		if (!$transformedValue instanceof $this->dataType && ($transformedValue !== NULL || $this->isRequired())) {
			throw new Tx_Extbase_MVC_Exception_InvalidArgumentValue('The value must be of type "' . $this->dataType . '", but was of type "' . (is_object($transformedValue) ? get_class($transformedValue) : gettype($transformedValue)) . '".' . ($this->deprecatedPropertyMapper->getMappingResults()->hasErrors() ? '<p>' . implode('<br />', $this->deprecatedPropertyMapper->getMappingResults()->getErrors()) . '</p>' : ''), 1251730701);
		}
		return $transformedValue;
	}

	/**
	 * Finds an object from the repository by searching for its technical UID.
	 *
	 * @param int $uid The object's uid
	 * @return object Either the object matching the uid or, if none or more than one object was found, NULL
	 */
	protected function findObjectByUid($uid) {
		$query = $this->queryFactory->create($this->dataType);
		$query->getQuerySettings()->setRespectSysLanguage(FALSE);
		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		return $query->matching($query->equals('uid', $uid))->execute()->getFirst();
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 * @api
	 */
	public function getValue() {
		if ($this->value === NULL) {
			return $this->defaultValue;
		} else {
			return $this->value;
		}
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 * @deprecated since Extbase 1.4.0, will be removed with Extbase 6.0
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * Return the Property Mapping Configuration used for this argument; can be used by the initialize*action to modify the Property Mapping.
	 *
	 * @return Tx_Extbase_MVC_Controller_MvcPropertyMappingConfiguration
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getPropertyMappingConfiguration() {
		return $this->propertyMappingConfiguration;
	}

	/**
	 * @return boolean TRUE if the argument is valid, FALSE otherwise
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function isValid() {
		return !$this->validationResults->hasErrors();
	}

	/**
	 * @return array<Tx_Extbase_Error_Result> Validation errors which have occured.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getValidationResults() {
		return $this->validationResults;
	}

	/**
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>