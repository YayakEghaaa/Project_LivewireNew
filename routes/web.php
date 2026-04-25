<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Filament\Verifikator\Resources\RombonganVerifikatorResource\Pages\EditRombonganVerifikator;
use App\Models\RombonganItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\SaveRombonganItemController;
use App\Http\Controllers\DashboardDataController;
use App\Http\Controllers\MonitoringDataController;
use App\Http\Controllers\VerifikatorDataController;// ← TAMBAHKAN INI

// TEMPORARY - HAPUS SETELAH DIPAKAI!
Route::get('/clear-all', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('permission:cache-reset');
    return '✅ Semua cache berhasil di-clear!';
});

// TEMPORARY - HAPUS SETELAH DIPAKAI!
Route::get('/check-log', function () {
    $logFile = storage_path('logs/laravel.log');
    if (!file_exists($logFile)) {
        return 'Log file tidak ada!';
    }
    $lines = array_slice(file($logFile), -100);
    return '<pre>' . implode('', $lines) . '</pre>';
});

Route::get('/fill-username', function () {
    \App\Models\User::whereNull('username')->orWhere('username', '')->get()
        ->each(fn($u) => $u->update(['username' => $u->name]));
    return '✅ Username berhasil diisi!';
});

Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if ($user->hasRole('opd')) {
        return redirect('/opd');
    }

    if ($user->hasRole('verifikator')) {
        return redirect('/verifikator');
    }

    if ($user->hasRole('monitoring')) {
        return redirect('/monitoring');
    }

    abort(403, 'Akun Anda belum memiliki role. Hubungi administrator.');
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user->hasRole('opd')) {
        return redirect('/opd');
    }

    if ($user->hasRole('verifikator')) {
        return redirect('/verifikator');
    }

    if ($user->hasRole('monitoring')) {
        return redirect('/monitoring');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/verifikator/rombongan-verifikators/verify-field', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
        'field_name' => 'required|string',
        'is_verified' => 'required|boolean',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);

    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    $verification = $rombonganItem->getOrCreateFieldVerification($data['field_name']);

    $verification->update([
        'is_verified' => $data['is_verified'],
        'verified_at' => $data['is_verified'] ? now() : null,
        'verified_by' => $data['is_verified'] ? auth()->id() : null,
    ]);

    // ✅ Cek progress dan update status_verifikasi di rombongan
    $rombongan = $rombonganItem->rombongan;
    $progress = $rombongan->getVerificationProgress();

    if ((int) $progress['percentage'] === 100) {
        $rombongan->update(['status_verifikasi' => 'Sudah']);
    } else {
        $rombongan->update(['status_verifikasi' => 'Belum']);
    }

    return response()->json([
        'success' => true,
        'message' => 'Verifikasi berhasil disimpan',
        'completed' => (int) $progress['percentage'] === 100,
    ]);
})->middleware('auth')->name('verifikator.verify-field');

Route::post('/verifikator/rombongan-verifikators/save-catatan', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
        'field_name' => 'required|string',
        'catatan' => 'nullable|string',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);

    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    $verification = $rombonganItem->getOrCreateFieldVerification($data['field_name']);

    $verification->update([
        'keterangan' => $data['catatan'],
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Catatan berhasil disimpan'
    ]);
})->middleware('auth')->name('verifikator.save-catatan');

Route::post('/verifikator/rombongan-verifikators/verify-all-fields', function () {
    $data = request()->validate([
        'rombongan_item_id' => 'required|exists:rombongan_items,id',
    ]);

    $rombonganItem = RombonganItem::find($data['rombongan_item_id']);

    if (!$rombonganItem) {
        return response()->json(['success' => false, 'message' => 'Item not found'], 404);
    }

    $fields = $rombonganItem->getVerifiableFields();

    foreach ($fields as $fieldName) {
        $verification = $rombonganItem->getOrCreateFieldVerification($fieldName);

        $verification->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);
    }

    // ✅ Update status_verifikasi di rombongan
    $rombongan = $rombonganItem->rombongan;
    $progress = $rombongan->getVerificationProgress();

    if ((int) $progress['percentage'] === 100) {
        $rombongan->update(['status_verifikasi' => 'Sudah']);
    }

    return response()->json([
        'success' => true,
        'message' => 'Semua field berhasil diverifikasi',
        'completed' => (int) $progress['percentage'] === 100,
    ]);
})->middleware('auth')->name('verifikator.verify-all-fields');

