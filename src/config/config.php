<?php 

/**
 * Configuration options for Salesforce Oath settings and REST API defaults.
 */
return array(

	/**
	 * Options include WebServer or UserPassword
	 */
	'authentication' => 'WebServer',

	/**
	 * Enter your credentials
	 * Username and Password are only necessary for UserPassword flow.
     * Likewise, callbackURI is only necessary for WebServer flow.
	 */
	'creditials' => array(
		//Required:
		'consumerKey'    => '',
		'consumerSecret' => '',
		'callbackURI'    => '',
		'loginURL'       => 'https://login.salesforce.com',

		//UserPassword flow only:
		'username'       => '',
		'password'       => '',
	),

	/**
	 * These are optional authentication parameters that can be specified for the WebServer flow.
	 * https://help.salesforce.com/apex/HTViewHelpDoc?id=remoteaccess_oauth_web_server_flow.htm&language=en_US
	 */
	'parameters' => array(
		'display'   => '',
		'immediate' => false,
		'state'     => '',
		'scope'     => '',
		'prompt'	=> '',
	),

	/**
	 * Default settings for resource requests.
	 * Format can be 'json', 'xml' or 'none'
	 * Compression can be set to 'gzip' or 'deflate'
	 */
	'defaults' => array(
		'method'          => 'get',
		'format'          => 'json',
		'compression'     => false,
		'compressionType' => 'gzip',
	),

	/**
	 * Where do you want to store access tokens fetched from Salesforce
	 */
	'storage' => array(
		'type'      => 'session', // 'session' or 'cache' are the two options
		'path'      => 'forrest_', // unique storage path to avoid collisions
		'expire_in' => 60, // number of minutes to expire cache/session
 	),

	/**
	 * If you'd like to specify an API version manually it can be done here.
	 * Format looks like '32.0'
	 */
	'version' => '',

	/**
	 * An optional redirect URL can be specified after the authentication is complete.
	 * If you override the routes included in this package, the authentication will return void.
	*/
	'authRedirect' => '/',

	/**
	 * Optional (and not recommended) if you need to override the instance_url returned from Saleforce
	 */
	'instanceURL' => '',

	/**
	 * Language
	 */
	'language' => 'en_US'

);