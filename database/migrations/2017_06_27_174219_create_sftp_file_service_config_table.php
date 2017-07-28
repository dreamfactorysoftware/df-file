<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSftpFileServiceConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('sftp_service_config')) {
            Schema::create(
                'sftp_service_config',
                function (Blueprint $t) {
                    $t->integer('service_id')->unsigned()->primary();
                    $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                    $t->string('host');
                    $t->integer('port')->default(22);
                    $t->string('username', 100)->nullable();
                    $t->text('password')->nullable();
                    $t->integer('timeout')->default(90);
                    $t->text('host_fingerprint')->nullable();
                    $t->text('private_key')->nullable();
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
        Schema::dropIfExists('sftp_service_config');
    }
}
