<?php
$router->get('/clearCache', 'HomeController@clearCache', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
/**Sản phẩm */
$router->get('/products', 'ProductsController@index');
$router->post('/products', 'ProductsController@add');
$router->put('/products/{id}', 'ProductsController@update');
$router->delete('/products/{id}', 'ProductsController@delete');
/**Khách hàng */
$router->get('/customer', 'CustomerController@index');
$router->post('/customer', 'CustomerController@add');
$router->put('/customer/{id}', 'CustomerController@update');
$router->delete('/customer/{id}', 'CustomerController@delete');
/**Nhập kho */
$router->get('/imports', 'ImportsController@index');
$router->post('/imports', 'ImportsController@add');
/**Bán hàng */
$router->get('/sellers', 'SellersController@index');
$router->post('/sellers', 'SellersController@add');
/**Combo dữ liệu */
$router->get('/units/combo', 'UnitsController@combo');
$router->get('/categories/combo', 'CategoriesController@combo');
$router->get('/manufacturer/combo', 'ManufacturerController@combo');
$router->get('/products/combo', 'ProductsController@combo');

// Route test nhanh
$router->setNotFound(function() {
    echo json_encode(['error' => 'API route not found']);
});