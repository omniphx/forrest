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
     * Refresh authentication token
     * @param  Array $refreshToken
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

        // Not sure if the below is correct. Used to only return the standard response
        $jsonResponse = $response->json();
        $this->session->putToken($jsonResponse);

        return $jsonResponse;
    }

    /**
     * Try retrieving token, if expired fire refresh method.
     * @return array
     */
    protected function getToken()
    {
        try {
            return $this->session->getToken();
        } catch (MissingTokenException $e) {
            return $this->refresh();
        }
    }
}