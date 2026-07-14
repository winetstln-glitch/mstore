<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user) {
            if (!$user->company_id) {
                $defaultCompany = Company::first();
                if ($defaultCompany) {
                    $user->update(['company_id' => $defaultCompany->id]);
                }
            }
        }

        return $next($request);
    }
}