# Omniphx/Forrest, Force.com REST API Client for Laravel 4
[![Latest Stable Version](https://poser.pugx.org/omniphx/forrest/v/stable.svg)](https://packagist.org/packages/omniphx/forrest) [![Total Downloads](https://poser.pugx.org/omniphx/forrest/downloads.svg)](https://packagist.org/packages/omniphx/forrest) [![Latest Unstable Version](https://poser.pugx.org/omniphx/forrest/v/unstable.svg)](https://packagist.org/packages/omniphx/forrest) [![License](https://poser.pugx.org/omniphx/forrest/license.svg)](https://packagist.org/packages/omniphx/forrest) [![Build Status](https://travis-ci.org/omniphx/forrest.svg?branch=master)](https://travis-ci.org/omniphx/forrest)

Forrest is a Force.com REST API client for Laravel 4. It provides access to restricted Salesforce information via the Web Server OAuth authentication flow. While this package is built for Laravel, it has been decoupled so that it can be extended into any framework or vanilla PHP application.

## Installation
>Note: If you are upgrading to Version 1.0, be sure to re-publish your config file with `php artisan config:publish omniphx/forrest`.

Forrest can be installed through composer. Open your `composer.json` file and add the following to the `require` key:

    "omniphx/forrest": "1.*"

After adding the key, run `composer update` from the command line to install the package.

If you are using Laravel, add the service provider in your `app/config/app.php` file:

    'Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider'

Followed by the alias:

    'Forrest' => 'Omniphx\Forrest\Providers\Laravel\Facades\Forrest'

### Configuration
You will need a configuation file to add your creditials. Publish a config file using the `artisan` command:
```bash
php artisan config:publish omniphx/forrest
```
You can find the config file in: `app/config/omniphx/forrest/config.php`

Update your config file with your `consumerKey`, `consumerSecret`, `loginURL` and `callbackURI`.

Optionally, you can specify a redirect after the callback is complete with the `authRedirect` key. If you plan to overide the routes included in this package, you can ignore the redirect option.

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
Forrest will come with the following routes included in it's package:

##### WebServer routes
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
##### User Password routes
```php
Route::get('/authenticate', function()
{
    Forrest::authenticate();

    $url = Config::get('forrest::authRedirect');

    return Redirect::to($url);
});
```

>Note: If you would like to customize the authentication process, these routes can be overwritten in your `route.php` file. Feel free to call the routes anything you like, but the callback must match what is configured in your Connected App settings and config file.

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

```JavaScript
{
    "totalSize": 2,
    "done": true,
    "records": [
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000xxxxxxx"
            },
            "Id": "001i000000xxxxxx"
        },
        {
            "attributes": {
                "type": "Account",
                "url": "\/services\/data\/v30.0\/sobjects\/Account\/001i000000xxxxxxx"
            },
            "Id": "001i000000xxxxxx"
        }
    ]
}
```
>The default format is JSON, but it can be changed to [XML](#xml-format):

### Create a new record
Create records with the POST method by passing it to the `method` key. Likewise, the body of the request should be passed to `body` key.

```php
$body = ['Name' => 'New Account'];
Forrest::sobjects('Account',[
    'method' => 'post',
    'body'   => $body]);
```

### Update a record
Update a record with the PATCH method.

```php
$body = ['Phone' => '555-555-5555'];
Forrest::sobjects('Account/001i000000xxxxxxx',[
    'method' => 'patch',
    'body'   => $body]);
```

### Delete a record
Delete a record with the DELETE method.

```php
Forrest::sobjects('Account/001i000000xxxxxxx', ['method' => 'delete');
```

### XML format
Change the request/response format to XML with the `format` key.

```php
Forrest::describe('Account',['format'=>'xml']);
```

## API Requests

With the exception of the `search` and `query` resources, all requests are made dynamically using method overloading. The available resources are stored in the user's session when they are authenticated.

First, determine which resources you have access to by calling:
```php
Session::get('resources');
```
or
```php
Forrest::resources();
```
Either will return the following array:
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

Resources can be extended by passing the additional parameters into the first argument:
```php
Forrest::sobjects('Account/describe/approvalLayouts/');
```

You can also add option methods and formats to calls:
```php
Forrest::theme(['format'=>'xml']);
```

### Additional API Requests

#### Refresh
If a refresh token is set, the server can refresh the access token on the user's behalf. Refresh tokens are only provided in the Web Server flows.
```php
Forrest::refresh();
```
>If you need a refresh token, be sure to specify this under `access scope` in your [Connected App](#setting-up-connected-app). You can also specify this in your configuration file by adding `'scope' => 'full refresh_token'`. Setting scope access in the config file is optional, the default scope access is determined by your Salesforce org.

#### Revoke
This will revoke the authorization token. The session will store a token, but it will become invalid.
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
    'parameters'=>[
        'channel'=>'App',
        'publishStatus' => 'Draft']]);
```

#### Suggested Queries
Returns a list of suggested searches based on a search text query. Matches search queries that other users have performed in Salesforce Knowledge. Like Suggest Articles, additional parameters can be passed into the second argument with the `parameters` key. Available for API version 30.0 or later.

```php
Forrest::suggestedQueries('app, [
    'parameters'=>['foo'=>'bar']]);
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
    'method'=>'post',
    'body'=>['foo'=>'bar'],
    'parameters'=>['flim'=>'flam']]);
```
> Read [Creating REST APIs using Apex REST](https://developer.salesforce.com/page/Creating_REST_APIs_using_Apex_REST) for more information.