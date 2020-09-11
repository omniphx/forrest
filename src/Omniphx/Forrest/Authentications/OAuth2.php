<?php

namespace Omniphx\Forrest\Authentications;

use Firebase\JWT\JWT;
use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\AuthenticationInterface;

class OAuth2 extends BaseAuthentication implements AuthenticationInterface
{
    public function authenticate($url = null)
    {
        $domain = $url ?? $this->credentials['loginURL'];
        $username = $this->credentials['username'];
        // OAuth Client ID
        $consumerKey = $this->credentials['consumerKey'];
        // Private Key
        $privateKey = $this->credentials['consumerSecret'];

        $header = ['alg' => 'RS256'];
        $payload = [
            'iss' => $consumerKey,
            'aud' => $domain,
            'sub' => $username,
            'exp' => now()->addMinutes(3)->timestamp
        ];

        $assertion = JWT::encode($payload, $privateKey, 'RS256', $header);

        $parameters = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $domain, $parameters);

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
        // Not supported for this option
    }
}
