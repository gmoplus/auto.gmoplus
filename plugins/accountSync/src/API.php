<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.7.0
 *	LICENSE: FL0F971OQTZ9 - https://www.flynax.com/license-agreement.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN: gmowin.com
 *	FILE: API.PHP
 *
 *	The software is a commercial product delivered under single, non-exclusive,
 *	non-transferable license for one domain or IP address. Therefore distribution,
 *	sale or transfer of the file in whole or in part without permission of Flynax
 *	respective owners is considered to be illegal and breach of Flynax License End
 *	User Agreement.
 *
 *	You are not allowed to remove this information from the file without permission
 *	of Flynax respective owners.
 *
 *	Flynax Classifieds Software 2022 |  All copyrights reserved.
 *
 *	https://www.flynax.com/
 *
 ******************************************************************************/

namespace Flynax\Plugins\AccountSync;

use Flynax\Plugins\AccountSync\Http\Request;
use Flynax\Plugins\AccountSync\Models\MetaData;
use Flynax\Plugins\AccountSync\Models\Token;

class API
{
    /**
     * API version
     * @var string
     */
    private $version = 'v1';

    /**
     * Sending API request to domain
     * @var string
     */
    private $to = '';

    /**
     * Is authentication required for sending request
     * @var bool
     */
    private $isAuth = false;

    /**
     * API handler file path
     * @var string
     */
    private $pluginUrlPart = 'plugins/accountSync';

    /**
     * Is request is asynchronous
     * @var bool
     */
    private $isAsync = false;

    /**
     * API constructor.
     * @param string $version
     * @param string $domain
     */
    public function __construct($domain = '', $version = 'v1')
    {
        $this->version = $version;
        $this->to = $domain;
    }

    /**
     * To which domain will API send requests
     *
     * @param string $domain - URL of the site to which you want to send API request
     * @return \Flynax\Plugins\AccountSync\API
     */
    public function withDomain($domain)
    {
        $new = clone $this;
        $new->setTo($domain);

        return $new;
    }

    /**
     * Send get HTTP request to the API endpoint with some data
     *
     * @param string $endpoint - API endpoint
     * @param array  $data     - Sending data
     *
     * @return \stdClass
     */
    public function get($endpoint, $data = array())
    {
        if ($this->isAuth) {
            $tokenManager = new Token();
            $data['token'] = $tokenManager->getCurrentSiteToken();
        }

        $url = $this->getFullUrl($endpoint);
        $url = $data ? sprintf("%s?%s", $url, http_build_query($data)) : $url;

        return $this->sendRequest('get', $url, $data);
    }

    /**
     * Send post request to the API endpoint with some data
     *
     * @param string $endpoint - API endpoint
     * @param array  $data     - Sending data
     *
     * @return \stdClass
     */
    public function post($endpoint, $data = array())
    {
        if ($this->isAuth) {
            $tokenManager = new Token();
            $data['token'] = $tokenManager->getCurrentSiteToken();
        }

        $url = $this->getFullUrl($endpoint);

        return $this->sendRequest('post', $url, $data);
    }

    /**
     * Send delete HTTP request to the API endpoint with some data
     *
     * @param string $endpoint - API endpoint
     * @param array  $data     - Sending data
     *
     * @return \stdClass
     */
    public function delete($endpoint, $data = array())
    {
        $url = $this->getFullUrl($endpoint);
        return $this->sendRequest('delete', $url, $data);
    }

    /**
     * Get full URL to the API by its endpoint
     *
     * @param string $endpoint - API endpoint
     * @return string
     */
    public function getFullUrl($endpoint)
    {
        return sprintf('%s/%s/api/%s/%s', rtrim($this->to, '/'), $this->pluginUrlPart, $this->version, $endpoint);
    }

    /**
     * Enable API auth
     */
    public function enableAuth()
    {
        $this->isAuth = true;
    }

    /**
     * Disable API auth
     */
    public function disableAuth()
    {
        $this->isAuth = false;
    }

    /**
     * Getter of the $to property
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Setter of the $to property
     *
     * @param string $to
     */
    public function setTo($to)
    {
        $this->to = $to;
    }

    /**
     * Version property getter
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Version property setter
     *
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Send request to API
     *
     * @param string $type - Request type: get, post, delete
     * @param string $url  - Full URL to the API endpoint which will handle this request
     * @param string $data - Sending with request data
     *
     * @return \stdClass - Response
     */
    private function sendRequest($type, $url, $data)
    {
        $request = new Request();

        return $request->send($type, $url, $data);
    }

    /**
     * Enable authentication of the API
     *
     * @return $this
     */
    public function auth()
    {
        $this->enableAuth();

        return $this;
    }

    /**
     * @return \Flynax\Plugins\AccountSync\API
     */
    public function toAll()
    {
        $token = new Token();
        $existingDomains = $token->getAll(true);
        $domainLists = array();

        foreach ($existingDomains as $info) {
            $domainLists[] = $info['Domain'];
        }

        return $this->withDomain($domainLists);
    }

    /**
     * todo: check this method, am I using it.
     */
    public function updateAccountTypesInfo()
    {
        $token = new Token();
        $metaData = new MetaData();
        $existingDomains = $token->getAll();

        foreach ($existingDomains as $info) {
            $result = $this->auth()->withDomain($info['Domain'])->get('account/types');
            if ($result->status == 200 && $result->body['code_phrase'] == 'success_account_types') {
                $metaData->set($info['Domain'], 'account_types', $result->body['account_types']);
            }
        }
    }

    public function getAllUsersOf($domain, $endpoint = '', $data = array())
    {
        $endpoint = $endpoint ?: 'accounts';
        return $domain ? $this->auth()->withDomain($domain)->get($endpoint, $data) : array();
    }
}
