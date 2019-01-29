<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('submitter_id');
            $table->integer('processor_id');
            $table->string('command');
            $table->string('status');
            $table->timestamps();
        });
        // The create function wasn't executing properly while trying to make these updates:
        DB::statement('ALTER TABLE `tasks` MODIFY `processor_id` INTEGER UNSIGNED NULL;');
        DB::statement('ALTER TABLE `tasks` ALTER `status` SET DEFAULT "queued";');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
