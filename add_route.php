<?php
$c = file_get_contents('routes/web.php');
$route = "
Route::get('/reset-atk-danger-xxx', function() {
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    \Illuminate\Support\Facades\DB::table('atk_transactions')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_transaction_items')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_products')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_customers')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_suppliers')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_categories')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_services')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_stock_movements')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_expense_categories')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_expenses')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_float_accounts')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_float_transactions')->truncate();
    \Illuminate\Support\Facades\DB::table('atk_cash_movements')->truncate();
    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    return 'Semua Data ATK Berhasil Dihapus! Silakan hapus URL ini dari routes/web.php jika sudah selesai.';
});
";
file_put_contents('routes/web.php', $c . "\n" . $route);
echo "Added route.\n";
