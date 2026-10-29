<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrownHotelController;

Route::get('/', fn () => response()->file(public_path('index.html')));
Route::get('/api/auth.php', [CrownHotelController::class, 'auth']);
Route::post('/api/auth.php', [CrownHotelController::class, 'auth']);
Route::get('/api/rooms.php', [CrownHotelController::class, 'rooms']);
Route::post('/api/rooms.php', [CrownHotelController::class, 'rooms']);
Route::patch('/api/rooms.php', [CrownHotelController::class, 'rooms']);
Route::get('/api/overview.php', [CrownHotelController::class, 'overview']);
Route::get('/api/reservations.php', [CrownHotelController::class, 'reservations']);
Route::post('/api/reservations.php', [CrownHotelController::class, 'reservations']);
Route::patch('/api/reservations.php', [CrownHotelController::class, 'reservations']);
Route::get('/api/transactions.php', [CrownHotelController::class, 'transactions']);
Route::post('/api/transactions.php', [CrownHotelController::class, 'transactions']);
Route::patch('/api/transactions.php', [CrownHotelController::class, 'transactions']);
Route::patch('/api/transactions.php', [CrownHotelController::class, 'transactions']);
