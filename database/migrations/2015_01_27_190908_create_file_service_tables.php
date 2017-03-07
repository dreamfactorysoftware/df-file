<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Symfony\Component\Console\Output\ConsoleOutput;

class CreateFileServiceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('file_service_config')) {
            Schema::create(
                'file_service_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->text('public_path')->nullable();
                    $t->text('container')->nullable();
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_service_config');
    }
}
