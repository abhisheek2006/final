<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Content\Clinic;
use App\Core\Config;
use App\Core\Response;
use App\Models\Appointment;
use App\Models\Message;
use App\Services\MongoDatabase;
use App\Services\SubmissionStore;

/**
 * Admin dashboard.
 */
final class DashboardController extends AdminController
{
    public function index(): Response
    {
        $dbOk = MongoDatabase::isAvailable();

        $fallback = $dbOk
            ? ['appointments' => 0, 'messages' => 0]
            : [
                'appointments' => Appointment::count(),
                'messages'     => Message::counts()['total'],
            ];

        $recent = Appointment::list([], 6);

        $mailer  = new \App\Services\Mailer();
        $envOk   = trim((string) \App\Core\Env::string('APP_KEY', '')) !== '';
        $mongoConfigured = MongoDatabase::configured();
        $mongoDriverAvailable = MongoDatabase::driverAvailable();
        $dbError = $dbOk ? '' : (!$mongoConfigured
            ? 'Set MONGODB_URI in .env.'
            : (!$mongoDriverAvailable ? 'Enable the PHP mongodb extension.' : 'Check the MongoDB URI, network access list and database user.'));

        $inventory = SubmissionStore::inventory();

        return $this->view('admin/dashboard', [
            'page'          => 'dashboard',
            'clinic'        => Clinic::info(),
            'dbOk'          => $dbOk,
            'dbError'       => $dbError,
            'statusCounts'  => Appointment::statusCounts(),
            'messageCounts' => $dbOk ? Message::counts() : $fallback,
            'recent'        => $recent,
            'fallback'      => $fallback,
            'inventory'     => $inventory,
            'mailReady'     => $mailer->isConfigured(),
            'mailReason'    => $mailer->isConfigured() ? '' : $mailer->reason(),
            'mongoConfigured' => $mongoConfigured,
            'mongoDriverAvailable' => $mongoDriverAvailable,
            'appKeySet'     => $envOk,
            'debug'         => Config::debug(),
        ]);
    }
}
