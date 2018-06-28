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

/**
 * Negotiator for access control requests
 */
class Negotiator
{
    /**
     * List of simple methods
     *
     * @var array
     */
    protected $simpleMethods = [
        'GET',
        'HEAD',
        'POST',
    ];

    /**
     * List of simple headers
     *
     * @var array
     */
    protected $simpleHeaders = [
        'Accept',
        'Accept-Language',
        'Content-Language',
    ];

    /**
     * @var bool $allowCredentials
     */
    protected $allowCredentials = false;

    /**
     * @return bool
     */
    public function getAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }

    /**
     * @param bool $allowCredentials
     * @return void
     */
    public function setAllowCredentials(bool $allowCredentials)
    {
        $this->allowCredentials = $allowCredentials;
    }

    /**
     * @var array $allowedHeaders
     */
    protected $allowedHeaders = [];

    /**
     * @return array
     */
    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    /**
     * @param array $allowedHeaders
     * @return void
     */
    public function setAllowedHeaders(array $allowedHeaders)
    {
        $this->allowedHeaders = $allowedHeaders;
    }

    /**
     * @var array $allowedMethods
     */
    protected $allowedMethods = [];

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /**
     * @param array $allowedMethods
     * @return void
     */
    public function setAllowedMethods(array $allowedMethods)
    {
        $this->allowedMethods = $allowedMethods;
    }

    /**
     * @var array $allowedOrigins
     */
    protected $allowedOrigins = [];

    /**
     * @return array
     */
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    /**
     * @param array $allowedOrigins
     * @return void
     */
    public function setAllowedOrigins(array $allowedOrigins)
    {
        $this->allowedOrigins = $allowedOrigins;
    }

    /**
     * @var string $allowedOriginsPattern
     */
    protected $allowedOriginsPattern;

    /**
     * @return string
     */
    public function getAllowedOriginsPattern(): string
    {
        return $this->allowedOriginsPattern;
    }

    /**
     * @param string $allowedOriginsPattern
     * @return void
     */
    public function setAllowedOriginsPattern(string $allowedOriginsPattern)
    {
        $this->allowedOriginsPattern = $allowedOriginsPattern;
    }

    /**
     * @var array $exposedHeaders
     */
    protected $exposedHeaders = [];

    /**
     * @return array
     */
    public function getExposedHeaders(): array
    {
        return $this->exposedHeaders;
    }

    /**
     * @param array $exposedHeaders
     * @return void
     */
    public function setExposedHeaders(array $exposedHeaders)
    {
        $this->exposedHeaders = $exposedHeaders;
    }

    /**
     * @var int $maximumAge
     */
    protected $maximumAge = 0;

    /**
     * @return int
     */
    public function getMaximumAge(): int
    {
        return $this->maximumAge;
    }

    /**
     * @param int $maximumAge
     * @return void
     */
    public function setMaximumAge(int $maximumAge)
    {
        $this->maximumAge = $maximumAge;
    }

    /**
     * Processes an access control request
     *
     * @param Request $request Access control request to process
     * @return void
     * @throws Exception\AccessDeniedException If access is not allowed
     */
    public function processRequest(Request $request, Response $response)
    {
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

            $response->setPreflight(true);
            $response->setAllowedMethods([$request->getRequestMethod()]);
            $response->setAllowedHeaders($request->getRequestHeaders());
            $response->setMaximumAge($this->getMaximumAge());
        }

        $origin = $request->getOrigin();
        $originUri = $origin->getScheme() . '://' . $origin->getHostname() . ($origin->getPort() ? ':' . $origin->getPort() : '');

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
     * @return bool
     */
    protected function isOriginUriAllowed(string $originUri): bool
    {
        // Check for exact match
        if (in_array($originUri, $this->allowedOrigins)) {
            return true;
        }

        // Check for pattern match
        if ($this->allowedOriginsPattern) {
            // Explicitely not using preg_quote() here to allow for pattern passthrough
            if (preg_match('~^' . $this->allowedOriginsPattern . '~i', $originUri) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns TRUE, if a request method is allowed, FALSE otherwise
     *
     * Note that simple methods are always allowed
     *
     * @param string $method The HTTP method (POST/PUT/...)
     * @return bool
     */
    protected function isMethodAllowed(string $method): bool
    {
        return in_array($method, $this->simpleMethods, true) ||
               in_array($method, $this->allowedMethods, true);
    }

    /**
     * Returns TRUE, if a request header is allowed, FALSE otherwise
     *
     * Note that simple headers are always allowed
     *
     * @param string $header The HTTP header (X-Foo/...)
     * @return bool
     */
    protected function isHeaderAllowed(string $header): bool
    {
        $header = strtolower($header);

        return in_array($header, array_map('strtolower', $this->simpleHeaders), true) ||
               in_array($header, array_map('strtolower', $this->allowedHeaders), true);
    }
}
