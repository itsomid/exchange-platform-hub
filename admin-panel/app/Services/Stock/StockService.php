<?php

namespace App\Services\Stock;

use App\Data\FileStoragePaths;
use ZanySoft\LaravelPDF\Facades\PDF;
use Mpdf\Output\Destination;

class StockService
{
    /**
     * Generate and save a stock contract PDF
     *
     * @param mixed $contract
     * @param mixed $stock
     * @param string $filename
     * @return string|false Returns filename on success, false on failure
     */
    public function generateContractPdf($contract, $stock, $filename = null)
    {
        try {
            // Font configuration
            $fontdata = [
                'iransans' => [
                    'R' => 'IRANSansWeb.ttf',
                    'B' => 'IRANSansWeb.ttf',
                    'I' => 'IRANSansWeb.ttf',
                    'BI' => 'IRANSansWeb.ttf',
                ]
            ];

            // Create PDF instance
            $pdf = PDF::make();
            $pdf->addCustomFont($fontdata, true);

            // Load the view
            $pdf->loadView('dashboard.stock_contract.contract_pdf', [
                'contract' => $contract,
                'stock' => $stock,
            ]);

            // Generate filename if not provided
            if (!$filename) {
                $username = $contract->user->username ?? 'user';
                $filename = $username . '_' . $contract->contract_number . '.pdf';
            }

            // Ensure directory exists
            $directory = storage_path('app/public/contracts/stock');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $filepath = storage_path('app/public/contracts/stock/' . $filename);

            // Save the PDF
            $pdf->Output($filepath, Destination::FILE);

            // Check if file was created successfully
            if (file_exists($filepath)) {
                return $filename; // Return just the filename for database storage
            }

            return false;

        } catch (\Exception $e) {
            \Log::error('Stock contract PDF generation failed: ' . $e->getMessage(), [
                'contract_id' => $contract->id ?? 'unknown',
                'stock_id' => $stock->id ?? 'unknown',
                'filename' => $filename
            ]);

            return false;
        }
    }

    /**
     * Generate and stream a stock contract PDF
     *
     * @param mixed $contract
     * @param mixed $stock
     * @param string $filename
     * @return \Illuminate\Http\Response
     */
    public function streamContractPdf($contract, $stock, $filename = null)
    {
        // Font configuration
        $fontdata = [
            'iransans' => [
                'R' => 'IRANSansWeb.ttf',
                'B' => 'IRANSansWeb.ttf',
                'I' => 'IRANSansWeb.ttf',
                'BI' => 'IRANSansWeb.ttf',
            ]
        ];

        // Create PDF instance
        $pdf = PDF::make();
        $pdf->addCustomFont($fontdata, true);

        // Load the view
        $pdf->loadView('dashboard.stock_contract.contract_pdf', [
            'contract' => $contract,
            'stock' => $stock,
        ]);

        // Generate filename if not provided
        if (!$filename) {
            $filename = 'contract_' . $contract->contract_number . '.pdf';
        }

        // Stream the PDF
        return $pdf->stream($filename);
    }


    /**
     * Check if stock contract PDF exists
     *
     * @param string $filename
     * @return bool
     */
    public function contractPdfExists($filename)
    {
        return file_exists(FileStoragePaths::CONTRACT_DOWNLOAD_URL($filename));
    }


}
