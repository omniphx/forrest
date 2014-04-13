<?php namespace Omniphx\Forrest;

use Illuminate\Session;

class Session {
	/**
     * Access token acquired through Oath2 authentication.
     * @var token;
     */
    protected $accessToken;

    /**
     * InstanceURL acquired through Oauth2 authentication.
     * @var URL;
     */
    protected $instanceURL;

    /**
     * Retrieve access token.
     * @return [type] [description]
     */
    public function getAccessToken(){
        $this->accessToken = Session::get('access_token');
        return $this->accessToken;
    }

    /**
     * Retrieve salesforce instance URL.
     * @return [type] [description]
     */
    public function getInstanceURL(){
        $this->instanceURL = Session::get('instance_url');
        return $this->instanceURL;
    }
}

