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