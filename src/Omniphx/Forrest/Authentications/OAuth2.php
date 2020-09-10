<?php

namespace Omniphx\Forrest\Authentications;

use Firebase\JWT\JWT;
use Omniphx\Forrest\Client as BaseAuthentication;
use Omniphx\Forrest\Interfaces\UserPasswordInterface;

class OAuth2 extends BaseAuthentication
{
    public function authenticate($url = null)
    {
        $domain = $url ?? $this->credentials['loginURL'];

        $authToken = $this->getAuthToken($domain);

        $this->tokenRepo->put($authToken);

        $this->storeVersion();
        $this->storeResources();
    }

    /**
     * @param  String $domain
     * @return String
     */
    private function getAuthToken($domain)
    {
        $username = $this->credentials['username'];
        $consumerKey = $this->credentials['consumerKey'];
        $privateKey = $this->credentials['consumerSecret'];

        $payload = [
            'iss' => $consumerKey,
            'sub' => $username,
            'aud' => $domain,
            'exp' => now()->addMinutes(3)->timestamp
        ];

        $assertion = JWT::encode($payload, $privateKey, 'RS256');

        $parameters = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion
        ];

        // \Psr\Http\Message\ResponseInterface
        $response = $this->httpClient->request('post', $domain, $parameters);

        $authTokenDecoded = json_decode($response->getBody()->getContents(), true);

        $this->handleAuthenticationErrors($authTokenDecoded);

        return $authTokenDecoded;
    }
}
