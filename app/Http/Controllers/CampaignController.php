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
                return $this->responseController->responseValidationError('Failed', ['Please provide account-id in header']);
            }
            $request->validate([
                'campaign_name' => 'required | string',
                'description' => 'required | string',
                'banner_image' => 'required',
                'banner_image.*' => 'image| mimes:jpeg,png,jpg,webp',
            ]);
            $account_id = $account->id;
            $account_plan = AccountPlan::where('account_id', $account_id)->orderBy('created_at', 'DESC')->first();
            if (!$account_plan) {
                return $this->responseController->responseValidationError('Failed', ["You don't have active subscription plan"]);
            }
            if ($account_plan->campaign_limit <= 0) {
                return $this->responseController->responseValidationError('Failed', ["Your campaign creation limit is exceeded."]);
            }
            $image = [];
            $banner_image = '/' . time() . '.' . $request->banner_image->extension();
            $request->banner_image->move(public_path('banners/images'), $banner_image);
            $banner_image = URL::asset('public/banners/images') . $banner_image;
            array_push($image, $banner_image);
            $image = json_encode($image);
            $c_images = [];
            if ($request->hasFile('images')) {

                foreach ($request->file('images') as $c_image) {
                    $img = '/' . time() . '_' . uniqid() . '.' . $c_image->getClientOriginalExtension();
                    $c_image->move(public_path('campaign/images'), $img);
                    $campaign_image = URL::asset('public/campaign/images') . $img;
                    $c_images[] = $campaign_image;
                }
            }
            $images = json_encode($c_images);
            $campaign = new Campaign;
            $campaign->account_id = $account_id;
            $campaign->account_plan_id = $account_plan->id;
            $campaign->unique_code = rand(100000, 999999);
            $campaign->campaign_name = $request->campaign_name;
            $campaign->description = $request->description;
            $campaign->campaign_url = 'http://127.0.0.1:8000/' . substr($request->route()->uri(), 0, 13) . $campaign->unique_code;
            $campaign->banner_image = $image;
            $campaign->images = $images ? $images : null;
            $campaign->save();

            $account_plan->campaign_limit -= 1;
            $account_plan->update();
            $campaign = new CampaignResource($campaign);
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
            return $this->responseController->responseValidationError('Failed', ['Campaign not found']);
        }
        $campaign->banner_image = implode('', json_decode($campaign->banner_image));
        $campaign->images = json_decode($campaign->images);
        $campaign = new CampaignResource($campaign);
        $data = [
            "msg" => "Campaign retrieved successfully",
            "campaign" => $campaign
        ];
        return $data;
    }
}
