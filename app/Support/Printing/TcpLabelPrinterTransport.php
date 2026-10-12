<?php

namespace App\Support\Printing;

use App\Models\Printer;
use InvalidArgumentException;
use RuntimeException;

class TcpLabelPrinterTransport implements LabelPrinterTransport
{
    public function __construct(private float $timeoutSeconds = 5) {}

    public function send(Printer $printer, string $zpl): void
    {
        $host = trim($printer->adres);

        if (! in_array($printer->type, array_keys(Printer::TYPES), true) || $printer->port < 1 || $printer->port > 65535 || ! is_finite($this->timeoutSeconds) || $this->timeoutSeconds <= 0 || $zpl === '') {
            throw new InvalidArgumentException('Invalid printer type, port, timeout, or empty label spool.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $host = '['.$host.']';
        } elseif (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new InvalidArgumentException('Printer address must be a hostname or IP address.');
        }

        $errorCode = 0;
        $errorMessage = '';
        $socket = @stream_socket_client('tcp://'.$host.':'.$printer->port, $errorCode, $errorMessage, $this->timeoutSeconds, STREAM_CLIENT_CONNECT);

        if ($socket === false) {
            throw new RuntimeException('Could not connect to printer #'.$printer->id.': '.$errorMessage, $errorCode);
        }

        try {
            $seconds = (int) $this->timeoutSeconds;
            $microseconds = (int) (($this->timeoutSeconds - $seconds) * 1000000);
            stream_set_timeout($socket, $seconds, $microseconds);
            $offset = 0;
            $length = strlen($zpl);

            while ($offset < $length) {
                $written = @fwrite($socket, substr($zpl, $offset));

                if ($written === false || $written === 0) {
                    throw new RuntimeException('Printer #'.$printer->id.' did not accept the complete label spool.');
                }

                $offset += $written;
            }
        } finally {
            fclose($socket);
        }
    }
}
