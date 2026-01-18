<?php

namespace App\Services\Olt;

class TelnetClient
{
    protected $socket;
    protected array $prompt = ['#', '>', '$'];
    protected string $buffer = '';

    public function connect(string $host, int $port = 23, int $timeout = 10): void
    {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if (!$this->socket) {
            throw new \Exception("Telnet connect failed: {$errstr} ({$errno})");
        }

        stream_set_blocking($this->socket, true);
        stream_set_timeout($this->socket, $timeout);
    }

    public function setPrompt(array $prompts): void
    {
        $this->prompt = $prompts;
    }

    public function login(string $username, string $password, array $userPrompts, array $passPrompts): void
    {
        $this->waitForAnyPrompt($userPrompts);
        $this->write($username);
        $this->waitForAnyPrompt($passPrompts);
        $this->write($password);
        $this->waitPrompt($this->prompt);
    }

    public function waitPrompt(array $prompts): string
    {
        return $this->waitForAnyPrompt($prompts);
    }

    protected function waitForAnyPrompt(array $prompts): string
    {
        $this->buffer = '';
        $maxLength = 1024 * 1024;

        while (!feof($this->socket)) {
            $char = fgetc($this->socket);
            if ($char === false) {
                break;
            }

            if (ord($char) === 255) {
                $this->handleIac();
                continue;
            }

            $this->buffer .= $char;

            if (str_contains($this->buffer, '--More--')) {
                fwrite($this->socket, ' ');
            }

            if (strlen($this->buffer) >= $maxLength) {
                break;
            }

            foreach ($prompts as $prompt) {
                if ($prompt !== '' && str_ends_with($this->buffer, $prompt)) {
                    return $this->buffer;
                }
            }
        }

        return $this->buffer;
    }

    protected function handleIac(): void
    {
        $cmd = fgetc($this->socket);
        if ($cmd === false) {
            return;
        }

        $cmdOrd = ord($cmd);

        if ($cmdOrd >= 251 && $cmdOrd <= 254) {
            $opt = fgetc($this->socket);
            if ($opt === false) {
                return;
            }

            $optOrd = ord($opt);
            $supported = [1, 3];

            if ($cmdOrd === 251) {
                if (in_array($optOrd, $supported, true)) {
                    fwrite($this->socket, chr(255) . chr(253) . chr($optOrd));
                } else {
                    fwrite($this->socket, chr(255) . chr(254) . chr($optOrd));
                }
            } elseif ($cmdOrd === 253) {
                if (in_array($optOrd, $supported, true)) {
                    fwrite($this->socket, chr(255) . chr(251) . chr($optOrd));
                } else {
                    fwrite($this->socket, chr(255) . chr(252) . chr($optOrd));
                }
            } else {
                fwrite($this->socket, chr(255) . chr(252) . chr($optOrd));
            }

            return;
        }

        if ($cmdOrd === 250) {
            while (!feof($this->socket)) {
                $ch = fgetc($this->socket);
                if ($ch === false) {
                    break;
                }

                if (ord($ch) === 255) {
                    $next = fgetc($this->socket);
                    if ($next !== false && ord($next) === 240) {
                        break;
                    }
                }
            }
        }
    }

    public function write(string $buffer): void
    {
        if (!$this->socket) {
            throw new \Exception('Not connected');
        }

        $buffer .= "\r\n";
        fwrite($this->socket, $buffer);
    }

    public function exec(string $command): string
    {
        $this->write($command);
        return $this->waitPrompt($this->prompt);
    }

    public function disconnect(): void
    {
        if ($this->socket) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
