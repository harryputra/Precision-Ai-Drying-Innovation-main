<?php

use App\Http\Controllers\Web\AuthWebController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DeviceWebController;
use App\Http\Controllers\Web\BatchWebController;
use App\Http\Controllers\Web\SensorWebController;
use App\Http\Controllers\Web\WeatherWebController;
use App\Http\Controllers\Web\AiDecisionWebController;
use App\Http\Controllers\Web\AiChatWebController;
use App\Http\Controllers\Web\AiSummaryController;
use App\Http\Controllers\Web\KnowledgeWebController;
use App\Http\Controllers\Web\NotificationWebController;
use App\Http\Controllers\Web\SystemLogWebController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ApiSettingsController;
use App\Http\Controllers\Web\QuickLoginAdminController;
use App\Http\Controllers\Web\QuickLoginController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ViewerDashboardController;
use Illuminate\Support\Facades\Route;

// Auth — rate limit: login longgar, register ketat (form publik)
Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthWebController::class, 'login'])->middleware('throttle:15,1');
Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthWebController::class, 'register'])->middleware('throttle:5,1');

// Quick-Login — 404 total saat dinonaktifkan (lihat QuickLoginController)
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/q/{token}', [QuickLoginController::class, 'show'])->name('quick-login.show');
    Route::post('/q/{token}', [QuickLoginController::class, 'login'])->name('quick-login.attempt');
});

// Locale switch
Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['id', 'en'])) {
        session(['locale' => $lang]);
    }
    return redirect()->back()->withInput();
})->name('locale.switch');

