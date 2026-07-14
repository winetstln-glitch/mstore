<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wash_services')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->rebuildSqliteTable(['car', 'motor', 'coffee']);

            return;
        }

        DB::statement("ALTER TABLE wash_services MODIFY vehicle_type ENUM('car','motor','coffee') NOT NULL DEFAULT 'car'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('wash_services')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $this->rebuildSqliteTable(['car', 'motor']);

            return;
        }

        DB::statement("ALTER TABLE wash_services MODIFY vehicle_type ENUM('car','motor') NOT NULL DEFAULT 'car'");
    }

    private function rebuildSqliteTable(array $allowedValues): void
    {
        $allowedList = implode("','", $allowedValues);
        $hasHolidayPrice = Schema::hasColumn('wash_services', 'holiday_price');
        $hasImage = Schema::hasColumn('wash_services', 'image');
        $hasDescription = Schema::hasColumn('wash_services', 'description');
        $hasIsActive = Schema::hasColumn('wash_services', 'is_active');
        $hasCreatedAt = Schema::hasColumn('wash_services', 'created_at');
        $hasUpdatedAt = Schema::hasColumn('wash_services', 'updated_at');

        $selectHolidayPrice = $hasHolidayPrice ? 'holiday_price' : 'NULL as holiday_price';
        $selectImage = $hasImage ? 'image' : 'NULL as image';
        $selectDescription = $hasDescription ? 'description' : 'NULL as description';
        $selectIsActive = $hasIsActive ? 'is_active' : '1 as is_active';
        $selectCreatedAt = $hasCreatedAt ? 'created_at' : 'NULL as created_at';
        $selectUpdatedAt = $hasUpdatedAt ? 'updated_at' : 'NULL as updated_at';

        DB::statement('PRAGMA foreign_keys=OFF');

        DB::statement("CREATE TABLE wash_services_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            name VARCHAR NOT NULL,
            vehicle_type VARCHAR NOT NULL DEFAULT 'car' CHECK(vehicle_type IN ('{$allowedList}')),
            price NUMERIC NOT NULL,
            holiday_price NUMERIC NULL,
            description TEXT NULL,
            image VARCHAR NULL,
            is_active TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )");

        DB::statement("INSERT INTO wash_services_new (
            id, name, vehicle_type, price, holiday_price, description, image, is_active, created_at, updated_at
        )
        SELECT
            id,
            name,
            CASE
                WHEN vehicle_type IN ('{$allowedList}') THEN vehicle_type
                ELSE 'car'
            END,
            price,
            {$selectHolidayPrice},
            {$selectDescription},
            {$selectImage},
            {$selectIsActive},
            {$selectCreatedAt},
            {$selectUpdatedAt}
        FROM wash_services");

        DB::statement('DROP TABLE wash_services');
        DB::statement('ALTER TABLE wash_services_new RENAME TO wash_services');
        DB::statement('PRAGMA foreign_keys=ON');
    }
};
