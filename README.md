# Omniphx/Forrest, Force.com REST API Client for Laravel 4

[![Build Status](https://travis-ci.org/omniphx/forrest.svg?branch=master)](https://travis-ci.org/omniphx/forrest)

Forrest is a Force.com REST API client for Laravel 4. Provides access to restricted Salesforce information via the Web Server OAuth Authentication Flow. This code is decoupled so that it can be extended into any framework or vanilla PHP application.

## Installation
Forrest can be installed through composer. Open your `composer.json` file and add the following to the `require` key:

	"omniphx/forrest": "dev-master"

After adding the key, run composer update from the command line to install the package:

```bash
composer update
```

Add the service provider to the `providers` array in your `app/config/app.php` file.

	'Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider'

Add the alias to the `aliases` array

	'Forrest' => 'Omniphx\Forrest\Providers\Laravel\Facades\Forrest'

## Getting Started
### Setting up a Connected App
1. Log into to your Salesforce org
2. Click on Setup in the upper right-hand menu
3. Under Build click Create -> Apps
4. Scroll to the bottom and click New under Connected Apps.
5. Enter the following details for the remote application:
	* Connected App Name
	* API Name
	* Contact Email
	* Enable OAuth Settings under the API dropdown
	* Callback URL
	* Select access scope (Full is recommended)
6. Click Save

After saving, you will now be given a Consumer Key and Consumer Secret.

### Configuration
Run the following `artisan` command to publish your config file:

```bash
php artisan config:publish omniphx/forrest
```

The config file can now be found in: `app/config/omniphx/forrest/config.php`

Update your config file with your `clientId`, `clientSecret`, `loginURL` and `callbackURI` that you plan on using.

Additionally, you can specify a `authRedirect` that will redirect the user once the callback is complete.

### Setup
Create the following Routes to complete the Web Server OAuth Authentication Flow:

```php
Route::get('/authenticate', function(){
	return Forrest::authenticate();
});

Route::get('/callback', function(){
	return Forrest::callback();
});
```

>Note: The routes can be called anything you like, but the callback must match what is configured in the Connected App settings and Laravel config file.

## Usage
### Query a record
The callback function will store an encrypted authentication token in the user's session which can now be used to make API requests such as:

```php
Forrest::query('SELECT Id FROM Account');
```

The default output format will be JSON, but it can also be changed to [XML](#xml-format):

```JavaScript
{
    "totalSize": 2,
    "done": true,
    "records": [
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000FO9zgAAD"
            },
            "Id": "001i000000FO9zgAAD"
        },
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000r0eNtAAI"
            },
            "Id": "001i000000r0eNtAAI"
        }
    ]
}
```

### Create a new record
Create records with the POST method by passing it to the `method` key. Likewise, the body of the request should be passed to `body` key.

```php
$body = ['Name' => 'New Account'];
Forrest::sobject('Account',[
	'method' => 'post',
	'body'   => $body]);
```

### Update a record
Update a record with the PATCH method.

```php
$body = ['Phone' => '555-555-5555'];
Forrest::sobject('Account/001i000000FO9zgAAD',[
	'method' => 'patch',
	'body'   => $body]);
```

### Delete a record
Delete a record with the DELETE method.

```php
$body = ['Phone' => '555-555-5555'];
Forrest::sobject('Account/001i000000FO9zgAAD',[
	'method' => 'delete',
	'body'   => $body]);
```

### XML format
Change the request/response format to XML with the `format` key.

```php
Forrest::describe('Account',['format'=>'xml']);
```

### API Requests

#### Versions
Returns all currently supported versions. Includes the verison, label and link to each version's root:

    Forrest::verions();

#### Resources
Returns list of all available resources for a specified API verision.

    Forrest::resources();

#### Identity
Returns information about the logged-in user.

    Forrest::identity();

#### Limits
Lists information about organizational limits. Available for API version 29.0 and later.

    Forrest::limits();

#### Query
Returns results for a specified SOQL query.

    Forrest::query('SELECT Id FROM Account');

#### Query Explain
Returns details of how Salesforce will process your query. Available for API verison 30.0 or later.

    Forrest::queryExplain('SELECT Id FROM Account');

#### Query All
Returns results for a specified SOQL query, but will also inlcude deleted records.

    Forrest::queryExplain('SELECT Id FROM Account');

#### Search

#### Scope Order

#### Search Layouts

#### Suggested Articles
Returns a list of Salesforce Knowledge articles based on teh 

#### Suggested Queries
Returns a list of suggested searches based on a search text query. Matches search queries that other users have performed in Salesforce Knowledge. Available for API version 30.0 or later.

    Forrest::suggestedQueries('foo',['publishStatus'=>'Online']);

### Additional API Requests
For a complete listing of resources see the REST API Guide in [Additional Resources](#additional-resources)

## Additional Resources
[Force.com REST API Developer's Guide](http://www.salesforce.com/us/developer/docs/api_rest/api_rest.pdf)

[Building a Ruby App in Heroku tutorial](http://www.salesforce.com/us/developer/docs/integration_workbook/integration_workbook.pdf)