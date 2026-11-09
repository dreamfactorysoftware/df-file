<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPassphraseToSftpServiceConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sftp_service_config') && !Schema::hasColumn('sftp_service_config', 'passphrase')) {
            Schema::table('sftp_service_config', function (Blueprint $t) {
                $t->text('passphrase')->nullable()->after('private_key');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('sftp_service_config', 'passphrase')) {
            Schema::table('sftp_service_config', function (Blueprint $t) {
                $t->dropColumn('passphrase');
            });
        }
    }
}
