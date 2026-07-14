<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wash_services')) {
            Schema::table('wash_services', function (Blueprint $table) {
                if (! Schema::hasColumn('wash_services', 'service_category')) {
                    $table->string('service_category')->default('main')->after('vehicle_type');
                }
                if (! Schema::hasColumn('wash_services', 'size_tier')) {
                    $table->string('size_tier')->default('none')->after('service_category');
                }
                if (! Schema::hasColumn('wash_services', 'package_type')) {
                    $table->string('package_type')->default('general')->after('size_tier');
                }
                if (! Schema::hasColumn('wash_services', 'sort_order')) {
                    $table->unsignedInteger('sort_order')->default(0)->after('package_type');
                }
            });
        }

        if (! Schema::hasTable('wash_services')) {
            return;
        }

        $services = DB::table('wash_services')->select('id', 'name', 'vehicle_type')->orderBy('id')->get();
        foreach ($services as $service) {
            $name = strtolower((string) $service->name);
            $category = str_contains($name, 'add on') || str_contains($name, 'add-on') ? 'addon' : 'main';
            $sizeTier = 'none';
            if (str_contains($name, 'kecil')) {
                $sizeTier = 'kecil';
            } elseif (str_contains($name, 'sedang')) {
                $sizeTier = 'sedang';
            } elseif (str_contains($name, 'extra besar')) {
                $sizeTier = 'extra_besar';
            } elseif (str_contains($name, 'besar')) {
                $sizeTier = 'besar';
            }

            $packageType = 'general';
            if (str_contains($name, 'body only')) {
                $packageType = 'body_only';
            } elseif (str_contains($name, 'kolong') || str_contains($name, 'vacum') || str_contains($name, 'vacuum')) {
                $packageType = 'full_clean';
            } elseif (str_contains($name, 'semir')) {
                $packageType = 'express';
            } elseif (str_contains($name, 'mesin')) {
                $packageType = 'engine_cleaner';
            } elseif (str_contains($name, 'jok kulit') || str_contains($name, 'leather')) {
                $packageType = 'leather_cleaner';
            }

            $vehicleRank = match ((string) $service->vehicle_type) {
                'car' => 1,
                'motor' => 2,
                'coffee' => 4,
                default => 3,
            };
            $categoryRank = match ($category) {
                'main' => 1,
                'addon' => 2,
                'skincare' => 3,
                default => 4,
            };
            $sizeRank = match ($sizeTier) {
                'kecil' => 1,
                'sedang' => 2,
                'besar' => 3,
                'extra_besar' => 4,
                default => 5,
            };
            $packageRank = match ($packageType) {
                'body_only' => 1,
                'full_clean' => 2,
                'express' => 3,
                'engine_cleaner' => 4,
                'leather_cleaner' => 5,
                default => 6,
            };
            $sortOrder = ($vehicleRank * 1000) + ($categoryRank * 100) + ($sizeRank * 10) + $packageRank;

            DB::table('wash_services')
                ->where('id', $service->id)
                ->update([
                    'service_category' => $category,
                    'size_tier' => $sizeTier,
                    'package_type' => $packageType,
                    'sort_order' => $sortOrder,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('wash_services')) {
            return;
        }

        Schema::table('wash_services', function (Blueprint $table) {
            if (Schema::hasColumn('wash_services', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
            if (Schema::hasColumn('wash_services', 'package_type')) {
                $table->dropColumn('package_type');
            }
            if (Schema::hasColumn('wash_services', 'size_tier')) {
                $table->dropColumn('size_tier');
            }
            if (Schema::hasColumn('wash_services', 'service_category')) {
                $table->dropColumn('service_category');
            }
        });
    }
};
