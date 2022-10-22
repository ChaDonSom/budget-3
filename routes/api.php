<?php

use App\Events\PushNotificationUpdated;
use App\Http\Controllers\AccountBatchUpdateController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TemplateController;
use App\Notifications\SelfNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load([
        'sharedUsers',
        'usersWhoSharedToMe',
        'notifications' => fn($query) => $query->whereNull('read_at')
    ]);
});

Route::prefix('beams')->middleware('auth:sanctum')->group(function () {
    Route::post('/self-notification', function (Request $request) {
        $request->user()->notify(new SelfNotification(title: $request->title, message: $request->message));
        return response("Successfully sent SelfNotification", 200);
    });

    Route::get('/token', function (Request $request) {
        if (!config('services.pusher.beams_instance_id') || !config('services.pusher.beams_secret_key')) {
            return abort(501, "Sorry, beams credentials are not available in ENV file on this server.");
        }

        if ($request->user()->id != $request->user_id) {
            return response("Inconsistent request: user_id doesn't match your id", 401);
        }

        $beamsClient = new \Pusher\PushNotifications\PushNotifications([
                "instanceId" => config('services.pusher.beams_instance_id'),
                "secretKey" => config('services.pusher.beams_secret_key'),
        ]);

        $beamsToken = $beamsClient->generateToken((string) "App.Models.User.{$request->user()->id}");
        return Response::json($beamsToken);
    });

    Route::post('/incoming', function (Request $request) {
        Log::info('found pusher beams incoming webhook');
        Log::info($request);
        return response('', 200);
    });
});

Route::get('/dismiss-notification/{id}', function ($uuid) {
    $notification = DatabaseNotification::where('data', 'like', '%"uuid":"' . $uuid . '"%')->firstOrFail();
    $notification->markAsRead();
    PushNotificationUpdated::dispatch($notification, $notification->notifiable);
    return $notification;
});

Route::middleware('auth:sanctum')->resource('accounts', AccountController::class);

Route::middleware('auth:sanctum')->resource('templates', TemplateController::class);

Route::middleware('auth:sanctum')->resource('batch-updates', AccountBatchUpdateController::class);