<?php

namespace App\Services\Olt;

use Illuminate\Support\Facades\Log;

class TelnetClient
{
    private $socket;
    private $buffer = '';
    private $prompt;
    private $errno;
    private $errstr;
    private $timeout = 10;
    private $debug = true; // Enabled for troubleshooting

    public function connect($host, $port = 23, $timeout = 10)
    {
        $this->timeout = $timeout;
        $this->socket = @fsockopen($host, $port, $this->errno, $this->errstr, $this->timeout);

        if (!$this->socket) {
            throw new \Exception("Connection failed: {$this->errstr} ({$this->errno})");
        }

        stream_set_timeout($this->socket, $this->timeout);
        
        // Initial negotiation or banner might come in immediately
        // We don't read it here, we let waitPrompt or login handle it
        return true;
    }

    public function login($username, $password, $usernamePrompts = ['Login:', 'Username:', 'user:'], $passwordPrompts = ['Password:', 'Pass:'])
    {
        // Normalize prompts to arrays
        if (!is_array($usernamePrompts)) $usernamePrompts = [$usernamePrompts];
        if (!is_array($passwordPrompts)) $passwordPrompts = [$passwordPrompts];

        $buffer = '';
        $maxRetries = 3;
        $found = false;

        // Try to get username prompt with wake-up retries
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                // Send a newline to wake up the OLT
                $this->write('');
                
                // Wait for a short time for the prompt
                // Use a short timeout for wake-up attempts, but last attempt uses full timeout
                $attemptTimeout = ($i == $maxRetries - 1) ? $this->timeout : 3; 
                
                $buffer = $this->waitPrompt($usernamePrompts, $attemptTimeout);
                $found = true;
                break;
            } catch (\Exception $e) {
                // If it's a timeout, we retry sending newline
                if ($i == $maxRetries - 1) {
                    throw $e; // Rethrow last error
                }
                // Otherwise continue loop to retry
                if ($this->debug) Log::info("Telnet Login Wakeup Retry " . ($i+1));
            }
        }

        if ($this->debug) Log::info("Telnet Login Banner: " . $buffer);
        
        $this->write($username);

        // Wait for any of the password prompts
        $buffer = $this->waitPrompt($passwordPrompts);
        
        $this->write($password);

        return true;
    }

    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;
    }

    public function exec($command)
    {
        $this->write($command);
        return $this->waitPrompt($this->prompt);
    }

    public function write($buffer)
    {
        if (!$this->socket) {
            throw new \Exception("Not connected");
        }

        // Add newline
        $buffer .= "\r\n";
        fwrite($this->socket, $buffer);
    }

    public function waitPrompt($prompts)
    {
        if (!$this->socket) {
            throw new \Exception("Not connected");
        }

        if (!is_array($prompts)) {
            $prompts = [$prompts];
        }

        $buffer = '';
        $startTime = time();

        while (!feof($this->socket)) {
            if (time() - $startTime > $this->timeout) {
                if ($this->debug) Log::error("Telnet Timeout. Buffer content: " . $buffer);
                // Truncate buffer for message to avoid huge error strings
                $safeBuffer = strlen($buffer) > 200 ? substr($buffer, -200) . '...' : $buffer;
                $safeBuffer = addslashes($safeBuffer); // Escape for safety
                throw new \Exception("Telnet timeout waiting for prompt: " . implode(', ', $prompts) . ". Buffer received: '" . $safeBuffer . "'");
            }

            $char = fgetc($this->socket);
            
            // Check for timeout in stream metadata
            $info = stream_get_meta_data($this->socket);
            if ($info['timed_out']) {
                 // Truncate buffer for message to avoid huge error strings
                 $safeBuffer = strlen($buffer) > 200 ? substr($buffer, -200) . '...' : $buffer;
                 $safeBuffer = addslashes($safeBuffer); // Escape for safety
                 throw new \Exception("Connection timed out. Buffer received: '" . $safeBuffer . "'");
            }

            if ($char === false) {
                break;
            }

            // Handle Telnet Negotiation (IAC)
            if (ord($char) == 255) {
                $cmd = fgetc($this->socket);
                $cmdOrd = ord($cmd);
                
                // DO (253), DONT (254), WILL (251), WONT (252) -> 3 bytes
                if ($cmdOrd >= 251 && $cmdOrd <= 254) {
                    $opt = fgetc($this->socket);
                    // Optionally log: Log::info("Telnet Negotiation: IAC $cmdOrd " . ord($opt));
                } 
                // SB (Subnegotiation) - Variable length
                elseif ($cmdOrd == 250) {
                     // Read until IAC SE (255 240)
                     while(!feof($this->socket)) {
                         $subChar = fgetc($this->socket);
                         if (ord($subChar) == 255) {
                             $next = fgetc($this->socket);
                             if (ord($next) == 240) { // SE
                                 break;
                             }
                         }
                     }
                }
                // Other 2-byte commands are consumed by reading $cmd
                continue;
            }

            $buffer .= $char;

            // Check against all possible prompts
            // We check if the trimmed buffer ends with the prompt (ignoring trailing spaces)
            // Or if the prompt is explicitly found at the end
            $trimmedBuffer = trim($buffer);
            foreach ($prompts as $prompt) {
                if ($prompt === null) continue;
                
                // Exact match at end
                if (substr($buffer, -strlen($prompt)) === $prompt) {
                    return $buffer;
                }
                
                // Loose match: buffer ends with prompt (ignoring whitespace)
                // e.g. "Login: " -> matches "Login:"
                if (str_ends_with(trim($buffer), trim($prompt))) {
                    return $buffer;
                }
            }
        }

        return $buffer;
    }

    public function disconnect()
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
    
    public function getBuffer()
    {
        return $this->buffer;
    }
}
