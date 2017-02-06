<?php

namespace Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Client;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Interfaces\UserPasswordInterface;

class UserPassword extends Client implements UserPasswordInterface
{
    public function __construct(
        ClientInterface $httpClient,
        EventInterface $event,
        InputInterface $input,
        RedirectInterface $redirect,
        StorageInterface $storage,
        $settings
    ) {
        parent::__construct($httpClient, $event, $input, $redirect, $storage, $settings);
    }

    public function authenticate($url = null)
    {
        $loginURL = $url === null ? $this->credentials['loginURL'] : $url;
        $loginURL .= '/services/oauth2/token';
        $parameters['form_params'] = [
            'grant_type'    => 'password',
            'client_id'     => $this->credentials['consumerKey'],
            'client_secret' => $this->credentials['consumerSecret'],
            'username'      => $this->credentials['username'],
            'password'      => $this->credentials['password'],
        ];

        $jsonResponse = $this->httpClient->request('post', $loginURL, $parameters);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $response = json_decode($jsonResponse->getBody(), true);
        $this->handleAuthenticationErrors($response);

        // Encrypt token and store token and in storage.
        $this->storage->putTokenData($response);

        // Store resources into the storage.
        $this->storeResources();
    }

    /**
     * Refresh authentication token by re-authenticating.
     *
     * @return mixed $response
     */
    public function refresh()
    {
        $tokenURL = $this->credentials['loginURL'].'/services/oauth2/token';

        $parameters['form_params'] = [
            'grant_type'    => 'password',
            'client_id'     => $this->credentials['consumerKey'],
            'client_secret' => $this->credentials['consumerSecret'],
            'username'      => $this->credentials['username'],
            'password'      => $this->credentials['password'],
        ];

        $response = $this->httpClient->request('post', $tokenURL, $parameters);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = json_decode($response->getBody(), true);

        // Encrypt token and store token and in storage.
        $this->storage->putTokenData($jsonResponse);
    }

    /**
     * Revokes access token from Salesforce. Will not flush token from storage.
     *
     * @return mixed
     */
    public function revoke()
    {
        $accessToken = $this->getTokenData()['access_token'];
        $url = $this->credentials['loginURL'].'/services/oauth2/revoke';

        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['form_params']['token'] = $accessToken;

        return $this->httpClient->request('post', $url, $options);
    }
}
