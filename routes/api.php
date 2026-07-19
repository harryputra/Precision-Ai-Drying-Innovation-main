<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AIAgentController;
use App\Http\Controllers\Api\ActuatorLogController;
use App\Http\Controllers\Api\AiConversationController;
use App\Http\Controllers\Api\AiDecisionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DryingBatchController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\IoTCommandController;
use App\Http\Controllers\Api\SensorReadingController;
use App\Http\Controllers\Api\WeatherDataController;

/*
|--------------------------------------------------------------------------
| Health check — dipakai healthcheck docker & verifikasi TTFB pasca-deploy
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    try {
        DB::select('select 1');
        $db = 'ok';
    } catch (\Throwable) {
        $db = 'fail';
    }

    return response()->json([
        'status' => $db === 'ok' ? 'ok' : 'degraded',
        'db'     => $db,
        'time'   => now()->toIso8601String(),
    ], $db === 'ok' ? 200 : 503);
});

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:15,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| AI Agent — n8n webhook endpoints (API key via X-AI-Webhook-Key header)
|--------------------------------------------------------------------------
*/
Route::prefix('ai')->middleware('ai.webhook')->group(function () {
    Route::get('/context', [AIAgentController::class, 'context']);          // n8n ambil snapshot
    Route::post('/decide', [AIAgentController::class, 'decide']);           // n8n kirim keputusan
    Route::post('/chat/reply', [AIAgentController::class, 'chatReply']);    // n8n simpan balasan

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/chat', [AIAgentController::class, 'chat']);           // user kirim pesan
    });
});

/*
|--------------------------------------------------------------------------
| Protected routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Devices
    Route::get('devices', [DeviceController::class, 'index']);
    Route::get('devices/{device}', [DeviceController::class, 'show']);
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('devices', [DeviceController::class, 'store']);
        Route::put('devices/{device}', [DeviceController::class, 'update']);
        Route::patch('devices/{device}', [DeviceController::class, 'update']);
        Route::delete('devices/{device}', [DeviceController::class, 'destroy']);
        Route::post('devices/{device}/heartbeat', [DeviceController::class, 'heartbeat']);
    });

    // Drying Batches
    Route::get('batches', [DryingBatchController::class, 'index']);
    Route::get('batches/{dryingBatch}', [DryingBatchController::class, 'show']);
    Route::get('batches-active', [DryingBatchController::class, 'active']);
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('batches', [DryingBatchController::class, 'store']);
        Route::put('batches/{dryingBatch}', [DryingBatchController::class, 'update']);
        Route::patch('batches/{dryingBatch}', [DryingBatchController::class, 'update']);
        Route::delete('batches/{dryingBatch}', [DryingBatchController::class, 'destroy']);
    });

    // Sensor Readings — read-only API
    Route::get('sensor-readings/latest', [SensorReadingController::class, 'latest']);
    Route::get('sensor-readings', [SensorReadingController::class, 'index']);
    Route::get('sensor-readings/{sensorReading}', [SensorReadingController::class, 'show']);

    // Weather Data — read-only API
    Route::get('weather/latest', [WeatherDataController::class, 'latest']);
    Route::get('weather', [WeatherDataController::class, 'index']);
    Route::get('weather/{weatherData}', [WeatherDataController::class, 'show']);

    // AI Decisions
    Route::get('ai-decisions/pending', [AiDecisionController::class, 'pending']);
    Route::get('ai-decisions', [AiDecisionController::class, 'index']);
    Route::get('ai-decisions/{aiDecision}', [AiDecisionController::class, 'show']);
    Route::middleware('role:admin,operator')->group(function () {
        Route::patch('ai-decisions/{aiDecision}/status', [AiDecisionController::class, 'updateStatus']);
    });

    // Actuator Logs — read-only
    Route::get('actuator-logs', [ActuatorLogController::class, 'index']);
    Route::get('actuator-logs/{actuatorLog}', [ActuatorLogController::class, 'show']);

    // Notifications
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy']);

    // Knowledge Base
    Route::get('knowledge-base', [KnowledgeBaseController::class, 'index']);
    Route::get('knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'show']);
    Route::get('knowledge-base-for-ai', [KnowledgeBaseController::class, 'forAi']);
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('knowledge-base', [KnowledgeBaseController::class, 'store']);
        Route::put('knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'update']);
        Route::patch('knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'update']);
        Route::delete('knowledge-base/{knowledgeBase}', [KnowledgeBaseController::class, 'destroy']);
    });

    // AI Conversations
    Route::get('conversations', [AiConversationController::class, 'index']);
    Route::get('conversations/{sessionId}', [AiConversationController::class, 'session']);
    Route::post('conversations', [AiConversationController::class, 'store']);
    Route::post('conversations/new-session', [AiConversationController::class, 'newSession']);
    Route::patch('conversations/{aiConversation}/feedback', [AiConversationController::class, 'feedback']);
});

/*
|--------------------------------------------------------------------------
| IoT Device ingress — protected dengan X-Device-Key header
| Set IOT_DEVICE_KEY di .env — ESP32 kirim key di header atau query string
|--------------------------------------------------------------------------
*/
Route::prefix('iot')->middleware('iot.device')->group(function () {
    Route::post('/sensor', [SensorReadingController::class, 'store']);          // kirim data sensor
    Route::post('/sensor/bulk', [SensorReadingController::class, 'bulkStore']); // bulk ingest
    Route::post('/weather', [WeatherDataController::class, 'store']);           // kirim data cuaca
    Route::post('/actuator', [ActuatorLogController::class, 'store']);          // log hasil aktuator
    Route::post('/decision', [AiDecisionController::class, 'store']);           // simpan keputusan AI
    Route::post('/notification', [NotificationController::class, 'store']);     // push notifikasi

    // ESP32 closed-loop command
    Route::get('/pending-command', [IoTCommandController::class, 'pendingCommand']); // ESP32 polling perintah
    Route::post('/command-ack', [IoTCommandController::class, 'commandAck']);        // ESP32 konfirmasi eksekusi
});
