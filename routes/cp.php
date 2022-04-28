<?php

use Illuminate\Support\Facades\Route;

Route::name('spam.')->prefix('spam')->group(function () {
    Route::get('/', function() {
        return view('statamic-akismet::cp.spam.index');
    })->name('index');
    Route::get('/{id}', function() {
        return view('statamic-akismet::cp.spam.show', ['id' => request()->id]);
    })->name('show');
});
