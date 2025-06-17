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
        Schema::create('migration_errors', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->longText('error_message');
            $table->longText('view_definition')->default('')->nullable();
            $table->string('stack_trace')->nullable();
            $table->text('migration_type')->nullable();
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
        Schema::dropIfExists('migration_errors');
    }
};
