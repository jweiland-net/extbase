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

require_once (t3lib_extMgm::extPath('extbase') . 'Tests/Unit/Fixtures/Entity.php');

class Tx_Extbase_Tests_Unit_MVC_Web_Routing_UriBuilderTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var tslib_fe
	 */
	protected $tsfeBackup;

	/**
	 * @var array
	 */
	protected $getBackup;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	/**
	 * @var tslib_cObj
	 */
	protected $mockContentObject;

	/**
	 * @var Tx_Extbase_MVC_Web_Request
	 */
	protected $mockRequest;

	/**
	 * @var Tx_Extbase_Service_ExtensionService
	 */
	protected $mockExtensionService;

	/**
	 * @var Tx_Extbase_MVC_Web_Routing_UriBuilder
	 */
	protected $uriBuilder;

	public function setUp() {
		$this->tsfeBackup = $GLOBALS['TSFE'];
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);

		$this->getBackup = t3lib_div::_GET();

		$this->mockContentObject = $this->getMock('tslib_cObj');
		$this->mockRequest = $this->getMock('Tx_Extbase_MVC_Web_Request');
		$this->mockExtensionService = $this->getMock('Tx_Extbase_Service_ExtensionService');
		$this->mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');

		$this->uriBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Routing_UriBuilder', array('build'));
		$this->uriBuilder->setRequest($this->mockRequest);
		$this->uriBuilder->_set('contentObject', $this->mockContentObject);
		$this->uriBuilder->injectConfigurationManager($this->mockConfigurationManager);

		$this->uriBuilder->injectExtensionService($this->mockExtensionService);
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
		t3lib_div::_GETset($this->getBackup);
	}

	/**
	 * @test
	 */
	public function settersAndGettersWorkAsExpected() {
		$this->uriBuilder
			->reset()
			->setArguments(array('test' => 'arguments'))
			->setSection('testSection')
			->setFormat('testFormat')
			->setCreateAbsoluteUri(TRUE)
			->setAbsoluteUriScheme('https')
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'))
			->setArgumentPrefix('testArgumentPrefix')
			->setLinkAccessRestrictedPages(TRUE)
			->setTargetPageUid(123)
			->setTargetPageType(321)
			->setNoCache(TRUE)
			->setUseCacheHash(FALSE);

		$this->assertEquals(array('test' => 'arguments'), $this->uriBuilder->getArguments());
		$this->assertEquals('testSection', $this->uriBuilder->getSection());
		$this->assertEquals('testFormat', $this->uriBuilder->getFormat());
		$this->assertEquals(TRUE, $this->uriBuilder->getCreateAbsoluteUri());
		$this->assertEquals('https', $this->uriBuilder->getAbsoluteUriScheme());
		$this->assertEquals(TRUE, $this->uriBuilder->getAddQueryString());
		$this->assertEquals(array('test' => 'addQueryStringExcludeArguments'), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
		$this->assertEquals('testArgumentPrefix', $this->uriBuilder->getArgumentPrefix());
		$this->assertEquals(TRUE, $this->uriBuilder->getLinkAccessRestrictedPages());
		$this->assertEquals(123, $this->uriBuilder->getTargetPageUid());
		$this->assertEquals(321, $this->uriBuilder->getTargetPageType());
		$this->assertEquals(TRUE, $this->uriBuilder->getNoCache());
		$this->assertEquals(FALSE, $this->uriBuilder->getUseCacheHash());
	}

	/**
	 * @test
	 */
	public function uriForPrefixesArgumentsWithExtensionAndPluginNameAndSetsControllerArgument() {
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->will($this->returnValue('tx_someextension_someplugin'));
		$expectedArguments = array('tx_someextension_someplugin' => array('foo' => 'bar', 'baz' => array('extbase' => 'fluid'), 'controller' => 'SomeController'));
		$GLOBALS['TSFE'] = NULL;
		$this->uriBuilder->uriFor(NULL, array('foo' => 'bar', 'baz' => array('extbase' => 'fluid')), 'SomeController', 'SomeExtension', 'SomePlugin');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForRecursivelyMergesAndOverrulesControllerArgumentsWithArguments() {
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->will($this->returnValue('tx_someextension_someplugin'));
		$arguments = array('tx_someextension_someplugin' => array('foo' => 'bar'), 'additionalParam' => 'additionalValue');
		$controllerArguments = array('foo' => 'overruled', 'baz' => array('extbase' => 'fluid'));
		$expectedArguments = array('tx_someextension_someplugin' => array('foo' => 'overruled', 'baz' => array('extbase' => 'fluid'), 'controller' => 'SomeController'), 'additionalParam' => 'additionalValue');

		$this->uriBuilder->setArguments($arguments);
		$this->uriBuilder->uriFor(NULL, $controllerArguments, 'SomeController', 'SomeExtension', 'SomePlugin');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForOnlySetsActionArgumentIfSpecified() {
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->will($this->returnValue('tx_someextension_someplugin'));
		$expectedArguments = array('tx_someextension_someplugin' => array('controller' => 'SomeController'));

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomeExtension', 'SomePlugin');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsControllerFromRequestIfControllerIsNotSet() {
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->will($this->returnValue('tx_someextension_someplugin'));
		$this->mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue('SomeControllerFromRequest'));

		$expectedArguments = array('tx_someextension_someplugin' => array('controller' => 'SomeControllerFromRequest'));

		$this->uriBuilder->uriFor(NULL, array(), NULL, 'SomeExtension', 'SomePlugin');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsExtensionNameFromRequestIfExtensionNameIsNotSet() {
		$this->mockExtensionService->expects($this->any())->method('getPluginNamespace')->will($this->returnValue('tx_someextensionnamefromrequest_someplugin'));
		$this->mockRequest->expects($this->once())->method('getControllerExtensionName')->will($this->returnValue('SomeExtensionNameFromRequest'));

		$expectedArguments = array('tx_someextensionnamefromrequest_someplugin' => array('controller' => 'SomeController'));

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', NULL, 'SomePlugin');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForSetsPluginNameFromRequestIfPluginNameIsNotSet() {
		$this->mockExtensionService->expects($this->once())->method('getPluginNamespace')->will($this->returnValue('tx_someextension_somepluginnamefromrequest'));
		$this->mockRequest->expects($this->once())->method('getPluginName')->will($this->returnValue('SomePluginNameFromRequest'));

		$expectedArguments = array('tx_someextension_somepluginnamefromrequest' => array('controller' => 'SomeController'));

		$this->uriBuilder->uriFor(NULL, array(), 'SomeController', 'SomeExtension');
		$this->assertEquals($expectedArguments, $this->uriBuilder->getArguments());
	}

	/**
	 * @test
	 */
	public function uriForDoesNotDisableCacheHashForNonCacheableActions() {
		$this->mockExtensionService->expects($this->any())->method('isActionCacheable')->will($this->returnValue(FALSE));
		$this->uriBuilder->uriFor('someNonCacheableAction', array(), 'SomeController', 'SomeExtension');
		$this->assertTrue($this->uriBuilder->getUseCacheHash());
	}

	/**
	 * @test
	 */
	public function buildBackendUriKeepsQueryParametersIfAddQueryStringIsSet() {
		t3lib_div::_GETset(array('M' => 'moduleKey', 'id' => 'pageId', 'foo' => 'bar'));

		$this->uriBuilder->setAddQueryString(TRUE);

		$expectedResult = 'mod.php?M=moduleKey&id=pageId&foo=bar';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriRemovesSpecifiedQueryParametersIfArgumentsToBeExcludedFromQueryStringIsSet() {
		t3lib_div::_GETset(array('M' => 'moduleKey', 'id' => 'pageId', 'foo' => 'bar'));

		$this->uriBuilder->setAddQueryString(TRUE);
		$this->uriBuilder->setArgumentsToBeExcludedFromQueryString(array('M', 'id'));

		$expectedResult = 'mod.php?foo=bar';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriKeepsModuleQueryParametersIfAddQueryStringIsNotSet() {
		t3lib_div::_GETset(array('M' => 'moduleKey', 'id' => 'pageId', 'foo' => 'bar'));

		$expectedResult = 'mod.php?M=moduleKey&id=pageId';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriMergesAndOverrulesQueryParametersWithArguments() {
		t3lib_div::_GETset(array('M' => 'moduleKey', 'id' => 'pageId', 'foo' => 'bar'));

		$this->uriBuilder->setArguments(array('M' => 'overwrittenModuleKey', 'somePrefix' => array('bar' => 'baz')));

		$expectedResult = 'mod.php?M=overwrittenModuleKey&id=pageId&somePrefix%5Bbar%5D=baz';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriConvertsDomainObjectsAfterArgumentsHaveBeenMerged() {
		t3lib_div::_GETset(array('M' => 'moduleKey'));

		$mockDomainObject = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject->_set('uid', '123');

		$this->uriBuilder->setArguments(array('somePrefix' => array('someDomainObject' => $mockDomainObject)));

		$expectedResult = 'mod.php?M=moduleKey&somePrefix%5BsomeDomainObject%5D=123';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriRespectsSection() {
		t3lib_div::_GETset(array('M' => 'moduleKey'));

		$this->uriBuilder->setSection('someSection');

		$expectedResult = 'mod.php?M=moduleKey#someSection';
		$actualResult = $this->uriBuilder->buildBackendUri();

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildBackendUriCreatesAbsoluteUrisIfSpecified() {
		t3lib_div::_GETset(array('M' => 'moduleKey'));

		$this->mockRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue('http://baseuri/' . TYPO3_mainDir));
		$this->uriBuilder->setCreateAbsoluteUri(TRUE);

		$expectedResult = 'http://baseuri/' . TYPO3_mainDir . 'mod.php?M=moduleKey';
		$actualResult = $this->uriBuilder->buildBackendUri();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildFrontendUriCreatesTypoLink() {
		$uriBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Routing_UriBuilder', array('buildTypolinkConfiguration'));
		$uriBuilder->_set('contentObject', $this->mockContentObject);
		$uriBuilder->expects($this->once())->method('buildTypolinkConfiguration')->will($this->returnValue(array('someTypoLinkConfiguration')));

		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->with(array('someTypoLinkConfiguration'));

		$uriBuilder->buildFrontendUri();
	}

	/**
	 * @test
	 */
	public function buildFrontendUriCreatesRelativeUrisByDefault() {
		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->will($this->returnValue('relative/uri'));

		$expectedResult = 'relative/uri';
		$actualResult = $this->uriBuilder->buildFrontendUri();

		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildFrontendUriDoesNotStripLeadingSlashesFromRelativeUris() {
		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->will($this->returnValue('/relative/uri'));

		$expectedResult = '/relative/uri';
		$actualResult = $this->uriBuilder->buildFrontendUri();

		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildFrontendUriCreatesAbsoluteUrisIfSpecified() {
		$uriBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Routing_UriBuilder', array('buildTypolinkConfiguration'));
		$uriBuilder->_set('contentObject', $this->mockContentObject);
		$uriBuilder->expects($this->once())->method('buildTypolinkConfiguration')->will($this->returnValue(array('foo' => 'bar')));

		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->with(array('foo' => 'bar', 'forceAbsoluteUrl' => TRUE))->will($this->returnValue('http://baseuri/relative/uri'));
		$uriBuilder->setCreateAbsoluteUri(TRUE);

		$expectedResult = 'http://baseuri/relative/uri';
		$actualResult = $uriBuilder->buildFrontendUri();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildFrontendUriSetsAbsoluteUriSchemeIfSpecified() {
		$uriBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Routing_UriBuilder', array('buildTypolinkConfiguration'));
		$uriBuilder->_set('contentObject', $this->mockContentObject);
		$uriBuilder->expects($this->once())->method('buildTypolinkConfiguration')->will($this->returnValue(array('foo' => 'bar')));

		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->with(array('foo' => 'bar', 'forceAbsoluteUrl' => TRUE, 'forceAbsoluteUrl.' => array('scheme' => 'someScheme')))->will($this->returnValue('http://baseuri/relative/uri'));
		$uriBuilder->setCreateAbsoluteUri(TRUE);
		$uriBuilder->setAbsoluteUriScheme('someScheme');

		$expectedResult = 'http://baseuri/relative/uri';
		$actualResult = $uriBuilder->buildFrontendUri();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function buildFrontendUriDoesNotSetAbsoluteUriSchemeIfCreateAbsoluteUriIsFalse() {
		$uriBuilder = $this->getAccessibleMock('Tx_Extbase_MVC_Web_Routing_UriBuilder', array('buildTypolinkConfiguration'));
		$uriBuilder->_set('contentObject', $this->mockContentObject);
		$uriBuilder->expects($this->once())->method('buildTypolinkConfiguration')->will($this->returnValue(array('foo' => 'bar')));

		$this->mockContentObject->expects($this->once())->method('typoLink_URL')->with(array('foo' => 'bar'))->will($this->returnValue('http://baseuri/relative/uri'));
		$uriBuilder->setCreateAbsoluteUri(FALSE);
		$uriBuilder->setAbsoluteUriScheme('someScheme');

		$expectedResult = 'http://baseuri/relative/uri';
		$actualResult = $uriBuilder->buildFrontendUri();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function resetSetsAllOptionsToTheirDefaultValue() {
		$this->uriBuilder
			->setArguments(array('test' => 'arguments'))
			->setSection('testSection')
			->setFormat('someFormat')
			->setCreateAbsoluteUri(TRUE)
			->setAddQueryString(TRUE)
			->setArgumentsToBeExcludedFromQueryString(array('test' => 'addQueryStringExcludeArguments'))
			->setArgumentPrefix('testArgumentPrefix')
			->setLinkAccessRestrictedPages(TRUE)
			->setTargetPageUid(123)
			->setTargetPageType(321)
			->setNoCache(TRUE)
			->setUseCacheHash(FALSE);

		$this->uriBuilder->reset();

		$this->assertEquals(array(), $this->uriBuilder->getArguments());
		$this->assertEquals('', $this->uriBuilder->getSection());
		$this->assertEquals('', $this->uriBuilder->getFormat());
		$this->assertEquals(FALSE, $this->uriBuilder->getCreateAbsoluteUri());
		$this->assertEquals(FALSE, $this->uriBuilder->getAddQueryString());
		$this->assertEquals(array(), $this->uriBuilder->getArgumentsToBeExcludedFromQueryString());
		$this->assertEquals(NULL, $this->uriBuilder->getArgumentPrefix());
		$this->assertEquals(FALSE, $this->uriBuilder->getLinkAccessRestrictedPages());
		$this->assertEquals(NULL, $this->uriBuilder->getTargetPageUid());
		$this->assertEquals(0, $this->uriBuilder->getTargetPageType());
		$this->assertEquals(FALSE, $this->uriBuilder->getNoCache());
		$this->assertEquals(TRUE, $this->uriBuilder->getUseCacheHash());
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationRespectsSpecifiedTargetPageUid() {
		$GLOBALS['TSFE']->id = 123;
		$this->uriBuilder->setTargetPageUid(321);

		$expectedConfiguration = array('parameter' => 321, 'useCacheHash' => 1);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationUsesCurrentPageUidIfTargetPageUidIsNotSet() {
		$GLOBALS['TSFE']->id = 123;

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationProperlySetsAdditionalArguments() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setArguments(array('foo' => 'bar', 'baz' => array('extbase' => 'fluid')));

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1, 'additionalParams' => '&foo=bar&baz[extbase]=fluid');
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationConvertsDomainObjects() {
		$mockDomainObject1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject1->_set('uid', '123');

		$mockDomainObject2 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject2->_set('uid', '321');

		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setArguments(array('someDomainObject' => $mockDomainObject1, 'baz' => array('someOtherDomainObject' => $mockDomainObject2)));

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1, 'additionalParams' => '&someDomainObject=123&baz[someOtherDomainObject]=321');
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationConsidersPageType() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setTargetPageType(2);

		$expectedConfiguration = array('parameter' => '123,2', 'useCacheHash' => 1);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationDisablesCacheHashIfNoCacheIsSet() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setNoCache(TRUE);

		$expectedConfiguration = array('parameter' => 123, 'no_cache' => 1);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationDoesNotSetUseCacheHashOptionIfUseCacheHashIsDisabled() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setUseCacheHash(FALSE);

		$expectedConfiguration = array('parameter' => 123);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationConsidersSection() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setSection('SomeSection');

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1, 'section' => 'SomeSection');
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function buildTypolinkConfigurationLinkAccessRestrictedPagesSetting() {
		$this->uriBuilder->setTargetPageUid(123);
		$this->uriBuilder->setLinkAccessRestrictedPages(TRUE);

		$expectedConfiguration = array('parameter' => 123, 'useCacheHash' => 1, 'linkAccessRestrictedPages' => 1);
		$actualConfiguration = $this->uriBuilder->_call('buildTypolinkConfiguration');

		$this->assertEquals($expectedConfiguration, $actualConfiguration);
	}

	/**
	 * @test
	 */
	public function convertDomainObjectsToIdentityArraysConvertsDomainObjects() {
		$mockDomainObject1 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject1->_set('uid', '123');

		$mockDomainObject2 = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_DomainObject_AbstractEntity'), array('dummy'));
		$mockDomainObject2->_set('uid', '321');

		$expectedResult = array('foo' => array('bar' => 'baz'), 'domainObject1' => '123', 'second' => array('domainObject2' =>'321'));
		$actualResult = $this->uriBuilder->_call('convertDomainObjectsToIdentityArrays', array('foo' => array('bar' => 'baz'), 'domainObject1' => $mockDomainObject1, 'second' => array('domainObject2' => $mockDomainObject2)));

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function conversionOfTansientObjectsIsInvoked() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractValueObject { public $name; public $uid; }');
		$mockValueObject = new $className;
		$mockValueObject->name = 'foo';

		$mockUriBuilder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_Routing_UriBuilder'), array('convertTransientObjectToArray'));
		$mockUriBuilder->expects($this->once())->method('convertTransientObjectToArray')->will($this->returnValue(array('foo' => 'bar')));
		$actualResult = $mockUriBuilder->_call('convertDomainObjectsToIdentityArrays', array('object' => $mockValueObject));

		$expectedResult = array('object' => array('foo' => 'bar'));
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Extbase_MVC_Exception_InvalidArgumentValue
	 */
	public function conversionOfTansientObjectsThrowsExceptionForOtherThanValueObjects() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractEntity { public $name; public $uid; }');
		$mockEntity = new $className;
		$mockEntity->name = 'foo';

		$mockUriBuilder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_Routing_UriBuilder'), array('dummy'));
		$mockUriBuilder->_call('convertDomainObjectsToIdentityArrays', array('object' => $mockEntity));
	}

	/**
	 * @test
	 */
	public function tansientObjectsAreConvertedToAnArrayOfProperties() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractValueObject { public $name; public $uid; }');
		$mockValueObject = new $className;
		$mockValueObject->name = 'foo';

		$mockUriBuilder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_Routing_UriBuilder'), array('dummy'));
		$actualResult = $mockUriBuilder->_call('convertTransientObjectToArray', $mockValueObject);

		$expectedResult = array('name' => 'foo', 'uid' => NULL, 'pid' => NULL);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function tansientObjectsAreRecursivelyConverted() {
		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractValueObject { public $name; public $uid; }');
		$mockInnerValueObject2 = new $className;
		$mockInnerValueObject2->name = 'foo';
		$mockInnerValueObject2->uid = 99;

		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractValueObject { public $object; public $uid; }');
		$mockInnerValueObject1 = new $className;
		$mockInnerValueObject1->object = $mockInnerValueObject2;

		$className = uniqid('Tx_Extbase_Tests_Fixtures_Object');
		eval('class ' . $className . ' extends Tx_Extbase_DomainObject_AbstractValueObject { public $object; public $uid; }');
		$mockValueObject = new $className;
		$mockValueObject->object = $mockInnerValueObject1;

		$mockUriBuilder = $this->getMock($this->buildAccessibleProxy('Tx_Extbase_MVC_Web_Routing_UriBuilder'), array('dummy'));
		$actualResult = $mockUriBuilder->_call('convertTransientObjectToArray', $mockValueObject);

		$expectedResult = array(
			'object' => array(
				'object' => 99,
				'uid' => NULL,
				'pid' => NULL
				),
			'uid' => NULL,
			'pid' => NULL
			);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeDefaultControllerAndActionDoesNotModifyArgumentsifSpecifiedControlerAndActionIsNotEqualToDefaults() {
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->will($this->returnValue('DefaultController'));
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'SomeController')->will($this->returnValue('defaultAction'));

		$arguments = array('controller' => 'SomeController', 'action' => 'someAction', 'foo' => 'bar');
		$extensionName = 'ExtensionName';
		$pluginName = 'PluginName';
		$expectedResult = array('controller' => 'SomeController', 'action' => 'someAction', 'foo' => 'bar');

		$actualResult = $this->uriBuilder->_callRef('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeDefaultControllerAndActionRemovesControllerIfItIsEqualToTheDefault() {
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->will($this->returnValue('DefaultController'));
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'DefaultController')->will($this->returnValue('defaultAction'));

		$arguments = array('controller' => 'DefaultController', 'action' => 'someAction', 'foo' => 'bar');
		$extensionName = 'ExtensionName';
		$pluginName = 'PluginName';
		$expectedResult = array('action' => 'someAction', 'foo' => 'bar');

		$actualResult = $this->uriBuilder->_callRef('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeDefaultControllerAndActionRemovesActionIfItIsEqualToTheDefault() {
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->will($this->returnValue('DefaultController'));
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'SomeController')->will($this->returnValue('defaultAction'));

		$arguments = array('controller' => 'SomeController', 'action' => 'defaultAction', 'foo' => 'bar');
		$extensionName = 'ExtensionName';
		$pluginName = 'PluginName';
		$expectedResult = array('controller' => 'SomeController', 'foo' => 'bar');

		$actualResult = $this->uriBuilder->_callRef('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function removeDefaultControllerAndActionRemovesControllerAndActionIfBothAreEqualToTheDefault() {
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultControllerNameByPlugin')->with('ExtensionName', 'PluginName')->will($this->returnValue('DefaultController'));
		$this->mockExtensionService->expects($this->atLeastOnce())->method('getDefaultActionNameByPluginAndController')->with('ExtensionName', 'PluginName', 'DefaultController')->will($this->returnValue('defaultAction'));

		$arguments = array('controller' => 'DefaultController', 'action' => 'defaultAction', 'foo' => 'bar');
		$extensionName = 'ExtensionName';
		$pluginName = 'PluginName';
		$expectedResult = array('foo' => 'bar');

		$actualResult = $this->uriBuilder->_callRef('removeDefaultControllerAndAction', $arguments, $extensionName, $pluginName);
		$this->assertEquals($expectedResult, $actualResult);
	}

}
?>