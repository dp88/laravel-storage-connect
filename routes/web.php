<?php
Route::prefix(config('storage-connect.path'))->middleware(config('storage-connect.middleware'))->group(function() {
    Route::get('authorize-user/{driver?}', function($driver) {
        return Auth::user()->getCloudConnection($driver)->authorize(request('redirect'));
    });

    Route::get('authorize/{driver?}', function($driver) {
        return StorageConnect::driver($driver)->authorize(request('redirect'));
    });

    Route::get('callback/{driver}', function($driver) {
        return StorageConnect::driver($driver)->finish();
    });
});