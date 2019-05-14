<?php

namespace Omniphx\Forrest;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Omniphx\Forrest\Exceptions\InvalidLoginCreditialsException;
use Omniphx\Forrest\Exceptions\SalesforceException;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Exceptions\MissingVersionException;

use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\FormatterInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\ResourceRepositoryInterface;

/**
 * API resources.
 *
 * @method ClientInterface chatter(array $options = [])
 * @method ClientInterface tabs(array $options = [])
 * @method ClientInterface appMenu(array $options = [])
 * @method ClientInterface quickActions(array $options = [])
 * @method ClientInterface queryAll(array $options = [])
 * @method ClientInterface commerce(array $options = [])
 * @method ClientInterface wave(array $options = [])
 * @method ClientInterface exchange-connect(array $options = [])
 * @method ClientInterface analytics(array $options = [])
 * @method ClientInterface identity(array $options = [])
 * @method ClientInterface composite(array $options = [])
 * @method ClientInterface theme(array $options = [])
 * @method ClientInterface nouns(array $options = [])
 * @method ClientInterface recent(array $options = [])
 * @method ClientInterface licensing(array $options = [])
 * @method ClientInterface limits(array $options = [])
 * @method ClientInterface async-queries(array $options = [])
 * @method ClientInterface emailConnect(array $options = [])
 * @method ClientInterface compactLayouts(array $options = [])
 * @method ClientInterface flexiPage(array $options = [])
 * @method ClientInterface knowledgeManagement(array $options = [])
 * @method ClientInterface sobjects(array $options = [])
 * @method ClientInterface actions(array $options = [])
 * @method ClientInterface support(array $options = [])
 *
 * Note: Not all methods are available to certain orgs/licenses
 *
 * search() and query() are not overloaded with the __call() method, this is because queries require urlencoding. I'm open to a more elegant solution, but prefer to leave it this way to make it simple to use.
 */
abstract class Client
{
    /**
     * HTTP request client.
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Event emitter.
     *
     * @var Interfaces\EventInterface
     */
    protected $event;

    protected $resourceRepo;

    protected $stateRepo;

    protected $tokenRepo;

    protected $versionRepo;

    /**
     * Config options.
     *
     * @var array
     */
    protected $settings;

    /**
     * Redirect handler.
     *
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * Inteface for Input calls.
     *
     * @var \Omniphx\Forrest\Interfaces\InputInterface
     */
    protected $input;

    /**
     * Inteface for Input calls.
     *
     * @var \Omniphx\Forrest\Interfaces\EncryptorInterface
     */
    protected $encryptor;

    protected $formatter;

    /**
     * Authentication credentials.
     *
     * @var array
     */
    protected $credentials;

    /**
     * Request options.
     *
     * @var array
     */
    private $options;

    /**
     * Request headers.
     *
     * @var array
     */
    private $headers;

    /**
     * Request parameters.
     *
     * @var array
     */
    private $parameters;

    public function __construct(
        ClientInterface $httpClient,
        EncryptorInterface $encryptor,
        EventInterface $event,
        InputInterface $input,
        RedirectInterface $redirect,
        RepositoryInterface $instanceURLRepo,
        RepositoryInterface $refreshTokenRepo,
        ResourceRepositoryInterface $resourceRepo,
        RepositoryInterface $stateRepo,
        RepositoryInterface $tokenRepo,
        RepositoryInterface $versionRepo,
        FormatterInterface $formatter,
        $settings)
    {
        $this->httpClient       = $httpClient;
        $this->encryptor        = $encryptor;
        $this->event            = $event;
        $this->input            = $input;
        $this->redirect         = $redirect;
        $this->instanceURLRepo  = $instanceURLRepo;
        $this->refreshTokenRepo = $refreshTokenRepo;
        $this->resourceRepo     = $resourceRepo;
        $this->stateRepo        = $stateRepo;
        $this->tokenRepo        = $tokenRepo;
        $this->versionRepo      = $versionRepo;
        $this->formatter        = $formatter;
        $this->settings         = $settings;
        $this->credentials      = $settings['credentials'];
    }

    /**
     * Try requesting token, if token expired try refreshing token.
     *
     * @param string $url
     * @param array  $options
     *
     * @return mixed
     */
    public function request($url, $options)
    {
        $this->url = $url;
        $this->options = array_replace_recursive($this->settings['defaults'], $options);

        try {
            return $this->handleRequest();
        } catch (TokenExpiredException $e) {
            $this->refresh();

            return $this->handleRequest();
        }
    }

    private function handleRequest()
    {
        if (isset($this->options['headers'])) {
            $this->parameters['headers'] = array_replace_recursive($this->formatter->setHeaders(), $this->options['headers']);
        } else {
            $this->parameters['headers'] = $this->formatter->setHeaders();
        }

        if (isset($this->options['body'])) {
            $this->parameters['body'] = $this->formatter->setBody($this->options['body']);
        }

        try {
            $response = $this->httpClient->request($this->options['method'], $this->url, $this->parameters);
        } catch (RequestException $ex) {
            $this->assignExceptions($ex);
        }

        $formattedResponse = $this->formatter->formatResponse($response);

        $this->event->fire('forrest.response', [$formattedResponse]);

        return $formattedResponse;
    }

