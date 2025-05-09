<?php

use Arbor\facades\Route;
use admin\controllers\Home;


Route::get('/', Home::class)->name('home');
