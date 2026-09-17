<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\SmartMoneyController;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\View;

View::share('errors', new ViewErrorBag());

$controller = new SmartMoneyController();
$req = new Request();
$view = $controller->index($req);

$user = \App\Models\User::first() ?: (object)['id' => 1, 'name' => 'Admin', 'status' => 'admin', 'allow_import' => 'Y', 'allow_receipt' => 'Y'];
\Illuminate\Support\Facades\Auth::setUser($user);

$rendered = $view->render();
echo "Blade rendered successfully! Total HTML length: " . strlen($rendered) . " bytes\n";
echo "Contains btnSyncFromStm: " . (strpos($rendered, 'btnSyncFromStm') !== false ? 'YES' : 'NO') . "\n";
echo "Contains patientDetailModal: " . (strpos($rendered, 'patientDetailModal') !== false ? 'YES' : 'NO') . "\n";
echo "Contains importExcelModal: " . (strpos($rendered, 'importExcelModal') !== false ? 'YES' : 'NO') . "\n";
echo "Contains btn-view-patient-detail: " . (strpos($rendered, 'btn-view-patient-detail') !== false ? 'YES' : 'NO') . "\n";
