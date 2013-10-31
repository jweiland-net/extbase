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
 * A Storage backend
 *
 * @package Extbase
 * @subpackage Persistence\Storage
 * @version $Id$
 */
class Tx_Extbase_Persistence_Storage_Typo3DbBackend implements Tx_Extbase_Persistence_Storage_BackendInterface, t3lib_Singleton {

	const OPERATOR_EQUAL_TO_NULL = 'operatorEqualToNull';
	const OPERATOR_NOT_EQUAL_TO_NULL = 'operatorNotEqualToNull';

	/**
	 * The TYPO3 database object
	 *
	 * @var t3lib_db
	 */
	protected $databaseHandle;

	/**
	 * @var Tx_Extbase_Persistence_Mapper_DataMapper
	 */
	protected $dataMapper;

	/**
	 * The TYPO3 page select object. Used for language and workspace overlay
	 *
	 * @var t3lib_pageSelect
	 */
	protected $pageSelectObject;

	/**
	 * A first-level TypoScript configuration cache
	 *
	 * @var array
	 */
	protected $pageTSConfigCache = array();

	/**
	 * Caches information about tables (esp. the existing column names)
	 *
	 * @var array
	 */
	protected $tableInformationCache = array();

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Extbase_Service_CacheService
	 */
	protected $cacheService;

	/**
	 * Constructor. takes the database handle from $GLOBALS['TYPO3_DB']
	 */
	public function __construct() {
		$this->databaseHandle = $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * @param Tx_Extbase_Service_CacheService $cacheService
	 * @return void
	 */
	public function injectCacheService(Tx_Extbase_Service_CacheService $cacheService) {
		$this->cacheService = $cacheService;
	}

	/**
	 * Adds a row to the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be inserted
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return int The uid of the inserted row
	 */
	public function addRow($tableName, array $row, $isRelation = FALSE) {
		$fields = array();
		$values = array();
		$parameters = array();
		if (isset($row['uid'])) {
			unset($row['uid']);
		}
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName;
			$values[] = '?';
			$parameters[] = $value;
		}

		$sqlString = 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		$this->replacePlaceholders($sqlString, $parameters, $tableName);
		// debug($sqlString,-2);
		$this->databaseHandle->sql_query($sqlString);
		$this->checkSqlErrors($sqlString);
		$uid = $this->databaseHandle->sql_insert_id();
		if (!$isRelation) {
			$this->clearPageCache($tableName, $uid);
		}
		return (int)$uid;
	}

