<?php namespace Omniphx\Forrest\AuthenticationFlows;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\AuthenticationInterface;
use GuzzleHttp\Exception\RequestException;

class WebServer implements AuthenticationInterface {

    /**
     * Interface for HTTP Client
     * @var GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * Interface for Redirect calls
     * @var Omniphx\Forrest\Interfaces\RedirectInterface
     */
    protected $redirect;

    /**
     * Inteface for Input calls
     * @var Omniphx\Forrest\Interfaces\InputInterface
     */
    protected $input;

    /**
     * Array of OAuth settings: client Id, client secret, callback URI, login URL, and redirect URL after authenticaiton.
     * @var array
     */
    protected $settings;

    public function __construct(
        ClientInterface $client,
        RedirectInterface $redirect,
        InputInterface $input,
        $settings)
    {
        $this->client   = $client;
        $this->redirect = $redirect;
        $this->input    = $input;
        $this->settings = $settings;
    }

	/**
     * Call this method to redirect user to login page and initiate
     * the Web Server OAuth Authentication Flow.
     * @return void
     */
    public function authenticate()
    {
        return $this->redirect->to(
            $this->settings['oauth']['loginURL']
            . '/services/oauth2/authorize'
            . '?response_type=code'
            . '&client_id=' . $this->settings['oauth']['clientId']
        	. '&redirect_uri=' . urlencode($this->settings['oauth']['callbackURI'])
            . '&display=' . $this->settings['optional']['display']
            . '&immediate=' . $this->settings['optional']['immediate']
            . '&state=' . $this->settings['optional']['state']
            . '&scope=' . $this->settings['optional']['scope']);
    }

    /**
     * When settings up your callback route, you will need to call this method to
     * acquire an authorization token. This token will be used for the API requests.
     * @return RedirectInterface
     */
    public function callback()
    {
        //Salesforce sends us an authorization code as part of the Web Server OAuth Authentication Flow
        $code  = $this->input->get('code');
        $state = $this->input->get('state');

        //Now we must make a request for the authorization token.
        $tokenURL = $this->settings['oauth']['loginURL'] . '/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'body' => [
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->settings['oauth']['clientId'],
                'client_secret' => $this->settings['oauth']['clientSecret'],
                'redirect_uri'  => $this->settings['oauth']['callbackURI']
            ]
        ]);

        return $response;
    }

    public function refresh($refreshToken)
    {
        $tokenURL = $this->settings['oauth']['loginURL'] . '/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'body'    => [
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->settings['oauth']['clientId'],
                'client_secret' => $this->settings['oauth']['clientSecret']
            ]
        ]);

        return $response;
    }

}