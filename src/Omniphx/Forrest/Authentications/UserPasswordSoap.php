<?php
/**
 * UserPasswordSoap - Authenticate a user via SOAP call instead of OAuth.
 *
 * Client apps must log in using valid credentials. After validating, the server stores
 * a token object in storage, for automatic reference on subsequent calls.
 *
 * This module provides the standard authenticate method, as well as a new authenticateUser
 * method for specifying the username and password at runtime, and not from config.
 *
 * Salesforce supports only the Transport Layer Security (TLS) protocol and frontdoor.jsp.
 * Ciphers must have a key length of at least 128 bits.
 *
 *
 * @version    SVN: $Id$
 */

namespace Omniphx\Forrest\Authentications;

use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\UserPasswordSoapInterface;

/**
 * UserPasswordSoap class.
 */
class UserPasswordSoap extends BaseAuthentication implements UserPasswordSoapInterface
{
    public function authenticate($url = null)
    {
        $loginURL = null === $url ? $this->credentials['loginURL'] : $url;
        $this->authenticateUser($loginURL);
    }

    public function authenticateUser($url = null, $username = null, $password = null)
    {
        $loginURL = null === $url ? $this->credentials['loginURL'] : $url;
        $loginURL .= '/services/Soap/u/46.0';

        $loginUser = null === $username ? $this->credentials['username'] : $username;
        $loginPassword = null === $password ? $this->credentials['password'] : $password;
        $this->credentials['username'] = $loginUser;
        $this->credentials['password'] = $loginPassword;
        $authToken = $this->getAuthUser($loginURL, $loginUser, $loginPassword);
        $this->tokenRepo->put($authToken);

        $this->storeVersion();
        $this->storeResources();
    }

    /**
     * Refresh authentication token by re-authenticating.
     *
     * @return void
     */
    public function refresh()
    {
        $this->authenticate();
        /* Per the SOAP Documenetationthe token life is extended at every call,
         * so the refresh is not needed. Token will expire in two hours from
         * last call by default.
         */
    }

    /**
     * Perform the actual SOAP login.
     *
     * @param string $tokenURL
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    private function getAuthUser($url, $username, $password)
    {
        /*
        SOAP Login method - Does not require a connected/defined application
        https://developer.salesforce.com/docs/atlas.en-us.api.meta/api/sforce_api_calls_login.htm
        */

        // Required to avoid a 500 error on invalid login and guzzle unhandled error
        $parameters['http_errors'] = false;

        $parameters['headers'] = [
            'Content-Type' => 'text/xml; charset=UTF-8',
            'SOAPAction' => 'login',
        ];

        /* Create a SOAP envelope containing the username and password
         *  and put the resulting SOAP message in the POST Body.
         */
        $parameters['body'] = '<?xml version="1.0" encoding="utf-8" ?><env:Envelope xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://schemas.xmlsoap.org/soap/envelope/"><env:Body><n1:login xmlns:n1="urn:partner.soap.sforce.com"><n1:username>'.$username.'</n1:username><n1:password>'.$password.'</n1:password></n1:login></env:Body></env:Envelope>';

        $response = $this->httpClient->request('post', $url, $parameters);
        $xmlResponseBody = $response->getBody();
        $authTokenDecoded = $this->convertSoapToJSON($xmlResponseBody);
        $this->handleAuthenticationErrors($authTokenDecoded);

        return $authTokenDecoded;
    }

    /**
     * Revokes access token from Salesforce. Will not flush token from storage.
     *
     * @return void
     */
    public function revoke()
    {
        /*
        Session Expiration
        Client apps aren’t required to explicitly log out to end a session. Sessions expire automatically after a predetermined length of inactivity. The default is two hours. If you make an API call, the inactivity timer is reset to zero. To change the session expiration (timeout) value, from Setup, enter Session Settings in the Quick Find box, and select Session Settings.

        Another thread states that the session keys are unique to a user, so for example
        a background task issuing logout has the ability to kill a web session.

        Here is the gist of it. When you make an API call to Salesforce, the they authenticate the calls by examining the SOAP header that contains a session key. Session keys are allocated on a per-user basis. What this means is that if you create two connections at the same time (i.e. using login() from two different threads) - they will both be using the same session key.

        Decision:  Dont invoke any type of logout/revoke method.  Protect your session keys!
        */
    }

    protected function extractToken($response)
    {
        $sessionId = $response->sessionId;
        $serverUrl = $response->serverUrl;
    }

    protected function convertSoapToJSON($response)
    {
        // make the result look like standard xml instead of SOAP
        // handle the result from a SOAP Fault the same as a regular result.
        if (false === strpos($response, '<soapenv:Fault>')) {
            $posResult1 = strpos($response, '<result>');
            $posResult2 = strpos($response, '</result>');
            $len = 9;
        } else {
            $posResult1 = strpos($response, '<soapenv:Fault>');
            $posResult2 = strpos($response, '</soapenv:Fault>');
            $len = 16;
        }

        // Start building a simple XML String
        $result = '<?xml version="1.0" encoding="UTF-8"?>'.substr($response, $posResult1, ($posResult2 - $posResult1 + $len));

        // replace namespaces
        $result = preg_replace("/(<\/?)(\w+):([^>]*>)/", '$1$2$3', $result);
        $result = str_replace('xsi:', '', $result);

        // Disable libxml errors to fetch error information as needed
        libxml_use_internal_errors(true);

        // Convert the XML to a standard object via JSON intermediary.
        $xml = simplexml_load_string($result);
        $json = json_encode($xml);
        $data = json_decode($json);

        // Create an empty response Object, then pick and choose items to insert from XML,
        $tokenResponse = [];
        $tokenResponse['signature'] = 'SOAPHasNoSecretSig';
        // $tokenResponse['issued_at'] = time(); // including this causes phpspec to fail

        // Handle errors from Login
        if (isset($data->faultcode)) {
            // Forrest is looking for error
            $tokenResponse['error'] = $data->faultcode;
        }
        if (isset($data->faultstring)) {
            // Forrest is looking for error_description
            $tokenResponse['error_description'] = $data->faultstring;
        }

        // Handle successful SOAP login
        if (isset($data->userId)) {
            // Forrest is looking for id.  SOAP doesnt return this, so build it
            // based on well known format.
            // https://login.salesforce.com/id/<organizationId>/<userId>
            if ('false' == $data->sandbox) {
                $base = 'https://login.salesforce.com';
            } else {
                $base = 'https://test.salesforce.com';
            }
            $tokenResponse['id'] = $base.'/id/'.$data->userInfo->organizationId.'/'.$data->userId;
        }
        // The SOAP session id is what you put in the REST calls header
        // as "Authorization: Bearer <access_token>"
        if (isset($data->sessionId)) {
            // Forrest is looking for access_token
            $tokenResponse['access_token'] = $data->sessionId;
            $tokenResponse['token_type'] = 'Bearer';
        }
        if (isset($data->serverUrl)) {
            // Forrest is looking for instance_url
            // Extract the base URI
            $servicesPosition = strpos($data->serverUrl, '/services');
            $url = substr($data->serverUrl, 0, $servicesPosition);
            $tokenResponse['instance_url'] = $url;
        }

        return $tokenResponse;
    }
}
