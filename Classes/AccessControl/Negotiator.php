<?php
namespace PAGEmachine\CORS\AccessControl;

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

/**
 * Negotiator for access control requests
 */
class Negotiator {

  /**
   * List of simple methods
   *
   * @var array
   */
  protected $simpleMethods = array(
    'GET',
    'HEAD',
    'POST',
  );

  /**
   * List of simple headers
   *
   * @var array
   */
  protected $simpleHeaders = array(
    'Accept',
    'Accept-Language',
    'Content-Language',
  );

  /**
   * @var boolean $allowCredentials
   */
  protected $allowCredentials = FALSE;
  
  /**
   * @return boolean
   */
  public function getAllowCredentials() {
    return $this->allowCredentials;
  }
  
  /**
   * @param boolean $allowCredentials
   * @return void
   */
  public function setAllowCredentials($allowCredentials) {
    $this->allowCredentials = $allowCredentials;
  }

  /**
   * @var array $allowedHeaders
   */
  protected $allowedHeaders = array();
  
  /**
   * @return array
   */
  public function getAllowedHeaders() {
    return $this->allowedHeaders;
  }
  
  /**
   * @param array $allowedHeaders
   * @return void
   */
  public function setAllowedHeaders(array $allowedHeaders) {
    $this->allowedHeaders = $allowedHeaders;
  }

  /**
   * @var array $allowedMethods
   */
  protected $allowedMethods = array();
  
  /**
   * @return array
   */
  public function getAllowedMethods() {
    return $this->allowedMethods;
  }
  
  /**
   * @param array $allowedMethods
   * @return void
   */
  public function setAllowedMethods(array $allowedMethods) {
    $this->allowedMethods = $allowedMethods;
  }

  /**
   * @var array $allowedOrigins
   */
  protected $allowedOrigins = array();
  
  /**
   * @return array
   */
  public function getAllowedOrigins() {
    return $this->allowedOrigins;
  }
  
  /**
   * @param array $allowedOrigins
   * @return void
   */
  public function setAllowedOrigins(array $allowedOrigins) {
    $this->allowedOrigins = $allowedOrigins;
  }

  /**
   * @var string $allowedOriginsPattern
   */
  protected $allowedOriginsPattern;
  
  /**
   * @return string
   */
  public function getAllowedOriginsPattern() {
    return $this->allowedOriginsPattern;
  }
  
  /**
   * @param string $allowedOriginsPattern
   * @return void
   */
  public function setAllowedOriginsPattern($allowedOriginsPattern) {
    $this->allowedOriginsPattern = $allowedOriginsPattern;
  }

  /**
   * @var array $exposedHeaders
   */
  protected $exposedHeaders = array();
  
  /**
   * @return array
   */
  public function getExposedHeaders() {
    return $this->exposedHeaders;
  }
  
  /**
   * @param array $exposedHeaders
   * @return void
   */
  public function setExposedHeaders(array $exposedHeaders) {
    $this->exposedHeaders = $exposedHeaders;
  }

  /**
   * @var integer $maximumAge
   */
  protected $maximumAge;
  
  /**
   * @return integer
   */
  public function getMaximumAge() {
    return $this->maximumAge;
  }
  
  /**
   * @param integer $maximumAge
   * @return void
   */
  public function setMaximumAge($maximumAge) {
    $this->maximumAge = $maximumAge;
  }

  /**
   * Processes an access control request
   *
   * @param Request $request Access control request to process
   * @return void
   * @throws Exception\AccessDeniedException If access is not allowed
   */
  public function processRequest(Request $request, Response $response) {

    if (!$request->isCrossOrigin()) {

      return;
    }

    if ($request->isPreflight()) {

      if (!$request->getRequestMethod()) {

        throw new Exception\AccessDeniedException('Missing request method', 1413983849);
      }

      if (!$this->isMethodAllowed($request->getRequestMethod())) {

        throw new Exception\AccessDeniedException('Request method "' . $request->getRequestMethod() . '" not allowed', 1413983927);
      }

      foreach ($request->getRequestHeaders() as $header) {
        
        if (!$this->isHeaderAllowed($header)) {

          throw new Exception\AccessDeniedException('Request header "' . $header . '" not allowed', 1413988013);
        }
      }

      $response->setIsPreflight(TRUE);
      $response->setAllowedMethods(array($request->getRequestMethod()));
      $response->setAllowedHeaders($request->getRequestHeaders());
      $response->setMaximumAge($this->getMaximumAge());
    }

    $originUri = $request->getOrigin()->getScheme() . '://' . $request->getOrigin()->getHostname();

    if ($this->isOriginUriAllowed('*') && !$request->hasCredentials()) {

      $response->setAllowedOrigin('*');
    } elseif ($this->isOriginUriAllowed($originUri)) {

      $response->setAllowedOrigin($originUri);
    } else {

      throw new Exception\AccessDeniedException('Access not allowed for origin "' . $originUri . '"', 1413983266);
    }

    if ($request->hasCredentials()) {

      $response->setAllowCredentials($this->getAllowCredentials());
    }

    $response->setExposedHeaders($this->getExposedHeaders());
  }

  /**
   * Returns TRUE, if access is allowed for an origin, FALSE otherwise
   *
   * @param string $originUri The origin URI
   * @return boolean
   */
  protected function isOriginUriAllowed($originUri) {

    // Check for exact match
    if (in_array($originUri, $this->allowedOrigins)) {

      return TRUE;
    }

    // Check for pattern match
    if ($this->allowedOriginsPattern) {

      // Explicitely not using preg_quote() here to allow for pattern passthrough
      if (preg_match('~^' . $this->allowedOriginsPattern . '~i', $originUri) === 1) {

        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Returns TRUE, if a request method is allowed, FALSE otherwise
   *
   * Note that simple methods are always allowed
   *
   * @param string $method The HTTP method (POST/PUT/...)
   * @return boolean
   */
  protected function isMethodAllowed($method) {

    return in_array($method, $this->simpleMethods, TRUE) ||
           in_array($method, $this->allowedMethods, TRUE);
  }

  /**
   * Returns TRUE, if a request header is allowed, FALSE otherwise
   *
   * Note that simple headers are always allowed
   *
   * @param string $header The HTTP header (X-Foo/...)
   * @return boolean
   */
  protected function isHeaderAllowed($header) {

    $header = strtolower($header);

    return in_array($header, array_map('strtolower', $this->simpleHeaders), TRUE) ||
           in_array($header, array_map('strtolower', $this->allowedHeaders), TRUE);
  }
}
