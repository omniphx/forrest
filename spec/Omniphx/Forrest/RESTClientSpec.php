<?php

namespace spec\Omniphx\Forrest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Omniphx\Forrest\Interfaces\ResourceInterface;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

class RESTClientSpec extends ObjectBehavior
{

	function let(ResourceInterface $mockedResource, ClientInterface $mockedClient, SessionInterface $mockedSession, RedirectInterface $mockedRedirect, InputInterface $mockedInput)
	{
		$settings  = [
            'clientId'     => 'testingClientId',
            'clientSecret' => 'testingClientSecret',
            'callbackURI'  => 'callbackURL',
            'loginURL'     => 'https://login.salesforce.com',
            'optional'     => [
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => ''],
			'authRedirect' => 'redirectURL'];


        $mockedSession->get('resources')->willReturn([
            'sobjects'     => '/services/data/v30.0/sobjects', 
            'connect'      => '/services/data/v30.0/connect',
            'query'        => '/services/data/v30.0/query',
            'theme'        => '/services/data/v30.0/theme',
            'queryAll'     => '/services/data/v30.0/queryAll',
            'tooling'      => '/services/data/v30.0/tooling',
            'chatter'      => '/services/data/v30.0/chatter',
            'analytics'    => '/services/data/v30.0/analytics',
            'recent'       => '/services/data/v30.0/recent',
            'process'      => '/services/data/v30.0/process',
            'identity'     => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'flexiPage'    => '/services/data/v30.0/flexiPage',
            'search'       => '/services/data/v30.0/search',
            'quickActions' => '/services/data/v30.0/quickActions',
            'appMenu'      => '/services/data/v30.0/appMenu']);

        $mockedSession->get('version')->willReturn([
            'url' => 'resourceURLs']);

        $mockedSession->getToken()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX']);


		$this->beConstructedWith($mockedResource, $mockedClient,$mockedSession,$mockedRedirect,$mockedInput,$settings);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\RESTClient');
    }

    function it_should_authenticate(RedirectInterface $mockedRedirect)
    {
    	$mockedRedirect->to(Argument::any())->willReturn('redirectURL');
    	$this->authenticate()->shouldReturn('redirectURL');
    }

    function it_should_callback(
        ClientInterface $mockedClient,
        ResourceInterface $mockedResource,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse,
        InputInterface $mockedInput,
        RedirectInterface $mockedRedirect)
    {
        $mockedInput->get('code')->shouldBeCalled(1)->willReturn('this code');
        $mockedInput->get('state')->shouldBeCalled(1)->willReturn('this state');

        $mockedClient->post(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

    	$mockedResponse->json()->shouldBeCalled()->willReturn(array('version1','version2'));

        $mockedResource->request(Argument::type('string'),Argument::type('array'))->willReturn(array('version1','version2'));

        $mockedRedirect->to(Argument::type('string'))->willReturn('redirectURL');

        $mockedSession->get('version')->willReturn(array('url'=>'sampleURL'));
        $mockedSession->putToken(Argument::type('array'))->shouldBeCalled();
        $mockedSession->put('version',Argument::type('string'))->shouldBeCalled();
        $mockedSession->put('resources',Argument::type('array'))->shouldBeCalled();

    	$this->callback()->shouldReturn('redirectURL');
    }

    function it_should_get_the_user_info(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedClient->get(Argument::type('string'),Argument::type('array'))->willReturn($mockedResponse);
        $mockedResponse->json()->willReturn('The User!');

        $this->getUser()->shouldReturn('The User!');
    }

    function it_should_revoke_the_authentication_token(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RedirectInterface $mockedRedirect)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedClient->post(Argument::type('string'),Argument::type('array'))->shouldBeCalled();
        $mockedRedirect->to(Argument::type('string'))->shouldBeCalled()->willReturn('redirectURL');
        $this->revoke()->shouldReturn('redirectURL');
    }

    function it_should_return_the_versions_resource(ResourceInterface $mockedResource)
    {   
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versions');

        $this->versions()->shouldReturn('versions');
    }

    function it_should_return_resources_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('version')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    function it_should_return_limits_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    function it_should_return_sobject_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObject');

