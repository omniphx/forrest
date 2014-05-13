<?php 

return array(

	/**
	 * Enter your OAuth creditials:
	 */
	'clientId' => '<insert your client Id>',
	'clientSecret' => '<insert your client secret>',
	'redirectURI' => '<insert your callback URL>',
	'loginURI' => 'https://login.salesforce.com',

	/**
	 * Display can be page, popup, touch or mobile
	 * Immediate determines whether the user should be prompted for login and approval. Values are either true or false. Default is false.
	 * State specifies any additional URL-encoded state data to be returned in the callback URL after approval.
	 * Scope specifies what data your application can access. For more details see: https://help.salesforce.com/HTViewHelpDoc?id=remoteaccess_oauth_scopes.htm&language=en_US
	 */
	'optional' => [
		'display' => 'page',
		'immediate' => 'false',
		'state' => '',
		'scope' => ''],

	/**
	 * After authentication token is received, redirect to:
	 */
	'authRedirect' => '<your homepage>',

	/**
	 * Default settings for resource requests.
	 */
	'defaults' => [
		'method' => 'get',
		'format' => 'json']

);