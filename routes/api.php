<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
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
// Public routes
Route::get('/status', function () {
    return response()->json([
        'status' => 'operational',
        'version' => '1.0.0',
        'timestamp' => now()->toDateTimeString()
    ]);
});
Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->noContent();
});
Route::post('/auth/change-password', [UserController::class, 'changePassword']);
// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});
// Authenticated routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
        Route::prefix('users')->group(function () {
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        });
    });
    // Book routes (accessible to all authenticated users)
        Route::prefix('books')->group(function () {
        Route::get('/', [BookController::class, 'index'])->name('books.index');
        Route::get('/{book}', [BookController::class, 'show'])->name('books.show');
        Route::post('/{book}/borrow', [BookController::class, 'borrow'])->name('books.borrow');
       // Route::post('/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    });
    // Transaction routes (accessible to all authenticated users)
        Route::prefix('transactions')->group(function () {
        Route::get('/stats', [TransactionController::class, 'userStats']);
        
        Route::get('/', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
        Route::post('/{transaction}/return', [TransactionController::class, 'returnBook'])->name('transactions.return');
       // Route::post('/{transaction}/renew', [TransactionController::class, 'renew'])->name('transactions.renew');
    });
            // Admin-only routes
            Route::prefix('admin')->group(function () {
            // Dashboard
            Route::get('/dashboard-stats', [AdminController::class, 'dashboardStats']);
             //Admin book management
              Route::prefix('books')->group(function () {
              Route::get('/', [BookController::class, 'adminIndex'])->name('admin.books.index');
              Route::post('/', [BookController::class, 'store'])->name('admin.books.store');
              Route::get('/{book}', [BookController::class, 'show'])->name('admin.books.show');
              Route::put('/{book}', [BookController::class, 'update'])->name('admin.books.update');
              Route::delete('/{book}', [BookController::class, 'destroy'])->name('admin.books.destroy');
        });
           
        // Admin user management
            Route::prefix('users')->group(function () {
            Route::get('/', [AdminController::class, 'getUsers'])->name('admin.users.index');
            Route::get('/{user}', [UserController::class, 'show'])->name('admin.users.show');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
        });
           // Admin transaction management
        
           Route::prefix('transactions')->group(function () {
           Route::get('/', [AdminController::class, 'getTransactions'])->name('admin.transactions.index');
           Route::post('/transactions/{transaction}/mark-returned', [TransactionController::class, 'markAsReturned'])
           ->name('admin.transactions.mark-returned');
            });

     });
});

