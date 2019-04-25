# Contribution Guide
## Build

After you've forked the repo, clone forrest into a new Laravel application or existing project. I recommend creating a new directory to store this project to seperate it from the rest of your codebase. This guide will assume it is named `library` but you can call it anything you like.

`git clone git@github.com:<username>/forrest.git library/forrest`

Next, update your `composer.json` to include the psr-4 auto-loader location. Your should already see the `App\\` namespace unless you've named it something else:
```
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "Omniphx\\Forrest\\": "library/forrest/"
    },
    "classmap": [
        "database/seeds",
        "database/factories"
    ]
},
```

Also add `"guzzlehttp/guzzle": ">6.0"` to the required section:
```
"require": {
    "php": "^7.1.3",
    "fideloper/proxy": "^4.0",
    "laravel/framework": "5.8.*",
    "laravel/tinker": "^1.0",
    "guzzlehttp/guzzle": ">6.0"
},
```

Next run: `composer update`

From your project's root, add the service provider and alias to your `config/app.php` (same as the install guide):
```
Omniphx\Forrest\Providers\Laravel\ForrestServiceProvider::class
'Forrest' => Omniphx\Forrest\Providers\Laravel\Facades\Forrest::class
```

And publish the forrest configuration: `php artisan vendor:publish`

For more details on configuration, see the README.md

## Testing

This project uses the PHPSpec testing framework. PHPSpec leverages mocks so that we only test the code that we've written and assume that other libraries and integrations (such as the Salesforce REST API) are working perfectly fine. You can read more about PHPSpec here: `http://www.phpspec.net/en/stable/`

You'll also need to be in the forrest directory, not your root/project directory to run tests.
1. `cd libary/forrest`
2. `composer update`
3. `vendor/bin/phpspec run` (it should be fast!)

All test are located in the `spec` folder and have a similar namespace to the files in our `src` folder.

If you add new test methods, please use descriptive method naming. For instance, `it_should_not_call_refresh_method_if_there_is_no_token` is a lot more helpful than `test1`