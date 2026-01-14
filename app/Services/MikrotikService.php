<?php

namespace App\Services;

use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;

class MikrotikService
{
    protected $client;
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
        
        try {
            $this->client = new Client([
                'host' => $router->host,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int)$router->port,
            ]);
        } catch (Exception $e) {
            Log::error("Failed to connect to Mikrotik {$router->name}: " . $e->getMessage());
            $this->client = null;
        }
    }

    /**
     * Check if client is connected
     */
    public function isConnected()
    {
        return $this->client !== null;
    }

    /**
     * Get all PPPoE Secrets
     */
    public function getSecrets()
    {
        if (!$this->client) return [];
        
        $query = new Query('/ppp/secret/print');
        return $this->client->query($query)->read();
    }

    /**
     * Create PPPoE Secret
     */
    public function createSecret($name, $password, $profile = 'default', $localAddress = null, $remoteAddress = null, $service = 'pppoe')
    {
        if (!$this->client) return false;

        try {
            $query = new Query('/ppp/secret/add');
            $query->equal('name', $name);
            $query->equal('password', $password);
            $query->equal('profile', $profile);
            $query->equal('service', $service);
            
            if ($localAddress) $query->equal('local-address', $localAddress);
            if ($remoteAddress) $query->equal('remote-address', $remoteAddress);

            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            Log::error("Mikrotik Add Secret Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update PPPoE Secret
     */
    public function updateSecret($oldName, $data)
    {
        if (!$this->client) return false;

        try {
            // Find ID first
            $query = new Query('/ppp/secret/print');
            $query->where('name', $oldName);
            $secrets = $this->client->query($query)->read();

            if (empty($secrets)) return false;

            $id = $secrets[0]['.id'];

            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $id);
            
            foreach ($data as $key => $value) {
                $query->equal($key, $value);
            }

            $this->client->query($query)->read();
            return true;
        } catch (Exception $e) {
            Log::error("Mikrotik Update Secret Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable/Disable Secret (Block/Unblock)
     */
    public function toggleSecret($name, $enable = true)
    {
        if (!$this->client) return false;

        try {
            $query = new Query('/ppp/secret/print');
            $query->where('name', $name);
            $secrets = $this->client->query($query)->read();

            if (empty($secrets)) return false;
            $id = $secrets[0]['.id'];

            $action = $enable ? 'enable' : 'disable';
            $query = new Query("/ppp/secret/$action");
            $query->equal('.id', $id);
            
            $this->client->query($query)->read();
            
            // If disabling, also kill active connection
            if (!$enable) {
                $this->killActive($name);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("Mikrotik Toggle Secret Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kill Active Connection
     */
    public function killActive($name)
    {
        if (!$this->client) return false;

        try {
            $query = new Query('/ppp/active/print');
            $query->where('name', $name);
            $active = $this->client->query($query)->read();

            foreach ($active as $conn) {
                $kill = new Query('/ppp/active/remove');
                $kill->equal('.id', $conn['.id']);
                $this->client->query($kill)->read();
            }
            return true;
        } catch (Exception $e) {
            Log::error("Mikrotik Kill Active Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if PPPoE user is active
     */
    public function isPppoeActive($username)
    {
        if (!$this->client) return false;

        try {
            $query = new Query('/ppp/active/print');
            $query->where('name', $username);
            $response = $this->client->query($query)->read();
            return count($response) > 0;
        } catch (Exception $e) {
            Log::error("Mikrotik query error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Interface Traffic (Monitor)
     */
    public function getInterfaceTraffic($interfaceName)
    {
        if (!$this->client) return null;

        try {
            $query = new Query('/interface/monitor-traffic');
            $query->equal('interface', $interfaceName);
            $query->equal('once', 'true');
            
            $response = $this->client->query($query)->read();
            
            if (!empty($response)) {
                return $response[0]; // rx-bits-per-second, tx-bits-per-second
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get Profiles
     */
    public function getProfiles()
    {
        if (!$this->client) return [];
        $query = new Query('/ppp/profile/print');
        return $this->client->query($query)->read();
    }

    /**
     * Get System Resource
     */
    public function getSystemResource()
    {
        if (!$this->client) return null;
        try {
            $query = new Query('/system/resource/print');
            $response = $this->client->query($query)->read();
            return !empty($response) ? $response[0] : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get PPPoE Active Count
     */
    public function getPppoeActiveCount()
    {
        if (!$this->client) return 0;
        try {
            $query = new Query('/ppp/active/print');
            $query->where('service', 'pppoe');
            return count($this->client->query($query)->read());
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get Hotspot Active Count
     */
    public function getHotspotActiveCount()
    {
        if (!$this->client) return 0;
        try {
            $query = new Query('/ip/hotspot/active/print');
            return count($this->client->query($query)->read());
        } catch (Exception $e) {
            return 0;
        }
    }
}
