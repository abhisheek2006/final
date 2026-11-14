<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Content\Clinic;
use App\Core\Response;
use App\Core\Session;
use App\Services\MaintenanceMode;
use Throwable;

final class MaintenanceController extends AdminController
{
    public function index(): Response
    {
        $storageError = '';
        try {
            $settings = MaintenanceMode::settings();
        } catch (Throwable) {
            $settings = [
                'enabled' => false,
                'message' => MaintenanceMode::DEFAULT_MESSAGE,
                'updated_at' => '',
            ];
            $storageError = 'Maintenance settings could not be loaded. Check the MongoDB connection and try again.';
        }

        return $this->view('admin/maintenance', [
            'page'         => 'maintenance',
            'clinic'       => Clinic::info(),
            'settings'     => $settings,
            'storageError' => $storageError,
        ]);
    }

    public function update(): Response
    {
        $this->assertCsrf();

        $enabled = $this->request->string('maintenance_enabled') === '1';
        $message = trim($this->request->string('maintenance_message'));

        if (mb_strlen($message) > 400) {
            Session::flashError('The maintenance message must be 400 characters or fewer.');

            return $this->redirect('/admin/maintenance');
        }

        try {
            MaintenanceMode::save($enabled, $message);
            Session::flashSuccess($enabled
                ? 'Maintenance mode is on. Visitors will see the maintenance notice.'
                : 'Maintenance mode is off. The public website is available again.');
        } catch (Throwable) {
            Session::flashError('The setting could not be saved. Check that MongoDB is configured and available.');
        }

        return $this->redirect('/admin/maintenance');
    }
}
