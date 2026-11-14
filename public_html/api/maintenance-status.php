<?php
declare(strict_types=1);

use App\Api\Endpoint;
use App\Services\MaintenanceMode;

require_once __DIR__ . '/_bootstrap.php';

Endpoint::guard();

Endpoint::respond(MaintenanceMode::publicStatus());
