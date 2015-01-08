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
	 * Enter your creditials
	 * Username and Password are only neccessary for UserPassword flow. Likewise, callbackURI is only necessary for WebServer flow.
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
	 * Format can be 'json' or 'xml'
	 * Compression can be set to 'gzip' or 'deflate'
	 */
	'defaults' => array(
		'method'          => 'get',
		'format'          => 'json',
		'compression'     => false,
		'compressionType' => 'gzip',
	),

	/**
	 * If you'd like to specify an API version manually it can be done here.
	 * Format looks like '32.0'
	 */
	'version' => '',

	/**
	 * An optional redirect URL can be specified after the authencation is complete.
	 * If you override the routes included in this package, the authentication will return void.
	*/
	'authRedirect' => '/',

	/**
	 * Optional (and not recomended) if you need to override the instance_url returned from Saleforce
	 */
	'instanceURL' => '',

	/**
	 * Langauge
	 */
	'language' => 'en_US'

);