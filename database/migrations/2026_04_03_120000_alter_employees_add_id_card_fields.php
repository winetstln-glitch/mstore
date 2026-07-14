<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'id_card_photo_path')) {
                $table->string('id_card_photo_path')->nullable()->after('document_path');
            }
            if (! Schema::hasColumn('employees', 'id_card_expires_at')) {
                $table->date('id_card_expires_at')->nullable()->after('id_card_photo_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'id_card_expires_at')) {
                $table->dropColumn('id_card_expires_at');
            }
            if (Schema::hasColumn('employees', 'id_card_photo_path')) {
                $table->dropColumn('id_card_photo_path');
            }
        });
    }
};
