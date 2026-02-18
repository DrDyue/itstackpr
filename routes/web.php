<?php

use Illuminate\Support\Facades\Route;

/*-------------------------- Routes for Employee Management --------------------------*/
use App\Http\Controllers\EmployeeController;

Route::resource('employees', EmployeeController::class)->except(['show']);


/*-------------------------- Routes for Building Management --------------------------*/
use App\Http\Controllers\BuildingController;

Route::resource('buildings', BuildingController::class)->except(['show']);


/*-------------------------- Routes for Room Management --------------------------*/
use App\Http\Controllers\RoomController;

Route::resource('rooms', RoomController::class)->except(['show']);


/*-------------------------- Routes for Device Type Management --------------------------*/
use App\Http\Controllers\DeviceTypeController;

Route::resource('device-types', DeviceTypeController::class)->except(['show']);

/*-------------------------- Routes for Device Management --------------------------*/
use App\Http\Controllers\DeviceController;

Route::resource('devices', DeviceController::class)->except(['show']);


/*-------------------------- Routes for Repair Management --------------------------*/
use App\Http\Controllers\RepairController;

Route::resource('repairs', RepairController::class)->except(['show']);


/*-------------------------- Routes for Device History --------------------------*/
use App\Http\Controllers\DeviceHistoryController;

Route::get('/device-history', [DeviceHistoryController::class, 'index'])->name('device-history.index');
Route::get('/devices/{device}/history', [DeviceHistoryController::class, 'device'])->name('devices.history');


/*-------------------------- Routes for Audit Log --------------------------*/
use App\Http\Controllers\AuditLogController;

Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');


/*-------------------------- Routes for Device Set Management --------------------------*/
use App\Http\Controllers\DeviceSetController;
Route::resource('device-sets', DeviceSetController::class)
    ->except(['show'])
    ->parameters(['device-sets' => 'deviceSet']);

Route::resource('device-sets', DeviceSetController::class)->except(['show']);

Route::post('/device-sets/{deviceSet}/items', [DeviceSetController::class, 'addItem'])
    ->name('device-sets.items.add');

Route::delete('/device-sets/{deviceSet}/items/{item}', [DeviceSetController::class, 'deleteItem'])
    ->name('device-sets.items.delete');



Route::get('/', function () {
    return view('welcome');
});
