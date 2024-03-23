<?php

namespace Omniphx\Forrest\Authentications;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\AuthenticationInterface;

class OAuthJWT extends BaseAuthentication implements AuthenticationInterface
{
    public static function getJWT($iss, $aud, $sub, $privateKey)
    {
        $payload = [
            'iss' => $iss,
            'aud' => $aud,
            'sub' => $sub,
            'exp' => Carbon::now()->addMinutes(3)->timestamp
        ];

        return JWT::encode($payload, $privateKey, 'RS256');
    }

    private function getDefaultInstanceURL()
    {
        if (isset($this->settings['instanceURL']) && !empty($this->settings['instanceURL'])) {
            return $this->settings['instanceURL'];
        } else {
            return $this->credentials['loginURL'];
        }
    }

    public function authenticate($fullInstanceUrl = null)
    {
        $fullInstanceUrl = $fullInstanceUrl ?? $this->getDefaultInstanceURL() . '/services/oauth2/token';

        $consumerKey = $this->credentials['consumerKey'];
        $loginUrl = $this->credentials['loginURL'];
        $username = $this->credentials['username'];
        $privateKey = $this->credentials['privateKey'];

        // Generate the form parameters
        $assertion = static::getJWT($consumerKey, $loginUrl, $username, $privateKey);
        $parameters = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $fullInstanceUrl, ['form_params' => $parameters]);

        $authToken = json_decode($response->getBody()->getContents(), true);

        $this->handleAuthenticationErrors($authToken);

        $this->tokenRepo->put($authToken);

        $this->storeVersion();
        $this->storeResources();
    }

    /**
     * @return void
     */
    public function refresh()
    {
        $this->authenticate();
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function revoke()
    {
        $accessToken = $this->tokenRepo->get()['access_token'];
        $url = $this->credentials['loginURL'].'/services/oauth2/revoke';

        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['form_params']['token'] = $accessToken;

        return $this->httpClient->request('post', $url, $options);
    }
}
