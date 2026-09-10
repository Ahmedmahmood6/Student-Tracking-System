<?php

use App\Http\Controllers\ParentReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/parent-report/{token}', [ParentReportController::class, 'show'])->name('parent.report.show');
