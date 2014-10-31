<?php
namespace PAGEmachine\CORS\Tests\AccessControl;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Mathias Brodala <mbrodala@pagemachine.de>, PAGEmachine AG
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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

use PAGEmachine\CORS\AccessControl\Response;

/**
 * Testcase for PAGEmachine\CORS\AccessControl\Response
 */
class ResponseTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

  /**
   * @test
   * @dataProvider properties
   *
   * @param string $allowedOrigin
   * @param boolean $allowCredentials
   * @param array $exposedHeaders
   * @param boolean $isPreflight
   * @param array $allowedMethods
   * @param array $allowedHeaders
   * 
   * @param array $expectedHeaders
   * @param boolean $expectedSkipBodyAndExit
   */
  public function sendsCorrectHeaders($allowedOrigin, $allowCredentials, array $exposedHeaders, $isPreflight, array $allowedMethods, array $allowedHeaders, array $expectedHeaders, $expectedSkipBodyAndExit) {

    $headers = array();
    $skipBodyAndExit = FALSE;

    $response = $this->getMockBuilder('PAGEmachine\\CORS\\AccessControl\\Response')
      ->setMethods(array('sendHeader', 'skipBodyAndExit'))
      ->getMock();
    $response->expects($this->any())
      ->method('sendHeader')
      ->will($this->returnCallback(function($header) use (&$headers) {

        $headers[] = $header;
      }));
    $response->expects($this->atMost(1))
      ->method('skipBodyAndExit')
      ->will($this->returnCallback(function() use (&$skipBodyAndExit) {

        $skipBodyAndExit = TRUE;
      }));

    $response->setAllowedOrigin($allowedOrigin);
    $response->setAllowCredentials($allowCredentials);
    $response->setExposedHeaders($exposedHeaders);
    $response->setIsPreflight($isPreflight);
    $response->setAllowedMethods($allowedMethods);
    $response->setAllowedHeaders($allowedHeaders);
    $response->send();

    $this->assertEquals($expectedHeaders, $headers);
    $this->assertEquals($expectedSkipBodyAndExit, $skipBodyAndExit);
  }

  /**
   * @return array
   */
  public function properties() {

    return array(
      'Basic' => array(
        'http://example.org',
        FALSE,
        array(),
        FALSE,
        array(),
        array(),
        array(
          'Access-Control-Allow-Origin: http://example.org',
        ),
        FALSE,
      ),
      'Credentials' => array(
        'http://example.org',
        TRUE,
        array(),
        FALSE,
        array(),
        array(),
        array(
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Credentials: true',
        ),
        FALSE,
      ),
      'Exposed headers' => array(
        'http://example.org',
        FALSE,
        array('X-Foo', 'X-Bar'),
        FALSE,
        array(),
        array(),
        array(
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Expose-Headers: X-Foo, X-Bar',
        ),
        FALSE,
      ),
      'Preflight' => array(
        'http://example.org',
        FALSE,
        array(),
        TRUE,
        array(),
        array(),
        array(
          'Access-Control-Allow-Origin: http://example.org',
        ),
        TRUE,
      ),
      'Preflight method' => array(
        'http://example.org',
        FALSE,
        array(),
        TRUE,
        array('PUT'),
        array(),
        array(
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Methods: PUT',
        ),
        TRUE,
      ),
      'Preflight headers' => array(
        'http://example.org',
        FALSE,
        array(),
        TRUE,
        array(),
        array('X-Foo'),
        array(
          'Access-Control-Allow-Origin: http://example.org',
          'Access-Control-Allow-Headers: X-Foo',
        ),
        TRUE,
      ),
    );
  }
}
