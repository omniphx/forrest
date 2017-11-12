<?php

namespace Omniphx\Forrest\Authentications;

use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\UserPasswordInterface;
use Psr\Http\Message\ResponseInterface;

class UserPassword extends BaseAuthentication implements UserPasswordInterface
{
    public function authenticate($url = null)
    {
        $loginURL = $url === null ? $this->credentials['loginURL'] : $url;
        $loginURL .= '/services/oauth2/token';

        $authToken = $this->getAuthToken($loginURL);

        $this->tokenRepo->put($authToken);

        $this->storeVersion();
        $this->storeResources();
    }

    /**
     * Refresh authentication token by re-authenticating.
     *
     * @return mixed $response
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
            'grant_type'    => 'password',
            'client_id'     => $this->credentials['consumerKey'],
            'client_secret' => $this->credentials['consumerSecret'],
            'username'      => $this->credentials['username'],
            'password'      => $this->credentials['password'],
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $url, $parameters);

        $authTokenDecoded = json_decode($response->getBody(), true);

        $this->handleAuthenticationErrors($authTokenDecoded);

        return $authTokenDecoded;
    }

    /**
     * Revokes access token from Salesforce. Will not flush token from storage.
     *
     * @return mixed
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
