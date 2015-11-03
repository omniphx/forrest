<?php

namespace Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Client;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Interfaces\WebServerInterface;

class WebServer extends Client implements WebServerInterface
{
    /**
     * Redirect handler.
     *
     * @var Redirect
     */
    protected $redirect;

    /**
     * Inteface for Input calls.
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

    /**
     * Authentication parameters.
     *
     * @var array
     */
    private $parameters;

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
        $this->parameters = $settings['parameters'];
    }

    /**
     * Call this method to redirect user to login page and initiate
     * the Web Server OAuth Authentication Flow.
     *
     * @return void
     */
    public function authenticate($loginURL = null)
    {
        if (!isset($loginURL)) {
            $loginURL = $this->credentials['loginURL'];
        }

        $loginURL .= '/services/oauth2/authorize';
        $loginURL .= '?response_type=code';
        $loginURL .= '&client_id='.$this->credentials['consumerKey'];
        $loginURL .= '&redirect_uri='.urlencode($this->credentials['callbackURI']);
        if ($this->parameters['display'] != '') {
            $loginURL .= '&display='.$this->parameters['display'];
        }
        if ($this->parameters['immediate']) {
            $loginURL .= '&immediate=true';
        }
        if ($this->parameters['state'] != '') {
            $loginURL .= '&state='.urlencode($this->parameters['state']);
        }
        if ($this->parameters['scope'] != '') {
            $scope = rawurlencode($this->parameters['scope']);
            $loginURL .= '&scope='.$scope;
        }
        if ($this->parameters['prompt'] != '') {
            $prompt = rawurlencode($this->parameters['prompt']);
            $loginURL .= '&prompt='.$prompt;
        }

        return $this->redirect->to($loginURL);
    }

    /**
     * When settings up your callback route, you will need to call this method to
     * acquire an authorization token. This token will be used for the API requests.
     *
     * @return RedirectInterface
     */
    public function callback()
    {
        //Salesforce sends us an authorization code as part of the Web Server OAuth Authentication Flow
        $code = $this->input->get('code');
        $state = $this->input->get('state');

        //Now we must make a request for the authorization token.
        $tokenURL = $this->credentials['loginURL'].'/services/oauth2/token';

        $this->client = new \GuzzleHttp\Client(['http_errors' => false]);

        $response = $this->client->post($tokenURL, [
            'form_params' => [
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->credentials['consumerKey'],
                'client_secret' => $this->credentials['consumerSecret'],
                'redirect_uri'  => $this->credentials['callbackURI'],
            ],
        ]);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = json_decode($response->getBody(), true);

        // Encrypt token and store token and in storage.
        $this->storage->putTokenData($jsonResponse);

        if (!empty($jsonResponse['refresh_token'])) {

            $this->storage->putRefreshToken($jsonResponse['refresh_token']);

        }

        // Store resources into the storage.
        $this->storeResources();

    }

    /**
     * Refresh authentication token.
     *
     * @param array $refreshToken
     *
     * @return mixed $response
     */
    public function refresh()
    {
        $refreshToken = $this->storage->getRefreshToken();

        $this->client = new \GuzzleHttp\Client(['http_errors' => false]);
                
        $tokenURL = $this->credentials['loginURL'].'/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'form_params'    => [
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->credentials['consumerKey'],
                'client_secret' => $this->credentials['consumerSecret'],
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
