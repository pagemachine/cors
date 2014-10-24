<?php
namespace PAGEmachine\CORS\Http;

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
 * Represents a URI as object
 */
class Uri {

  /**
   * @var string $scheme
   */
  protected $scheme;
  
  /**
   * @return string
   */
  public function getScheme() {
    return $this->scheme;
  }
  
  /**
   * @param string $scheme
   * @return void
   */
  public function setScheme($scheme) {
    $this->scheme = $scheme;
  }

  /**
   * @var string $hostname
   */
  protected $hostname;
  
  /**
   * @return string
   */
  public function getHostname() {
    return $this->hostname;
  }
  
  /**
   * @param string $hostname
   * @return void
   */
  public function setHostname($hostname) {
    $this->hostname = $hostname;
  }

  /**
   * @var integer $port
   */
  protected $port;
  
  /**
   * @return integer
   */
  public function getPort() {
    return $this->port;
  }
  
  /**
   * @param integer $port
   * @return void
   */
  public function setPort($port) {
    $this->port = $port;
  }

  /**
   * @var string $username
   */
  protected $username;
  
  /**
   * @return string
   */
  public function getUsername() {
    return $this->username;
  }
  
  /**
   * @param string $username
   * @return void
   */
  public function setUsername($username) {
    $this->username = $username;
  }

  /**
   * @var string $password
   */
  protected $password;
  
  /**
   * @return string
   */
  public function getPassword() {
    return $this->password;
  }
  
  /**
   * @param string $password
   * @return void
   */
  public function setPassword($password) {
    $this->password = $password;
  }

  /**
   * @var string $path
   */
  protected $path;
  
  /**
   * @return string
   */
  public function getPath() {
    return $this->path;
  }
  
  /**
   * @param string $path
   * @return void
   */
  public function setPath($path) {
    $this->path = $path;
  }

  /**
   * @var string $query
   */
  protected $query;
  
  /**
   * @return string
   */
  public function getQuery() {
    return $this->query;
  }
  
  /**
   * @param string $query
   * @return void
   */
  public function setQuery($query) {
    $this->query = $query;
  }

  /**
   * @var string $fragment
   */
  protected $fragment;
  
  /**
   * @return string
   */
  public function getFragment() {
    return $this->fragment;
  }
  
  /**
   * @param string $fragment
   * @return void
   */
  public function setFragment($fragment) {
    $this->fragment = $fragment;
  }

  /**
   * Builds a new Uri object
   *
   * @param string $uri A full request URI
   */
  public function __construct($uri = NULL) {

    if ($uri !== NULL) {

      $this->setPropertiesFromUriComponents(parse_url($uri));
    }
  }

  /**
   * Builds a new URI object from server environment
   *
   * @param array $environment Server environment (e.g. $_SERVER)
   * @return Uri
   */
  public function fromEnvironment(array $environment) {

    $uri = new self();
    $uri->setScheme(
      isset($environment['HTTPS']) && ($environment['HTTPS'] == 'on' || $environment['HTTPS'] == 1)
      ||
      isset($environment['HTTP_X_FORWARDED_PROTO']) && $environment['HTTP_X_FORWARDED_PROTO'] == 'https'
      ? 'https'
      : 'http'
    );
    $uri->setHostname($environment['HTTP_HOST']);
    $uri->setPort(isset($environment['SERVER_PORT']) ? (int) $environment['SERVER_PORT'] : NULL);
    $uri->setUsername(isset($environment['PHP_AUTH_USER']) ? $environment['PHP_AUTH_USER'] : NULL);
    $uri->setPassword(isset($environment['PHP_AUTH_PW']) ? $environment['PHP_AUTH_PW'] : NULL);

    $requestUriParts = explode('?', $environment['REQUEST_URI'], 1);
    $uri->setPath($requestUriParts[0]);

    if (isset($requestUriParts[1])) {

      $queryParts = explode('#', $requestUriParts[1], 1);
      $uri->setQuery($queryParts[0]);
      $uri->setFragment(isset($queryParts[1]) ? $queryParts[1] : NULL);
    }

    return $uri;
  }

  /**
   * Sets internal properties from URI components,
   * as returned by parse_url()
   *
   * @param array $uriComponents
   * @return void
   */
  protected function setPropertiesFromUriComponents(array $uriComponents) {

    // Map some component names to more readable properties
    static $componentPropertyMapping = array(
      'host' => 'hostname',
      'user' => 'username',
      'pass' => 'password',
    );

    // Determine port from scheme if not present
    if (!isset($uriComponents['port']) && isset($uriComponents['scheme'])) {

      switch ($uriComponents['scheme']) {

        case 'http':
        
          $uriComponents['port'] = 80;
          break;

        case 'https':

          $uriComponents['port'] = 443;
          break;
      }
    }

    foreach ($uriComponents as $component => $value) {

      $property = isset($componentPropertyMapping[$component]) ? $componentPropertyMapping[$component] : $component;
      $this->$property = $value;
    }
  }
}