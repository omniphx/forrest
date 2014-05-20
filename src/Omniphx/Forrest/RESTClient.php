<?php namespace Omniphx\Forrest;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\ResourceInterface;

class RESTClient {

    /**
     * Inteface for Resource calls
     * @var Omniphx\Forrest\Interfaces\ResourceInterface
     */
    protected $resource;

    /**
     * Interface for HTTP Client
     * @var GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * Interface for Session calls
     * @var Omniphx\Forrest\Interfaces\SessionInterface
     */
    protected $session;

    
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

    public function __construct(ResourceInterface $resource, ClientInterface $client, SessionInterface $session, RedirectInterface $redirect, InputInterface $input, $settings){
        $this->resource = $resource;
        $this->client  = $client;
        $this->session = $session;
        $this->redirect = $redirect;
        $this->input = $input;
        $this->settings = $settings;
    }

    /**
     * Call this method to redirect user to login page and initiate
     * the Web Server OAuth Authentication Flow.
     * @return void
     */
    public function authenticate()
    {
        return $this->redirect->to($this->settings['oauth']['loginURL'] . '/services/oauth2/authorize'
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
        $code = $this->input->get('code');
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

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        $jsonResponse = $response->json();

        // Encypt token and store token and in session.
        $this->session->putToken($jsonResponse);

        // Store resources into the session.
        $this->putResources();
   
        //Redirect to user's homepage. Can change this in Oauth settings config.
        return $this->redirect->to($this->settings['authRedirect']);

    }
    
    /**
     * Revokes access token from Salesforce. Will not flush token from Session.
     * @return RedirectInterface
     */
    public function revoke(){
        $accessToken = $this->session->getToken()['access_token'];
        $url = 'https://login.salesforce.com/services/oauth2/revoke';
        $options['headers']['content-type'] = 'application/x-www-form-urlencoded';
        $options['body']['token'] = $accessToken;

        $this->client->post($url,$options);

        $redirectURL = $this->settings['authRedirect'];

        return $this->redirect->to($redirectURL);
    }

    /**
     * Request that returns all currently supported versions.
     * Includes the verison, label and link to each version's root.
     * Formats: json, xml
     * Methods: get
     * @param  array  $options
     * @return array $versions
     */
    public function versions($options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= '/services/data/';
        $versions = $this->resource->request($url,$options);
        return $versions;
    }

    /**
     * Lists availabe resources for specified API version.
     * Includes resource name and URI.
     * Formats: json, xml
     * Methods: get
     * @param  array $options
     * @return array $resources
     */
    public function resources($options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('version')['url'];
        $resources = $this->resource->request($url,$options);

        return $resources;
    }

    /**
     * Returns information about the logged-in user.
     * @param  array
     * @return array $identity
     */
    public function identity($options =[]){
        $token       = $this->session->getToken();
        $accessToken = $token['access_token'];
        $url         = $token['id'];

        $options['headers']['Authorization'] = "OAuth $accessToken";

        $identity = $this->resource->request($url,$options);

        return $identity;
    }

    /**
     * Lists information about organizational limits.
     * Available for API version 29.0 and later.
     * Returns limits for daily API calls, Data storage, etc.
     * @param  array $options
     * @return array $limits
     */
    public function limits($options =[]){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('version')['url'];
        $url .= '/limits';

        $limits = $this->resource->request($url,$options);

        return $limits;
    }

    /**
     * Executes a specified SOQL query
     * @param  string $query
     * @param  array $options
     * @return array $queryResults
     */
    public function query($query,$options = []) {
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['query'];
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->resource->request($url,$options);

        return $queryResults;
    }

    /**
     * Details how Salesforce will process your query.
     * Available for API verison 30.0 or later
     * @param  string $query   
     * @param  array $options 
     * @return array $queryExplain
     */
    public function queryExplain($query,$options = []) {
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['query'];
        $url .= '?explain=';
        $url .= urlencode($query);
        
        $queryExplain = $this->resource->request($url,$options);

        return $queryExplain;
    }

    /**
     * Executes a SOQL query, but will also returned records that have
     * been deleted.
     * Available for API version 29.0 or later
     * @param  string $query   
     * @param  array $options 
     * @return array $queryResults     
     */
    public function queryAll($query,$options = []) {
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['queryAll'];
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->resource->request($url,$options);

        return $queryResults;
    }

    /**
     * Executes the specified SOSL query
     * @param  string $query   
     * @param  array $options 
     * @return array          
     */
    public function search($query,$options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['search'];
        $url .= '?s=';
        $url .= urlencode($query);

        $searchResults = $this->resource->request($url,$options);

        return $searchResults;
    }

    /**
     * Returns an ordered list of objects in the default global search
     * scope of a logged-in user. Global search keeps track of which
     * objects the user interacts with and how often and arranges the
     * search results accordingly. Objects used most frequently appear
     * at the top of the list.
     * @param  array $options
     * @return array          
     */
    public function scopeOrder($options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['search'];
        $url .= '/scopeOrder';

        $scopeOrder = $this->resource->request($url,$options);

        return $scopeOrder;
    }

    /**
     * Returns search result layout information for the objects in the query string.
     * @param  array $objectList 
     * @param  array $options  
     * @return array             
     */
    public function searchLayouts($objectList,$options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['search'];
        $url .= '/layout/?q=';
        $url .= urlencode($objectList);

        $searchLayouts = $this->resource->request($url,$options);

        return $searchLayouts;
    }

    /**
     * Returns a list of Salesforce Knowledge articles whose titles match the user’s
     * search query string. Provides a shortcut to navigate directly to likely
     * relevant articles, before the user performs a search.
     * Available for API version 30.0 or later
     * @param  string $query
     * @param  array $searchParameters
     * @param  array $option
     * @return array
     */
    public function suggestedArticles($query,$searchParameters = [],$options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['search'];
        $url .= '/suggestTitleMatches?q=';
        $url .= urlencode($query);

        foreach ($searchParameters as $key => $value) {
            $url .= '&';
            $url .= $key;
            $url .= '=';
            $url .= $value;
        }

        $suggestedArticles = $this->resource->request($url,$options);

        return $suggestedArticles;
    }

    /**
     * Returns a list of suggested searches based on the user’s query string text
     * matching searches that other users have performed in Salesforce Knowledge.
     * Provides a way to improve search effectiveness, before the user performs a
     * search.
     * Available for API version 30.0 or later
     * @param  string $query            
     * @param  array $searchParameters 
     * @param  array $options          
     * @return array                   
     */
    public function suggestedQueries($query,$searchParameters = [],$options = []){
        $url = $this->session->getToken()['instance_url'];
        $url .= $this->session->get('resources')['search'];
        $url .= '/suggestSearchQueries?q=';
        $url .= urlencode($query);

        foreach ($searchParameters as $key => $value) {
            $url .= '&';
            $url .= $key;
            $url .= '=';
            $url .= $value;
        }

        $suggestedQueries = $this->resource->request($url,$options);

        return $suggestedQueries;
    }

    /**
     * Returns any resource that is available to the authenticated
     * user. Reference Force.com's REST API guide to read about more
     * methods that can be called or refence them by calling the
     * Session::get('resources') method.
     * @param  string $name      
     * @param  array $arguments 
     * @return array            
     */
    public function __call($name,$arguments){

        $url = $this->session->getToken()['instance_url'];

        $url .= $this->session->get('resources')[$name];
        if(isset($arguments[0])){
            $url .= "/$arguments[0]";
        }

        $options = [];
        if(isset($arguments[1])){
            foreach($arguments[1] as $key => $value){
                $options[$key] = $value;
            }
        }

        return $this->resource->request($url,$options);
    }

    /**
     * Checks to see if version is specified in configuration and if not then
     * assign the latest version number availabe to the user's instance.
     * Once a version number is determined, it will be stored in the user's
     * session with the 'version' key.
     * @return void
     */
    private function putVersion(){
        $configVersion = $this->settings['version'];

        if(isset($configVersion)){
            $versions = $this->versions();
            foreach ($versions as $version) {
                if($version['version'] == $configVersion){
                    $this->session->put('version',$version);
                }
            }
        }
        else {
            $versions = $this->versions();
            $lastestVersion = end($versions);
            $this->session->put('version', $lastestVersion);
        }
    }

    /**
     * Checks to see if version is specified. If not then call putVersion.
     * Once a version is determined, determine the available resources the
     * user has access to and store them in teh user's sesion.
     * @return void
     */
    private function putResources(){
        try{
            $version = $this->session->get('version');
        }
        catch( \Exception $e) {
            $this->putVersion();
            $resources = $this->resources();
            $this->session->put('resources', $resources);
        }
    }

}