<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiusService
{
    protected $connection = 'radius';

    /**
     * Add User to Radius
     */
    public function addUser($username, $password)
    {
        try {
            // Check if exists
            $exists = DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)
                ->exists();

            if ($exists) {
                // Update password
                DB::connection($this->connection)->table('radcheck')
                    ->where('username', $username)
                    ->where('attribute', 'Cleartext-Password')
                    ->update(['value' => $password]);
            } else {
                // Insert
                DB::connection($this->connection)->table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => 'Cleartext-Password',
                    'op' => ':=',
                    'value' => $password
                ]);
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Radius Add User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Block User (Auth-Type := Reject)
     */
    public function blockUser($username)
    {
        try {
            // Remove existing block if any
            DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->delete();

            // Add Reject
            DB::connection($this->connection)->table('radcheck')->insert([
                'username' => $username,
                'attribute' => 'Auth-Type',
                'op' => ':=',
                'value' => 'Reject'
            ]);
            
            // Disconnect active session (need Mikrotik/CoA for this, Radius just updates DB)
            return true;
        } catch (\Exception $e) {
            Log::error("Radius Block User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unblock User
     */
    public function unblockUser($username)
    {
        try {
            DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->delete();
            return true;
        } catch (\Exception $e) {
            Log::error("Radius Unblock User Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if Radius DB is connected
     */
    public function checkConnection()
    {
        try {
            DB::connection($this->connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
