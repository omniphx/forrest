# Omniphx/Forrest, Force.com REST API Client for Laravel 4

[![Build Status](https://travis-ci.org/omniphx/forrest.svg?branch=master)](https://travis-ci.org/omniphx/forrest)

Forrest is a Force.com REST API client for Laravel 4. Provides access to restricted Salesforce information via the Web Server OAuth Authentication Flow. While this package is built for Laravel, it has been decoupled so that it can be extended into any framework or vanilla PHP application.

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
Publish your config file using the `artisan` command:

```bash
php artisan config:publish omniphx/forrest
```

The config file is published in: `app/config/omniphx/forrest/config.php`

Update your config file with your `clientId`, `clientSecret`, `loginURL` and `callbackURI`.

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

>Note: The routes can be called anything you like, but the callback must match what is configured in the Connected App settings and the published config file.

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

```php
Forrest::verions();
```

#### Resources
Returns list of all available resources for a specified API verision.

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
Forrest::queryExplain('SELECT Id FROM Account');
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
//Optional parameters
$parameters = [
    'channel'       => 'App',
    'publishStatus' => 'Draft'];

Forrest::suggestedArticles('foo', ['parameters'=> $parameters]);
```
<!-- 
#### Suggested Queries
Returns a list of suggested searches based on a search text query. Matches search queries that other users have performed in Salesforce Knowledge. Like Suggest Articles, additional parameters can be passed into the second argument with the `parameters` key. Available for API version 30.0 or later.

```php
Forrest::suggestedQueries('foo');
``` -->

### Additional API Requests

The above resources were explicitly defined because `search` and `query` resources require URL encoding. Other resources such as `sobject` and `describe` can be called dynamically using method overloading.

First, determine which resources you have access to:

```php
Forrest::resources();
```

This returns a list of available resources:
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
Next, you can call resource simply by referring to the key in the resources array:
```php
Forrest::theme();
```
or
```php
Forrest::appMenu();
```

Resources can be extended by passing the extension into the first argument:
```php
Forrest::sobjects('Account/describe/approvalLayouts/');
```

You can also add option methods and formats to calls:
```php
Forrest::theme(['format'=>'xml']);
```

For a complete listing of API resources, refer to the [Force.com REST API Developer's Guide](http://www.salesforce.com/us/developer/docs/api_rest/api_rest.pdf)