    /**
     * GET method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function get($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'GET');
    }

    /**
     * POST method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function post($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'POST');
    }

    /**
     * PUT method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function put($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'PUT');
    }

    /**
     * DELETE method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function delete($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'DELETE');
    }

    /**
     * HEAD method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function head($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'HEAD');
    }

    /**
     * PATCH method call using any custom path.
     *
     * @param string $path
     * @param array  $requestBody
     * @param array  $options
     *
     * @return mixed
     */
    public function patch($path, $requestBody = [], $options = [])
    {
        return $this->sendRequest($path, $requestBody, $options, 'PATCH');
    }

    /**
     * Prepares options and sends the request.
     *
     * @param $path
     * @param $requestBody
     * @param $options
     * @param $method
     *
     * @return mixed
     */
    private function sendRequest($path, $requestBody, $options, $method)
    {
        $url = $this->instanceURLRepo->get();
        $url .= '/'.trim($path, "/\t\n\r\0\x0B");

        $options['method'] = $method;
        if (!empty($requestBody)) {
            $options['body'] = $requestBody;
        }

        return $this->request($url, $options);
    }

    /**
     * Request that returns all currently supported versions.
     * Includes the verison, label and link to each version's root.
     * Formats: json, xml
     * Methods: get.
     *
     * @param array $options
     *
     * @return array $versions
     */
    public function versions($options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= '/services/data';

        $versions = $this->request($url, $options);

        return $versions;
    }

    /**
     * Lists availabe resources for specified API version.
     * Includes resource name and URI.
     * Formats: json, xml
     * Methods: get.
     *
     * @param array $options
     *
     * @return array $resources
     */
    public function resources($options = [])
    {
        $url = $this->getBaseUrl();
        $resources = $this->request($url, $options);

        return $resources;
    }

    /**
     * Returns information about the logged-in user.
     *
     * @param  array
     *
     * @return array $identity
     */
    public function identity($options = [])
    {
        $token = $this->tokenRepo->get();
        $accessToken = $token['access_token'];
        $url = $token['id'];

        $options['headers']['Authorization'] = "OAuth $accessToken";

        $identity = $this->request($url, $options);

        return $identity;
    }

    /**
     * Lists information about organizational limits.
     * Available for API version 29.0 and later.
     * Returns limits for daily API calls, Data storage, etc.
     *
     * @param array $options
     *
     * @return array $limits
     */
    public function limits($options = [])
    {
        $url = $this->getBaseUrl();
        $url .= '/limits';

        $limits = $this->request($url, $options);

        return $limits;
    }

    /**
     * Describes all global objects available in the organization.
     *
     * @param string $object_name
     * @param array $options
     * @return array
     */
    public function describe($object_name = null, $options = [])
    {
        $url = sprintf('%s/sobjects', $this->getBaseUrl());
        
        if ( ! empty($object_name)) {
            $url .= sprintf('/%s/describe', $object_name);
        }

        return $this->request($url, $options);
    }

    /**
     * Executes a specified SOQL query.
     *
     * @param string $query
     * @param array  $options
     *
     * @return array $queryResults
     */
    public function query($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('query');
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }

    /**
     * Calls next query.
     *
     * @param       $nextUrl
     * @param array $options
     *
     * @return mixed
     */
    public function next($nextUrl, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $nextUrl;

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }

    /**
     * Details how Salesforce will process your query.
     * Available for API verison 30.0 or later.
     *
     * @param string $query
     * @param array  $options
     *
     * @return array $queryExplain
     */
    public function queryExplain($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('query');
        $url .= '?explain=';
        $url .= urlencode($query);

        $queryExplain = $this->request($url, $options);

        return $queryExplain;
    }

    /**
     * Executes a SOQL query, but will also returned records that have
     * been deleted.
     * Available for API version 29.0 or later.
     *
     * @param string $query
     * @param array  $options
     *
     * @return array $queryResults
     */
    public function queryAll($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('queryAll');
        $url .= '?q=';
        $url .= urlencode($query);

        $queryResults = $this->request($url, $options);

        return $queryResults;
    }

