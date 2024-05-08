<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DonationResource;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\Donation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Token;

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
                'amount.required' => 'Donation amount is required',
                'amount.integer' => 'amount should be in numbers'
            ]);
            $campaign = Campaign::where('unique_code', $id)->first();
            if (!$campaign) {
                return $this->responseController->responseValidationError('Failed', 'Campaign not found');
            }
            $account = $campaign->account()->first();
            // dd($account);
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $response = $stripe->paymentIntents->create([
                'amount' => $request->amount,
                'currency' => 'usd',
                'payment_method' => 'pm_card_visa',
                'description' => 'Donated by Rohan',
            ]);

            $donation = new Donation;
            $donation->campaign_id = $campaign->id;
            $donation->account_id = $account->id;
            $donation->donner_name = $request->donner_name;
            $donation->donner_email = $request->donner_email;
            $donation->amount = $request->amount / 100;
            $donation->save();
            $donation = new DonationResource($donation);
            return $this->responseController->responseValidation($response->status);
        } catch (ValidationException $exception) {
            $exception = $exception->validator->errors();
            return $this->responseController->responseValidationError('Failed', $exception);
        }
    }


    public function getDonationByCampaign(Request $request, string $id)
    {
        try {
            $account = Account::where('id', $request->header('account-id'))->first();
            if (!$account) {
                return $this->responseController->responseValidationError('Failed', ["account-id" => ['Please provide account-id in header']]);
            }
            $campaign = Campaign::where('id', $id)->first();
            if (!$campaign) {
                return $this->responseController->responseValidationError('Failed', 'Campaign not found.');
            }
            $donations = $campaign->donation()->get();
            if (!$donations) {
                return $this->responseController->responseValidationError('Failed', 'No donations have been done in ' . $campaign->campaign_name . ' campaign.');
            }
            $totalDonation = 0;
            foreach ($donations as $key => $donation) {
                $totalDonation += $donation->amount;
            }
            return $this->responseController->responseValidation('Total donation in ' . $campaign->campaign_name . ' campaign', $totalDonation);
        } catch (ValidationException $e) {
            $err = $e->validator->errors();
            return $this->responseController->responseValidationError('Failed', $err);
        }
    }
}
