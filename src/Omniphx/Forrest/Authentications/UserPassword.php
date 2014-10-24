<?php namespace Omniphx\Forrest\Authentications;

use Omniphx\Forrest\Client;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\UserPasswordInterface;

class UserPassword extends Client implements UserPasswordInterface
{
    /**
     * Redirect handler
     * @var Redirect
     */
    protected $redirect;

    /**
     * Inteface for Input calls
     * @var Omniphx\Forrest\Interfaces\InputInterface
     */
    protected $input;

    public function __construct(
        ClientInterface $client,
        SessionInterface $session,
        RedirectInterface $redirect,
        InputInterface $input,
        $settings)
    {
        $this->client   = $client;
        $this->session  = $session;
        $this->redirect = $redirect;
        $this->input    = $input;
        $this->settings = $settings;
    }

    public function authenticate($loginURL = null){
        $tokenURL = $this->settings['oauth']['loginURL'];
        $tokenURL .= '/services/oauth2/token';
        $parameters['body'] = [
            'grant_type'    => 'password',
            'client_id'     => $this->settings['oauth']['consumerKey'],
            'client_secret' => $this->settings['oauth']['consumerSecret'],
            'username'      => $this->settings['oauth']['username'],
            'password'      => $this->settings['oauth']['password'],
        ];
        $response = $this->client->post($tokenURL, $parameters);
        
        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

        // Encypt token and store token and in session.
        $this->session->putToken($jsonResponse);

        // Store resources into the session.
        $this->storeResources();
    }

    /**
     * Refresh authentication token by re-authenticating
     * @return mixed $response
     */
    public function refresh()
    {
        $tokenURL = $this->settings['oauth']['loginURL'] . '/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'body' => [
                'grant_type'    => 'password',
                'client_id'     => $this->settings['oauth']['consumerKey'],
                'client_secret' => $this->settings['oauth']['consumerSecret'],
                'username'      => $this->settings['oauth']['username'],
                'password'      => $this->settings['oauth']['password'],
            ]
        ]);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

        // Encypt token and store token and in session.
        $this->session->putToken($jsonResponse);
    }

    /**
     * Revokes access token from Salesforce. Will not flush token from Session.
     * @return mixed
     */
    public function revoke()
    {
        $accessToken = $this->getToken()['access_token'];
        $url         = $this->settings['oauth']['loginURL'] . '/services/oauth2/revoke';

        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['body']['token']           = $accessToken;

        $this->client->post($url, $options);

        $redirectURL = $this->settings['authRedirect'];

        return $this->redirect->to($redirectURL);
    }

    /**
     * Try requesting token, if token expired try refreshing token
     * @param  string $url
     * @param  array $options
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