<?php
$router->get('/clearCache', 'HomeController@clearCache', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

$router->get('/products', 'ProductsController@index');
$router->post('/products', 'ProductsController@add');

$router->get('/units/combo', 'UnitsController@combo');

$router->get('/categories/combo', 'CategoriesController@combo');

// Route test nhanh
$router->setNotFound(function() {
    echo json_encode(['error' => 'API route not found']);
});