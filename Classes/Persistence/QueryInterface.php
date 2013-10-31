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
 * A persistence query interface
 *
 * @package Extbase
 * @subpackage Persistence
 * @api
 */
interface Tx_Extbase_Persistence_QueryInterface {

	/**
	 * The '=' comparison operator.
	 * @api
	*/
	const OPERATOR_EQUAL_TO = 1;

	/**
	 * The '!=' comparison operator.
	 * @api
	*/
	const OPERATOR_NOT_EQUAL_TO = 2;

	/**
	 * The '<' comparison operator.
	 * @api
	*/
	const OPERATOR_LESS_THAN = 3;

	/**
	 * The '<=' comparison operator.
	 * @api
	*/
	const OPERATOR_LESS_THAN_OR_EQUAL_TO = 4;

	/**
	 * The '>' comparison operator.
	 * @api
	*/
	const OPERATOR_GREATER_THAN = 5;

	/**
	 * The '>=' comparison operator.
	 * @api
	*/
	const OPERATOR_GREATER_THAN_OR_EQUAL_TO = 6;

	/**
	 * The 'like' comparison operator.
	 * @api
	*/
	const OPERATOR_LIKE = 7;

	/**
	 * The 'contains' comparison operator.
	 * @api
	*/
	const OPERATOR_CONTAINS = 8;

	/**
	 * The 'in' comparison operator.
	 * @api
	*/
	const OPERATOR_IN = 9;

	/**
	 * Constants representing the direction when ordering result sets.
	 */
	const ORDER_ASCENDING = 'ASC';
	const ORDER_DESCENDING = 'DESC';

	/**
	 * An inner join.
	 */
	const JCR_JOIN_TYPE_INNER = '{http://www.jcp.org/jcr/1.0}joinTypeInner';

	/**
	 * A left-outer join.
	 */
	const JCR_JOIN_TYPE_LEFT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeLeftOuter';

	/**
	 * A right-outer join.
	 */
	const JCR_JOIN_TYPE_RIGHT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeRightOuter';

	/**
	 * Charset of strings in QOM
	 */
	const CHARSET = 'utf-8';

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the node-tuple source; non-NULL
	 */
	public function getSource();

	/**
	 * Executes the query against the backend and returns the result
	 *
	 * @return Tx_Extbase_Persistence_QueryResultInterface|array The query result object or an array if $this->getQuerySettings()->getReturnRawQueryResult() is TRUE
	 * @api
	 */
	public function execute();

	/**
	 * Executes the query against the database and returns the number of matching objects
	 *
	 * @return integer The number of matching objects
	 * @deprecated since Extbase 1.3.0; was removed in FLOW3; will be removed in Extbase 1.5.0
	 */
	public function count();

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings);

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setLimit($limit);

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setOffset($offset);

	/**
	 * Sets the statement of this query programmatically. If you use this, you will lose the abstraction from a concrete storage
	 * backend (database).
	 *
	 * @param string $statement The statement
	 * @param array $parameters An array of parameters. These will be bound to placeholders '?' in the $statement.
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function statement($statement, array $parameters = array());

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param object $constraint Some constraint, depending on the backend
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function matching($constraint);

	/**
	 * Performs a logical conjunction of the two given constraints.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalAnd($constraint1);

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return object
	 * @api
	 */
	public function logicalOr($constraint1);

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return object
	 * @api
	 */
	public function logicalNot($constraint);

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return object
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE);

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function like($propertyName, $operand);

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * @param string $propertyName The name of the (multivalued) property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function contains($propertyName, $operand);

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return object
	 * @api
	 */
	public function in($propertyName, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function lessThan($propertyName, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function greaterThan($propertyName, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return object
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand);

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType();

	/**
	 * Sets the Query Settings. These Query settings must match the settings expected by
	 * the specific Storage Backend.
	 *
	 * @param Tx_Extbase_Persistence_QuerySettingsInterface $querySettings The Query Settings
	 * @return void
	 * @api This method is not part of FLOW3 API
	 */
	public function setQuerySettings(Tx_Extbase_Persistence_QuerySettingsInterface $querySettings);

	/**
	 * Returns the Query Settings.
	 *
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface $querySettings The Query Settings
	 * @api This method is not part of FLOW3 API
	 */
	public function getQuerySettings();
}
?>