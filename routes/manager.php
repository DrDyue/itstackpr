<?php

use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'manager'])->group(function () {
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::match(['put', 'patch'], '/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::get('/buildings/find-by-name', [BuildingController::class, 'findByName'])->name('buildings.find-by-name');

    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::match(['put', 'patch'], '/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/find-by-name', [RoomController::class, 'findByName'])->name('rooms.find-by-name');

    Route::get('/device-types/find-by-name', [DeviceTypeController::class, 'findByName'])->name('device-types.find-by-name');
    Route::post('/device-types', [DeviceTypeController::class, 'store'])->name('device-types.store');
    Route::match(['put', 'patch'], '/device-types/{deviceType}', [DeviceTypeController::class, 'update'])->name('device-types.update');
    Route::delete('/device-types/{deviceType}', [DeviceTypeController::class, 'destroy'])->name('device-types.destroy');
    Route::get('/device-types', [DeviceTypeController::class, 'index'])->name('device-types.index');

    Route::get('/devices/{device}/quick-update', [DeviceController::class, 'quickUpdateRedirect'])->name('devices.quick-update.redirect');
    Route::post('/devices/{device}/quick-update', [DeviceController::class, 'quickUpdate'])->name('devices.quick-update');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::match(['put', 'patch'], '/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');

    Route::post('/repairs/{repair}/transition', [RepairController::class, 'transition'])->name('repairs.transition');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::match(['put', 'patch'], '/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
    Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');
});