	/**
	 * Updates a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $row The row to be updated
	 * @param boolean $isRelation TRUE if we are currently inserting into a relation table, FALSE by default
	 * @return bool
	 */
	public function updateRow($tableName, array $row, $isRelation = FALSE) {
		if (!isset($row['uid'])) throw new InvalidArgumentException('The given row must contain a value for "uid".');
		$uid = (int)$row['uid'];
		unset($row['uid']);
		$fields = array();
		$parameters = array();
		foreach ($row as $columnName => $value) {
			$fields[] = $columnName . '=?';
			$parameters[] = $value;
		}
		$parameters[] = $uid;

		$sqlString = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $fields) . ' WHERE uid=?';
		$this->replacePlaceholders($sqlString, $parameters, $tableName);
		// debug($sqlString,-2);
		$returnValue = $this->databaseHandle->sql_query($sqlString);
		$this->checkSqlErrors($sqlString);
		if (!$isRelation) {
			$this->clearPageCache($tableName, $uid);
		}
		return $returnValue;
	}

	/**
	 * Deletes a row in the storage
	 *
	 * @param string $tableName The database table name
	 * @param array $identifier An array of identifier array('fieldname' => value). This array will be transformed to a WHERE clause
	 * @param boolean $isRelation TRUE if we are currently manipulating a relation table, FALSE by default
	 * @return bool
	 */
	public function removeRow($tableName, array $identifier, $isRelation = FALSE) {
		$statement = 'DELETE FROM ' . $tableName . ' WHERE ' . $this->parseIdentifier($identifier);
		$this->replacePlaceholders($statement, $identifier, $tableName);
		if (!$isRelation && isset($identifier['uid'])) {
			$this->clearPageCache($tableName, $identifier['uid'], $isRelation);
		}
		// debug($statement, -2);
		$returnValue = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		return $returnValue;
	}

	/**
	 * Fetches row data from the database
	 *
	 * @param string $identifier The Identifier of the row to fetch
	 * @param Tx_Extbase_Persistence_Mapper_DataMap $dataMap The Data Map
	 * @return array|FALSE
	 */
	public function getRowByIdentifier($tableName, array $identifier) {
		$statement = 'SELECT * FROM ' . $tableName . ' WHERE ' . $this->parseIdentifier($identifier);
		$this->replacePlaceholders($statement, $identifier, $tableName);
		// debug($statement,-2);
		$res = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		$row = $this->databaseHandle->sql_fetch_assoc($res);
		if ($row !== FALSE) {
			return $row;
		} else {
			return FALSE;
		}
	}

	protected function parseIdentifier(array $identifier) {
		$fieldNames = array_keys($identifier);
		$suffixedFieldNames = array();
		foreach ($fieldNames as $fieldName) {
			$suffixedFieldNames[] = $fieldName . '=?';
		}
		return implode(' AND ', $suffixedFieldNames);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		$parameters = array();

		$statement = $query->getStatement();
		if($statement instanceof Tx_Extbase_Persistence_QOM_Statement) {
			$sql = $statement->getStatement();
			$parameters = $statement->getBoundVariables();
		} else {
			$parameters = array();
			$statementParts = $this->parseQuery($query, $parameters);
			$sql = $this->buildQuery($statementParts, $parameters);
		}
		$tableName = 'foo';
		if (is_array($statementParts && !empty($statementParts['tables'][0]))) {
			$tableName = $statementParts['tables'][0];
		}
		$this->replacePlaceholders($sql, $parameters, $tableName);
		// Tx_Extbase_Utility_Debugger::var_dump($sql, 'sql');die("Ende");
		$result = $this->databaseHandle->sql_query($sql);
		$this->checkSqlErrors($sql);
		$rows = $this->getRowsFromResult($query->getSource(), $result);
		$this->databaseHandle->sql_free_result($result);
		$rows = $this->doLanguageAndWorkspaceOverlay($query->getSource(), $rows);
		// TODO: implement $objectData = $this->processObjectRecords($statementHandle);
		return $rows;
	}

	/**
	 * Returns the number of tuples matching the query.
	 *
	 * @param Tx_Extbase_Persistence_QOM_QueryObjectModelInterface $query
	 * @return integer The number of matching tuples
	 */
	public function getObjectCountByQuery(Tx_Extbase_Persistence_QueryInterface $query) {
		$constraint = $query->getConstraint();
		if($constraint instanceof Tx_Extbase_Persistence_QOM_StatementInterface) {
			throw new Tx_Extbase_Persistence_Storage_Exception_BadConstraint('Could not execute count on queries with a constraint of type Tx_Extbase_Persistence_QOM_StatementInterface', 1256661045);
		}
		$parameters = array();
		$statementParts = $this->parseQuery($query, $parameters);
		// if limit is set, we need to count the rows "manually" as COUNT(*) ignores LIMIT constraints
		if (!empty($statementParts['limit'])) {
			$statement = $this->buildQuery($statementParts, $parameters);
			$this->replacePlaceholders($statement, $parameters, $statementParts['tables'][0]);
			$result = $this->databaseHandle->sql_query($statement);
			$this->checkSqlErrors($statement);
			$count = $this->databaseHandle->sql_num_rows($result);
		} else {
			$statementParts['fields'] = array('COUNT(*)');
			// having orderings without grouping is not compatible with non-MySQL DBMS
			$statementParts['orderings'] = array();
			if (isset($statementParts['keywords']['distinct'])) {
				unset($statementParts['keywords']['distinct']);
				$statementParts['fields'] = array('COUNT(DISTINCT ' . reset($statementParts['tables']) . '.uid)');
			}
			$statement = $this->buildQuery($statementParts, $parameters);
			$this->replacePlaceholders($statement, $parameters, $statementParts['tables'][0]);
			$result = $this->databaseHandle->sql_query($statement);
			$this->checkSqlErrors($statement);
			$rows = $this->getRowsFromResult($query->getSource(), $result);
			$count = current(current($rows));
		}
		$this->databaseHandle->sql_free_result($result);
		return (int)$count;
	}

	/**
	 * Parses the query and returns the SQL statement parts.
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query The query
	 * @return array The SQL statement parts
	 */
	public function parseQuery(Tx_Extbase_Persistence_QueryInterface $query, array &$parameters) {
		$sql = array();
		$sql['keywords'] = array();
		$sql['tables'] = array();
		$sql['unions'] = array();
		$sql['fields'] = array();
		$sql['where'] = array();
		$sql['additionalWhereClause'] = array();
		$sql['orderings'] = array();
		$sql['limit'] = array();

		$source = $query->getSource();

		$this->parseSource($source, $sql);
		$this->parseConstraint($query->getConstraint(), $source, $sql, $parameters);
		$this->parseOrderings($query->getOrderings(), $source, $sql);
		$this->parseLimitAndOffset($query->getLimit(), $query->getOffset(), $sql);

		$tableNames = array_unique(array_keys($sql['tables'] + $sql['unions']));
		foreach ($tableNames as $tableName) {
			if (is_string($tableName) && strlen($tableName) > 0) {
				$this->addAdditionalWhereClause($query->getQuerySettings(), $tableName, $sql);
			}
		}

		return $sql;
	}

	/**
	 * Returns the statement, ready to be executed.
	 *
	 * @param array $sql The SQL statement parts
	 * @return string The SQL statement
	 */
	public function buildQuery(array $sql) {
		$statement = 'SELECT ' . implode(' ', $sql['keywords']) . ' '. implode(',', $sql['fields']) . ' FROM ' . implode(' ', $sql['tables']) . ' '. implode(' ', $sql['unions']);
		if (!empty($sql['where'])) {
			$statement .= ' WHERE ' . implode('', $sql['where']);
			if (!empty($sql['additionalWhereClause'])) {
				$statement .= ' AND ' . implode(' AND ', $sql['additionalWhereClause']);
			}
		} elseif (!empty($sql['additionalWhereClause'])) {
			$statement .= ' WHERE ' . implode(' AND ', $sql['additionalWhereClause']);
		}
		if (!empty($sql['orderings'])) {
			$statement .= ' ORDER BY ' . implode(', ', $sql['orderings']);
		}
		if (!empty($sql['limit'])) {
			$statement .= ' LIMIT ' . $sql['limit'];
		}
		return $statement;
	}

	/**
	 * Checks if a Value Object equal to the given Object exists in the data base
	 *
	 * @param Tx_Extbase_DomainObject_AbstractValueObject $object The Value Object
	 * @return mixed The matching uid if an object was found, else FALSE
	 */
	public function getUidOfAlreadyPersistedValueObject(Tx_Extbase_DomainObject_AbstractValueObject $object) {
		$fields = array();
		$parameters = array();
		$dataMap = $this->dataMapper->getDataMap(get_class($object));
		$properties = $object->_getProperties();
		foreach ($properties as $propertyName => $propertyValue) {
			// FIXME We couple the Backend to the Entity implementation (uid, isClone); changes there breaks this method
			if ($dataMap->isPersistableProperty($propertyName) && $propertyName !== 'uid' && $propertyName !== 'pid' && $propertyName !== 'isClone') {
				if ($propertyValue === NULL) {
					$fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . ' IS NULL';
				} else {
					$fields[] = $dataMap->getColumnMap($propertyName)->getColumnName() . '=?';
					$parameters[] = $this->getPlainValue($propertyValue);
				}
			}
		}
		$sql = array();
		$sql['additionalWhereClause'] = array();
		$tableName = $dataMap->getTableName();
		$this->addVisibilityConstraintStatement(new Tx_Extbase_Persistence_Typo3QuerySettings(), $tableName, $sql);
		$statement = 'SELECT * FROM ' . $tableName;
		$statement .= ' WHERE ' . implode(' AND ', $fields);
		if (!empty($sql['additionalWhereClause'])) {
			$statement .= ' AND ' . implode(' AND ', $sql['additionalWhereClause']);
		}
		$this->replacePlaceholders($statement, $parameters, $tableName);
		// debug($statement,-2);
		$res = $this->databaseHandle->sql_query($statement);
		$this->checkSqlErrors($statement);
		$row = $this->databaseHandle->sql_fetch_assoc($res);
		if ($row !== FALSE) {
			return (integer) $row['uid'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Transforms a Query Source into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source
	 * @param array &$sql
	 * @return void
	 */
	protected function parseSource(Tx_Extbase_Persistence_QOM_SourceInterface $source, array &$sql) {
		if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			$className = $source->getNodeTypeName();
			$tableName = $this->dataMapper->getDataMap($className)->getTableName();
			$this->addRecordTypeConstraint($className, $sql);
			$sql['fields'][$tableName] = $tableName . '.*';
			$sql['tables'][$tableName] = $tableName;
		} elseif ($source instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
			$this->parseJoin($source, $sql);
		}
	}

	/**
	 * Add a constraint to ensure that the record type of the returned tuples is matching the data type of the repository.
	 *
	 * @param string $className The class name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addRecordTypeConstraint($className, &$sql) {
		if ($className !== NULL) {
			$dataMap = $this->dataMapper->getDataMap($className);
			if ($dataMap->getRecordTypeColumnName() !== NULL) {
				$recordTypes = array();
				if ($dataMap->getRecordType() !== NULL) {
					$recordTypes[] = $dataMap->getRecordType();
				}
				foreach ($dataMap->getSubclasses() as $subclassName) {
					$subclassDataMap = $this->dataMapper->getDataMap($subclassName);
					if ($subclassDataMap->getRecordType() !== NULL) {
						$recordTypes[] = $subclassDataMap->getRecordType();
					}
				}
				if (count($recordTypes) > 0) {
					$recordTypeStatements = array();
					foreach ($recordTypes as $recordType) {
						$tableName = $dataMap->getTableName();
						$recordTypeStatements[] = $tableName . '.' . $dataMap->getRecordTypeColumnName() . '=' . $this->databaseHandle->fullQuoteStr($recordType, $tableName);
					}
					$sql['additionalWhereClause'][] = '(' . implode(' OR ', $recordTypeStatements) . ')';
				}
			}
		}
	}

	/**
	 * Transforms a Join into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_JoinInterface $join The join
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseJoin(Tx_Extbase_Persistence_QOM_JoinInterface $join, array &$sql) {
		$leftSource = $join->getLeft();
		$leftClassName = $leftSource->getNodeTypeName();
		$this->addRecordTypeConstraint($leftClassName, $sql);
		$leftTableName = $leftSource->getSelectorName();
		// $sql['fields'][$leftTableName] = $leftTableName . '.*';
		$rightSource = $join->getRight();
		if ($rightSource instanceof	Tx_Extbase_Persistence_QOM_JoinInterface) {
			$rightClassName = $rightSource->getLeft()->getNodeTypeName();
			$rightTableName = $rightSource->getLeft()->getSelectorName();
		} else {
			$rightClassName = $rightSource->getNodeTypeName();
			$rightTableName = $rightSource->getSelectorName();
			$sql['fields'][$leftTableName] = $rightTableName . '.*';
		}
		$this->addRecordTypeConstraint($rightClassName, $sql);

		$sql['tables'][$leftTableName] = $leftTableName;
		$sql['unions'][$rightTableName] = 'LEFT JOIN ' . $rightTableName;

		$joinCondition = $join->getJoinCondition();
		if ($joinCondition instanceof Tx_Extbase_Persistence_QOM_EquiJoinCondition) {
			$column1Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty1Name(), $leftClassName);
			$column2Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty2Name(), $rightClassName);
			$sql['unions'][$rightTableName] .= ' ON ' . $joinCondition->getSelector1Name() . '.' . $column1Name . ' = ' . $joinCondition->getSelector2Name() . '.' . $column2Name;
		}
		if ($rightSource instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
			$this->parseJoin($rightSource, $sql);
		}
	}

	/**
	 * Transforms a constraint into SQL and parameter arrays
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint The constraint
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @param array &$parameters The parameters that will replace the markers
	 * @param array $boundVariableValues The bound variables in the query (key) and their values (value)
	 * @return void
	 */
	protected function parseConstraint(Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint = NULL, Tx_Extbase_Persistence_QOM_SourceInterface $source, array &$sql, array &$parameters) {
		if ($constraint instanceof Tx_Extbase_Persistence_QOM_AndInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql, $parameters);
			$sql['where'][] = ' AND ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_OrInterface) {
			$sql['where'][] = '(';
			$this->parseConstraint($constraint->getConstraint1(), $source, $sql, $parameters);
			$sql['where'][] = ' OR ';
			$this->parseConstraint($constraint->getConstraint2(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_NotInterface) {
			$sql['where'][] = 'NOT (';
			$this->parseConstraint($constraint->getConstraint(), $source, $sql, $parameters);
			$sql['where'][] = ')';
		} elseif ($constraint instanceof Tx_Extbase_Persistence_QOM_ComparisonInterface) {
			$this->parseComparison($constraint, $source, $sql, $parameters);
		}
	}

	/**
	 * Parse a Comparison into SQL and parameter arrays.
	 *
	 * @param Tx_Extbase_Persistence_QOM_ComparisonInterface $comparison The comparison to parse
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source
	 * @param array &$sql SQL query parts to add to
	 * @param array &$parameters Parameters to bind to the SQL
	 * @param array $boundVariableValues The bound variables in the query and their values
	 * @return void
	 */
	protected function parseComparison(Tx_Extbase_Persistence_QOM_ComparisonInterface $comparison, Tx_Extbase_Persistence_QOM_SourceInterface $source, array &$sql, array &$parameters) {
		$operand1 = $comparison->getOperand1();
		$operator = $comparison->getOperator();
		$operand2 = $comparison->getOperand2();

			/**
			 * This if enables equals() to behave like in(). Use in() instead.
			 * @deprecated since Extbase 1.3; will be removed in Extbase 1.5
			 */
		if (($operator === Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO) && (is_array($operand2) || ($operand2 instanceof ArrayAccess) || ($operand2 instanceof Traversable))) {
			$operator = Tx_Extbase_Persistence_QueryInterface::OPERATOR_IN;
		}

		if ($operator === Tx_Extbase_Persistence_QueryInterface::OPERATOR_IN) {
			$items = array();
			$hasValue = FALSE;
			foreach ($operand2 as $value) {
				$value = $this->getPlainValue($value);
				if ($value !== NULL) {
					$items[] = $value;
					$hasValue = TRUE;
				}
			}
			if ($hasValue === FALSE) {
				$sql['where'][] = '1<>1';
			} else {
				$this->parseDynamicOperand($operand1, $operator, $source, $sql, $parameters, NULL, $operand2);
				$parameters[] = $items;
			}
		} elseif ($operator === Tx_Extbase_Persistence_QueryInterface::OPERATOR_CONTAINS) {
			if ($operand2 === NULL) {
				$sql['where'][] = '1<>1';
			} else {
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				$propertyName = $operand1->getPropertyName();
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
				$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
				$dataMap = $this->dataMapper->getDataMap($className);
				$columnMap = $dataMap->getColumnMap($propertyName);
				$typeOfRelation = ($columnMap instanceof Tx_Extbase_Persistence_Mapper_ColumnMap ? $columnMap->getTypeOfRelation() : NULL);
				if ($typeOfRelation === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
					$relationTableName = $columnMap->getRelationTableName();
					$sql['where'][] = $tableName . '.uid IN (SELECT ' . $columnMap->getParentKeyFieldName() . ' FROM ' . $relationTableName . ' WHERE ' . $columnMap->getChildKeyFieldName() . '=?)';
					$parameters[] = intval($this->getPlainValue($operand2));
				} elseif ($typeOfRelation === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
					$parentKeyFieldName = $columnMap->getParentKeyFieldName();
					if (isset($parentKeyFieldName)) {
						$childTableName = $columnMap->getChildTableName();
						$sql['where'][] = $tableName . '.uid=(SELECT ' . $childTableName . '.' . $parentKeyFieldName . ' FROM ' . $childTableName . ' WHERE ' . $childTableName . '.uid=?)';
						$parameters[] = intval($this->getPlainValue($operand2));
					} else {
						$sql['where'][] = 'FIND_IN_SET(?,' . $tableName . '.' . $columnName . ')';
						$parameters[] = intval($this->getPlainValue($operand2));
					}
				} else {
					throw new Tx_Extbase_Persistence_Exception_RepositoryException(
						'Unsupported or non-existing property name "' . $propertyName . '" used in relation matching.',
						1327065745
					);
				}
			}
		} else {
			if ($operand2 === NULL) {
				if ($operator === Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO) {
					$operator = self::OPERATOR_EQUAL_TO_NULL;
				} elseif ($operator === Tx_Extbase_Persistence_QueryInterface::OPERATOR_NOT_EQUAL_TO) {
					$operator = self::OPERATOR_NOT_EQUAL_TO_NULL;
				}
			}
			$this->parseDynamicOperand($operand1, $operator, $source, $sql, $parameters);
			$parameters[] = $this->getPlainValue($operand2);
		}
	}

	/**
	 * Returns a plain value, i.e. objects are flattened out if possible.
	 *
	 * @param mixed $input
	 * @return mixed
	 */
	protected function getPlainValue($input) {
		if (is_array($input)) {
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('An array could not be converted to a plain value.', 1274799932);
		}
		if ($input instanceof DateTime) {
			return $input->format('U');
		} elseif (is_object($input)) {
			if ($input instanceof Tx_Extbase_DomainObject_DomainObjectInterface) {
				return $input->getUid();
			} else {
				throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('An object of class "' . get_class($input) . '" could not be converted to a plain value.', 1274799934);
			}
		} elseif (is_bool($input)) {
			return $input === TRUE ? 1 : 0;
		} else {
			return $input;
		}
	}

	/**
	 * Parse a DynamicOperand into SQL and parameter arrays.
	 *
	 * @param Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @param array &$parameters The parameters that will replace the markers
	 * @param string $valueFunction an optional SQL function to apply to the operand value
	 * @return void
	 */
	protected function parseDynamicOperand(Tx_Extbase_Persistence_QOM_DynamicOperandInterface $operand, $operator, Tx_Extbase_Persistence_QOM_SourceInterface $source, array &$sql, array &$parameters, $valueFunction = NULL, $operand2 = NULL) {
		if ($operand instanceof Tx_Extbase_Persistence_QOM_LowerCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $source, $sql, $parameters, 'LOWER');
		} elseif ($operand instanceof Tx_Extbase_Persistence_QOM_UpperCaseInterface) {
			$this->parseDynamicOperand($operand->getOperand(), $operator, $source, $sql, $parameters, 'UPPER');
		} elseif ($operand instanceof Tx_Extbase_Persistence_QOM_PropertyValueInterface) {
			$propertyName = $operand->getPropertyName();
			if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) { // FIXME Only necessary to differ from  Join
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
				$tableName = $source->getJoinCondition()->getSelector1Name();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			$operator = $this->resolveOperator($operator);

			$constraintSQL = '';
			if ($valueFunction === NULL) {
				$constraintSQL .= (!empty($tableName) ? $tableName . '.' : '') . $columnName .  ' ' . $operator . ' ?';
			} else {
				$constraintSQL .= $valueFunction . '(' . (!empty($tableName) ? $tableName . '.' : '') . $columnName .  ') ' . $operator . ' ?';
			}

			$sql['where'][] = $constraintSQL;
		}
	}

	protected function addUnionStatement(&$className, &$tableName, &$propertyPath, array &$sql) {
		$explodedPropertyPath = explode('.', $propertyPath, 2);
		$propertyName = $explodedPropertyPath[0];
		$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
		$tableName = $this->dataMapper->convertClassNameToTableName($className);
		$columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);
		$parentKeyFieldName = $columnMap->getParentKeyFieldName();
		$childTableName = $columnMap->getChildTableName();
		if ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_ONE) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.' . $columnName . '=' . $childTableName . '.uid';
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_MANY) {
			if (isset($parentKeyFieldName)) {
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $tableName . '.uid=' . $childTableName . '.' . $parentKeyFieldName;
			} else {
				$onStatement = '(FIND_IN_SET(' . $childTableName . '.uid, ' . $tableName . '.' . $columnName . '))';
				$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $onStatement;
			}
			$className = $this->dataMapper->getType($className, $propertyName);
		} elseif ($columnMap->getTypeOfRelation() === Tx_Extbase_Persistence_Mapper_ColumnMap::RELATION_HAS_AND_BELONGS_TO_MANY) {
			$relationTableName = $columnMap->getRelationTableName();
			$sql['unions'][$relationTableName] = 'LEFT JOIN ' . $relationTableName . ' ON ' . $tableName . '.uid=' . $relationTableName . '.' . $columnMap->getParentKeyFieldName();
			$sql['unions'][$childTableName] = 'LEFT JOIN ' . $childTableName . ' ON ' . $relationTableName . '.'.  $columnMap->getChildKeyFieldName() . '=' . $childTableName . '.uid';
			$className = $this->dataMapper->getType($className, $propertyName);
		} else {
			throw new Tx_Extbase_Persistence_Exception('Could not determine type of relation.', 1252502725);
		}
		// TODO check if there is another solution for this
		$sql['keywords']['distinct'] = 'DISTINCT';
		$propertyPath = $explodedPropertyPath[1];
		$tableName = $childTableName;
	}

	/**
	 * Returns the SQL operator for the given JCR operator type.
	 *
	 * @param string $operator One of the JCR_OPERATOR_* constants
	 * @return string an SQL operator
	 */
	protected function resolveOperator($operator) {
		switch ($operator) {
			case self::OPERATOR_EQUAL_TO_NULL:
				$operator = 'IS';
				break;
			case self::OPERATOR_NOT_EQUAL_TO_NULL:
				$operator = 'IS NOT';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_IN:
				$operator = 'IN';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO:
				$operator = '=';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_NOT_EQUAL_TO:
				$operator = '!=';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN:
				$operator = '<';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO:
				$operator = '<=';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN:
				$operator = '>';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO:
				$operator = '>=';
				break;
			case Tx_Extbase_Persistence_QueryInterface::OPERATOR_LIKE:
				$operator = 'LIKE';
				break;
			default:
				throw new Tx_Extbase_Persistence_Exception('Unsupported operator encountered.', 1242816073);
		}

		return $operator;
	}

	/**
	 * Replace query placeholders in a query part by the given
	 * parameters.
	 *
	 * @param string $sqlString The query part with placeholders
	 * @param array $parameters The parameters
	 * @param string $tableName
	 * @return string The query part with replaced placeholders
	 */
	protected function replacePlaceholders(&$sqlString, array $parameters, $tableName = 'foo') {
		// TODO profile this method again
		if (substr_count($sqlString, '?') !== count($parameters)) throw new Tx_Extbase_Persistence_Exception('The number of question marks to replace must be equal to the number of parameters.', 1242816074);
		$offset = 0;
		foreach ($parameters as $parameter) {
			$markPosition = strpos($sqlString, '?', $offset);
			if ($markPosition !== FALSE) {
				if ($parameter === NULL) {
					$parameter = 'NULL';
				} elseif (is_array($parameter) || ($parameter instanceof ArrayAccess) || ($parameter instanceof Traversable)) {
					$items = array();
					foreach ($parameter as $item) {
						$items[] = $this->databaseHandle->fullQuoteStr($item, $tableName);
					}
					$parameter = '(' . implode(',', $items) . ')';
				} else {
					$parameter = $this->databaseHandle->fullQuoteStr($parameter, $tableName);
				}
				$sqlString = substr($sqlString, 0, $markPosition) . $parameter . substr($sqlString, $markPosition + 1);
			}
			$offset = $markPosition + strlen($parameter);
		}
	}

	/**
	 * Adds additional WHERE statements according to the query settings.
	 *
	 * @param Tx_Extbase_Persistence_QuerySettingsInterface $querySettings The TYPO3 4.x specific query settings
	 * @param string $tableName The table name to add the additional where clause for
	 * @param string $sql
	 * @return void
	 */
	protected function addAdditionalWhereClause(Tx_Extbase_Persistence_QuerySettingsInterface $querySettings, $tableName, &$sql) {
		$this->addVisibilityConstraintStatement($querySettings, $tableName, $sql);
		if ($querySettings->getRespectSysLanguage()) {
			$this->addSysLanguageStatement($tableName, $sql, $querySettings);
		}
		if ($querySettings->getRespectStoragePage()) {
			$this->addPageIdStatement($tableName, $sql, $querySettings->getStoragePageIds());
		}
	}

	/**
	 * Builds the enable fields statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addEnableFieldsStatement($tableName, array &$sql) {
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			if (TYPO3_MODE === 'FE') {
				$statement = $GLOBALS['TSFE']->sys_page->enableFields($tableName);
			} else { // TYPO3_MODE === 'BE'
				$statement = t3lib_BEfunc::deleteClause($tableName);
				$statement .= t3lib_BEfunc::BEenableFields($tableName);
			}
			if(!empty($statement)) {
				$statement = substr($statement, 5);
				$sql['additionalWhereClause'][] = $statement;
			}
		}
	}

	/**
	 * Adds enableFields and deletedClause to the query if necessary
	 *
	 * @param Tx_Extbase_Persistence_QuerySettingsInterface $querySettings
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addVisibilityConstraintStatement(Tx_Extbase_Persistence_QuerySettingsInterface $querySettings, $tableName, array &$sql) {
		$statement = '';
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			$ignoreEnableFields = $querySettings->getIgnoreEnableFields();
			$enableFieldsToBeIgnored = $querySettings->getEnableFieldsToBeIgnored();
			$includeDeleted = $querySettings->getIncludeDeleted();
			if (TYPO3_MODE === 'FE') {
				$statement .= $this->getFrontendConstraintStatement($tableName, $ignoreEnableFields, $enableFieldsToBeIgnored, $includeDeleted);
			} else {
				// TYPO3_MODE === 'BE'
				$statement .= $this->getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted);
			}
			if (!empty($statement)) {
				$statement = strtolower(substr($statement, 1, 3)) === 'and' ? substr($statement, 5) : $statement;
				$sql['additionalWhereClause'][] = $statement;
			}
		}
	}

	/**
	 * Returns constraint statement for frontend context
	 *
	 * @param string $tableName
	 * @param boolean $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param array $enableFieldsToBeIgnored If $ignoreEnableFields is true, this array specifies enable fields to be ignored. If it is NULL or an empty array (default) all enable fields are ignored.
	 * @param boolean $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 * @throws Tx_Extbase_Persistence_Storage_Exception_InconsistentQuerySettingsException
	 */
	protected function getFrontendConstraintStatement($tableName, $ignoreEnableFields, $enableFieldsToBeIgnored = array(), $includeDeleted) {
		$statement = '';
		if ($ignoreEnableFields && !$includeDeleted) {
			if (count($enableFieldsToBeIgnored)) {
				// array_combine() is necessary because of the way \TYPO3\CMS\Frontend\Page\PageRepository::enableFields() is implemented
				$statement .= $GLOBALS['TSFE']->sys_page->enableFields($tableName, -1, array_combine($enableFieldsToBeIgnored, $enableFieldsToBeIgnored));
			} else {
				$statement .= $GLOBALS['TSFE']->sys_page->deleteClause($tableName);
			}
		} elseif (!$ignoreEnableFields && !$includeDeleted) {
			$statement .= $GLOBALS['TSFE']->sys_page->enableFields($tableName);
		} elseif (!$ignoreEnableFields && $includeDeleted) {
			throw new Tx_Extbase_Persistence_Storage_Exception_InconsistentQuerySettingsException('Query setting "ignoreEnableFields=FALSE" can not be used together with "includeDeleted=TRUE" in frontend context.', 1327678173);
		}
		return $statement;
	}

	/**
	 * Returns constraint statement for backend context
	 *
	 * @param string $tableName
	 * @param boolean $ignoreEnableFields A flag indicating whether the enable fields should be ignored
	 * @param boolean $includeDeleted A flag indicating whether deleted records should be included
	 * @return string
	 */
	protected function getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted) {
		$statement = '';
		if (!$ignoreEnableFields) {
			$statement .= t3lib_BEfunc::BEenableFields($tableName);
		}
		if (!$includeDeleted) {
			$statement .= t3lib_BEfunc::deleteClause($tableName);
		}
		return $statement;
	}

	/**
	 * Builds the language field statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function addSysLanguageStatement($tableName, array &$sql) {
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl'])) {
			if(isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== NULL) {
				$sql['additionalWhereClause'][] = $tableName . '.' . $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] . ' IN (0,-1)';
			}
		}
	}

	/**
	 * Builds the page ID checking statement
	 *
	 * @param string $tableName The database table name
	 * @param array &$sql The query parts
	 * @param array $storagePageIds list of storage page ids
	 * @return void
	 */
	protected function addPageIdStatement($tableName, array &$sql, array $storagePageIds) {
		if (empty($this->tableInformationCache[$tableName]['columnNames'])) {
			$this->tableInformationCache[$tableName]['columnNames'] = $this->databaseHandle->admin_get_fields($tableName);
		}
		if (is_array($GLOBALS['TCA'][$tableName]['ctrl']) && array_key_exists('pid', $this->tableInformationCache[$tableName]['columnNames'])) {
			$sql['additionalWhereClause'][] = $tableName . '.pid IN (' . implode(', ', $storagePageIds) . ')';
		}
	}

	/**
	 * Transforms orderings into SQL.
	 *
	 * @param array $orderings An array of orderings (Tx_Extbase_Persistence_QOM_Ordering)
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source
	 * @param array &$sql The query parts
	 * @return void
	 */
	protected function parseOrderings(array $orderings, Tx_Extbase_Persistence_QOM_SourceInterface $source, array &$sql) {
		foreach ($orderings as $propertyName => $order) {
			switch ($order) {
				case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_ASCENDING: // Deprecated since Extbase 1.1
				case Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING:
					$order = 'ASC';
					break;
				case Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_ORDER_DESCENDING: // Deprecated since Extbase 1.1
				case Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING:
					$order = 'DESC';
					break;
				default:
					throw new Tx_Extbase_Persistence_Exception_UnsupportedOrder('Unsupported order encountered.', 1242816074);
			}
			$className = '';
			$tableName = '';
			if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
				$className = $source->getNodeTypeName();
				$tableName = $this->dataMapper->convertClassNameToTableName($className);
				while (strpos($propertyName, '.') !== FALSE) {
					$this->addUnionStatement($className, $tableName, $propertyName, $sql);
				}
			} elseif ($source instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
				$tableName = $source->getLeft()->getSelectorName();
			}
			$columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
			if (strlen($tableName) > 0) {
				$sql['orderings'][] = $tableName . '.' . $columnName . ' ' . $order;
			} else {
				$sql['orderings'][] = $columnName . ' ' . $order;
			}
		}
	}

	/**
	 * Transforms limit and offset into SQL
	 *
	 * @param int $limit
	 * @param int $offset
	 * @param array &$sql
	 * @return void
	 */
	protected function parseLimitAndOffset($limit, $offset, array &$sql) {
		if ($limit !== NULL && $offset !== NULL) {
			$sql['limit'] = intval($offset) . ', ' . intval($limit);
		} elseif ($limit !== NULL) {
			$sql['limit'] = intval($limit);
		}
	}

	/**
	 * Transforms a Resource from a database query to an array of rows.
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source (selector od join)
	 * @param resource $result The result
	 * @return array The result as an array of rows (tuples)
	 */
	protected function getRowsFromResult(Tx_Extbase_Persistence_QOM_SourceInterface $source, $result) {
		$rows = array();
		while ($row = $this->databaseHandle->sql_fetch_assoc($result)) {
			if (is_array($row)) {
				// TODO Check if this is necessary, maybe the last line is enough
				$arrayKeys = range(0, count($row));
				array_fill_keys($arrayKeys, $row);
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Performs workspace and language overlay on the given row array. The language and workspace id is automatically
	 * detected (depending on FE or BE context). You can also explicitly set the language/workspace id.
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source The source (selector od join)
	 * @param array $row The row array (as reference)
	 * @param string $languageUid The language id
	 * @param string $workspaceUidUid The workspace id
	 * @return void
	 */
	protected function doLanguageAndWorkspaceOverlay(Tx_Extbase_Persistence_QOM_SourceInterface $source, array $rows, $languageUid = NULL, $workspaceUid = NULL) {
		$overlayedRows = array();
		foreach ($rows as $row) {
			if (!($this->pageSelectObject instanceof t3lib_pageSelect)) {
				if (TYPO3_MODE == 'FE') {
					if (is_object($GLOBALS['TSFE'])) {
						$this->pageSelectObject = $GLOBALS['TSFE']->sys_page;
					} else {
						$this->pageSelectObject = t3lib_div::makeInstance('t3lib_pageSelect');
					}
				} else {
					$this->pageSelectObject = t3lib_div::makeInstance( 't3lib_pageSelect' );
				}
			}
			if (is_object($GLOBALS['TSFE'])) {
				if ($languageUid === NULL) {
						// get the language UID of the content that should be output
					$languageUid = $GLOBALS['TSFE']->sys_language_content;
					$languageMode = $GLOBALS['TSFE']->sys_language_mode;
				}
				if ($workspaceUid !== NULL) {
					$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
				}
			} else {
				if ($languageUid === NULL) {
					$languageUid = intval(t3lib_div::_GP('L'));
				}
				if ($workspaceUid === NULL) {
					$workspaceUid = $GLOBALS['BE_USER']->workspace;
				}
				$this->pageSelectObject->versioningWorkspaceId = $workspaceUid;
			}
			if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
				$tableName = $source->getSelectorName();
			} elseif ($source instanceof Tx_Extbase_Persistence_QOM_JoinInterface) {
				$tableName = $source->getRight()->getSelectorName();
			}
			$this->pageSelectObject->versionOL($tableName, $row, TRUE);
			if ($this->pageSelectObject->versioningPreview && isset($row['_ORIG_uid'])) {
				$row['uid'] = $row['_ORIG_uid'];
			}
			if($tableName == 'pages') {
				$row = $this->pageSelectObject->getPageOverlay($row, $languageUid);
			} elseif(isset($GLOBALS['TCA'][$tableName]['ctrl']['languageField']) && $GLOBALS['TCA'][$tableName]['ctrl']['languageField'] !== '') {
				if (in_array($row[$GLOBALS['TCA'][$tableName]['ctrl']['languageField']], array(-1,0))) {
					$overlayMode = ($languageMode === 'strict') ? 'hideNonTranslated' : '';
					$row = $this->pageSelectObject->getRecordOverlay($tableName, $row, $languageUid, $overlayMode);
				}
			}
			if ($row !== NULL && is_array($row)) {
				$overlayedRows[] = $row;
			}
		}
		return $overlayedRows;
	}

	/**
	 * Checks if there are SQL errors in the last query, and if yes, throw an exception.
	 *
	 * @return void
	 * @param string $sql The SQL statement
	 * @throws Tx_Extbase_Persistence_Storage_Exception_SqlError
	 */
	protected function checkSqlErrors($sql='') {
		$error = $this->databaseHandle->sql_error();
		if ($error !== '') {
			$error .= $sql ? ': ' . $sql : '';
			throw new Tx_Extbase_Persistence_Storage_Exception_SqlError($error, 1247602160);
		}
	}

	/**
	 * Clear the TYPO3 page cache for the given record.
	 * If the record lies on a page, then we clear the cache of this page.
	 * If the record has no PID column, we clear the cache of the current page as best-effort.
	 *
	 * Much of this functionality is taken from t3lib_tcemain::clear_cache() which unfortunately only works with logged-in BE user.
	 *
	 * @param $tableName Table name of the record
	 * @param $uid UID of the record
	 * @return void
	 */
	protected function clearPageCache($tableName, $uid) {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($frameworkConfiguration['persistence']['enableAutomaticCacheClearing']) && $frameworkConfiguration['persistence']['enableAutomaticCacheClearing'] === '1') {
		} else {
			// if disabled, return
			return;
		}

		$pageIdsToClear = array();
		$storagePage = NULL;

		$columns = $this->databaseHandle->admin_get_fields($tableName);
		if (array_key_exists('pid', $columns)) {
			$result = $this->databaseHandle->exec_SELECTquery('pid', $tableName, 'uid='.intval($uid));
			if ($row = $this->databaseHandle->sql_fetch_assoc($result))	{
				$storagePage = $row['pid'];
				$pageIdsToClear[] = $storagePage;
			}
		} elseif (isset($GLOBALS['TSFE'])) {
			// No PID column - we can do a best-effort to clear the cache of the current page if in FE
			$storagePage = $GLOBALS['TSFE']->id;
			$pageIdsToClear[] = $storagePage;
		}

		if ($storagePage === NULL) {
			return;
		}
		if (!isset($this->pageTSConfigCache[$storagePage])) {
			$this->pageTSConfigCache[$storagePage] = t3lib_BEfunc::getPagesTSconfig($storagePage);
		}
		if (isset($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd'])) {
			$clearCacheCommands = t3lib_div::trimExplode(',',strtolower($this->pageTSConfigCache[$storagePage]['TCEMAIN.']['clearCacheCmd']),1);
			$clearCacheCommands = array_unique($clearCacheCommands);
			foreach ($clearCacheCommands as $clearCacheCommand)	{
				if (t3lib_utility_Math::canBeInterpretedAsInteger($clearCacheCommand))	{
					$pageIdsToClear[] = $clearCacheCommand;
				}
			}
		}

		// TODO check if we can hand this over to the Dispatcher to clear the page only once, this will save around 10% time while inserting and updating
		$this->cacheService->clearPageCache($pageIdsToClear);
	}
}

?>
