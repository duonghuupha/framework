<?php
$router->get('/clearCache', 'HomeController@clearCache', ['cache' => 300]); // cache 5 phút
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');
/**Dùng chung*/
$router->get('/categories', 'CategoriesController@index');
$router->post('/categories', 'CategoriesController@add');
$router->put('/categories/{id}', 'CategoriesController@update');
$router->delete('/categories/{id}', 'CategoriesController@delete');
/**giong thu cung*/
$router->get('/petbreed', 'PetbreedController@index');
$router->post('/petbreed', 'PetbreedController@add');
$router->put('/petbreed/{id}', 'PetbreedController@update');
$router->delete('/petbreed/{id}', 'PetbreedController@delete');
/**Din vi tinh**/
$router->get('/units', 'UnitsController@index');
$router->post('/units', 'UnitsController@add');
$router->put('/units/{id}', 'UnitsController@update');
$router->delete('/units/{id}', 'UnitsController@delete');
/**Sản phẩm */
$router->get('/products', 'ProductsController@index');
$router->post('/products', 'ProductsController@add');
$router->put('/products/{id}', 'ProductsController@update');
$router->delete('/products/{id}', 'ProductsController@delete');
/** Dich vu**/
$router->get('/services', 'ServicesController@index');
$router->post('/services', 'ServicesController@add');
$router->put('/services/{id}', 'ServicesController@update');
$router->delete('/services/{id}', 'ServicesController@delete');
/** Nha cung cap**/
$router->get('/manufacturer', 'ManufacturerController@index');
$router->post('/manufacturer', 'ManufacturerController@add');
$router->put('/manufacturer/{id}', 'ManufacturerController@update');
$router->delete('/manufacturer/{id}', 'ManufacturerController@delete');
/**Khách hàng */
$router->get('/customer', 'CustomerController@index');
$router->post('/customer', 'CustomerController@add');
$router->put('/customer/{id}', 'CustomerController@update');
$router->delete('/customer/{id}', 'CustomerController@delete');
/**Nhập kho */
$router->get('/imports', 'ImportsController@index');
$router->post('/imports', 'ImportsController@add');
$router->get('/imports/details/{id}', 'ImportsController@details');
/**Bán hàng */
$router->get('/sellers', 'SellersController@index');
$router->post('/sellers', 'SellersController@add');
$router->get('/sellers/details/{id}', 'SellersController@details');
$router->get('/sellers/details_payment/{id}', 'SellersController@details_payment');
/**Phiếu thu */
$router->get('/receipts', 'ReceiptsController@index');
$router->post('/receipts', 'ReceiptsController@add');
$router->put('/receipts/{id}', 'ReceiptsController@cancelReceipt');
/**Phiếu chi */
$router->get('/expenses', 'ExpensesController@index');
$router->post('/expenses', 'ExpensesController@add');
$router->put('/expenses/{id}', 'ExpensesController@cancelExpense');
/**Combo dữ liệu */
$router->get('/units/combo', 'UnitsController@combo');
$router->get('/categories/combo', 'CategoriesController@combo');
$router->get('/manufacturer/combo', 'ManufacturerController@combo');
$router->get('/products/combo', 'ProductsController@combo');
$router->get('/customer/combo', 'CustomerController@combo');
$router->get('/petbreed/combo', 'PetbreedController@combo');
$router->get('/services/combo', 'ServicesController@combo');
/**Các API khác*/
$router->get('/customer/{id}/debt', 'CustomerController@debt');
$router->get('/manufacturer/{id}/debt', 'ManufacturerController@debt');

// Route test nhanh
$router->setNotFound(function() {
    echo json_encode(['error' => 'API route not found']);
});