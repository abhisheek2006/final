<?php
declare(strict_types=1);

/**
 * Booking endpoint.
 *
 * The booking pages are static HTML, so this script receives the submission
 * and answers with JSON. All validation, rate limiting, spam scoring, storage
 * and notification mail is performed by the existing controllers.
 *
 * POST fields: booking_type=consultation|follow_up plus the booking-form fields.
 */

use App\Api\Endpoint;
use App\Controllers\AppointmentController;
use App\Core\Request;
use App\Core\Response;
use App\Services\MaintenanceMode;

require_once __DIR__ . '/_bootstrap.php';

Endpoint::guard();

$maintenance = MaintenanceMode::publicStatus();
if ($maintenance['enabled']) {
    Endpoint::respond([
        'ok'      => false,
        'message' => 'Online booking is temporarily unavailable while the website is under maintenance. Please call the clinic.',
        'maintenance' => true,
    ], 503);
}

$request = Endpoint::request();
Endpoint::requirePost($request);

$type = $request->string('booking_type') === 'follow_up' ? 'follow_up' : 'consultation';

Endpoint::form($request, static function (Request $request) use ($type): Response {
    $controller = new AppointmentController($request);

    return $type === 'follow_up'
        ? $controller->storeFollowUp()
        : $controller->storeConsultation();
});
