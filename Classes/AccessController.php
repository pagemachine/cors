<?php
namespace PAGEmachine\CORS;

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

use PAGEmachine\CORS\Http\Uri;

class AccessController {

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
  protected $allowedHeaders;
  
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
  protected $allowedMethods;
  
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
   * @var array $exposedHeaders
   */
  protected $exposedHeaders;
  
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
   * Sets up a new object
   *
   * @param array $allowedOrigins List of allowed hosts
   */
  public function __construct(array $allowedOrigins = array()) {

    $this->setAllowedOrigins($allowedOrigins);
  }

  /**
   * Returns TRUE, if the current request is cross origin, FALSE otherwise
   *
   * A request is cross origin if either the host name or the scheme does not match
   *
   * @param Uri $origin The origin
   * @param Uri $request The current request
   * @return boolean
   */
  public function isCrossOriginRequest(Uri $origin, Uri $request) {

    return $origin->getHostname() != $request->getHostname() ||
      $origin->getScheme() != $request->getScheme();
  }

  /**
   * Returns TRUE, if access is allowed for an origin, FALSE otherwise
   *
   * @param string $originUri The origin URI
   * @return boolean
   */
  public function isOriginUriAllowed($originUri) {

    if (in_array($originUri, $this->allowedOrigins)) {

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Sends all access control HTTP headers for an origin
   *
   * @param Uri $origin The origin
   * @return void
   */
  public function sendHeadersForOrigin(Uri $origin) {

    $originUri = $origin->getScheme() . '://' . $origin->getHostname();

    if ($this->getAllowCredentials()) {

      header('Access-Control-Allow-Credentials: true');
    }

    if (count($this->getAllowedHeaders())) {

      header('Access-Control-Allow-Headers: ' . implode(', ', $this->getAllowedHeaders()));
    }

    if (count($this->getAllowedMethods())) {

      header('Access-Control-Allow-Methods: ' . implode(', ', $this->getAllowedMethods()));
    }

    if ($this->isOriginUriAllowed('*')) {

      header('Access-Control-Allow-Origin: *');
    } elseif ($this->isOriginUriAllowed($originUri)) {

      header('Access-Control-Allow-Origin: ' . $originUri);
    }

    if (count($this->getExposedHeaders())) {

      header('Access-Control-Expose-Headers: ' . implode(', ', $this->getExposedHeaders()));
    }

    if ($this->getMaximumAge()) {

      header('Access-Control-Max-Age: ' . $this->getMaximumAge());
    }
  }
}
