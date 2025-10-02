<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Run the seeder
$seeder = new Database\Seeders\ComprehensiveSeeder();
$seeder->run();

echo "Seeder completed successfully!\n";
