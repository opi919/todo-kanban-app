<?php

use App\Models\Task;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/htplnsrekgtslotsvypa', function () {
    $data['tasks'] = Task::orderBy('status','desc')->get()->groupBy('status');
    return view('show-tasks', $data);
});
