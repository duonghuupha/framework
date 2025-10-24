<?php
$router->get('/clearCache', 'HomeController@clearCache', ['cache' => 300]); // cache 5 phút
$router->get('/', 'HomeController@index', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
<<<<<<< HEAD
$router->post('/logout', 'AuthController@logout');
$router->get('/info', 'AuthController@info');
//$router->post('/register', 'AuthController@register');
=======
$router->middleware('AuthMiddleware')->get('/info', 'AuthController@info');
$router->post('/logout', 'AuthController@logout');
>>>>>>> c88be8bdb211c4c07b44face07f870187fd707fa

// Route test nhanh
$router->get('/', function() {
    echo json_encode(['message' => 'Router hoạt động']);
});
