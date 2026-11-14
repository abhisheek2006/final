<?php
declare(strict_types=1);

/**
 * Route table.
 *
 * Clean URLs only. public_html/.htaccess (and the built-in PHP server router)
 * send every non-file request to public_html/index.php, which boots the
 * kernel from app/bootstrap.php and dispatches through the router below.
 *
 * @var App\Core\Kernel $kernel
 */

use App\Controllers\Admin\AppointmentController as AdminAppointmentController;
use App\Controllers\Admin\AuthController as AdminAuthController;
use App\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Controllers\Admin\FaqController as AdminFaqController;
use App\Controllers\Admin\MaintenanceController as AdminMaintenanceController;
use App\Controllers\Admin\MessageController as AdminMessageController;
use App\Controllers\AppointmentController;
use App\Controllers\ContactController;
use App\Controllers\FaqController;
use App\Controllers\HomeController;
use App\Controllers\PageController;
use App\Controllers\SeoController;
use App\Core\Request;
use App\Core\Response;

$router = $kernel->router();

/* -------------------------------------------------------------------------- */
/* Public pages                                                                */
/* -------------------------------------------------------------------------- */
$router->get('/',          [HomeController::class, 'index']);
$router->get('/about-us',  [PageController::class, 'about']);
$router->get('/faq',       [FaqController::class, 'index']);
$router->get('/contact',   [PageController::class, 'contact']);
$router->get('/privacy-policy', [PageController::class, 'privacy']);

/* -------------------------------------------------------------------------- */
/* Booking                                                                     */
/* -------------------------------------------------------------------------- */
$router->get('/book-consultation', [AppointmentController::class, 'consultation']);
$router->post('/book-consultation', [AppointmentController::class, 'storeConsultation']);
$router->get('/book-follow-up',    [AppointmentController::class, 'followUp']);
$router->post('/book-follow-up',    [AppointmentController::class, 'storeFollowUp']);

/* -------------------------------------------------------------------------- */
/* Contact submission                                                          */
/* -------------------------------------------------------------------------- */
$router->post('/contact', [ContactController::class, 'store']);

/* -------------------------------------------------------------------------- */
/* SEO endpoints                                                               */
/* -------------------------------------------------------------------------- */
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt',  [SeoController::class, 'robots']);
$router->get('/faqs.json',   [FaqController::class, 'feed']);

/* -------------------------------------------------------------------------- */
/* Health probe (used by the deployment checklist)                             */
/* -------------------------------------------------------------------------- */
$router->get('/health', static function (Request $request): Response {
    return (new Response())->json([
        'ok'      => true,
        'app'     => config('app.name'),
        'version' => config('app.version'),
        'env'     => config('app.env'),
        'time'    => date('c'),
    ])->noCache();
});

/* -------------------------------------------------------------------------- */
/* Admin — authentication                                                      */
/* -------------------------------------------------------------------------- */
$router->get('/admin',              [AdminDashboardController::class, 'index']);
$router->get('/admin/login',        [AdminAuthController::class, 'showLogin']);
$router->post('/admin/login',       [AdminAuthController::class, 'login']);
$router->post('/admin/logout',      [AdminAuthController::class, 'logout']);
$router->get('/admin/maintenance',  [AdminMaintenanceController::class, 'index']);
$router->post('/admin/maintenance', [AdminMaintenanceController::class, 'update']);

/* -------------------------------------------------------------------------- */
/* Admin — appointments                                                        */
/* -------------------------------------------------------------------------- */
$router->get('/admin/appointments',                 [AdminAppointmentController::class, 'index']);
$router->post('/admin/appointments/{id}/status',     [AdminAppointmentController::class, 'updateStatus']);
$router->post('/admin/appointments/{id}/delete',     [AdminAppointmentController::class, 'delete']);

/* -------------------------------------------------------------------------- */
/* Admin — messages                                                            */
/* -------------------------------------------------------------------------- */
$router->get('/admin/messages',                [AdminMessageController::class, 'index']);
$router->get('/admin/messages/{id}',           [AdminMessageController::class, 'read']);
$router->post('/admin/messages/{id}/delete',   [AdminMessageController::class, 'delete']);

/* -------------------------------------------------------------------------- */
/* Admin — FAQs                                                                */
/* -------------------------------------------------------------------------- */
$router->get('/admin/faqs',                  [AdminFaqController::class, 'index']);
$router->get('/admin/faqs/new',              [AdminFaqController::class, 'create']);
$router->post('/admin/faqs/new',             [AdminFaqController::class, 'store']);
$router->get('/admin/faqs/{id}/edit',        [AdminFaqController::class, 'edit']);
$router->post('/admin/faqs/{id}/edit',       [AdminFaqController::class, 'update']);
$router->post('/admin/faqs/{id}/delete',     [AdminFaqController::class, 'delete']);

/* -------------------------------------------------------------------------- */
/* JSON-LD emitted on every page                                                */
/* -------------------------------------------------------------------------- */
