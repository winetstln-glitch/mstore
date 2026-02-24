<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SettingController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:setting.view', only: ['index']),
            new Middleware('permission:setting.update', only: ['update']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Exclude 'telegram' group and 'subscription_packages' from general settings
        $settings = Setting::where('group', '!=', 'telegram')
            ->where('key', '!=', 'subscription_packages')
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');
        $accountOptions = Account::orderBy('code')->get();

        return view('settings.index', compact('settings', 'accountOptions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($key === 'mixradius_api_token' && ($value === null || $value === '')) {
                continue;
            }
            $affected = Setting::where('key', $key)->update(['value' => $value]);
            if ($affected === 0) {
                $group = str_starts_with($key, 'mixradius_') ? 'mixradius' : 'general';
                $type = $key === 'mixradius_enforce_customer_login' ? 'boolean' : 'text';
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group,
                    'type' => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                ]);
            }
        }

        return redirect()->back()->with('success', __('Settings updated successfully.'));
    }
}
