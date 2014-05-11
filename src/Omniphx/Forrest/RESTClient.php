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
     * Array of OAuth settings: client Id, client secret, callback URL, login URL, and redirect URL
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
        return $this->redirect->to($this->settings['loginURI']
                        	. '/services/oauth2/authorize?response_type=code&client_id='
                        	. $this->settings['clientId']
                        	. '&redirect_uri='
                        	. urlencode($this->settings['redirectURI']));
    }

    /**
     * When settings up your callback route, you will need to call this method to
     * acquire an authorization token. This token will be used for the API requests.
     * @return function Redirect()
     */
    public function callback()
    {
        //Salesforce sends us an authorization code as part of the Web Server OAuth Authentication Flow
        $code = $this->input->get('code');
        $state = $this->input->get('state');

        //Now we must make a request for the authorization token.
        $tokenURL = $this->settings['loginURI'] . '/services/oauth2/token';
        $response = $this->client->post($tokenURL, [
            'body' => [
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->settings['clientId'],
                'client_secret' => $this->settings['clientSecret'],
                'redirect_uri'  => $this->settings['redirectURI']
            ]
        ]);

        $jsonResponse = $response->json();

        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        // Can be accessed now with $this->session->getToken['access_token'];
        // putToken() will also encrypt the token.
        $this->session->putToken($jsonResponse);

        //Now that we have the Salesforce instance, we can see what versions it supports
        $versions = $this->versions();

        //Find the latest API version.
        $lastestVersion = end($versions);

        //Store latest API version in current session. Referenced in version().
        $this->session->put('version', $lastestVersion);
        

        $resources = $this->version();
        $this->session->put('resources', $resources);
   
        //Redirect to user's homepage. Can change this in Oauth settings config.
        return $this->redirect->to($this->settings['authRedirect']);

    }

    /**
     * http://salesforce.stackexchange.com/questions/11728/salesforce-any-api-for-getting-user-information
     * @return json
     */
    public function getUser(){
        $token = $this->session->getToken();
        $accessToken = $token['access_token'];
        $idURL       = $token['id'];

        $header = array("Authorization" => "OAuth $accessToken");

        $response = $this->client->get($idURL, [
            'headers' => $header
        ]);

        return $response->json();
    }

    
    /**
     * Revokes access Token from Salesforce. Will not flush token from Session.
     * @return function Redirect()
     */
    public function revoke(){
        $accessToken = $this->session->getToken()['access_token'];
        $url = 'https://login.salesforce.com/services/oauth2/revoke';
        
        $response = $this->client->post($url, 
            ['body' => 
                ['token' => $accessToken]
        ]);

        return $this->redirect->to($this->settings['authRedirect']);
    }

    /**
     * Request that returns all supported versions
     * @param  array  $options
     * @return json
     */
    public function versions($options = array('method'=>'GET','format'=>'JSON')){
        $uri = "/services/data/";
        $resource = $this->resource->request($uri,$options);
        return $resource;
    }

    public function version($options = array('method'=>'GET','format'=>'JSON')){
        $uri = $this->session->get('version')['url'];
        $resource = $this->resource->request($uri,$options);

        return $resource;
    }
    
    public function sObject($sObjectName, $options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName";
        
        return $this->resource->request($uri,$options);
    }

    public function describe($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe";

        return $this->resource->request($uri,$options);
    }

    public function sObjectDeleted($sObjectName,$startDateAndTime,$endDateAndTime,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/deleted/?start=$startDateAndTime&end=$endDateAndTime";

        return $this->resource->request($uri,$options);
    }

    public function sObjectUpdated($sObjectName,$startDateAndTime,$endDateAndTime,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/updated/?start=$startDateAndTime&end=$endDateAndTime";

        return $this->resource->request($uri,$options);
    }

    public function sObjectById($sObjectName,$id,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObject/$id";

        return $this->resource->request($uri,$options);
    }

    public function sObjectByExternalId($sObjectName,$fieldName,$fieldValue,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/$fieldName/$fieldValue";

        return $this->resource->request($uri,$options);
    }

    public function sObjectApprovalLayout($sObjectName,$approvalProcessName=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/approvalLayouts/$approvalProcessName";
        
        return $this->resource->request($uri,$options);
    }

    public function sObjectCompactLayout($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/compactLayouts/";
        
        return $this->resource->request($uri,$options);
    }

    public function sObjectLayout($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/layouts/"; 
        
        return $this->resource->request($uri,$options);
    }

    public function sObjectQuickActions($sObjectName,$actionName=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName";
        
        return $this->resource->request($uri,$options);
    }

    public function sObjectQuickActionsDescribe($sObjectName,$actionName,$parentId=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName/describe/$parentId";
        
        return $this->resource->request($uri,$options);
    }

    public function sObjectQuickActionsDefaultValues($sObjectName,$actionName,$parentId=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName/defaultValues/$parentId";
        
        return $this->resource->request($uri,$options);
    }

    public function suggestedCaseArticle($caseSubject,$caseDescription,$articleLanguage='en',$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/Case/suggestedArticles?language=$articleLanguage&subject=$caseSubject&description=$caseDescription";
        
        return $this->resource->request($uri,$options);
    }

    public function suggestedCaseArticleById($caseId,$articleLanguage='en',$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/Case/$caseId/suggestedArticles?language=$articleLanguage";
        
        return $this->resource->request($uri,$options);
    }

    public function userPassword($userId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/User/$userId/password";
        
        return $this->resource->request($uri,$options);
    }

    public function appMenu($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['appMenu'];
        $uri = "$resourceURI/AppSwitcher/";
        
        return $this->resource->request($uri,$options);
    }

    public function appMenuSF1($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['appMenu'];
        $uri = "$resourceURI/Salesforce1/";
        
        return $this->resource->request($uri,$options);
    }

    public function flexiPage($flexiId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['flexiPage'];
        $uri = "$resourceURI/$flexiId";
        
        return $this->resource->request($uri,$options);
    }

    public function processApprovals($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['process'];
        $uri = "$resourceURI/approvals/";
        
        return $this->resource->request($uri,$options);
    }

    public function processRules($sObjectName,$workflowRuleId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['process'];
        $uri = "$resourceURI/rules/$sObjectName/$workflowRuleId";
        
        return $this->resource->request($uri,$options);
    }

    public function query($query,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['query'];
        $uri = "$resourceURI?q=".urlencode($query);

        return $this->resource->request($uri,$options);
    }

    public function queryExplain($query,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['query'];
        $uri = "$resourceURI?explain=" . urlencode($query);
        
        return $this->resource->request($uri,$options);
    }

    public function queryAll($query,$format,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['queryAll'];
        $uri = "$resourceURI?q=" . urlencode($query);
        
        return $this->resource->request($uri,$options);
    }

    public function quickActions($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['quickActions'];
        $uri = "$resourceURI";
        
        return $this->resource->request($uri,$options);
    }

    public function search($query,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI?s=" . urlencode($query);
        
        return $this->resource->request($uri,$options);
    }

    public function searchScopeOrder($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/scopeOrder";

        return $this->resource->request($uri,$options);
    }

    public function searchLayouts($objectList,$options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/layout/?q=" . urlencode($objectList);

        return $this->resource->request($uri,$options);
    }

    public function searchSuggestedArticles($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/scopeOrder";

        return $this->resource->request($uri,$options);
    }

    public function searchSuggestedQueries($query, $language = 'en', $options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/suggestSearchQueries?q=" . urlencode($query) . "&language=$language";

        return $this->resource->request($uri,$options);
    }

    public function recentlyViewed($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['recent'];
        $uri = $resourceURI;

        return $this->resource->request($uri,$options);

    }

    public function themes($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['theme'];
        $uri = $resourceURI;

        return $this->resource->request($uri,$options);
    }

}