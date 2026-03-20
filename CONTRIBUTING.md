# Contribution Guide

## Build

Clone Forrest into a Laravel application or existing project. A separate sandbox app is recommended so package changes are easy to test in isolation. This guide assumes the package lives at `libraries/forrest`.

```bash
git clone git@github.com:<username>/forrest.git libraries/forrest
```

Next, point your Laravel app at the local package clone using a Composer path repository and require the package from that path. A minimal root `composer.json` setup looks like this:

```json
{
  "require": {
    "omniphx/forrest": "dev-master"
  },
  "repositories": [
    {
      "type": "path",
      "url": "libraries/forrest",
      "options": {
        "symlink": true
      }
    }
  ]
}
```

Then install or update dependencies from the Laravel app root:

```bash
composer update omniphx/forrest firebase/php-jwt
```

Laravel will auto-discover the service provider and `Forrest` alias. Publish the package configuration from the application root:

```bash
php artisan vendor:publish --provider="Omniphx\\Forrest\\Providers\\Laravel\\ForrestServiceProvider"
```

For more details on application configuration, see `README.md`.

## Testing

This project uses PHPSpec. PHPSpec leverages mocks so that we only test the code that we have written and assume that external libraries and integrations, such as the Salesforce REST API, are working correctly. You can read more about PHPSpec here: `http://www.phpspec.net/en/stable/`

Run package tests from the Forrest directory, not the Laravel application root:

1. `cd libraries/forrest`
2. `composer update`
3. `vendor/bin/phpspec run` (it should be fast!)

All tests are located in the `spec` folder and use namespaces that mirror the files in `src`.

If you add new test methods, please use descriptive method naming. For instance, `it_should_not_call_refresh_method_if_there_is_no_token`
