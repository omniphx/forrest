# Omniphx/Forrest, Force.com REST API Client for Laravel 5
[![Laravel](https://img.shields.io/badge/Laravel-5.0-orange.svg?style=flat-square)](http://laravel.com)
[![Latest Stable Version](https://img.shields.io/packagist/v/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)
[![Total Downloads](https://img.shields.io/packagist/dt/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)
[![Build Status](https://img.shields.io/travis/omniphx/forrest.svg?style=flat-square)](https://travis-ci.org/omniphx/forrest)
[![License](https://img.shields.io/packagist/l/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)

Salesforce/Force.com REST API client for Laravel. It provides access to restricted Salesforce information via Oauth 2.0. REST is a lightweight alternative to the SOAP API and is useful for mobile users.

 While this package is built for Laravel, it has been decoupled so that it can be extended into any framework or vanilla PHP application.

## Installation
>If you are upgrading to Version 2.0, be sure to re-publish your config file.

Forrest can be installed through composer. Open your `composer.json` file and add the following to the `require` key:

    "omniphx/forrest": "2.0.*@dev"

Next run `composer update` from the command line to install the package.

If you are using Laravel, add the service provider to your `bootstrap/app.php` file:

    'Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider'

Followed by the alias:

    'Forrest' => 'Omniphx\Forrest\Providers\Laravel\Facades\Forrest'

### Configuration
You will need a configuration file to add your credentials. Publish a config file using the `artisan` command:
```bash
php artisan vendor:publish
```
You can find the config file in: `config/forrest.php`

After you have set up am connected app (see below), update your config file with a `consumerKey`, `consumerSecret`, `loginURL` and `callbackURI`.

## Getting Started
### Setting up a Connected App
1. Log into to your Salesforce org
2. Click on Setup in the upper right-hand menu
3. Under Build click `Create > Apps`
4. Scroll to the bottom and click `New` under Connected Apps.
5. Enter the following details for the remote application:
    * Connected App Name
    * API Name
    * Contact Email
    * Enable OAuth Settings under the API dropdown
    * Callback URL
    * Select access scope (If you need a refresh token, specify it here)
6. Click `Save`

After saving, you will now be given a Consumer Key and Consumer Secret. Add those to your config file.

### Setup
Forrest will come with the following routes included in it's package.

>Feel free to overwrite these routes in `routes.php`. They can be called anything you like, but the callback must match what is configured in your config file and Connected App settings for your Salesforce org.

##### Web Server authentication flow
```php
Route::get('/authenticate', function()
{
    return Forrest::authenticate();
});

Route::get('/callback', function()
{
    Forrest::callback();

    $url = Config::get('forrest::authRedirect');

    return Redirect::to($url);
});
```
##### Username-Password authentication flow
```php
Route::get('/authenticate', function()
{
    Forrest::authenticate();

    $url = Config::get('forrest::authRedirect');

    return Redirect::to($url);
});
```
>With the Username Password flow, you can directly authenticate with the `Forrest::authenticate()` method. The routing provides backwards compatability with the Web Server flow if you switch between the two.

#### Custom login urls
Sometimes users will need to connect to a sandbox or custom url. To do this, simply pass the url as an argument for the authenticatation method:
```php
Route::get('/authenticate', function()
{
    $loginURL = 'https://test.salesforce.com';

    return Forrest::authenticate($loginURL);
});
```

## Usage
### Query a record
The callback function will store an encrypted authentication token in the user's session which can now be used to make API requests such as:
```php
Forrest::query('SELECT Id FROM Account');
```
Result:
```JavaScript
{
    "totalSize": 2,
    "done": true,
    "records": [
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000xxx"
            },
            "Id": "001i000000xxx"
        },
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000xxx"
            },
            "Id": "001i000000xxx"
        }
    ]
}
```
>The default format is JSON, but it can be changed to [XML](#xml-format)

If you are querying more than 2000 records, you response will include:
```
"nextRecordsUrl" : "/services/data/v20.0/query/01gD0000002HU6KIAW-2000"
```

Simply, call `Forrest::next($nextRecordsUrl)` to return the next 2000 records.

### Create a new record
Records can be created using the following format.
```php
$body = ['Name' => 'New Account'];
Forrest::sobjects('Account',[
    'method' => 'post',
    'body'   => $body]);
```

### Update a record
Update a record with the PUT method.

```php
$body = [
    'Name'  => 'Acme'
    'Phone' => '555-555-5555'];

Forrest::sobjects('Account/001i000000xxx',[
    'method' => 'put',
    'body'   => $body]);
```

### Upsert a record
Update a record with the PATCH method and if the external Id doesn't exist, it will insert a new record.

```php
$body = [
    'Phone' => '555-555-5555',
    'External_Id__c' => 'XYZ1234'];

Forrest::sobjects('Account',[
    'method' => 'patch',
    'body'   => $body]);
```

### Delete a record
Delete a record with the DELETE method.

```php
Forrest::sobjects('Account/001i000000xxx', ['method' => 'delete']);
```

### XML format
Change the request/response format to XML with the `format` key or make it default in your config file.

```php
Forrest::describe('Account',['format'=>'xml']);
```

## API Requests

With the exception of the `search` and `query` resources, all requests are made dynamically using method overloading. The available resources are stored in the user's session when they are authenticated.

First, determine which resources you have access to by calling:
```php
Forrest::resources();
```
This is a sample returned array:
```php
Array
(
    [sobjects] => /services/data/v30.0/sobjects
    [connect] => /services/data/v30.0/connect
    [query] => /services/data/v30.0/query
    [theme] => /services/data/v30.0/theme
    [queryAll] => /services/data/v30.0/queryAll
    [tooling] => /services/data/v30.0/tooling
    [chatter] => /services/data/v30.0/chatter
    [analytics] => /services/data/v30.0/analytics
    [recent] => /services/data/v30.0/recent
    [process] => /services/data/v30.0/process
    [identity] => https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000aaaaAAA
    [flexiPage] => /services/data/v30.0/flexiPage
    [search] => /services/data/v30.0/search
    [quickActions] => /services/data/v30.0/quickActions
    [appMenu] => /services/data/v30.0/appMenu
)
```
Next, you can call resources by referring to the specified key. For instance:
```php
Forrest::theme();
```
or
```php
Forrest::appMenu();
```

Resource urls can be extended by passing additional parameters into the first argument:
```php
Forrest::sobjects('Account/describe/approvalLayouts/');
```

You can also add optional parameters to requests:
```php
Forrest::theme(['format'=>'xml']);
```

### Additional API Requests

#### Refresh
If a refresh token is set, the server can refresh the access token on the user's behalf. Refresh tokens are only provided if you use the Web Server flow.
```php
Forrest::refresh();
```
>If you need a refresh token, be sure to specify this under `access scope` in your [Connected App](#setting-up-connected-app). You can also specify this in your configuration file by adding `'scope' => 'full refresh_token'`. Setting scope access in the config file is optional, the default scope access is determined by your Salesforce org.

#### Revoke
This will revoke the authorization token. The session will continue to store a token, but it will become invalid.
```php
Forrest::revoke();
```

#### Versions
Returns all currently supported versions. Includes the verison, label and link to each version's root:
```php
Forrest::versions();
```

#### Resources
Returns list of available resources for a specific API.
```php
Forrest::resources();
```

#### Identity
Returns information about the logged-in user.
```php
Forrest::identity();
```

#### Limits
Lists information about organizational limits. Available for API version 29.0 and later.
>Note: this call is part of a pilot program and may not be availabe to all orgs without a request to Salesforce.
```php
Forrest::limits();
```

#### Describe
Describes all global objects availabe in the organization.
```php
Forrest::describe();
```

#### Query
Returns results for a specified SOQL query.
```php
Forrest::query('SELECT Id FROM Account');
```

#### Query Explain
Returns details of how Salesforce will process your query. Available for API verison 30.0 or later.
```php
Forrest::queryExplain('SELECT Id FROM Account');
```

#### Query All
Returns results for a specified SOQL query, but will also inlcude deleted records.
```php
Forrest::queryAll('SELECT Id FROM Account');
```

#### Search
Returns the specified SOSL query
```php
Forrest::search('Find {foo}');
```

#### Scope Order
Global search keeps track of which objects the user interacts with and arranges them when the user performs a global search. This call will return this ordered list of objects.
```php
Forrest::scopeOrder();
```

#### Search Layouts
Returns the search results layout for the objects in the query string. List should be formatted as a string, but delimited by a comma.
```php
Forrest::searchLayouts('Account,Contact,Lead');
```

#### Suggested Articles
Returns a list of Salesforce Knowledge articles based on the a search query. Pass additional parameters into the second argument. Available for API verison 30.0 or later.

> Salesforce Knowledge must be enabled for this to work.

```php
Forrest::suggestedArticles('foo', [
    'parameters' => [
        'channel' => 'App',
        'publishStatus' => 'Draft']]);
```

#### Suggested Queries
Returns a list of suggested searches based on a search text query. Matches search queries that other users have performed in Salesforce Knowledge. Like Suggest Articles, additional parameters can be passed into the second argument with the `parameters` key. Available for API version 30.0 or later.

```php
Forrest::suggestedQueries('app, [
    'parameters' => ['foo' => 'bar']]);
```
For a complete listing of API resources, refer to the [Force.com REST API Developer's Guide](http://www.salesforce.com/us/developer/docs/api_rest/api_rest.pdf)

### Custom Apex endpoints
If you create a custom API using Apex, you can use the `custom()` method for consuming them.
```php
Forrest::custom('/myEndpoint');
```
Additional options and parameters can be passed in like this:
```php
Forrest::custom('/myEndpoint', [
    'method' => 'post',
    'body' => ['foo' => 'bar'],
    'parameters' => ['flim' => 'flam']]);
```
> Read [Creating REST APIs using Apex REST](https://developer.salesforce.com/page/Creating_REST_APIs_using_Apex_REST) for more information.

### Raw Requests
You can always make raw requests if one of the above methods doesn't meet your needs (which is unlikely)
```php
Forrest::get('/services/data/v20.0/endpoint');
Forrest::head('/services/data/v20.0/endpoint');
Forrest::post('/services/data/v20.0/endpoint', ['my'=>'param']);
Forrest::put('/services/data/v20.0/endpoint', ['my'=>'param']);
Forrest::patch('/services/data/v20.0/endpoint', ['my'=>'param']);
Forrest::delete('/services/data/v20.0/endpoint');
```

### Raw response output
By default, this package will return the body of a response as either a deserialized JSON object or a SimpleXMLElement object.

There might be times, when you would rather handle this differently. To do this, simply use any format other than 'json' or 'xml' and the code will return a Guzzle response object.

```php
$response = Forrest::sobjects($resource, ['format'=> 'none']);
$content = (string) $response->getBody(); // Guzzle response
```

For more information about Guzzle responses, see their [documentation](http://guzzle.readthedocs.org/en/latest/http-messages.html#responses).

### Event Listener

```php
Event::listen('forrest.response', function($request, $response) {
    dd((string) $response);
});
```