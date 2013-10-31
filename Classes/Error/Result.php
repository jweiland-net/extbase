<?php

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Result object for operations dealing with objects, such as the Property Mapper or the Validators.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Tx_Extbase_Error_Result {

	/**
	 * @var array<Tx_Extbase_Error_Error>
	 */
	protected $errors = array();

	/**
	 * Caches the existence of errors
	 * @var boolean
	 */
	protected $errorsExist = FALSE;

	/**
	 * @var array<Tx_Extbase_Error_Warning>
	 */
	protected $warnings = array();

	/**
	 * Caches the existence of warning
	 * @var boolean
	 */
	protected $warningsExist = FALSE;

	/**
	 * @var array<Tx_Extbase_Error_Notice>
	 */
	protected $notices = array();

	/**
	 * Caches the existence of notices
	 * @var boolean
	 */
	protected $noticesExist = FALSE;

	/**
	 * The result objects for the sub properties
	 *
	 * @var array<Tx_Extbase_Error_Result>
	 */
	protected $propertyResults = array();

	/**
	 * @var Tx_Extbase_Error_Result
	 */
	protected $parent = NULL;

	/**
	 * Injects the parent result and propagates the
	 * cached error states upwards
	 *
	 * @param Tx_Extbase_Error_Error $parent
	 * @return void
	 */
	public function setParent(Tx_Extbase_Error_Result $parent) {
		if ($this->parent !== $parent) {
			$this->parent = $parent;
			if ($this->hasErrors()) {
				$parent->setErrorsExist();
			}
			if ($this->hasWarnings()) {
				$parent->setWarningsExist();
			}
			if ($this->hasNotices()) {
				$parent->setNoticesExist();
			}
		}
	}

	/**
	 * Add an error to the current Tx_Extbase_Error_Result object
	 *
	 * @param Tx_Extbase_Error_Error $error
	 * @return void
	 * @api
	 */
	public function addError(Tx_Extbase_Error_Error $error) {
		$this->errors[] = $error;
		$this->setErrorsExist();
	}

	/**
	 * Add a warning to the current Tx_Extbase_Error_Result object
	 *
	 * @param Tx_Extbase_Error_Warning $warning
	 * @return void
	 * @api
	 */
	public function addWarning(Tx_Extbase_Error_Warning $warning) {
		$this->warnings[] = $warning;
		$this->setWarningsExist();
	}

	/**
	 * Add a notice to the current Tx_Extbase_Error_Result object
	 *
	 * @param Tx_Extbase_Error_Notice $notice
	 * @return void
	 * @api
	 */
	public function addNotice(Tx_Extbase_Error_Notice $notice) {
		$this->notices[] = $notice;
		$this->setNoticesExist();
	}

	/**
	 * Get all errors in the current Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return array<Tx_Extbase_Error_Error>
	 * @api
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Get all warnings in the current Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return array<Tx_Extbase_Error_Warning>
	 * @api
	 */
	public function getWarnings() {
		return $this->warnings;
	}

	/**
	 * Get all notices in the current Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return array<Tx_Extbase_Error_Notice>
	 * @api
	 */
	public function getNotices() {
		return $this->notices;
	}

	/**
	 * Get the first error object of the current Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return Tx_Extbase_Error_Error
	 * @api
	 */
	public function getFirstError() {
		reset($this->errors);
		return current($this->errors);
	}

	/**
	 * Get the first warning object of the current Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return Tx_Extbase_Error_Warning
	 * @api
	 */
	public function getFirstWarning() {
		reset($this->warnings);
		return current($this->warnings);
	}

	/**
	 * Get the first notice object of the curren Tx_Extbase_Error_Result object (non-recursive)
	 *
	 * @return Tx_Extbase_Error_Notice
	 * @api
	 */
	public function getFirstNotice() {
		reset($this->notices);
		return current($this->notices);
	}

	/**
	 * Return a Tx_Extbase_Error_Result object for the given property path. This is
	 * a fluent interface, so you will proboably use it like:
	 * $result->forProperty('foo.bar')->getErrors() -- to get all errors
	 * for property "foo.bar"
	 *
	 * @param string $propertyPath
	 * @return Tx_Extbase_Error_Result
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function forProperty($propertyPath) {
		if ($propertyPath === '' || $propertyPath === NULL) {
			return $this;
		}
		if (strpos($propertyPath, '.') !== FALSE) {
			return $this->recurseThroughResult(explode('.', $propertyPath));
		}
		if (!isset($this->propertyResults[$propertyPath])) {
			$this->propertyResults[$propertyPath] = new Tx_Extbase_Error_Result();
			$this->propertyResults[$propertyPath]->setParent($this);
		}
		return $this->propertyResults[$propertyPath];	}

	/**
	 * Internal use only!
	 *
	 * @param array $pathSegments
	 * @return Tx_Extbase_Error_Result
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function recurseThroughResult(array $pathSegments) {
		if (count($pathSegments) === 0) {
			return $this;
		}

		$propertyName = array_shift($pathSegments);

		if (!isset($this->propertyResults[$propertyName])) {
			$this->propertyResults[$propertyName] = new Tx_Extbase_Error_Result();
			$this->propertyResults[$propertyName]->setParent($this);
		}

		return $this->propertyResults[$propertyName]->recurseThroughResult($pathSegments);
	}

	/**
	 * Sets the error cache to TRUE and propagates the information
	 * upwards the Tx_Extbase_Error_Result-Object Tree
	 *
	 * @return void
	 */
	protected function setErrorsExist() {
		$this->errorsExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setErrorsExist();
		}
	}

	/**
	 * Sets the warning cache to TRUE and propagates the information
	 * upwards the Tx_Extbase_Error_Result-Object Tree
	 *
	 * @return void
	 */
	protected function setWarningsExist() {
		$this->warningsExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setWarningsExist();
		}
	}

	/**
	 * Sets the notices cache to TRUE and propagates the information
	 * upwards the Tx_Extbase_Error_Result-Object Tree
	 *
	 * @return void
	 */
	protected function setNoticesExist() {
		$this->noticesExist = TRUE;
		if ($this->parent !== NULL) {
			$this->parent->setNoticesExist();
		}
	}

	/**
	 * Does the current Tx_Extbase_Error_Result object have Notices, Errors or Warnings? (Recursively)
	 *
	 * @return boolean
	 */
	public function hasMessages() {
		return $this->errorsExist || $this->noticesExist || $this->warningsExist;
	}

	/**
	 * Clears the result
	 *
	 * @return void
	 */
	public function clear() {
		$this->errors = array();
		$this->notices = array();
		$this->warnings = array();

		$this->warningsExist = FALSE;
		$this->noticesExist = FALSE;
		$this->errorsExist = FALSE;

		$this->propertyResults = array();
	}

	/**
	 * Internal use only!
	 *
	 * @param string $propertyName
	 * @param string $checkerMethodName
	 * @return boolean
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function hasProperty($propertyName, $checkerMethodName) {
		if (count($this->{$propertyName}) > 0) {
			return TRUE;
		}
		foreach ($this->propertyResults as $subResult) {
			if ($subResult->{$checkerMethodName}()) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Does the current Tx_Extbase_Error_Result object have Errors? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasErrors() {
		return $this->hasProperty('errors', 'hasErrors');
	}


	/**
	 * Does the current Tx_Extbase_Error_Result object have Warnings? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasWarnings() {
		return $this->hasProperty('warnings', 'hasWarnings');
	}

	/**
	 * Does the current Tx_Extbase_Error_Result object have Notices? (Recursively)
	 *
	 * @return boolean
	 * @api
	 */
	public function hasNotices() {
		return $this->hasProperty('notices', 'hasNotices');
	}

	/**
	 * Get a list of all Error objects recursively. The result is an array,
	 * where the key is the property path where the error occured, and the
	 * value is a list of all errors (stored as array)
	 *
	 * @return array<Tx_Extbase_Error_Error>
	 * @api
	 */
	public function getFlattenedErrors() {
		$result = array();
		$this->flattenTree('errors', $result, array());
		return $result;
	}

	/**
	 * Get a list of all Warning objects recursively. The result is an array,
	 * where the key is the property path where the warning occured, and the
	 * value is a list of all warnings (stored as array)
	 *
	 * @return array<Tx_Extbase_Error_Warning>
	 * @api
	 */
	public function getFlattenedWarnings() {
		$result = array();
		$this->flattenTree('warnings', $result, array());
		return $result;
	}

	/**
	 * Get a list of all Notice objects recursively. The result is an array,
	 * where the key is the property path where the notice occured, and the
	 * value is a list of all notices (stored as array)
	 *
	 * @return array<Tx_Extbase_Error_Notice>
	 * @api
	 */
	public function getFlattenedNotices() {
		$result = array();
		$this->flattenTree('notices', $result, array());
		return $result;
	}

	/**
	 * Only use internally!
	 *
	 * Flatten a tree of Tx_Extbase_Error_Result objects, based on a certain property.
	 *
	 * @param string $propertyName
	 * @param array $result
	 * @param array $level
	 * @return void
	 */
	public function flattenTree($propertyName, &$result, $level) {
		if (count($this->$propertyName) > 0) {
			$result[implode('.', $level)] = $this->$propertyName;
		}
		foreach ($this->propertyResults as $subPropertyName => $subResult) {
			array_push($level, $subPropertyName);
			$subResult->flattenTree($propertyName, $result, $level);
			array_pop($level);
		}
	}

	/**
	 * Merge the given Tx_Extbase_Error_Result object into this one.
	 *
	 * @param Tx_Extbase_Error_Result $otherResult
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function merge(Tx_Extbase_Error_Result $otherResult) {
		if ($otherResult->errorsExist) {
			$this->mergeProperty($otherResult, 'getErrors', 'addError');
		}
		if ($otherResult->warningsExist) {
			$this->mergeProperty($otherResult, 'getWarnings', 'addWarning');
		}
		if ($otherResult->noticesExist) {
			$this->mergeProperty($otherResult, 'getNotices', 'addNotice');
		}
		foreach ($otherResult->getSubResults() as $subPropertyName => $subResult) {
			/** @var $subResult Tx_Extbase_Error_Result */
			if (array_key_exists($subPropertyName, $this->propertyResults) && $this->propertyResults[$subPropertyName]->hasMessages()) {
				$this->forProperty($subPropertyName)->merge($subResult);
			} else {
				$this->propertyResults[$subPropertyName] = $subResult;
				$subResult->setParent($this);
			}
		}
	}

	/**
	 * Merge a single property from the other result object.
	 *
	 * @param Tx_Extbase_Error_Result $otherResult
	 * @param string $getterName
	 * @param string $adderName
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function mergeProperty(Tx_Extbase_Error_Result $otherResult, $getterName, $adderName) {
		foreach ($otherResult->$getterName() as $messageInOtherResult) {
			$this->$adderName($messageInOtherResult);
		}
	}

	/**
	 * Get a list of all sub Result objects available.
	 *
	 * @return array<Tx_Extbase_Erro_Result>
	 */
	public function getSubResults() {
		return $this->propertyResults;
	}
}

?>