        $this->sObject('Account')->shouldReturn('sObject');
    }

    function it_should_return_describe_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('describe');

        $this->describe('Account')->shouldReturn('describe');
    }

    function it_should_return_sObjectDeleted_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectDeleted');

        $this->sObjectDeleted('Account','startDate','endDate')->shouldReturn('sObjectDeleted');
    }

    function it_should_return_sObjectUpdated_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectUpdated');

        $this->sObjectUpdated('Account','startDate','endDate')->shouldReturn('sObjectUpdated');
    }

    function it_should_return_sObjectById_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectById');

        $this->sObjectById('Account','id')->shouldReturn('sObjectById');
    }

    function it_should_return_sObjectByExternalId_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectByExternalId');

        $this->sObjectByExternalId('sObject','fieldName','fieldValue')->shouldReturn('sObjectByExternalId');
    }

    function it_should_return_sObjectApprovalLayout_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectApprovalLayout');

        $this->sObjectApprovalLayout('sObject','approvalProcess')->shouldReturn('sObjectApprovalLayout');
    }

    function it_should_return_sObjectCompactLayout_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectCompactLayout');

        $this->sObjectCompactLayout('sObject')->shouldReturn('sObjectCompactLayout');
    }

    function it_should_return_sObjectLayout_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectLayout');

        $this->sObjectLayout('sObject')->shouldReturn('sObjectLayout');
    }

    function it_should_return_sObjectQuickActions_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectQuickActions');

        $this->sObjectQuickActions('sObject','actionName')->shouldReturn('sObjectQuickActions');
    }

    function it_should_return_sObjectQuickActionsDescribe_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectQuickActionsDescribe');

        $this->sObjectQuickActionsDescribe('sObject','actionName','parentId')->shouldReturn('sObjectQuickActionsDescribe');
    }

    function it_should_return_sObjectQuickActionsDefaultValues_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('sObjectQuickActionsDefaultValues');

        $this->sObjectQuickActionsDefaultValues('sObject','actionName','parentId')->shouldReturn('sObjectQuickActionsDefaultValues');
    }

    function it_should_return_suggestedCaseArticle_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('suggestedCaseArticle');

        $this->suggestedCaseArticle('caseSubject','caseDescription')->shouldReturn('suggestedCaseArticle');
    }

    function it_should_return_suggestedCaseArticleById_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('suggestedCaseArticleById');

        $this->suggestedCaseArticleById('caseId')->shouldReturn('suggestedCaseArticleById');
    }

    function it_should_return_userPassword_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('userPassword');

        $this->userPassword('userId')->shouldReturn('userPassword');
    }

    function it_should_return_appMenu_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('appMenu');

        $this->appMenu()->shouldReturn('appMenu');
    }

    function it_should_return_appMenuOne_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('appMenuOne');

        $this->appMenuOne()->shouldReturn('appMenuOne');
    }

    function it_should_return_flexiPage_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('flexiPage');

        $this->flexiPage('flexiId')->shouldReturn('flexiPage');
    }

    function it_should_return_processApprovals_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('processApprovals');

        $this->processApprovals()->shouldReturn('processApprovals');
    }

    function it_should_return_processRules_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('processRules');

        $this->processRules('sObject','workflowRuleId')->shouldReturn('processRules');
    }

    function it_should_return_query_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    function it_should_return_queryExplain_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    function it_should_return_queryAll_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    function it_should_return_quickActions_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    function it_should_return_search_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    function it_should_return_searchScopeOrder_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->searchScopeOrder()->shouldReturn('searchScopeOrder');
    }

    function it_should_return_searchLayouts_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    function it_should_return_searchSuggestedArticles_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchSuggestedArticles');

        $this->searchSuggestedArticles()->shouldReturn('searchSuggestedArticles');
    }

    function it_should_return_searchSuggestedQueries_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->searchSuggestedQueries('query')->shouldReturn('searchSuggestedQueries');
    }

    function it_should_return_recentlyViewed_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('recentlyViewed');

        $this->recentlyViewed()->shouldReturn('recentlyViewed');
    }

    function it_should_return_themes_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled()->willReturn(['theme'=>'themeURI']);
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('themes');

        $this->themes()->shouldReturn('themes');
    }

    function letGo()
    {
        //Let go any resources
    }
}
