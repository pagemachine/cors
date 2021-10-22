<?php

declare(strict_types = 1);

namespace Pagemachine\Cors\AccessControl;

/*
 * This file is part of the Pagemachine CORS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 3
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Pagemachine\Cors\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Represents a HTTP request
 */
class Request
{
    /**
     * @var bool
     */
    protected $isCrossOrigin = false;

    /**
     * @var bool
     */
    protected $isPreflight = false;

    /**
     * @var bool
     */
    protected $hasCredentials = false;

    /**
     * @var Uri $origin
     */
    protected $origin;

    /**
     * @return Uri
     */
    public function getOrigin(): Uri
    {
        return $this->origin;
    }

    /**
     * @param Uri $origin
     * @return void
     */
    public function setOrigin(Uri $origin)
    {
        $this->origin = $origin;
    }

    /**
     * @var Uri $destination
     */
    protected $destination;

    /**
     * @return Uri
     */
    public function getDestination(): Uri
    {
        return $this->destination;
    }

    /**
     * @param Uri $destination
     * @return void
     */
    public function setDestination(Uri $destination)
    {
        $this->destination = $destination;
    }

    /**
     * @var string $requestMethod
     */
    protected $requestMethod = '';

    /**
     * @return string
     */
    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * @param string $requestMethod
     * @return void
     */
    public function setRequestMethod(string $requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * @var array $requestHeaders
     */
    protected $requestHeaders = [];

    /**
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    /**
     * @param array $requestHeaders
     * @return void
     */
    public function setRequestHeaders(array $requestHeaders)
    {
        $this->requestHeaders = $requestHeaders;
    }

    /**
     * Constructs a new request object
     *
     * @param array $environment Server environment (from $_SERVER)
     */
    public function __construct(array $environment)
    {
        $this->destination = Uri::fromEnvironment($environment);

        if (isset($environment['HTTP_ORIGIN'])) {
            $this->origin = new Uri($environment['HTTP_ORIGIN']);

            $this->isCrossOrigin = $this->origin->getScheme() !== $this->destination->getScheme() ||
                                   $this->origin->getHostname() !== $this->destination->getHostname() ||
                                   $this->origin->getNormalizedPort() !== $this->destination->getNormalizedPort();

            $this->hasCredentials = isset($environment['HTTP_COOKIE']) ||
                                    isset($environment['HTTP_AUTHORIZATION']) ||
                                    isset($environment['SSL_CLIENT_VERIFY']) && $environment['SSL_CLIENT_VERIFY'] !== 'NONE';

            if ($environment['REQUEST_METHOD'] === 'OPTIONS') {
                $this->isPreflight = true;

                if (!empty($environment['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    $this->requestMethod = $environment['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
                }

                if (!empty($environment['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    $this->requestHeaders = GeneralUtility::trimExplode(',', $environment['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
                }
            }
        }
    }

    /**
     * Returns TRUE, if the current request is cross origin, FALSE otherwise
     *
     * A request is cross origin if one of scheme, host or protocol does not match
     *
     * @return bool
     */
    public function isCrossOrigin(): bool
    {
        return $this->isCrossOrigin;
    }

    /**
     * @param bool $isCrossOrigin
     * @return void
     */
    public function setCrossOrigin(bool $isCrossOrigin)
    {
        $this->isCrossOrigin = $isCrossOrigin;
    }

    /**
     * Returns TRUE, if the current request has credentials, FALSE otherwise
     *
     * Credentials include cookies, HTTP authentication data and SSL client certificates
     *
     * @return bool
     */
    public function hasCredentials(): bool
    {
        return $this->hasCredentials;
    }

    /**
     * @param bool $hasCredentials
     * @return void
     */
    public function setHasCredentials(bool $hasCredentials)
    {
        $this->hasCredentials = $hasCredentials;
    }

    /**
     * Returns TRUE if the current request is a preflight request, FALSE otherwise
     *
     * @return bool
     */
    public function isPreflight(): bool
    {
        return $this->isPreflight;
    }

    /**
     * @param bool $isPreflight
     * @return void
     */
    public function setPreflight(bool $isPreflight)
    {
        $this->isPreflight = $isPreflight;
    }
}
