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
    if (app()->environment('production')) {
        abort(403, 'This route is not available in production.');
    }
    
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
