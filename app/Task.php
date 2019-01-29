<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Object_;

class Task extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'submitter_id', 'command'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Query the database for the task
     *
     * @return Object
     */
    public static function getTopPriorityTask()
    {
        $results = DB::table('tasks')
                    ->select('id', 'command')
                    ->where('processor_id', '=', NULL)
                    ->where('status', '=', 'queued')
                    ->orderBy('id', 'asc')
                    ->take(1)
                    ->get();

        return $results;
    }

    //TODO: move average execution time logic here
    public static function calculateAverageExecutionTime()
    {
        //write code here
    }

}
