<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWebdavFileServiceConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('webdav_service_config')) {
            Schema::create(
                'webdav_service_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('base_uri');
                    $t->string('username', 100)->nullable();
                    $t->text('password')->nullable();
                    $t->integer('auth_type')->nullable();
                    $t->integer('encoding')->nullable();
                    $t->string('proxy')->nullable();
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
        Schema::dropIfExists('webdav_service_config');
    }
}
