<?php
$router->get('/clearCache', 'HomeController@clearCache', ['cache' => 300]); // cache 5 phút
$router->post('/', 'HomeController@index', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
$router->get('/info', 'AuthController@info');
$router->get('/personnel', 'PersonnelController@index');

// Route test nhanh
$router->setNotFound(function() {
    echo json_encode(['error' => 'API route not found']);
});