require __DIR__ . '/auth.php';

Route::middleware(['auth', 'role:opd'])->prefix('opd')->group(function () {
    Route::post('/rombongan-items/update-field', [SaveRombonganItemController::class, 'updateField'])
        ->name('opd.rombongan-items.update-field');

    Route::post('/rombongan-items/bulk-update', [SaveRombonganItemController::class, 'bulkUpdate'])
        ->name('opd.rombongan-items.bulk-update');
        
    Route::get('/data/{type}',            [DashboardDataController::class, 'show'])        ->name('opd.dashboard-data');
    Route::get('/data/{type}/pdf',        [DashboardDataController::class, 'downloadPdf']) ->name('opd.dashboard-data.pdf');
    Route::get('/data/{type}/excel',      [DashboardDataController::class, 'downloadExcel'])->name('opd.dashboard-data.excel');
    Route::get('/data/{type}/csv',        [DashboardDataController::class, 'downloadCsv']) ->name('opd.dashboard-data.csv');
});

Route::middleware(['auth', 'role:monitoring'])->prefix('monitoring')->group(function () {
    Route::get('/data/{type}',       [MonitoringDataController::class, 'show'])            ->name('monitoring.dashboard-data');
    Route::get('/data/{type}/pdf',   [MonitoringDataController::class, 'downloadPdf'])     ->name('monitoring.dashboard-data.pdf');
    Route::get('/data/{type}/excel', [MonitoringDataController::class, 'downloadExcel'])   ->name('monitoring.dashboard-data.excel');
    Route::get('/data/{type}/csv',   [MonitoringDataController::class, 'downloadCsv'])     ->name('monitoring.dashboard-data.csv');

    Route::get('/data-all',          [MonitoringDataController::class, 'showAll'])         ->name('monitoring.dashboard-data-all');
    Route::get('/data-all/excel',    [MonitoringDataController::class, 'downloadAllExcel'])->name('monitoring.dashboard-data-all.excel');
    Route::get('/data-all/pdf',      [MonitoringDataController::class, 'downloadAllPdf'])  ->name('monitoring.dashboard-data-all.pdf');
    Route::get('/data-all/csv',      [MonitoringDataController::class, 'downloadAllCsv'])  ->name('monitoring.dashboard-data-all.csv');
});
Route::middleware(['auth', 'role:verifikator'])->prefix('verifikator')->group(function () {
    Route::get('/data/{type}',       [VerifikatorDataController::class, 'show'])            ->name('verifikator.dashboard-data');
    Route::get('/data/{type}/pdf',   [VerifikatorDataController::class, 'downloadPdf'])     ->name('verifikator.dashboard-data.pdf');
    Route::get('/data/{type}/excel', [VerifikatorDataController::class, 'downloadExcel'])   ->name('verifikator.dashboard-data.excel');
    Route::get('/data/{type}/csv',   [VerifikatorDataController::class, 'downloadCsv'])     ->name('verifikator.dashboard-data.csv');

    Route::get('/data-all',          [VerifikatorDataController::class, 'showAll'])         ->name('verifikator.dashboard-data-all');
    Route::get('/data-all/excel',    [VerifikatorDataController::class, 'downloadAllExcel'])->name('verifikator.dashboard-data-all.excel');
    Route::get('/data-all/pdf',      [VerifikatorDataController::class, 'downloadAllPdf'])  ->name('verifikator.dashboard-data-all.pdf');
    Route::get('/data-all/csv',      [VerifikatorDataController::class, 'downloadAllCsv'])  ->name('verifikator.dashboard-data-all.csv');
});