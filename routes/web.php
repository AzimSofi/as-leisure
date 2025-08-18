<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RoadtaxController;

Route::get('/', [RoadtaxController::class, 'showRoadtaxes']);
