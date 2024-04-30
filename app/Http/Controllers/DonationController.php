<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Donation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DonationController extends Controller
{

    protected $responseController;
    public function __construct(ResponseController $responseController)
    {
        $this->responseController = $responseController;
    }
    public function donations(Request $request, string $id)
    {
        try {
            $request->validate([
                'donner_name' => 'required',
                'donner_email' => 'required|email',
                'amount' => 'required|integer'
            ], [
                'donner_name.required' => 'Donner name is required',
                'donner_email.required' => 'Donner email is required',
                'donner_email.email' => 'Donner email should be valid email address',
                'amount.required' => 'Donation amount is required to donate',
                'amount.integer' => 'amount should be in numbers'
            ]);
            $campaign = Campaign::where('unique_code', $id)->first();
            if (!$campaign) {
                return $this->responseController->responseValidationError('Failed', 'Campaign not found');
            }

            $donation = new Donation;
            $donation->campaign_id = $campaign->id;
            $donation->donner_name = $request->donner_name;
            $donation->donner_email = $request->donner_email;
            $donation->amount = $request->amount;
            $donation->save();
        } catch (ValidationException $exception) {
            $exception = $exception->validator->errors();
            return $this->responseController->responseValidationError('Failed', $exception);
        }
    }
}