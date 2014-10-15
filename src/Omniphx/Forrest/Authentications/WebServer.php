<?php namespace Omniphx\Forrest\Authentications;

use Omniphx\Forrest\Client;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\WebServerInterface;

class WebServer extends Client implements WebServerInterface
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

	/**
     * Call this method to redirect user to login page and initiate
     * the Web Server OAuth Authentication Flow.
     * @return void
     */
    public function authenticate($loginURL = null)
    {
        if(!isset($loginURL)){
            $loginURL = $this->settings['oauth']['loginURL'];
        }

        return $this->redirect->to($loginURL
            . '/services/oauth2/authorize'
            . '?response_type=code'
            . '&client_id=' . $this->settings['oauth']['consumerKey']
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
                'client_id'     => $this->settings['oauth']['consumerKey'],
                'client_secret' => $this->settings['oauth']['consumerSecret'],
                'redirect_uri'  => $this->settings['oauth']['callbackURI']
            ]
        ]);

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

        // Encypt token and store token and in session.
        $this->session->putToken($jsonResponse);
        $this->session->putRefreshToken($jsonResponse['refresh_token']);

        // Store resources into the session.
        $this->storeResources();
    }

    /**
     * Refresh authentication token
     * @param  Array $refreshToken
     * @return mixed $response
     */
    public function refresh($refreshToken)
    {
        $tokenURL = $this->settings['oauth']['loginURL'] . '/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'body'    => [
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->settings['oauth']['consumerKey'],
                'client_secret' => $this->settings['oauth']['consumerSecret']
            ]
        ]);

        return $response;
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