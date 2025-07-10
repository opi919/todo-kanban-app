<?php

use App\Models\Task;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

Route::get('/', function () {
    return view('welcome');
});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/todo/livewire/update', $handle);
});

Livewire::setScriptRoute(function ($handle) {
    return Route::get('/todo/vendor/livewire/livewire.js', $handle);
});

Route::get('/htplnsrekgtslotsvypa', function () {
    $data['tasks'] = Task::where('organization_id', 1)->orderBy('status', 'desc')->get()->groupBy('status');
    return view('show-tasks', $data);
});
