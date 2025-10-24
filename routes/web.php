<?php
$router->get('/clearCache', 'HomeController@clearCache'); // cache 5 phút
$router->get('/', 'HomeController@index', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
$router->middleware('AuthMiddleware')->get('/info', 'AuthController@info');
$router->post('/logout', 'AuthController@logout');

// Route test nhanh
$router->get('/', function() {
    echo json_encode(['message' => 'Router hoạt động']);
});
