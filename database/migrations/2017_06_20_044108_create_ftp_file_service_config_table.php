<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFtpFileServiceConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ftp_service_config')) {
            Schema::create(
                'ftp_service_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('host');
                    $t->integer('port')->default(21);
                    $t->string('username', 100)->nullable();
                    $t->text('password')->nullable();
                    $t->boolean('ssl')->default(0);
                    $t->integer('timeout')->default(90);
                    $t->boolean('passive')->default(1);
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
        Schema::dropIfExists('ftp_service_config');
    }
}
