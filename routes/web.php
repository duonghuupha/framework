<?php
$router->get('/clearCache', 'HomeController@clearCache'); // cache 5 phút
$router->get('/', 'HomeController@index', ['cache' => 300]); // cache 5 phút
/*$router->get('/users', 'HomeController@index', ['cache' => 120]);
$router->post('/users/create', 'HomeController@testCreate');
$router->put('/users/update', 'UserController@update');
$router->delete('/users/delete', 'UserController@delete');*/
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->middleware('AuthMiddleware')->get('/info', 'AuthController@info');
//$router->post('/register', 'AuthController@register');

$router->setNotFound(function() {
    echo json_encode(['error' => 'API route not found']);
});
