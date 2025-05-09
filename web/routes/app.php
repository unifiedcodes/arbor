<?php

use Arbor\facades\Route;
use web\controllers\Home;


Route::get('/', Home::class)->name('home');
