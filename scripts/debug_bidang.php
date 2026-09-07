<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    new App\Models\Bidang();
    echo "Bidang OK\n";
} catch (Throwable $e) {
    echo get_class($e).': '.$e->getMessage()."\n";
    foreach ($e->getTrace() as $i => $f) {
        if ($i > 16) {
            break;
        }
        echo "#$i ".($f['file'] ?? '?').':'.($f['line'] ?? '?').' -> '.($f['function'] ?? '')."\n";
    }
}
