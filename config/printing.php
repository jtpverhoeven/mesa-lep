<?php

return [
    'disabled_cvar' => 'MESA_DISABLE_PRINTING',
    'alignment_cvar' => 'MESA_ZEBRA_ALIGN',
    'connection' => 'database',
    'queue' => 'printing',
    'lock_wait_seconds' => 10,
    'timeout_seconds' => 5,
    'max_spool_bytes' => 16 * 1024 * 1024,
];
