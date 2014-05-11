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