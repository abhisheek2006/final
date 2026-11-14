<?php
declare(strict_types=1);

/**
 * Contact endpoint.
 *
 * Answers JSON so the static contact page can show the outcome without a page
 * reload. Validation, spam scoring, storage and the acknowledgement mail are
 * handled by the existing controller.
 *
 * POST fields: name, phone, email (optional), subject, message, honeypot.
 */

use App\Api\Endpoint;
use App\Controllers\ContactController;
use App\Core\Request;
use App\Core\Response;
use App\Services\MaintenanceMode;

require_once __DIR__ . '/_bootstrap.php';

Endpoint::guard();

$maintenance = MaintenanceMode::publicStatus();
if ($maintenance['enabled']) {
    Endpoint::respond([
        'ok'      => false,
        'message' => 'Online messages are temporarily unavailable while the website is under maintenance. Please call the clinic.',
        'maintenance' => true,
    ], 503);
}

$request = Endpoint::request();
Endpoint::requirePost($request);

Endpoint::form($request, static function (Request $request): Response {
    return (new ContactController($request))->store();
});
