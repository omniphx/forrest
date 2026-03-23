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

This project uses PHPUnit for unit coverage. Most tests focus on package behavior with mocked collaborators and PSR-7 responses rather than live Salesforce integration tests.

Run package tests from the Forrest directory, not the Laravel application root:

1. `cd libraries/forrest`
2. `composer update`
3. `vendor/bin/phpunit`

All tests are located in the `tests` folder and generally mirror the areas under `src`.

If you add new test methods, please use descriptive names that explain the behavior being asserted.

## Live Salesforce Integration Test

The package includes an opt-in integration test that uses the Username-Password flow to authenticate against a real Salesforce org and then exercises `identity()`, `resources()`, and a SOQL query against the authenticated `User` record.

Keep the required values in either the package `.env`, the parent Laravel app `.env`, or your shell environment:

```txt
RUN_SALESFORCE_INTEGRATION_TESTS=true
SF_CONSUMER_KEY=123455
SF_CONSUMER_SECRET=ABCDEF
SF_LOGIN_URL=https://login.salesforce.com
SF_USERNAME=mattjmitchener@gmail.com
SF_PASSWORD=password123
```

Run only the live test from the Forrest directory:

```bash
vendor/bin/phpunit --filter UserPasswordIntegrationTest
```

The test is disabled by default so normal package test runs remain deterministic and do not make live network calls. The GitHub Actions `Salesforce Integration` job uses the same environment variable names via repository secrets on pull requests where those secrets are available.
