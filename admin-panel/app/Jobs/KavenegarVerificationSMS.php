<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class KavenegarVerificationSMS implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $tries = 10;

    public $timeout = 45;

    public $failOnTimeout = true;

    protected $receptor;

    protected $code;

    public function __construct($verify_user)
    {
        $this->receptor = $verify_user->receptor;
        $this->code = $verify_user->code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try {
            $result = \Kavenegar::VerifyLookup($this->receptor, $this->code, null, null, 'registerverify', null);

            if ($result[0]->status !== 5) {
                Log::channel('smserrorlog')->error('Verification SMS failed', [
                    'status' => 'failed',
                    'result' => 'Status = '.$result[0]->status.', StatusText = '.$result[0]->statustext,
                ]);
            }
        } catch (\Kavenegar\Exceptions\ApiException|\Kavenegar\Exceptions\HttpException $e) {

            $this->failed($e);
        }

    }

    public function failed(?Exception $exception): void
    {

        \Log::channel('smserrorlog')->error($exception->getMessage(), ['mobile' => $this->receptor]);

    }
}
