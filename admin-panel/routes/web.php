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

// Add this line for PDF generation
use ZanySoft\LaravelPDF\Facades\PDF;


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

    // Font configuration
    $fontdata = array(
        'iransans' => [
            'R' => 'IRANSansWeb.ttf',      // regular font
            'B' => 'IRANSansWeb.ttf',      // bold font (using same file)
            'I' => 'IRANSansWeb.ttf',      // italic font (using same file)
            'BI' => 'IRANSansWeb.ttf',     // bold italic font (using same file)
        ]
    );

    // Create PDF instance
    $pdf = PDF::make();
    $pdf->addCustomFont($fontdata, true);

    // Load the view
    $pdf->loadView('dashboard.stock_contract.contract_pdf', [
        'contract' => $contract,
    ]);

    // Ensure directory exists
    $directory = storage_path('app/public/contracts/stock');
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }

    // Save the PDF
    $filename = 'contract_' . $contract->contract_number . '.pdf';
    $filepath = storage_path('app/public/contracts/stock/' . $filename);

    try {
        // Use the correct mPDF Output method to save to file
        $pdf->Output($filepath, \Mpdf\Output\Destination::FILE);

        // Check if file was created
        if (file_exists($filepath)) {
            return 'PDF saved successfully! File: ' . $filepath . ' Size: ' . filesize($filepath) . ' bytes';
        } else {
            return 'PDF save failed - file not created';
        }
    } catch (\Exception $e) {
        return 'PDF save error: ' . $e->getMessage();
    }
});

Route::get('pdf-stream', function () {
    // Get a sample contract (latest)
    $contract = \App\Models\StockContract::with(['user', 'stock'])->latest()->first();
    if (!$contract) {
        return 'No contract found.';
    }

    // Font configuration
    $fontdata = array(
        'iransans' => [
            'R' => 'IRANSansWeb.ttf',
            'B' => 'IRANSansWeb_Bold.ttf',
            'I' => 'IRANSansWeb.ttf',
            'BI' => 'IRANSansWeb.ttf',
        ]
    );

    // Create PDF instance
    $pdf = PDF::make();
    $pdf->addCustomFont($fontdata, true);

    // Load the view
    $pdf->loadView('dashboard.stock_contract.contract_pdf', [
        'contract' => $contract,
    ]);

    // Stream the PDF
    return $pdf->stream('contract_' . $contract->contract_number . '.pdf');
});
