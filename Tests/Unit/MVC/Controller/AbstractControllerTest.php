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

class Tx_Extbase_Tests_Unit_MVC_Controller_AbstractControllerTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 */
	public function theExtensionNameIsInitialized() {
		$extensionName = uniqid('Test');
		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('initializeObjects'), array(), 'Tx_' . $extensionName . '_Controller');
		$this->assertSame($extensionName, $controller->_get('extensionName'));
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_UnsupportedRequestType
	 */
	public function processRequestWillThrowAnExceptionIfTheGivenRequestIsNotSupported() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Web_Response');

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('mapRequestArgumentsToControllerArguments'), array(), '', FALSE);
		$controller->_set('supportedRequestTypes', array('Tx_Something_Request'));
		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequest() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(TRUE);

		$mockResponse = $this->getMock('Tx_Extbase_MVC_Web_Response');

		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')
			->with('Tx_Extbase_MVC_Web_Routing_UriBuilder')
			->will($this->returnValue($this->getMock('Tx_Extbase_MVC_Web_Routing_UriBuilder')));

		$controller = $this->getAccessibleMock(
			'Tx_Extbase_MVC_Controller_AbstractController',
			array('initializeArguments', 'initializeControllerArgumentsBaseValidators', 'mapRequestArgumentsToControllerArguments', 'buildControllerContext'),
			array(), '', FALSE
		);
		$controller->_set('objectManager', $mockObjectManager);

		$controller->processRequest($mockRequest, $mockResponse);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_StopAction
	 */
	public function forwardThrowsAStopActionException() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockRequest->expects($this->once())->method('setDispatched')->with(FALSE);
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_call('forward', 'foo');
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_StopAction
	 */
	public function forwardSetsControllerAndArgumentsAtTheRequestObjectIfTheyAreSpecified() {
		$arguments = array('foo' => 'bar');

		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('foo');
		$mockRequest->expects($this->once())->method('setControllerName')->with('Bar');
		$mockRequest->expects($this->once())->method('setControllerExtensionName')->with('Baz');
		$mockRequest->expects($this->once())->method('setArguments')->with($arguments);

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_call('forward', 'foo', 'Bar', 'Baz', $arguments);
	}

	/**
	 * @test
	 */
	public function redirectRedirectsToTheSpecifiedAction() {
		$arguments = array('foo' => 'bar');

		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Web_Response');

		$mockUriBuilder = $this->getMock('Tx_Extbase_MVC_Web_Routing_UriBuilder');
		$mockUriBuilder->expects($this->once())->method('reset')->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setTargetPageUid')->with(123)->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(TRUE)->will($this->returnValue($mockUriBuilder));
		$mockUriBuilder->expects($this->once())->method('uriFor')->with('theActionName', $arguments, 'TheControllerName', 'TheExtensionName')->will($this->returnValue('the uri'));

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('redirectToUri'), array(), '', FALSE);
		$controller->expects($this->once())->method('redirectToUri')->with('the uri');
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_set('uriBuilder', $mockUriBuilder);
		$controller->_call('redirect', 'theActionName', 'TheControllerName', 'TheExtensionName', $arguments, 123);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_StopAction
	 */
	public function throwStatusSetsTheSpecifiedStatusHeaderAndStopsTheCurrentAction() {
		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockResponse = $this->getMock('Tx_Extbase_MVC_Web_Response');
		$mockResponse->expects($this->once())->method('setStatus')->with(404, 'File Really Not Found');
		$mockResponse->expects($this->once())->method('setContent')->with('<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('request', $mockRequest);
		$controller->_set('response', $mockResponse);
		$controller->_call('throwStatus', 404, 'File Really Not Found', '<h1>All wrong!</h1><p>Sorry, the file does not exist.</p>');
	}

	/**
	 * @test
	 */
	public function initializeControllerArgumentsBaseValidatorsRegistersValidatorsDeclaredInTheArgumentModels() {
		$mockValidators = array(
			'foo' => $this->getMock('Tx_Extbase_Validation_Validator_ValidatorInterface'),
		);

		$mockValidatorResolver = $this->getMock('Tx_Extbase_Validation_ValidatorResolver', array(), array(), '', FALSE);
		$mockValidatorResolver->expects($this->at(0))->method('getBaseValidatorConjunction')->with('FooType')->will($this->returnValue($mockValidators['foo']));
		$mockValidatorResolver->expects($this->at(1))->method('getBaseValidatorConjunction')->with('BarType')->will($this->returnValue(NULL));

		$mockArgumentFoo = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array('foo'), '', FALSE);
		$mockArgumentFoo->expects($this->once())->method('getDataType')->will($this->returnValue('FooType'));
		$mockArgumentFoo->expects($this->once())->method('setValidator')->with($mockValidators['foo']);

		$mockArgumentBar = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array('bar'), '', FALSE);
		$mockArgumentBar->expects($this->once())->method('getDataType')->will($this->returnValue('BarType'));
		$mockArgumentBar->expects($this->never())->method('setValidator');

		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$controller = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Controller_AbstractController'), array('dummy'), array(), '', FALSE);
		$controller->_set('arguments', $mockArguments);
		$controller->injectValidatorResolver($mockValidatorResolver);
		$controller->_call('initializeControllerArgumentsBaseValidators');
	}

	/**
	 * This test checks @deprecated behavior.
	 * @test
	 * @deprecated since Extbase 1.4.0, will be removed in Extbase 6.0
	 */
	public function mapRequestArgumentsToControllerArgumentsPreparesInformationAndValidatorsAndMapsAndValidates() {
		$mockValidator = $this->getMock('Tx_Extbase_MVC_Controller_ArgumentsValidator');
		$mockObjectManager = $this->getMock('Tx_Extbase_Object_ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('create')
			->with('Tx_Extbase_MVC_Controller_ArgumentsValidator')
			->will($this->returnValue($mockValidator));

		$mockArgumentFoo = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array('foo'), '', FALSE);
		$mockArgumentFoo->expects($this->any())->method('getName')->will($this->returnValue('foo'));
		$mockArgumentBar = $this->getMock('Tx_Extbase_MVC_Controller_Argument', array(), array('bar'), '', FALSE);
		$mockArgumentBar->expects($this->any())->method('getName')->will($this->returnValue('bar'));

		$mockArguments = new Tx_Extbase_MVC_Controller_Arguments();
		$mockArguments->addArgument($mockArgumentFoo);
		$mockArguments->addArgument($mockArgumentBar);

		$mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array('requestFoo', 'requestBar')));

		$mockMappingResults = $this->getMock('Tx_Extbase_Property_MappingResults');

		$mockDeprecatedPropertyMapper = $this->getMock('Tx_Extbase_Property_Mapper', array(), array(), '', FALSE);
		$mockDeprecatedPropertyMapper->expects($this->once())->method('mapAndValidate')
			->with(array('foo', 'bar'), array('requestFoo', 'requestBar'), $mockArguments, array(), $mockValidator)
			->will($this->returnValue(TRUE));
		$mockDeprecatedPropertyMapper->expects($this->once())->method('getMappingResults')->will($this->returnValue($mockMappingResults));

		$controller = $this->getAccessibleMock(
			'Tx_Extbase_MVC_Controller_AbstractController',
			array('dummy'), array(), '', FALSE
		);

		$mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$mockConfigurationManager->expects($this->any())->method('isFeatureEnabled')->with('rewrittenPropertyMapper')->will($this->returnValue(FALSE));
		$controller->_set('configurationManager', $mockConfigurationManager);

		$controller->_set('arguments', $mockArguments);
		$controller->_set('request', $mockRequest);
		$controller->_set('deprecatedPropertyMapper', $mockDeprecatedPropertyMapper);
		$controller->_set('objectManager', $mockObjectManager);

		$controller->_call('mapRequestArgumentsToControllerArguments');

		$this->assertSame($mockMappingResults, $controller->_get('argumentsMappingResults'));
	}
}
?>