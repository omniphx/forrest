## Forrest, Force.com REST API Client for Laravel 4

[![Build Status](https://travis-ci.org/omniphx/forrest.svg?branch=master)](https://travis-ci.org/omniphx/forrest)

This Salesforce.com REST API Client is built to provide access to restricted Salesforce information via OAuth 2.0 using the Web Server OAuth Authentication Flow. This code is decoupled so that it can be extended into any Frameworks or vanilla PHP application.

### Installation
Forrest can be installed through composer. Open your `composer.json` file and add the following to the 'require' key:

```javascript
"omniphx/forrest": "dev-master"
```

After adding the key, run composer update from the command line to install the package:

```bash
composer update
```

Add the service provider to the `providers` array in your `app/config/app.php` file.

```php
'Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider'
```

Add the alias to the `aliases` array

```php
'Forrest' => 'Omniphx\Forrest\Providers\Laravel\Facades\Forrest'
```

### Getting Started

First, you will need to set up a Connected App in your Salesforce Org. You can do this by:

1. Log into to your Salesforce org
2. Click on Setup in the upper right-hand menu
3. Under Build click Create -> Apps
4. Scroll to the bottom and click New under Connected Apps. Enter the following details for the remote application:
..1. Connected App Name
..2. API Name
..3. Contact Email
..4. Select Enable OAuth Settings under the API dropdown
..5. Callback URL
..6. Select access scope (Full is recommended)
..7. Click Save

After saving, you will now be given a Consumer Key and Consumer Secret.

Next, you will need to store these keys into your config file.

Run the following from the command line:

```bash
php artisan config:publish omniphx/forrest
```

Now the config file will be availabe to you in `app/config/omniphx/forrest/config.php`. Update the config file with your Consumer Key,Consumer Secret, Login URL, Callback URL and Redirect URL (for after your app has become authenticated).

Next you will need to set your Routes to complete the Web Server OAuth Authentication Flow:

```php
Route::get('/authenticate', function(){
	return Forrest::authenticate();
});

Route::get('/callback', function(){
	return Forrest::callback();
});
```

The callback function will store an encrypted authentication token in the user's session which can now be used to make API requests such as:

```php
Forrest::query('SELECT Id FROM Account');
```

### API Requests




### Additional Resources

[Building an App in Heroku tutorial (Ruby, but the concept can be transferable)](http://www.salesforce.com/us/developer/docs/integration_workbook/integration_workbook.pdf)

[Force.com REST API Developer's Guide](https://www.salesforce.com/us/developer/docs/api_rest/)

[Force.com REST API Developer's Guide (pdf)](http://www.salesforce.com/us/developer/docs/api_rest/api_rest.pdf)

### Future plans

Will release to Packagist when stable. Version 1.0 will support API Version 30.0 / Spring 14.