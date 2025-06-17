<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_statuses', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('start_id')->nullable();
            $table->bigInteger('end_id')->nullable();
            $table->string('job_id');
            $table->string('table_name');
            $table->text('stack_trace')->nullable();
            $table->boolean('completed')->default(false);
            $table->bigInteger('records_processed')->default(0);
            $table->float('execution_time')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_statuses');
    }
};
