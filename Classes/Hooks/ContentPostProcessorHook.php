<?php
namespace PAGEmachine\CORS\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use PAGEmachine\CORS\AccessControl\Negotiator;
use PAGEmachine\CORS\AccessControl\Request;
use PAGEmachine\CORS\AccessControl\Response;

/**
 * Sends CORS-related headers as configured
 */
class ContentPostProcessorHook {

  /**
   * @var TypoScriptFrontendController
   */
  protected $frontendController;

  /**
   * Processes configuration and sends headers
   *
   * @param array $parameters
   * @param TypoScriptFrontendController $frontendController
   * @return void
   */
  public function process(array $parameters, TypoScriptFrontendController $frontendController) {

    $negotiator = new Negotiator();
    $this->frontendController = $frontendController;
    $configuration = $this->getConfiguration($frontendController->config['config']);

    if (isset($configuration['allowCredentials'])) {

      $negotiator->setAllowCredentials($configuration['allowCredentials']);
    }

    if (isset($configuration['allowHeaders'])) {

      $negotiator->setAllowedHeaders($configuration['allowHeaders']);
    }

    if (isset($configuration['allowMethods'])) {

      $negotiator->setAllowedMethods($configuration['allowMethods']);
    }

    if (isset($configuration['allowOrigin'])) {

      $negotiator->setAllowedOrigins($configuration['allowOrigin']);
    }

    if (isset($configuration['allowOriginPattern'])) {

      $negotiator->setAllowedOriginsPattern($configuration['allowOriginPattern']);
    }

    if (isset($configuration['exposeHeaders'])) {

      $negotiator->setExposedHeaders($configuration['exposeHeaders']);
    }

    if (isset($configuration['maxAge'])) {

      $negotiator->setMaximumAge($configuration['maxAge']);
    }

    $response = new Response();

    try {

      $negotiator->processRequest(new Request($_SERVER), $response);
    } catch (\PAGEmachine\CORS\AccessControl\Exception $e) {

      // No need to go any further since the client will abort anyways
      $response->skipBodyAndExit();
    }

    $response->send();
  }

  /**
   * Returns the parsed and processed configuration
   *
   * @param array $rawConfiguration Raw configuration from TypoScriptFrontendController::$config['config']
   * @return array
   */
  protected function getConfiguration(array $rawConfiguration) {

    $configuration = isset($rawConfiguration['cors.']) ? $rawConfiguration['cors.'] : array();

    foreach ($configuration as $option => $value) {

      // Perform stdWrap processing on all options
      if (StringUtility::isLastPartOfString($option, '.') && isset($value['stdWrap.'])) {

        unset($configuration[$option]);
        $option = substr($option, 0, -1);
        $value = $this->frontendController->cObj->stdWrap($configuration[$option], $value['stdWrap.']);
      }

      switch ($option) {

        case 'allowCredentials':

          $value = in_array($value, array('1', 'true'), TRUE);
          break;

        case 'allowHeaders':
        case 'allowMethods':
        case 'allowOrigin':
        case 'exposeHeaders':

          $value = GeneralUtility::trimExplode(',', $value);
          break;

        case 'maxAge':

          $value = (int) $value;
          break;
      }

      if ($option == 'allowOrigin.' && isset($value['pattern'])) {

        $option = 'allowOriginPattern';
        $value = $value['pattern'];
      }

      $configuration[$option] = $value;
    }

    return $configuration;
  }
}