    /**
     * Executes the specified SOSL query.
     *
     * @param string $query
     * @param array  $options
     *
     * @return array
     */
    public function search($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('search');
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
     *
     * @param array $options
     *
     * @return array
     */
    public function scopeOrder($options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('search');
        $url .= '/scopeOrder';

        $scopeOrder = $this->request($url, $options);

        return $scopeOrder;
    }

    /**
     * Returns search result layout information for the objects in the query string.
     *
     * @param array $objectList
     * @param array $options
     *
     * @return array
     */
    public function searchLayouts($objectList, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('search');
        $url .= '/layout/?q=';
        $url .= urlencode($objectList);

        $searchLayouts = $this->request($url, $options);

        return $searchLayouts;
    }

    /**
     * Returns a list of Salesforce Knowledge articles whose titles match the user’s
     * search query. Provides a shortcut to navigate directly to likely
     * relevant articles, before the user performs a search.
     * Available for API version 30.0 or later.
     *
     * @param string $query
     * @param array  $options
     *
     * @return array
     */
    public function suggestedArticles($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('search');
        $url .= '/suggestTitleMatches?q=';
        $url .= urlencode($query);

        $parameters = [
            'language'      => $this->settings['language'],
            'publishStatus' => 'Online',
        ];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '&'.http_build_query($parameters);
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
     * @param string $query
     * @param array  $options
     *
     * @return array
     */
    public function suggestedQueries($query, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get('search');
        $url .= '/suggestSearchQueries?q=';
        $url .= urlencode($query);

        $parameters = ['language' => $this->settings['language']];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '&'.http_build_query($parameters);
        }

        $suggestedQueries = $this->request($url, $options);

        return $suggestedQueries;
    }

    /**
     * Request to a custom Apex REST endpoint.
     *
     * @param string $customURI
     * @param array  $options
     *
     * @return mixed
     */
    public function custom($customURI, $options = [])
    {
        $url = $this->instanceURLRepo->get();
        $url .= '/services/apexrest';
        $url .= $customURI;

        $parameters = [];

        if (isset($options['parameters'])) {
            $parameters = array_replace_recursive($parameters, $options['parameters']);
            $url .= '?'.http_build_query($parameters);
        }

        return $this->request($url, $options);
    }

    /**
     * Public accessor to the Guzzle Client Object.
     *
     * @return HttpClientInterface
     */
    public function getClient()
    {
        return $this->httpClient;
    }

    /**
     * Returns any resource that is available to the authenticated
     * user. Reference Force.com's REST API guide to read about more
     * methods that can be called or refence them by calling the
     * Session::get('resources') method.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return array
     */
    public function __call($name, $arguments)
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->resourceRepo->get($name);
        $url .= $this->appendURL($arguments);

        $options = $this->setOptions($arguments);

        return $this->request($url, $options);
    }

    private function appendURL($arguments) {
        if (!isset($arguments[0])) return '';
        if (!is_string($arguments[0])) return '';

        return "/$arguments[0]";
    }

    private function setOptions($arguments) {
        $options = [];

        foreach ($arguments as $argument) {
            $this->setArgument($argument, $options);
        }

        return $options;
    }

    private function setArgument($argument, &$options) {
        if (!is_array($argument)) return;
        foreach ($argument as $key => $value) {
            $options[$key] = $value;
        }
    }

    /**
     * Refresh authentication token.
     *
     * @return mixed $response
     */
    abstract public function refresh();

    /**
     * Revokes access token from Salesforce. Will not flush token from storage.
     *
     * @return mixed
     */
    abstract public function revoke();

    protected function getBaseUrl()
    {
        $url = $this->instanceURLRepo->get();
        $url .= $this->versionRepo->get()['url'];

        return $url;
    }

    /**
     * Checks to see if version is specified in configuration and if not then
     * assign the latest version number available to the user's instance.
     * Once a version number is determined, it will be stored in the user's
     * storage with the 'version' key.
     *
     * @return void
     */
    protected function storeVersion()
    {
        $versions = $this->versions();

        $this->storeConfiguredVersion($versions);
        $this->storeLatestVersion($versions);
    }

    private function storeConfiguredVersion($versions)
    {
        $configVersion = $this->settings['version'];
        if (empty($configVersion)) return;

        foreach($versions as $version) {
            $this->determineIfConfiguredVersionExists($version, $configVersion);
        }
    }

    private function determineIfConfiguredVersionExists($version, $configVersion)
    {
        if ($version['version'] !== $configVersion) return;
        $this->versionRepo->put($version);
    }

    private function storeLatestVersion($versions)
    {
        $latestVersion = end($versions);
        $this->versionRepo->put($latestVersion);
    }

    /**
     * Checks to see if version is specified. If not then call storeVersion.
     * Once a version is determined, determine the available resources the
     * user has access to and store them in teh user's sesion.
     *
     * @return void
     */
    protected function storeResources()
    {
        $resources = $this->resources(['format' => 'json']);
        $this->resourceRepo->put($resources);
    }

    protected function handleAuthenticationErrors(array $response)
    {
        if (!isset($response['error'])) return;

        throw new InvalidLoginCreditialsException($response['error_description']);
    }

    /**
     * Method will elaborate on RequestException.
     *
     * @param RequestException $ex
     *
     * @throws SalesforceException
     * @throws TokenExpiredException
     */
    private function assignExceptions(RequestException $ex)
    {
        if ($ex->hasResponse() && $ex->getResponse()->getStatusCode() == 401) {
            throw new TokenExpiredException('Salesforce token has expired', $ex);
        } elseif ($ex->hasResponse()) {
            throw new SalesforceException('Salesforce response error: '.$ex->getMessage(), $ex);
        } else {
            throw new SalesforceException('Invalid request: %s', $ex);
        }
    }
}
