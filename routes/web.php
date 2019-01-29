<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

//GET - the next available task with highest priority
$router->get('task',  ['uses' => 'TaskController@showTopTask']);

//GET - the status of the task with id = $id
$router->get('task/{id}', ['uses' => 'TaskController@showTaskStatus']);


$router->get('task-stats', ['uses' => 'TaskController@showAverageExecutionTime']);

//POST - submit a new task to the queue
$router->post('task', ['uses' => 'TaskController@create']);

//PUT - update status of an existing task
$router->put('task/{id}', ['uses' => 'TaskController@updateStatus']);

