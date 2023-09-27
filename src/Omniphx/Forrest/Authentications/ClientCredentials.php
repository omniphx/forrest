<?php

namespace Omniphx\Forrest\Authentications;

use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\ClientCredentialsInterface;

class ClientCredentials extends BaseAuthentication implements ClientCredentialsInterface
{
    public function authenticate($url = null)
    {
        $loginURL = null === $url ? $this->credentials['loginURL'] : $url;
        $loginURL .= '/services/oauth2/token';

        $authToken = $this->getAuthToken($loginURL);

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
        $tokenURL = $this->credentials['loginURL'] . '/services/oauth2/token';
        $authToken = $this->getAuthToken($tokenURL);

        $this->tokenRepo->put($authToken);
    }

    /**
     * @param  String $tokenURL
     * @param  Array $parameters
     * @return String
     */
    private function getAuthToken($url)
    {
        $parameters['form_params'] = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->credentials['consumerKey'],
            'client_secret' => $this->credentials['consumerSecret'],
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $url, $parameters);

        $authTokenDecoded = json_decode($response->getBody()->getContents(), true);

        $this->handleAuthenticationErrors($authTokenDecoded);

        return $authTokenDecoded;
    }

    /**
     * Revokes access token from Salesforce. Will not flush token from storage.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revoke()
    {
        $accessToken = $this->tokenRepo->get();
        $url = $this->credentials['loginURL'].'/services/oauth2/revoke';

        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['form_params']['token'] = $accessToken;

        return $this->httpClient->request('post', $url, $options);
    }
}
