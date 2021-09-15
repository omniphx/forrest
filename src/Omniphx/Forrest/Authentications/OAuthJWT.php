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
        $header = ['alg' => 'RS256'];
        $payload = [
            'iss' => $iss,
            'aud' => $aud,
            'sub' => $sub,
            'exp' => Carbon::now()->addMinutes(3)->timestamp
        ];

        return JWT::encode($payload, $privateKey, 'RS256', $header);
    }

    public function authenticate($url = null)
    {
        $domain = $url ?? $this->credentials['loginURL'] . '/services/oauth2/token';
        $username = $this->credentials['username'];
        // OAuth Client ID
        $consumerKey = $this->credentials['consumerKey'];
        // Private Key
        $privateKey = $this->credentials['privateKey'];

        // Generate the form parameters
        $assertion = static::getJWT($consumerKey, $domain, $username, $privateKey);
        $parameters = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $domain, ['form_params' => $parameters]);

        $authToken = json_decode($response->getBody()->getContents(), true);

        $this->handleAuthenticationErrors($authToken);

        $this->tokenRepo->put($authToken);

        $this->storeVersion();
        $this->storeResources();
    }

    public function refresh()
    {
        $this->authenticate();
    }

    public function revoke()
    {
        $accessToken = $this->tokenRepo->get()['access_token'];
        $url = $this->credentials['loginURL'].'/services/oauth2/revoke';

        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['form_params']['token'] = $accessToken;

        return $this->httpClient->request('post', $url, $options);
    }
}
