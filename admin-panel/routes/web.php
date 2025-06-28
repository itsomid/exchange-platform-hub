<?php

use App\Models\ReferralCode;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// Add this line
use Illuminate\Support\Facades\Log;

// Add this line if not already present
use Illuminate\Support\Str;

// Add this line if not already present for S3 test
use Illuminate\Support\Facades\Storage;

// Add this line if not already present for S3 test
use Spatie\LaravelPdf\Facades\Pdf;

// Add this line for PDF generation

//Route::view('/', 'welcome');

Route::redirect('', '/admin/login');

Route::get('/test', function () {
    return $user_id = ReferralCode::query()
        ->inRandomOrder()
        ->value('user_id');

    return \App\Models\User::query()->find($user_id);
});

Route::get('test-omid', function () {
    $asset = \App\Services\Exchanges\Asset\AssetFactory::make('coinex');

    foreach ($asset->getBalance() as $balance) {
        echo 'currency:' . $balance->getCcy() . ' available:' . $balance->getAvailable() . ' frozen:' . $balance->getFrozen() . '<br>' . PHP_EOL;
    }
});

Route::get('test-omid-withdraw', function () {

    $asset = \App\Services\Exchanges\Asset\AssetFactory::make('coinex');
    $res = $asset->withdraw(
        resolve(\App\Services\Exchanges\Asset\DTO\WithdrawRequestDTO::class)
            ->setCurrency('DOGE')
//        ->setChain('TRX')
            ->setAmount("1")
            ->setWithdrawMethod(\App\Services\Exchanges\Asset\Enum\WithdrawMethodEnum::INTER_USER)
            ->setAddress("o.shabani@hotmail.com")
    );
    dd($res);
});

Route::get('/s3-testt', function () {
    try {
        $fileName = 'test/test_' . Str::random(6) . '.txt';
        $fileContent = "این یه تست دقیق‌تره 😎";

        // مرحله 1: آپلود فایل
        $uploaded = Storage::disk('s3')->put($fileName, $fileContent);

        if (!$uploaded) {
            return '❌ آپلود انجام نشد.';
        }

        // مرحله 2: بررسی وجود فایل
        if (!Storage::disk('s3')->exists($fileName)) {
            return '❌ فایل بعد از آپلود پیدا نشد.';
        }

        // مرحله 3: گرفتن لینک فایل (در صورت نیاز)
        $url = Storage::disk('s3')->url($fileName);

        return "✅ فایل با موفقیت آپلود شد! <br> مسیر روی S3: <code>{$fileName}</code><br> لینک: <a href='{$url}' target='_blank'>{$url}</a>";

    } catch (\Exception $e) {
        Log::error('S3 Test Error: ' . $e->getMessage());
        return '❌ خطا در آپلود: ' . $e->getMessage();
    }
});

Route::get('/test-email', function () {
    try {
        Mail::raw('This is a test email from Laravel.', function ($message) {
            $message->to(['o.shabani@hotmail.com', 'omid.it.shabani@gmail.com']) // Replace 'another@example.com' with the second recipient's email
            ->subject('Test Email');
        });
        return 'Test email sent successfully!';
    } catch (\Exception $e) {
        Log::error('Email sending failed: ' . $e->getMessage());
        return 'Failed to send test email: ' . $e->getMessage();
    }
});
Route::get('clean-spot', function () {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key checks temporarily if needed

    DB::table('spot_orders')->truncate();
    DB::table('spot_trades')->truncate();
    DB::table('trading_commissions')->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Re-enable foreign key checks

    return response()->json(['message' => 'Spot tables truncated successfully.']);
});

Route::get('pdf-test', function () {
    // Get a sample contract (latest)
    $contract = \App\Models\StockContract::with(['user', 'stock'])->latest()->first();
    if (!$contract) {
        return 'No contract found.';
    }
     $stock = $contract->stock;
//    return view('dashboard.stock_contract.contract_pdf', [
//        'contract' => $contract,
//        'stock' => $stock,
//    ]);


    return Pdf::view('dashboard.stock_contract.contract_pdf', [
        'contract' => $contract,
        'stock' => $stock,
    ])
    ->format('a4')
    ->withBrowsershot(function ($browsershot) {
        $browsershot->noSandbox();
        $browsershot->setOption('timeout', 120000); // 120 ثانیه
    })
    ->save(storage_path('app/public/contracts/stock/test.pdf'));
});
