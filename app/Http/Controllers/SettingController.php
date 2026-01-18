<?php

namespace App\Http\Controllers;

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
            
        return view('settings.index', compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            // Handle array values (e.g. work_schedule) by json_encoding them
            if (is_array($value)) {
                $value = json_encode($value);
            }
            Setting::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->back()->with('success', __('Settings updated successfully.'));
    }
}
