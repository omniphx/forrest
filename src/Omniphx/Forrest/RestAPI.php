<?php namespace Omniphx\Forrest;

use Input;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;

class RestAPI {

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

    public function __construct(ClientInterface $client, SessionInterface $session, RedirectInterface $redirect, InputInterface $input, $settings){
        $this->client  = $client;
        $this->session = $session;
        $this->redirect = $redirect;
        $this->input = $input;
        $this->settings = $settings;
    }

    public function resource($pURI,$options=[]){
        $accessToken = $this->session->get('token')['access_token'];
        $instanceURL = $this->session->get('token')['instance_url'];

        $url = $instanceURL . $pURI;
        $header = array("Authorization" => "OAuth $accessToken");

        switch($options['method']){
            case 'GET':
                $response = $this->client->get($url, [
                    'headers' => $header
                ]);
                if($options['format'] == 'JSON'){
                    return $response->json();
                } elseif($options['format'] == 'XML'){
                    return $response->xml();
                }
                break;
            case 'POST':
                break;
        }
    }

	public function authenticate()
    {
        return $this->redirect->to($this->settings['loginURI']
                        	. '/services/oauth2/authorize?response_type=code&client_id='
                        	. $this->settings['clientId']
                        	. '&redirect_uri='
                        	. urlencode($this->settings['redirectURI']));
    }

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
        $this->session->put('token', $jsonResponse);
        // Response returns an json of access_token, instance_url, id, issued_at, and signature.
        // Can be accessed now with $this->session->get('token')['access_token']


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
        $accessToken = $this->session->get('token')['access_token'];
        $idURL       = $this->session->get('token')['id'];

        $header = array("Authorization" => "OAuth $accessToken");

        $response = $this->client->get($idURL, [
            'headers' => $header
        ]);

        return $response->json();
    }

    public function revoke(){
        $accessToken = $this->session->get('token')['access_token'];
        $url = 'https://login.salesforce.com/services/oauth2/revoke';
        
        $response = $this->client->post($url, [
            'body' => ['token' => $accessToken]
        ]);

        return Redirect::to('/');
    }

    public function versions($options = array('method'=>'GET','format'=>'JSON')){
        $uri = "/services/data/";
        $resource = $this->resource($uri,$options);
        return $resource;
    }

    public function version($options = array('method'=>'GET','format'=>'JSON')){
        $uri = $this->session->get('version')['url'];
        $resource = $this->resource($uri,$options);

        return $resource;
    }
    
    public function sObject($sObjectName, $options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = $resourceURI.'/'.$sObjectName;
        
        return $this->resource($uri,$options);
    }

    public function describe($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe";

        return $this->resource($uri,$options);
    }

    public function sObjectDeleted($sObjectName,$startDateAndTime,$endDateAndTime,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/deleted/?start=$startDateAndTime&end=$endDateAndTime";

        return $this->resource($uri,$options);
    }

    public function sObjectUpdated($sObjectName,$startDateAndTime,$endDateAndTime,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/updated/?start=$startDateAndTime&end=$endDateAndTime";

        return $this->resource($uri,$options);
    }

    public function sObjectById($sObjectName,$id,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObject/$id";

        return $this->resource($uri,$options);
    }

    public function sObjectByExternalId($sObjectName,$fieldName,$fieldValue,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/$fieldName/$fieldValue";

        return $this->resource($uri,$options);
    }

    public function sObjectApprovalLayout($sObjectName,$approvalProcessName=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/approvalLayouts/$approvalProcessName";
        
        return $this->resource($uri,$options);
    }

    public function sObjectCompactLayout($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/compactLayouts/";
        
        return $this->resource($uri,$options);
    }

    public function sObjectLayout($sObjectName,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/describe/layouts/"; 
        
        return $this->resource($uri,$options);
    }

    public function sObjectQuickActions($sObjectName,$actionName=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName";
        
        return $this->resource($uri,$options);
    }

    public function sObjectQuickActionsDescribe($sObjectName,$actionName,$parentId=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName/describe/$parentId";
        
        return $this->resource($uri,$options);
    }

    public function sObjectQuickActionsDefaultValues($sObjectName,$actionName,$parentId=null,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/$sObjectName/quickActions/$actionName/defaultValues/$parentId";
        
        return $this->resource($uri,$options);
    }

    public function suggestedCaseArticle($caseSubject,$caseDescription,$articleLanguage='en',$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/Case/suggestedArticles?language=$articleLanguage&subject=$caseSubject&description=$caseDescription";
        
        return $this->resource($uri,$options);
    }

    public function suggestedCaseArticleById($caseId,$articleLanguage='en',$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/Case/$caseId/suggestedArticles?language=$articleLanguage";
        
        return $this->resource($uri,$options);
    }

    public function userPassword($userId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['sobjects'];
        $uri = "$resourceURI/User/$userId/password";
        
        return $this->resource($uri,$options);
    }

    //New Call
    public function appMenu($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['appMenu'];
        $uri = "$resourceURI/AppSwitcher/";
        
        return $this->resource($uri,$options);
    }

    public function appMenuSF1($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['appMenu'];
        $uri = "$resourceURI/Salesforce1/";
        
        return $this->resource($uri,$options);
    }

    public function flexiPage($flexiId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['flexiPage'];
        $uri = "$resourceURI/$flexiId";
        
        return $this->resource($uri,$options);
    }

    public function processApprovals($options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['process'];
        $uri = "$resourceURI/approvals/";
        
        return $this->resource($uri,$options);
    }

    public function processRules($sObjectName,$workflowRuleId,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['process'];
        $uri = "$resourceURI/rules/$sObjectName/$workflowRuleId";
        
        return $this->resource($uri,$options);
    }

    public function query($query,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['query'];
        $uri = "$resourceURI?q=".urlencode($query);

        return $this->resource($uri,$options);
    }

    public function queryExplain($query,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['query'];
        $uri = "$resourceURI?explain=" . urlencode($query);
        
        return $this->resource($uri,$options);
    }

    public function queryAll($query,$format,$options = array('method'=>'GET','format'=>'JSON')) {
        $resourceURI = $this->session->get('resources')['queryAll'];
        $uri = "$resourceURI?q=" . urlencode($query);
        
        return $this->resource($uri,$options);
    }

    public function quickActions($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['quickActions'];
        $uri = "$resourceURI";
        
        return $this->resource($uri,$options);
    }

    public function search($query,$options = array('method'=>'GET','format'=>'JSON'))
    {
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI?s=" . urlencode($query);
        
        return $this->resource($uri,$options);
    }

    public function searchScopeOrder($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/scopeOrder";

        return $this->resource($uri,$options);
    }

    public function searchLayouts($objectList,$options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/layout/?q=" . urlencode($objectList);

        return $this->resource($uri,$options);
    }

    public function searchSuggestedArticles($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/scopeOrder";

        return $this->resource($uri,$options);
    }

    public function searchSuggestedQueries($query, $language = 'en', $options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['search'];
        $uri = "$resourceURI/suggestSearchQueries?q=" . urlencode($query) . "&language=$language";

        return $this->resource($uri,$options);
    }

    public function recentlyViewed($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['recent'];
        $uri = $resourceURI;

        return $this->resource($uri,$options);

    }

    public function themes($options = array('method'=>'GET','format'=>'JSON')){
        $resourceURI = $this->session->get('resources')['theme'];
        $uri = $resourceURI;

        return $this->resource($uri,$options);
    }

}