<?php

/*
|--------------------------------------------------------------------------
| Forrest Routes
|--------------------------------------------------------------------------
|
| UserPassword authentication routes
|
*/

Route::get('/authenticate', function () {
    Forrest::authenticate();

    $url = Config::get('forrest::authRedirect');

    return Redirect::to($url);
});
