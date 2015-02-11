<?php namespace Omniphx\Forrest;

abstract class Client {

    /**
     * HTTP request client
     * @var Client
     */
    protected $client;

    /**
     * Config options
     * @var array
     */
    protected $settings;

    /**
     * Storage handler
     * @var storage
     */
    protected $storage;

    /**
     * Reqeust headers
     * @var Array
     */
    private $headers;

    /**
     * GET method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function get($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'GET';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * POST method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function post($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'POST';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * PUT method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function put($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'PUT';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * DELETE method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function delete($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'DELETE';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * HEAD method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function head($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'HEAD';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * PATCH method call using any custom path
     * @param string $path
     * @param array $requestBody
     * @param array $options
     */
    public function patch($path, $requestBody = array(), $options = array()) {
        $url = $this->getInstanceUrl();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = 'PATCH';
        if($requestBody) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * Request that returns all currently supported versions.
     * Includes the verison, label and link to each version's root.
     * Formats: json, xml
     * Methods: get
     * @param  array  $options
     * @return array $versions
     */
    public function versions($options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= '/services/data/';

        $versions = $this->request($url, $options);

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
    public function resources($options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('version')['url'];

        $resources = $this->request($url, $options);

        return $resources;
    }

    /**
     * Returns information about the logged-in user.
     * @param  array
     * @return array $identity
     */
    public function identity($options =[])
    {
        $token       = $this->getToken();
        $accessToken = $token['access_token'];
        $url         = $token['id'];

        $options['headers']['Authorization'] = "OAuth $accessToken";

        $identity = $this->request($url, $options);

        return $identity;
    }

    /**
     * Lists information about organizational limits.
     * Available for API version 29.0 and later.
     * Returns limits for daily API calls, Data storage, etc.
     * @param  array $options
     * @return array $limits
     */
    public function limits($options =[])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('version')['url'];
        $url .= '/limits';

        $limits = $this->request($url, $options);

        return $limits;
    }

    /**
     * Describes all global objects availabe in the organization.
     * @return array
     */
    public function describe($options =[])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('version')['url'];
        $url .= '/sobjects';

        $describe = $this->request($url, $options);

        return $describe;
    }

    /**
     * Executes a specified SOQL query.
     * @param  string $query
     * @param  array $options
     * @return array $queryResults
     */
    public function query($query, $options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['query'];
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }
    
    /**
     * Calls next query
     * @param       $nextUrl
     * @param array $options
     *
     * @return mixed
     */
    public function next($nextUrl, $options = [])
    {
        $url  = $this->getToken()['instance_url'];
        $url .= $this->storage->get('resources')['query'];
        $url .= '/'.$nextUrl;

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }

    /**
     * Details how Salesforce will process your query.
     * Available for API verison 30.0 or later
     * @param  string $query
     * @param  array $options
     * @return array $queryExplain
     */
    public function queryExplain($query, $options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['query'];
        $url .= '?explain=';
        $url .= urlencode($query);

        $queryExplain = $this->request($url, $options);

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
    public function queryAll($query,$options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['queryAll'];
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }

    /**
     * Executes the specified SOSL query
     * @param  string $query
     * @param  array $options
     * @return array
     */
    public function search($query,$options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['search'];
        $url .= '?q=';
        $url .= urlencode($query);

        $searchResults = $this->request($url, $options);

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
    public function scopeOrder($options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['search'];
        $url .= '/scopeOrder';

        $scopeOrder = $this->request($url, $options);

        return $scopeOrder;
    }

    /**
     * Returns search result layout information for the objects in the query string.
     * @param  array $objectList
     * @param  array $options
     * @return array
     */
    public function searchLayouts($objectList,$options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['search'];
        $url .= '/layout/?q=';
        $url .= urlencode($objectList);

        $searchLayouts = $this->request($url, $options);

        return $searchLayouts;
    }

    /**
     * Returns a list of Salesforce Knowledge articles whose titles match the user’s
     * search query. Provides a shortcut to navigate directly to likely
     * relevant articles, before the user performs a search.
     * Available for API version 30.0 or later
     * @param  string $query
     * @param  array $searchParameters
     * @param  array $option
     * @return array
     */
    public function suggestedArticles($query,$options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['search'];
        $url .= '/suggestTitleMatches?q=';
        $url .= urlencode($query);

        $parameters = [
            'language'      => $this->settings['language'],
            'publishStatus' => 'Online'];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '&' . http_build_query($parameters);
        }

        $suggestedArticles = $this->request($url, $options);

        return $suggestedArticles;
    }

    /**
     * Returns a list of suggested searches based on the user’s query string text
     * matching searches that other users have performed in Salesforce Knowledge.
     * Available for API version 30.0 or later.
     *
     * Tested this and can't get it to work. I think the request is set up correctly.
     *
     * @param  string $query
     * @param  array $options
     * @return array
     */
    public function suggestedQueries($query,$options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')['search'];
        $url .= '/suggestSearchQueries?q=';
        $url .= urlencode($query);

        $parameters = ['language' => $this->settings['language']];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '&' . http_build_query($parameters);
        }

        $suggestedQueries = $this->request($url, $options);

        return $suggestedQueries;
    }

    /**
     * Request to a custom Apex REST endpoint
     * @param  String $customURI
     * @param  Array $option
     * @return mixed
     */
    public function custom($customURI, $options = [])
    {
        $url = $this->getInstanceUrl();
        $url .= '/services/apexrest';
        $url .= $customURI;

        $parameters = [];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '?' . http_build_query($parameters);
        }

