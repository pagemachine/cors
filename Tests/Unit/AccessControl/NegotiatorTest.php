<?php
namespace PAGEmachine\Cors\Tests\Unit\AccessControl;

/*
 * This file is part of the PAGEmachine CORS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use PAGEmachine\Cors\AccessControl\Negotiator;

/**
 * Testcase for PAGEmachine\Cors\AccessControl\Negotiator
 */
class NegotiatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

  /**
   * @var \PAGEmachine\Cors\AccessControl\Request
   */
  protected $request;

  /**
   * @var \PAGEmachine\Cors\AccessControl\Response
   */
  protected $response;

  /**
   * @var \PAGEmachine\Cors\AccessControl\Negotiator
   */
  protected $negotiator;

  /**
   * Common setup for all tests
   */
  public function setUp() {

    $origin = $this->getMockBuilder('PAGEmachine\\Cors\\Http\\Uri')
      ->setMethods(NULL)
      ->getMock();
    $this->request = $this->getMockBuilder('PAGEmachine\\Cors\\AccessControl\\Request')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $this->request->setOrigin($origin);

    $this->response = $this->getResponseMock();

    $this->negotiator = new Negotiator();
  }

  /**
   * @test
   */
  public function doesNothingForSameOriginRequest() {

    $expectedResponse = $this->getResponseMock();

    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals($expectedResponse, $this->response);
  }

  /**
   * @test
   */
  public function allowsWildcardOriginWithoutCredentials() {

    $this->request->setIsCrossOrigin(TRUE);

    $this->negotiator->setAllowedOrigins(array('*'));
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals('*', $this->response->getAllowedOrigin());
  }

  /**
   * @test
   * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
   * @expectedExceptionCode 1413983266
   */
  public function throwsExceptionForWildcardOriginWithCredentials() {

    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setHasCredentials(TRUE);

    $this->negotiator->setAllowedOrigins(array('*'));
    $this->negotiator->processRequest($this->request, $this->response);
  }

  /**
   * @test
   */
  public function allowsOriginByList() {

    $this->request->getOrigin()->setScheme('http');
    $this->request->getOrigin()->setHostname('example.org');
    $this->request->setIsCrossOrigin(TRUE);

    $this->negotiator->setAllowedOrigins(array('http://example.org', 'http://example.com'));
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals('http://example.org', $this->response->getAllowedOrigin());
  }

  /**
   * @test
   * @dataProvider originPatternRequests
   *
   * @param string $pattern
   * @param string $origin
   */
  public function allowsOriginByPattern($pattern, $origin) {

    $this->request->setIsCrossOrigin(TRUE);

    $scheme = parse_url($origin, PHP_URL_SCHEME);
    $this->request->getOrigin()->setScheme($scheme);
    $host = parse_url($origin, PHP_URL_HOST);
    $this->request->getOrigin()->setHostname($host);

    $this->negotiator->setAllowedOriginsPattern($pattern);
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals($origin, $this->response->getAllowedOrigin());
  }

  /**
   * @test
   * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
   * @expectedExceptionCode 1413983266
   */
  public function throwsExceptionIfOriginIsNotAllowed() {

    $this->request->getOrigin()->setScheme('http');
    $this->request->getOrigin()->setHostname('example.org');
    $this->request->setIsCrossOrigin(TRUE);

    $this->negotiator->processRequest($this->request, $this->response);
  }

  /**
   * @test
   * @dataProvider credentialRequests
   *
   * @param boolean $requestHasCredentials
   * @param boolean $allowCredentials
   * @param boolean $expectedAllowCredentials
   */
  public function allowsCredentials($requestHasCredentials, $allowCredentials, $expectedAllowCredentials) {

    $this->request->getOrigin()->setScheme('http');
    $this->request->getOrigin()->setHostname('example.org');
    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setHasCredentials($requestHasCredentials);

    $this->negotiator->setAllowedOrigins(array('http://example.org', 'http://example.com'));
    $this->negotiator->setAllowCredentials($allowCredentials);
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals($expectedAllowCredentials, $this->response->getAllowCredentials());
  }

  /**
   * @test
   * @dataProvider exposedHeaderRequests
   *
   * @param array $exposedHeaders
   * @return array
   */
  public function exposesHeaders(array $exposedHeaders) {

    $this->request->getOrigin()->setScheme('http');
    $this->request->getOrigin()->setHostname('example.org');
    $this->request->setIsCrossOrigin(TRUE);

    $this->negotiator->setAllowedOrigins(array('http://example.org', 'http://example.com'));
    $this->negotiator->setExposedHeaders($exposedHeaders);
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertEquals($exposedHeaders, $this->response->getExposedHeaders());
  }

  /**
   * @test
   * @dataProvider preflightRequests
   *
   * @param string $requestMethod
   * @param array $requestHeaders
   * @param array $allowedMethods
   * @param array $allowedHeaders
   */
  public function allowsValidPreflightRequests($requestMethod, array $requestHeaders, array $allowedMethods, array $allowedHeaders) {

    $this->request->getOrigin()->setScheme('http');
    $this->request->getOrigin()->setHostname('example.org');
    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setIsPreflight(TRUE);
    $this->request->setRequestMethod($requestMethod);
    $this->request->setRequestHeaders($requestHeaders);

    $this->negotiator->setAllowedOrigins(array('http://example.org'));
    $this->negotiator->setAllowedMethods($allowedMethods);
    $this->negotiator->setAllowedHeaders($allowedHeaders);
    $this->negotiator->processRequest($this->request, $this->response);

    $this->assertContains($requestMethod, $this->response->getAllowedMethods());

    foreach ($requestHeaders as $requestHeader) {

      $this->assertContains($requestHeader, $this->response->getAllowedHeaders());
    }
  }

  /**
   * @test
   * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
   * @expectedExceptionCode 1413983849
   */
  public function throwsExceptionForPreflightWithoutRequestMethod() {

    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setIsPreflight(TRUE);

    $this->negotiator->processRequest($this->request, $this->response);
  }

  /**
   * @test
   * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
   * @expectedExceptionCode 1413983927
   */
  public function throwsExceptionForPreflightWithNotAllowedRequestMethod() {

    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setIsPreflight(TRUE);
    $this->request->setRequestMethod('DELETE');

    $this->negotiator->setAllowedMethods(array('PUT'));
    $this->negotiator->processRequest($this->request, $this->response);
  }

  /**
   * @test
   * @expectedException PAGEmachine\Cors\AccessControl\Exception\AccessDeniedException
   * @expectedExceptionCode 1413988013
   */
  public function throwsExceptionForPreflightWithNotAllowedRequestHeaders() {

    $this->request->setIsCrossOrigin(TRUE);
    $this->request->setIsPreflight(TRUE);
    $this->request->setRequestMethod('POST');
    $this->request->setRequestHeaders(array('X-Foo'));

    $this->negotiator->processRequest($this->request, $this->response);
  }

  /**
   * @return array
   */
  public function originPatternRequests() {

    return array(
      array(
        'http:\/\/example\.(org|com)',
        'http://example.org',
      ),
      array(
        'http:\/\/example\.(org|com)',
        'http://example.com',
      ),
    );
  }

  /**
   * @return array
   */
  public function credentialRequests() {

    return array(
      'No credentials, not allowed' => array(
        FALSE,
        FALSE,
        FALSE,
      ),
      'No credentials, allowed' => array(
        FALSE,
        TRUE,
        FALSE,
      ),
      'Credentials, not allowed' => array(
        TRUE,
        FALSE,
        FALSE,
      ),
      'Credentials, allowed' => array(
        TRUE,
        TRUE,
        TRUE,
      ),
    );
  }

  /**
   * @return array
   */
  public function exposedHeaderRequests() {

    return array(
      'No headers' => array(
        array(),
      ),
      'Exposed headers' => array(
        array('X-Foo', 'X-Bar'),
      ),
    );
  }

  /**
   * @return array
   */
  public function preflightRequests() {

    return array(
      'Simple method with custom header' => array(
        'POST',
        array('X-Foo'),
        array(),
        array('X-Foo', 'X-Bar'),
      ),
      'Non-simple method' => array(
        'PUT',
        array(),
        array('PUT'),
        array(),
      ),
      'Non-simple method with custom header' => array(
        'DELETE',
        array('X-Bar'),
        array('PUT', 'DELETE'),
        array('X-Bar'),
      ),
    );
  }

  /**
   * Builds a mocked response object
   *
   * @return \PAGEmachine\Cors\AccessControl\Response
   */
  protected function getResponseMock() {

    return $this->getMockBuilder('PAGEmachine\\Cors\\AccessControl\\Response')
      ->setMethods(NULL)
      ->getMock();
  }
}
