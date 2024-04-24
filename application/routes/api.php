<?php

\Moudarir\CodeigniterApi\Routes\Router::group('users', ['namespace' => 'api'], function () {
    \Moudarir\CodeigniterApi\Routes\Router::get('', 'apiUsers@index');
    \Moudarir\CodeigniterApi\Routes\Router::post('', 'apiUsers@create');
    \Moudarir\CodeigniterApi\Routes\Router::post('login', 'apiUsers@login');
    \Moudarir\CodeigniterApi\Routes\Router::put('{id}', 'apiUsers@update');
    \Moudarir\CodeigniterApi\Routes\Router::get('{id}', 'apiUsers@show');
});