        return $this->request($url, $options);
    }

    /**
     * Public accessor to the Guzzle Client Object
     *
     * @return GuzzleHttp\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
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
    public function __call($name,$arguments)
    {
        $url = $this->getInstanceUrl();
        $url .= $this->storage->get('resources')[$name];

        $options = [];

        if (isset($arguments[0])) {
            if (is_string($arguments[0])) {
                $url .= "/$arguments[0]";
            }
            else if (is_array($arguments[0])){
                foreach ($arguments[0] as $key => $value) {
                    $options[$key] = $value;
                }
            }
        }

        if (isset($arguments[1])) {
            if (is_array($arguments[1])) {
                foreach ($arguments[1] as $key => $value) {
                    $options[$key] = $value;
                }
            }
        }

        return $this->request($url, $options);
    }

    /**
     * Get token
     * @return array
     */
    public function getToken()
    {
        return $this->storage->getToken();
    }

    /**
     * Get the instance URL
     * @return string
     */
    protected function getInstanceUrl()
    {
        $url = $this->settings['instanceURL'];

        if (empty($url)){
            $url  = $this->getToken()['instance_url'];
        }

        return $url;
    }

    /**
     * Checks to see if version is specified in configuration and if not then
     * assign the latest version number availabe to the user's instance.
     * Once a version number is determined, it will be stored in the user's
     * storage with the 'version' key.
     * @return void
     */
    protected function storeVersion()
    {
        $configVersion = $this->settings['version'];

        if ($configVersion != null){
            $versions = $this->versions(['format'=>'json']);
            foreach ($versions as $version) {
                if ($version['version'] == $configVersion){
                    $this->storage->put('version',$version);
                }
            }
        }
        else {
            $versions = $this->versions(['format'=>'json']);
            $latestVersion = end($versions);
            $this->storage->put('version', $latestVersion);
        }
    }

    /**
     * Checks to see if version is specified. If not then call storeVersion.
     * Once a version is determined, determine the available resources the
     * user has access to and store them in teh user's sesion.
     * @return void
     */
    protected function storeResources()
    {
        try {
            $version = $this->storage->get('version');
            $resources = $this->resources(['format'=>'json']);
            $this->storage->put('resources', $resources);
        }
        catch (\Exception $e) {
            $this->storeVersion();
            $resources = $this->resources(['format'=>'json']);
            $this->storage->put('resources', $resources);
        }
    }

    /**
     * Method returns the response for the requested resource
     * @param  string $pURI
     * @param  array  $pOptions
     * @return mixed
     */
    protected function requestResource($pURL, array $pOptions)
    {
        $options = array_replace_recursive($this->settings['defaults'], $pOptions);

        $format = $options['format'];
        $method = $options['method'];

        $this->setHeaders($options);

        $parameters['headers'] = $this->headers;

        if (isset($options['body'])) {
            $parameters['body'] = $this->formatBody($options);
        }

        $request = $this->client->createRequest($method,$pURL,$parameters);

        try {
            $response = $this->client->send($request);

            $this->event->fire('forrest.response', array($request, $response));

            return $this->responseFormat($response, $format);

        } catch(RequestException $e) {
            $this->assignExceptions($e);
        }


    }

    /**
     * Set the headers for the request
     * @param array $options
     * @return array $headers
     */
    private function setHeaders(array $options)
    {
        $format = $options['format'];

        $authToken = $this->storage->getToken();

        $accessToken = $authToken['access_token'];
        $tokenType   = $authToken['token_type'];

        $this->headers['Authorization'] = "$tokenType $accessToken";

        $this->setRequestFormat($options['format']);
        $this->setCompression($options);
    }

    /**
     * Format the body for the request
     * @param array $options
     * @return array $body
     */
    private function formatBody(array $options)
    {
        $format = $options['format'];
        $data   = $options['body'];

        if ($format == 'json') {
            $body = json_encode($data);
        }
        else if($format == 'xml') {
            $body = urlencode($data);
        }

        return $body;
    }

    //Need to think through this for it to work
    private function setRequestFormat($format)
    {
        if ($format == 'json') {
            $this->headers['Accept'] = 'application/json';
            $this->headers['Content-Type'] = 'application/json';
        }
        else if ($format == 'xml') {
            $this->headers['Accept'] = 'application/xml';
            $this->headers['Content-Type'] = 'application/xml';
        }
        else if ($format == 'urlencoded') {
            $this->headers['Accept'] = 'application/x-www-form-urlencoded';
            $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
    }

    private function setCompression($options)
    {
        if ($options['compression'] == true) {
            $this->headers['Accept-Encoding'] = $options['compressionType'];
            $this->headers['Content-Encoding'] = $options['compressionType'];
        }
    }

    /**
     * Returns the response in the configured  format
     * @param  Response $response
     * @param  string $format
     * @return mixed $response
     */
    private function responseFormat($response,$format)
    {
        if ($format == 'json') {
            return $response->json();
        }
        else if ($format == 'xml') {
            return $response->xml();
        }

        return $response;
    }

    /**
     * Method will elaborate on RequestException
     * @param  GuzzleHttp\Exception\ClientException $e
     * @return mixed
     */
    private function assignExceptions($e)
    {
        if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '401') {
            throw new TokenExpiredException(sprintf("Salesforce token has expired"));
        } else if($e->hasResponse()){
            throw new SalesforceException(sprintf("Salesforce response error: %s",$e->getResponse()));
        } else {
            throw new SalesforceException(sprintf("Invalid request: %s",$e->getRequest()));
        }
    }

}
