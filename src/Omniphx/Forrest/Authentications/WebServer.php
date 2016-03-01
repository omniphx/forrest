<?php

namespace Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Client;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Interfaces\WebServerInterface;

class WebServer extends Client implements WebServerInterface
{
    /**
     * Authentication parameters.
     *
     * @var array
     */
    private $parameters;

    public function __construct(
        ClientInterface $client,
        EventInterface $event,
        InputInterface $input,
        RedirectInterface $redirect,
        StorageInterface $storage,
        $settings
    ) {
        parent::__construct($client, $event, $input, $redirect, $storage, $settings);
        $this->parameters = $this->settings['parameters'];
    }

    /**
     * Call this method to redirect user to login page and initiate
     * the Web Server OAuth Authentication Flow.
     *
     * @param null $loginURL
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function authenticate($url = null)
    {
        $loginURL = $url === null ? $this->credentials['loginURL'] : $url;
        $this->storage->put('loginURL', $loginURL);
        $loginURL .= '/services/oauth2/authorize';
        $loginURL .= '?response_type=code';
        $loginURL .= '&client_id='.$this->credentials['consumerKey'];
        $loginURL .= '&redirect_uri='.urlencode($this->credentials['callbackURI']);
        $loginURL .= !empty($this->parameters['display']) ? '&display='.$this->parameters['display'] : '';
        $loginURL .= $this->parameters['immediate'] ? '&immediate=true' : '';
        $loginURL .= !empty($this->parameters['state']) ? '&state='.urlencode($this->parameters['state']) : '';
        $loginURL .= !empty($this->parameters['scope']) ? '&scope=' . rawurlencode($this->parameters['scope']) : '';
        $loginURL .= !empty($this->parameters['prompt']) ? '&prompt=' . rawurlencode($this->parameters['prompt']) : '';

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
        $loginURL = $this->getLoginURL();

        //Salesforce sends us an authorization code as part of the Web Server OAuth Authentication Flow
        $code = $this->input->get('code');

        $tokenURL = $loginURL . '/services/oauth2/token';

        //Now we must make a request for the authorization token.
        $response = $this->client->post($tokenURL, [
            'body' => [
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->credentials['consumerKey'],
                'client_secret' => $this->credentials['consumerSecret'],
                'redirect_uri'  => $this->credentials['callbackURI'],
            ],
        ]);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

        // Encrypt token and store token in storage.
        $this->storage->putTokenData($jsonResponse);
        if (isset($jsonResponse['refresh_token'])) {
            $this->storage->putRefreshToken($jsonResponse['refresh_token']);
        }

        // Store resources into the storage.
        $this->storeResources();
    }

    /**
     * Refresh authentication token.
     *
     * @return mixed $response
     */
    public function refresh()
    {
        $refreshToken = $this->storage->getRefreshToken();
        $tokenURL = $this->getLoginURL();
        $tokenURL .= '/services/oauth2/token';

        $response = $this->client->post($tokenURL, [
            'body' => [
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->credentials['consumerKey'],
                'client_secret' => $this->credentials['consumerSecret'],
            ],
        ]);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

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
        $options['body']['token'] = $accessToken;

        return $this->client->post($url, $options);
    }

    /**
     * Retrieve login URL
     * 
     * @return string
     */
    private function getLoginURL()
    {
        try {
            //Session storage will not persist between the callback, recommend cache storage
            return $this->storage->get('loginURL');
        } catch(MissingKeyException $e) {
            return $loginURL = $this->credentials['loginURL'];
        }
    }
}
