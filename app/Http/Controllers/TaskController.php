<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{

    /**
     * Get the next available task with highest priority.
     *
     * @return \Illuminate\Http\Response
     */
    public function showTopTask()
    {
        $task = Task::getTopPriorityTask();

        // TODO: fix array_has with proper parsing
        if(!$task || !isset($task[0])){
            return $this->formatErrorResponse('There are no tasks to be executed.', 404);
        }

        return response()->json(['data' => $task], 200);
    }

    /**
     * Get the status of the task with id = $id
     *
     * @param integer $id The id of the task we're fetching the status of
     *
     * @return \Illuminate\Http\Response
     */
    public function showTaskStatus($id)
    {
        if(Cache::get($id)){
            return response()->json(['status' => 'executing'], 200);
        }

        // Query the Task from the database by id
        $task = Task::find($id);

        if(!$task){
            return $this->formatErrorResponse('Task does not exist.', 404);
        }

        return response()->json(['status' => $task->status], 200);
    }

    /**
     * Submit a new task to the queue.
     *
     * @param Request $request All of the POST request data
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'submitter_id' => 'required',
            'command' => 'required'
        ]);

        $jsonBody = $request->all();
        $task = Task::create($jsonBody);

        if(!$task) {
            return $this->formatErrorResponse('Task not created.', 400);
        }

        return response()->json(['data' => $task], 201);
    }

    /**
     * Update status of an existing task
     *
     * @param integer $id The id of the task we are updating
     * @param Request $request All of the POST request data
     *
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateStatus($id, Request $request)
    {
        // Make sure the status is set
        $this->validate($request, [
            'status' => 'required'
        ]);

        $newStatus = $request['status'];

        // Simulated authentication
        $processor_id = $request->header('Processor-Id');

        // TODO: define status strings as constants
        if($newStatus != 'executing' && $newStatus != 'completed'){
            return $this->formatErrorResponse('Status must be either executing or completed.', 400);
        }

        // the requester doesn't have a Processor-Id header set
        if($processor_id == ''){
            return $this->formatErrorResponse('User not authorized to update tasks.', 401);
        }

        // Query the Task from the database by id
        $task = Task::find($id);

        if(!$task) {
            return $this->formatErrorResponse('Task does not exist.', 404);
        }

        // Either it's not the processor who started the task, or not processor not assigned
        if(!is_null($task->processor_id) && $task->processor_id != $processor_id) {
            return $this->formatErrorResponse('User not authorized to update this task.', 400);
        }

        if($task->status == $newStatus || $task->status == 'completed') {
            return $this->formatErrorResponse('Task is already ' . $task->status . '.', 400);
        }

        $task->processor_id = $processor_id;
        $task->status = $newStatus;
        $task->save();

        if($task->status == 'completed' && Cache::has($id)){

            $startTime = Cache::get($id);
            $executionTime = time() - $startTime;
            Cache::forget($id); //remove the completed task from the cache

            if(Cache::has('totalJobs')){
                Cache::increment('totalJobs');
            } else {
                Cache::forever('totalJobs', 1);
            }

            if(Cache::has('totalTime')){
                Cache::increment('totalTime', $executionTime);
            } else {
                Cache::forever('totalTime', $executionTime);
            }

        } else {
            // I'm not sure what kind of commands we're running,
            // so I'm not going to expire the cache. The processor
            // should update and trigger a removal if the command
            // executes correctly.
            Cache::forever($id, time());
        }

        return response()->json($task, 200);
    }

    /**
     * Get average execution time of all previous tasks
     *
     * @return \Illuminate\Http\Response
     */
    //TODO: move function to Task Model
    public function showAverageExecutionTime(){

        if(Cache::has('totalJobs') && Cache::has('totalTime')){
            //TODO: additional checks around math
            $avgTime = Cache::get('totalTime') / Cache::get('totalJobs');
            //TODO: format in a cleaner way
            $avgTime = date("H:i:s", $avgTime);
            return response()->json(['Average Execution Time' => $avgTime], 200);
        } else {
            return response()->json(['message' => 'Average Execution Time has not been calculated.'], 200);
        }
    }

    /**
     * Create and format an Response object for error messages
     *
     * @param String $message Error message we want to display
     * @param integer $status HTTP status code
     *
     * @return \Illuminate\Http\Response
     */
    //TODO: Unit test
    public function formatErrorResponse($message, $status){
        return response()->json([
            'error' => [
                'message' => $message
            ]
        ], $status);
    }

}