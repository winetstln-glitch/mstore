<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nas')) {
            Schema::create('nas', function (Blueprint $table) {
                $table->id();
                $table->string('nasname', 128)->unique();
                $table->string('shortname', 32)->nullable();
                $table->string('type', 30)->default('mikrotik');
                $table->integer('ports')->nullable();
                $table->string('secret', 60);
                $table->string('server', 64)->nullable();
                $table->string('community', 50)->nullable();
                $table->string('description', 200)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('radcheck')) {
            Schema::create('radcheck', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 64)->index();
                $table->string('attribute', 64);
                $table->string('op', 2)->default(':=');
                $table->string('value', 253);
            });
        }

        if (! Schema::hasTable('radreply')) {
            Schema::create('radreply', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('username', 64)->index();
                $table->string('attribute', 64);
                $table->string('op', 2)->default(':=');
                $table->string('value', 253);
            });
        }

        if (! Schema::hasTable('radacct')) {
            Schema::create('radacct', function (Blueprint $table) {
                $table->bigIncrements('radacctid');
                $table->string('acctsessionid', 64);
                $table->string('acctuniqueid', 32)->unique();
                $table->string('username', 64)->nullable()->index();
                $table->string('groupname', 64)->nullable();
                $table->string('realm', 64)->nullable();
                $table->string('nasipaddress', 15)->nullable();
                $table->string('nasportid', 32)->nullable();
                $table->string('nasporttype', 32)->nullable();
                $table->timestamp('acctstarttime')->nullable();
                $table->timestamp('acctupdatetime')->nullable();
                $table->timestamp('acctstoptime')->nullable();
                $table->integer('acctsessiontime')->nullable();
                $table->string('acctauthentic', 32)->nullable();
                $table->string('connectinfo_start', 50)->nullable();
                $table->string('connectinfo_stop', 50)->nullable();
                $table->bigInteger('acctinputoctets')->nullable();
                $table->bigInteger('acctoutputoctets')->nullable();
                $table->string('calledstationid', 50)->nullable();
                $table->string('callingstationid', 50)->nullable();
                $table->string('acctterminatecause', 32)->nullable();
                $table->string('servicetype', 32)->nullable();
                $table->string('framedprotocol', 32)->nullable();
                $table->string('framedipaddress', 15)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('radacct');
        Schema::dropIfExists('radreply');
        Schema::dropIfExists('radcheck');
        Schema::dropIfExists('nas');
    }
};