// Protected — semua role
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Sensor & Weather — read-only, semua role
    Route::get('/sensor-readings', [SensorWebController::class, 'index'])->name('web.sensor.index');
    Route::get('/sensor-readings/export', [SensorWebController::class, 'export'])->name('web.sensor.export');
    Route::get('/weather', [WeatherWebController::class, 'index'])->name('web.weather.index');
    Route::get('/weather/export', [WeatherWebController::class, 'export'])->name('web.weather.export');

    // AI — read semua role
    Route::get('/ai/decisions', [AiDecisionWebController::class, 'index'])->name('web.ai.decisions');
    Route::get('/ai/decisions/export/excel', [AiDecisionWebController::class, 'exportExcel'])->name('web.ai.decisions.export.excel');
    Route::get('/ai/decisions/export/csv', [AiDecisionWebController::class, 'exportCsv'])->name('web.ai.decisions.export.csv');
    Route::get('/ai/decisions/export/pdf', [AiDecisionWebController::class, 'exportPdf'])->name('web.ai.decisions.export.pdf');
    Route::get('/ai/decisions/{aiDecision}', [AiDecisionWebController::class, 'show'])->name('web.ai.decisions.show');
    Route::get('/ai/chat', [AiChatWebController::class, 'index'])->name('web.ai.chat');
    Route::post('/ai/chat/send', [AiChatWebController::class, 'send'])->name('web.ai.chat.send');
    Route::get('/ai/summary', [AiSummaryController::class, 'index'])->name('web.ai.summary');

    // AI Manual Trigger — admin + operator only
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('/ai/trigger-decision', [AiDecisionWebController::class, 'triggerDecision'])->name('web.ai.trigger');
    });

    // Notifications — semua role (own notifications)
    Route::get('/notifications', [NotificationWebController::class, 'index'])->name('web.notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationWebController::class, 'markRead'])->name('web.notifications.read');
    Route::post('/notifications/read-all', [NotificationWebController::class, 'markAllRead'])->name('web.notifications.read-all');

    // Logs — read semua role
    Route::get('/logs', [SystemLogWebController::class, 'index'])->name('web.logs.index');
    Route::get('/logs/export', [SystemLogWebController::class, 'export'])->name('web.logs.export');

    // Profile — semua role
    Route::get('/profile', [ProfileController::class, 'show'])->name('web.profile.show');
    Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('web.profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('web.profile.password');

    // Devices — admin + operator (CUD); viewer diblok oleh middleware untuk create/edit/delete
    Route::get('/devices', [DeviceWebController::class, 'index'])->name('web.devices.index');
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/devices/create', [DeviceWebController::class, 'create'])->name('web.devices.create');
        Route::post('/devices', [DeviceWebController::class, 'store'])->name('web.devices.store');
        Route::get('/devices/{device}/edit', [DeviceWebController::class, 'edit'])->name('web.devices.edit');
        Route::patch('/devices/{device}', [DeviceWebController::class, 'update'])->name('web.devices.update');
        Route::delete('/devices/{device}', [DeviceWebController::class, 'destroy'])->name('web.devices.destroy');
    });
    Route::get('/devices/{device}', [DeviceWebController::class, 'show'])->name('web.devices.show');

    // Batches — admin + operator (CUD)
    Route::get('/batches', [BatchWebController::class, 'index'])->name('web.batches.index');
    Route::get('/batches/export/excel', [BatchWebController::class, 'exportExcel'])->name('web.batches.export.excel');
    Route::get('/batches/export/csv', [BatchWebController::class, 'exportCsv'])->name('web.batches.export.csv');
    Route::get('/batches/export/pdf', [BatchWebController::class, 'exportPdf'])->name('web.batches.export.pdf');
    Route::middleware('role:admin,operator')->group(function () {
        Route::get('/batches/requests', [BatchWebController::class, 'pendingRequests'])->name('web.batches.requests');
        Route::post('/batches/{dryingBatch}/approve', [BatchWebController::class, 'approveRequest'])->name('web.batches.approve');
        Route::post('/batches/{dryingBatch}/reject', [BatchWebController::class, 'rejectRequest'])->name('web.batches.reject');
        Route::get('/batches/create', [BatchWebController::class, 'create'])->name('web.batches.create');
        Route::post('/batches', [BatchWebController::class, 'store'])->name('web.batches.store');
        Route::get('/batches/{dryingBatch}/edit', [BatchWebController::class, 'edit'])->name('web.batches.edit');
        Route::patch('/batches/{dryingBatch}', [BatchWebController::class, 'update'])->name('web.batches.update');
        Route::delete('/batches/{dryingBatch}', [BatchWebController::class, 'destroy'])->name('web.batches.destroy');
    });
    Route::get('/batches/{dryingBatch}', [BatchWebController::class, 'show'])->name('web.batches.show');

    // Knowledge Base — admin + operator (CUD)
    Route::get('/knowledge-base', [KnowledgeWebController::class, 'index'])->name('web.knowledge.index');
    Route::middleware('role:admin,operator')->group(function () {
        Route::post('/knowledge-base', [KnowledgeWebController::class, 'store'])->name('web.knowledge.store');
        Route::put('/knowledge-base/{knowledgeBase}', [KnowledgeWebController::class, 'update'])->name('web.knowledge.update');
        Route::delete('/knowledge-base/{knowledgeBase}', [KnowledgeWebController::class, 'destroy'])->name('web.knowledge.destroy');
    });

    // User Management — admin only
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [RoleController::class, 'index'])->name('users.index');
        Route::get('/users/create', [RoleController::class, 'create'])->name('users.create');
        Route::post('/users', [RoleController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [RoleController::class, 'edit'])->name('users.edit');
        Route::patch('/users/{user}/role', [RoleController::class, 'updateRole'])->name('users.role');

        // Quick-Login config — toggle tanpa restart
        Route::get('/quick-login', [QuickLoginAdminController::class, 'index'])->name('quick-login.index');
        Route::post('/quick-login', [QuickLoginAdminController::class, 'update'])->name('quick-login.update');

        // API Settings — kelola API keys dari dashboard
        Route::get('/api-settings', [ApiSettingsController::class, 'index'])->name('api-settings.index');
        Route::post('/api-settings', [ApiSettingsController::class, 'update'])->name('api-settings.update');
        Route::post('/api-settings/test-gemini', [ApiSettingsController::class, 'testGemini'])->name('api-settings.test-gemini');
        Route::post('/api-settings/test-groq', [ApiSettingsController::class, 'testGroq'])->name('api-settings.test-groq');
        Route::post('/api-settings/test-openweather', [ApiSettingsController::class, 'testOpenWeather'])->name('api-settings.test-openweather');
    });
});


// ── Viewer routes — petani (role: viewer) ───────────────────────────────────
// Layout sederhana, hanya info actionable. Tidak ada akses data teknikal.
Route::middleware(['auth', 'role:viewer'])->prefix('viewer')->name('viewer.')->group(function () {
    Route::get('/dashboard',      [ViewerDashboardController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/poll', [ViewerDashboardController::class, 'poll'])->name('dashboard.poll');
    Route::get('/batches',        [ViewerDashboardController::class, 'batches'])->name('batches');
    Route::get('/notifications',  [ViewerDashboardController::class, 'notifications'])->name('notifications');
    Route::get('/chat',           [ViewerDashboardController::class, 'chat'])->name('chat');
    Route::post('/chat/send',     [ViewerDashboardController::class, 'sendChat'])->name('chat.send');
    Route::get('/request',        [ViewerDashboardController::class, 'requestForm'])->name('request');
    Route::post('/request',       [ViewerDashboardController::class, 'storeRequest'])->name('request.store');
});
