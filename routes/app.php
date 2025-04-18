<?php

use app\controllers\Home;


$this->get('/', Home::class)->name('home');