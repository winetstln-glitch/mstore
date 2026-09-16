<?php
$c = file_get_contents('routes/web.php');
// Remove the old route
$c = preg_replace('/Route::get\(\'\/reset-atk-danger-xxx\'.*?\}\);\s*/s', '', $c);

// Add the new robust route
$route = "
Route::get('/reset-atk-danger-xxx', function() {
    try {
        \$driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if (\$driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif (\$driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
        } elseif (\$driver === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('SET session_replication_role = replica;');
        }

        \$tables = [
            'atk_transactions', 'atk_transaction_items', 'atk_products',
            'atk_customers', 'atk_suppliers', 'atk_categories', 'atk_services',
            'atk_stock_movements', 'atk_expense_categories', 'atk_expenses',
            'atk_float_accounts', 'atk_float_transactions', 'atk_cash_movements'
        ];

        \$schema = \Illuminate\Support\Facades\Schema::connection(null);

        foreach (\$tables as \$table) {
            if (\$schema->hasTable(\$table)) {
                if (\$driver === 'pgsql') {
                    \Illuminate\Support\Facades\DB::statement('TRUNCATE TABLE ' . \$table . ' CASCADE;');
                } else {
                    \Illuminate\Support\Facades\DB::table(\$table)->truncate();
                }
            }
        }

        if (\$driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif (\$driver === 'sqlite') {
            \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
        } elseif (\$driver === 'pgsql') {
            \Illuminate\Support\Facades\DB::statement('SET session_replication_role = DEFAULT;');
        }

        return 'Semua Data ATK Berhasil Dihapus! Silakan hapus URL ini dari routes/web.php';
    } catch (\Throwable \$e) {
        return 'Error: ' . \$e->getMessage();
    }
});
";
file_put_contents('routes/web.php', trim($c) . "\n" . $route);
echo "Added new route.\n";
