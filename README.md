
# Salesforce REST API Client for Laravel <img align="right" src="https://raw.githubusercontent.com/omniphx/images/master/Forrest.png">

[![Laravel](https://img.shields.io/badge/Laravel-6.0-orange.svg?style=flat-square)](http://laravel.com)
[![Latest Stable Version](https://img.shields.io/packagist/v/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)
[![Total Downloads](https://img.shields.io/packagist/dt/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)
[![License](https://img.shields.io/packagist/l/omniphx/forrest.svg?style=flat-square)](https://packagist.org/packages/omniphx/forrest)
[![Build Status](https://img.shields.io/travis/omniphx/forrest.svg?style=flat-square)](https://travis-ci.org/omniphx/forrest)




Salesforce/Force.com REST API client for Laravel. While it acts as more of a wrapper of the API methods, it should provide you with all the flexibility you will need to interact with the REST service.

Currently the only support is for Laravel and Lumen.

Interested in Eloquent Salesforce Models? Check out [@roblesterjr04](https://github.com/roblesterjr04)'s [EloquentSalesForce](https://github.com/roblesterjr04/EloquentSalesForce) project that utilizes Forrest as it's API layer.

## Installation
>If you are upgrading to Version 2.0, be sure to re-publish your config file.

Forrest can be installed through composer. Open your `composer.json` file and add the following to the `require` key:
```php
"omniphx/forrest": "2.*"
```
Next run `composer update` from the command line to install the package.

### Laravel Installation

The package will automatically register the service provider and `Forrest` alias for Laravel `>=5.5`. For earlier versions, add the service provider and alias to your `config/app.php` file:

```php
Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider::class
'Forrest' => Omniphx\Forrest\Providers\Laravel\Facades\Forrest::class
```

>For Laravel 4, add `Omniphx\Forrest\Providers\Laravel4\ForrestServiceProvider` in `app/config/app.php`. Alias will remain the same.

### Lumen Installation

```php
class_alias('Omniphx\Forrest\Providers\Laravel\Facades\Forrest', 'Forrest');
$app->register(Omniphx\Forrest\Providers\Lumen\ForrestServiceProvider::class);
$app->configure('forrest');
$app->withFacades();
```
Then you'll utilize the Lumen service provider by registering it in the `bootstrap/app.php` file.

### Configuration
You will need a configuration file to add your credentials. Publish a config file using the `artisan` command:
```bash
php artisan vendor:publish
```
This will publish a `config/forrest.php` file that can switch between authentication types as well as other settings.

After adding the config file, update your `.env` to include the following values (details for getting a consumer key and secret are outlined below):
```
CONSUMER_KEY=123455
CONSUMER_SECRET=ABCDEF
CALLBACK_URI=https://test.app/callback
LOGIN_URL=https://login.salesforce.com
USERNAME=mattjmitchener@gmail.com
PASSWORD=password123
```

>For Lumen, you should copy the config file from `src/config/config.php` and add it to a `forrest.php` configuration file under a config directory in the root of your application.

>For Laravel 4, run `php artisan config:publish omniphx/forrest` which create `app/config/omniphx/forrest/config.php`

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

After saving, you will now be given a Consumer Key and Consumer Secret. Update your config file with values for `consumerKey`, `consumerSecret`, `loginURL` and `callbackURI`.

### Setup
Creating authentication routes

##### Web Server authentication flow
```php
Route::get('/authenticate', function()
{
    return Forrest::authenticate();
});

Route::get('/callback', function()
{
    Forrest::callback();

    return Redirect::to('/');
});
```
##### Username-Password authentication flow
With the Username Password flow, you can directly authenticate with the `Forrest::authenticate()` method.

>To use this authentication you must add your username, and password to the config file. Security token might need to be ammended to your password unless your IP address is whitelisted.

```php
Route::get('/authenticate', function()
{
    Forrest::authenticate();
    return Redirect::to('/');
});
```
##### SOAP authentication flow
(When you cannot create a connected App in Salesforce)

1. Salesforce allows individual logins via a SOAP Login
2. The Bearer access token returned from the SOAP login can be used similar to Oauth key
3. Update your config file and set the `authentication` value to `UserPasswordSoap`
4. Update your config file with values for `loginURL`, `username`, and `password`.
With the Username Password SOAP flow, you can directly authenticate with the `Forrest::authenticate()` method.

>To use this authentication you can add your username, and password to the config file. Security token might need to be ammended to your password unless your IP address is whitelisted.

```php
Route::get('/authenticate', function()
{
    Forrest::authenticate();
    return Redirect::to('/');
});
```

If your application requires logging in to salesforce as different users, you can alternatively pass in the login url, username, and password to the `Forrest::authenticateUser()` method.

>Security token might need to be ammended to your password unless your IP address is whitelisted.

```php
Route::Post('/authenticate', function(Request $request)
{
    Forrest::authenticateUser('https://login.salesforce.com',$request->username, $request->password);
    return Redirect::to('/');
});
```



#### Custom login urls
Sometimes users will need to connect to a sandbox or custom url. To do this, simply pass the url as an argument for the authenticatation method:
```php
Route::get('/authenticate', function()
{
    $loginURL = 'https://test.salesforce.com';

    return Forrest::authenticate($loginURL);
});
```
>Note: You can specify a default login URL in your config file.

## Basic usage
After authentication, your app will store an encrypted authentication token which can be used to make API requests.
### Query a record
```php
Forrest::query('SELECT Id FROM Account');
```
Sample result:
```JSON
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
If you are querying more than 2000 records, you response will include:
```JSON
{
    "nextRecordsUrl" : "/services/data/v20.0/query/01gD0000002HU6KIAW-2000"
}
```

Simply, call `Forrest::next($nextRecordsUrl)` to return the next 2000 records.

### Create a new record
Records can be created using the following format.
```php
Forrest::sobjects('Account',[
    'method' => 'post',
    'body'   => ['Name' => 'Dunder Mifflin']
]);
```

### Update a record
Update a record with the PUT method.

```php
Forrest::sobjects('Account/001i000000xxx',[
    'method' => 'put',
    'body'   => [
        'Name'  => 'Dunder Mifflin',
        'Phone' => '555-555-5555'
    ]
]);
```

### Upsert a record
Update a record with the PATCH method and if the external Id doesn't exist, it will insert a new record.

```php
$externalId = 'XYZ1234';

Forrest::sobjects('Account/External_Id__c/' + $externalId, [
    'method' => 'patch',
    'body'   => [
        'Name'  => 'Dunder Mifflin',
        'Phone' => '555-555-5555'
    ]
]);
```

### Delete a record
Delete a record with the DELETE method.

```php
Forrest::sobjects('Account/001i000000xxx', ['method' => 'delete']);
```

### Setting headers
Sometimes you need the ability to set custom headers (e.g., creating a Lead with an assignment rule)
```php
Forrest::sobjects('Lead',[
    'method' => 'post',
    'body' => [
        'Company' => 'Dunder Mifflin',
        'LastName' => 'Scott'
    ],
    'headers' => [
        'Sforce-Auto-Assign' => '01Q1N000000yMQZUA2'
    ]
]);
```
>To disable assignment rules, use `'Sforce-Auto-Assign' => 'false'`

### XML format
Change the request/response format to XML with the `format` key or make it default in your config file.

```php
Forrest::sobjects('Account',['format'=>'xml']);
```

## API Requests

With the exception of the `search` and `query` resources, all resources are requested dynamically using method overloading.

You can determine which resources you have access to by calling with the resource method
```php
Forrest::resources();
```
This sample output shows the resourses available to call via the API:
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
From the list above, I can call resources by referring to the specified key.
```php
Forrest::theme();
```
Or...
```php
Forrest::appMenu();
```

Additional resource url parameters can also be passed in
```php
Forrest::sobjects('Account/describe/approvalLayouts/');
```

As well as new formatting options, headers or other configurations
```php
Forrest::theme(['format'=>'xml']);
```

### Upsert multiple records (Bulk API 2.0)

Bulk API requests are especially handy when you need to quickly load large amounts of data into your Salesforce org. The key differences is that it requires at least three separate requests (Create, Add, Close), and the data being loaded is sent in a CSV format.

To illustrate, following are three requests to upsert a CSV of `Contacts` records.

#### Create

Create a bulk upload job with the POST method, the body contains the following job properties:

- `object` is the type of objects you're loading (they must all be the same type per job)
- `externalIdFieldName` is the external ID, if this exists it'll update and if it doesn't a new record will be inserted. Only needed for upsert operations.
- `contentType` is CSV, this is currently the only valid value.
- `operation` is set to `upsert` to both add and update records.

We're storing the response in `$bulkJob` in order to reference the unique Job ID in the Add and Close requests below.

> See [Create a Job](https://developer.salesforce.com/docs/atlas.en-us.api_bulk_v2.meta/api_bulk_v2/create_job.htm) for the full list of options available here.

```php
$bulkJob = Forrest::jobs('ingest', [
    'method' => 'post',
    'body' => [
        "object" => "Contact",
        "externalIdFieldName" => "externalId",
        "contentType" => "CSV",
        "operation" => "upsert"
    ]
]);
```

#### Add Data

Using the Job ID from the Create POST request, you then send the CSV data to be processed using a PUT request. This assumes you've loaded your CSV contents to `$csv`

> See [Prepare CSV Files](https://developer.salesforce.com/docs/atlas.en-us.api_bulk_v2.meta/api_bulk_v2/datafiles_prepare_csv.htm) for details on how it should be formatted.

```php
Forrest::jobs('ingest/' . $bulkJob['id'] . '/batches', [
    'method' => 'put',
    'headers' => [
        'Content-Type' => 'text/csv'
    ],
    'body' => $csv
]);
```

#### Close

You must close the job before the records can be processed, to do so you send an `UploadComplete` state using a PATCH request to the Job ID.

> See [Close or Abort a Job](https://developer.salesforce.com/docs/atlas.en-us.api_bulk_v2.meta/api_bulk_v2/close_job.htm) for more options and details on how to abort a job.

```php
$response = Forrest::jobs('ingest/' . $bulkJob['id'] . '/', [
    'method' => 'patch',
    'body' => [
        "state" => "UploadComplete"
    ]
]);
```

> **Bulk API 2.0 is available in API version 41.0 and later**. For more information on Salesforce Bulk API, check out the [official documentation](https://developer.salesforce.com/docs/atlas.en-us.api_bulk_v2.meta/api_bulk_v2/introduction_bulk_api_2.htm) and [this tutorial](https://trailhead.salesforce.com/en/content/learn/modules/api_basics/api_basics_bulk) on how to perform a successful Bulk Upload.

### Additional API Requests

#### Refresh
If a refresh token is set, the server can refresh the access token on the user's behalf. Refresh tokens are only for the Web Server flow.
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
Returns list of available resources based on the logged in user's permission and API version.
```php
Forrest::resources();
```

#### Identity
Returns information about the logged-in user.
```php
Forrest::identity();
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
If needed, you can make raw requests to an endpoint of your choice.
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

### Event Listener
This package makes use of Guzzle's event listers
```php
Event::listen('forrest.response', function($request, $response) {
    dd((string) $response);
});
```

For more information about Guzzle responses and event listeners, refer to their [documentation](http://guzzle.readthedocs.org).
