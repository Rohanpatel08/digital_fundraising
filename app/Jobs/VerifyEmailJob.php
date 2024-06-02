<?php

namespace App\Jobs;

use App\Mail\VerificationEmail;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    /**
     * Create a new job instance.
     */
    public function __construct($account)
    {
        $this->account = new Account();
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->account->sendEmailVerificationNotification();
    }
}
