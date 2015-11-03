<?php

namespace Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Client;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Interfaces\UserPasswordInterface;

class UserPassword extends Client implements UserPasswordInterface
{
    /**
     * Redirect handler.
     *
     * @var Redirect
     */
    protected $redirect;

    /**
     * Interface for Input calls.
     *
     * @var Omniphx\Forrest\Interfaces\InputInterface
     */
    protected $input;

    /**
     * Authentication credentials.
     *
     * @var array
     */
    private $credentials;

    public function __construct(
        ClientInterface $client,
        StorageInterface $storage,
        RedirectInterface $redirect,
        InputInterface $input,
        EventInterface $event,
        $settings)
    {
        $this->client = $client;
        $this->storage = $storage;
        $this->redirect = $redirect;
        $this->input = $input;
        $this->event = $event;
        $this->settings = $settings;
        $this->credentials = $settings['credentials'];
    }

    public function authenticate($loginURL = null)
    {
        $tokenURL = $this->credentials['loginURL'];
        $tokenURL .= '/services/oauth2/token';
        $parameters['form_params'] = [
            'grant_type'    => 'password',
            'client_id'     => $this->credentials['consumerKey'],
            'client_secret' => $this->credentials['consumerSecret'],
            'username'      => $this->credentials['username'],
            'password'      => $this->credentials['password'],
        ];

        $this->client = new \GuzzleHttp\Client(['http_errors' => false]);
        
        $response = $this->client->post($tokenURL, $parameters);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = json_decode($response->getBody(), true);

        // Encrypt token and store token and in storage.
        $this->storage->putTokenData($jsonResponse);

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
        $this->client = new \GuzzleHttp\Client(['http_errors' => false]);
        $response = $this->client->post($tokenURL, [
            'form_params' => [
                'grant_type'    => 'password',
                'client_id'     => $this->credentials['consumerKey'],
                'client_secret' => $this->credentials['consumerSecret'],
                'username'      => $this->credentials['username'],
                'password'      => $this->credentials['password'],
            ],
        ]);

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
        $this->client = new \GuzzleHttp\Client(['http_errors' => false]);
                
        return $this->client->post($url, $options);
    }

    /**
     * Try requesting token, if token expired try refreshing token.
     *
     * @param string $url
     * @param array  $options
     *
     * @return mixed
     */
    public function request($url, $options)
    {
        try {
            return $this->requestResource($url, $options);
        } catch (TokenExpiredException $e) {
            $this->refresh();

            return $this->requestResource($url, $options);
        }
    }
}
