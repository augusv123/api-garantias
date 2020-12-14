<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('register', 'RegisterController@register');
Route::post('login', 'RegisterController@login');
Route::post('loginPC', 'RegisterController@loginPC');

// Route::middleware('auth:api')->group( function () {
//     Route::get('/test','IndexController@test');

// });
Route::middleware('auth:api')->group( function () {
    
});
Route::get('/test','IndexController@test');
Route::get('/datosRegFabricacion','RegistroDeFabricacionController@datosRegFabricacion');
Route::get('/ordenDeProduccion','RegistroDeFabricacionController@ordenDeProduccion');
Route::get('/datosRegFabricacionCompletos','RegistroDeFabricacionController@datosRegFabricacionCompletos');
Route::get('/getDescripcionItem','RegistroDeFabricacionController@getDescripcionItem');
Route::get('/getMovimientosConSaldo','RegistroDeFabricacionController@getMovimientosConSaldo');
Route::get('/getTipoGarantiaItem','RegistroDeFabricacionController@getTipoGarantiaItem');
Route::get('/getItems','RegistroDeFabricacionController@getItems');
Route::get('/getInfoCliente','RegistroDeFabricacionController@getInfoCliente');
Route::get('/item','RegistroDeFabricacionController@item');
Route::get('/getUserByEmailAndPassword','RegistroDeFabricacionController@getUserByEmailAndPassword');
Route::get('/getEtiquetaSAP','RegistroDeFabricacionController@getEtiquetaSAP');
Route::get('/getFamiliasComerciales','RegistroDeFabricacionController@getFamiliasComerciales');
Route::get('/getCategoriasDeGarantias','RegistroDeFabricacionController@getCategoriasDeGarantias');
Route::get('/getFamComGarantias','RegistroDeFabricacionController@getFamComGarantias');
Route::get('/addFamComGarantia','RegistroDeFabricacionController@addFamComGarantia');
Route::get('/deleteFamComGarantia','RegistroDeFabricacionController@deleteFamComGarantia');



Route::get('/getRegistroSap','RegistroDeFabricacionController@getRegistroSap');
Route::get('/getGruposMateriales','RegistroDeFabricacionController@getGruposMateriales');
Route::get('/getDescripcionFamiliaSAP','RegistroDeFabricacionController@getDescripcionFamiliaSAP');
