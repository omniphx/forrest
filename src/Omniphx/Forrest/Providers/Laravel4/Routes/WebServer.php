<?php

/*
|--------------------------------------------------------------------------
| Forrest Routes
|--------------------------------------------------------------------------
|
| Here are the Web Server authentication routes
|
*/

Route::get('/authenticate', function () {
    return Forrest::authenticate();
});

Route::get('/callback', function () {
    Forrest::callback();

    $url = Config::get('forrest::authRedirect');

    return Redirect::to($url);
});
