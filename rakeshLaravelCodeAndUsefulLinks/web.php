<?
use App\Http\Controllers\{PaymentController};

Route::post('send-btcpay', [PaymentController::class, 'payment'])->name('send.btcpay');