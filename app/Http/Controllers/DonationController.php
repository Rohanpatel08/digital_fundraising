<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\DonationResource;
use App\Models\Account;
use App\Models\Campaign;
use App\Models\Donation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Charge;
use Stripe\PaymentIntent;
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
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

            Stripe::setApiKey(config('services.stripe.secret'));

            $response = $stripe->paymentIntents->create([
                'amount' => $request->amount,
                'currency' => 'usd',
                'payment_method' => 'pm_card_visa_debit',
                'description' => 'Donated by Rohan',
            ]);
            $res = $stripe->paymentIntents->confirm(
                $response->id,
                [
                    'payment_method' => 'pm_card_visa',
                    'return_url' => env('CAMPAIGN_URL') . $campaign->unique_code,
                ]
            );
            $donation = new Donation;
            $donation->campaign_id = $campaign->id;
            $donation->account_id = $account->id;
            $donation->donner_name = $request->donner_name;
            $donation->donner_email = $request->donner_email;
            $donation->amount = $request->amount / 100;
            $donation->save();
            $donation = new DonationResource($donation);

            // if ($res->status === 'requires_action') {
            //     $nextActionType = $res->next_action->type;
            //     switch ($nextActionType) {
            //         case 'redirect_to_url':
            //             $redirectUrl = $res->next_action->redirect_to_url->url;
            //             return redirect()->away($redirectUrl);
            //             break;
            //         default:
            //             break;
            //     }
            // }
            return $this->responseController->responseValidation('success', 'Donated by ' . $donation->donner_name);
        } catch (ValidationException $exception) {
            $exception = $exception->validator->errors();
            return $this->responseController->responseValidationError('Failed', $exception);
        }
    }


    public function getDonationByCampaign(Request $request, string $id)
    {
        try {
            // $account = Account::where('id', $request->header('account-id'))->first();
            // if (!$account) {
            //     return $this->responseController->responseValidationError('Failed', ["account-id" => ['Please provide account-id in header']]);
            // }
            $campaign = Campaign::where('unique_code', $id)->first();
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
                Log::info('Donner name: ' . $donation->donner_name);
            }
            return $this->responseController->responseValidation('Total donation in ' . $campaign->campaign_name . ' campaign. (Donation in USD)', $totalDonation);
        } catch (ValidationException $e) {
            $err = $e->validator->errors();
            return $this->responseController->responseValidationError('Failed', $err);
        }
    }

    public function getDonationByAccount(Request $request)
    {
        try {
            $account = Account::where('id', $request->header('account-id'))->first();
            if (!$account) {
                return $this->responseController->responseValidationError('Failed', ["account-id" => ['Please provide account-id in header']]);
            }
            $donations = $account->donation()->get();
            $totalDonation = 0;
            foreach ($donations as $key => $donation) {
                $totalDonation += $donation->amount;
            }
            return $this->responseController->responseValidation('Total donation in ' . $account->nonprofit_name . ' campaign. (Donation in USD)', $totalDonation);
        } catch (Exception $e) {
            $err = $e->getMessage();
            $errCode = $e->getCode();
            return $this->responseController->responseValidationError('Failed', $err, $errCode);
        }
    }
}
