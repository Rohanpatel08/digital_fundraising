<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\Account;
use App\Models\AccountPlan;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    protected $responseController;

    public function __construct(ResponseController $responseController)
    {
        $this->responseController = $responseController;
    }
    public function createCampaign(Request $request)
    {
        try {
            $account = Account::where('id', $request->header('account-id'))->first();
            if (!$account) {
                return $this->responseController->responseValidationError('Failed', ["account-id" => ['Please provide account-id in header']]);
            }
            $request->validate([
                'campaign_name' => 'required | string',
                'description' => 'required | string',
                'banner_image' => 'required | mimes:jpeg,png,jpg',
            ], [
                'campaign_name.required' => 'campaign name is required.',
                'description.required' => 'campaign name is required.',
                'banner_image.required' => 'banner image is required.',
                'banner_image.mimes' => 'banner image must be in jpeg, png, or jpg format.',
            ]);
            $account_id = $account->id;
            $account_plan = AccountPlan::where('account_id', $account_id)->orderBy('created_at', 'DESC')->first();
            if (!$account_plan) {
                return $this->responseController->responseValidationError('Failed', ["active_subscription" => ["You don't have active subscription plan"]]);
            }
            if ($account_plan->campaign_limit <= 0) {
                return $this->responseController->responseValidationError('Failed', ["campaign_limit" => ["Your campaign creation limit is exceeded."]]);
            }
            $b_image = time() . '.' . $request->banner_image->extension();
            $request->banner_image->move(public_path('banners/images'), $b_image);

            $c_images = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $c_image) {
                    $img = '/' . time() . '_' . uniqid() . '.' . $c_image->getClientOriginalExtension();
                    $c_image->move(public_path('campaign/images'), $img);
                    $c_images[] = $img;
                }
            }
            $images = json_encode($c_images);
            $campaign = new Campaign;
            $campaign->account_id = $account_id;
            $campaign->account_plan_id = $account_plan->id;
            $campaign->unique_code = rand(100000, 999999);
            $campaign->campaign_name = $request->campaign_name;
            $campaign->description = $request->description;
            $campaign->campaign_url = env('CAMPAIGN_URL') . $campaign->unique_code;
            $campaign->banner_image = $b_image;
            $campaign->images = isset($images) ? $images : [];
            $campaign->save();

            $account_plan->campaign_limit -= 1;
            $account_plan->update();
            $campaign = $campaign->campaign_url;
        } catch (ValidationException $err) {
            $error = $err->validator->errors();
            return $this->responseController->responseValidationError('Failed', $error);
        }
        return $this->responseController->responseValidation('Campaign Created', $campaign);
    }

    public function getCampaignByCode(string $id)
    {
        $campaign = Campaign::where('unique_code', $id)->first();
        if (!$campaign) {
            return $this->responseController->responseValidationError('Failed', ["campaign" => ['Campaign not found']]);
        }
        $campaign->banner_image = URL::asset('/banners/images') . '/' . $campaign->banner_image;
        $c_images = [];
        $images = json_decode($campaign->images);
        foreach ($images as $key => $img) {
            $img = URL::asset('/campaign/images') . $img;
            array_push($c_images, $img);
        }
        $campaign->images = $c_images;
        $campaign = new CampaignResource($campaign);
        $data = [
            "msg" => "Campaign retrieved successfully",
            "campaign" => $campaign
        ];
        return $data;
    }
}