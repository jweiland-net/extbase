<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Sebastian Kurfuerst <sebastian@typo3.org>
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

class Tx_Extbase_Tests_Unit_MVC_Web_RequestTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @test
	 */
	public function isCachedReturnsFalseByDefault() {
		$request = new Tx_Extbase_MVC_Web_Request();
		$this->assertFalse($request->isCached());
	}

	/**
	 * @test
	 */
	public function isCachedReturnsTheValueWhichWasPreviouslySet() {
		$request = new Tx_Extbase_MVC_Web_Request();
		$request->setIsCached(TRUE);
		$this->assertTrue($request->isCached());
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnNullByDefault() {
		$request = new Tx_Extbase_MVC_Web_Request();
		$this->assertNull($request->getReferringRequest());
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnCorrectlyBuiltReferringRequest() {
		$request = new Tx_Extbase_MVC_Web_Request();
		$request->setArgument('__referrer', array(
			'@controller' => 'Foo',
			'@action' => 'bar'
		));
		$referringRequest = $request->getReferringRequest();
		$this->assertNotNull($referringRequest);

		$this->assertAttributeEquals('Foo', 'controllerName', $referringRequest);
		$this->assertAttributeEquals('bar', 'controllerActionName', $referringRequest);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_Security_Exception_InvalidHash
	 */
	public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValidated() {
		$request = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Request', array('dummy'));
		$request->setArgument('__referrer', array(
			'@controller' => 'Foo',
			'@action' => 'bar',
			'arguments' => base64_encode('some manipulated arguments string without valid HMAC')
		));
		$request->_set('hashService', new Tx_Extbase_Security_Cryptography_HashService());
		$request->getReferringRequest();
	}
}
?>