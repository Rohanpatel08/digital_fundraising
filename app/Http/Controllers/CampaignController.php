<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignResource;
use App\Models\AccountPlan;
use App\Models\Campaign;
use App\Models\User;
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
            $request->validate([
                'campaign_name' => 'required | string',
                'description' => 'required | string',
                'banner_image' => 'required',
                'banner_image.*' => 'image| mimes:jpeg,png,jpg,webp',
            ]);
            $account = User::where('nonprofit_name', $request->header('nonprofit_name'))->first();
            if (!$account) {
                return $this->responseController->responseValidationError('Failed', "Please provide a valid non-profit name in header");
            }
            $account_id = $account->id;
            $account_plan = AccountPlan::where('user_id', $account_id)->orderBy('created_at', 'DESC')->first();
            if (!$account_plan) {
                return $this->responseController->responseValidationError('Failed', "You don't have active subscription plan");
            }
            if ($account_plan->campaign_limit <= 0) {
                return $this->responseController->responseValidationError('Failed', "Your campaign creation limit is exceeded.");
            }
            $image = [];
            $banner_image = '/' . time() . '.' . $request->banner_image->extension();
            $request->banner_image->move(public_path('banners/images'), $banner_image);
            $banner_image = URL::asset('public/banners/images') . $banner_image;
            array_push($image, $banner_image);
            $image = json_encode($image);

            $c_images = [];
            if ($request->hasFile('campaign_images')) {
                $images = [];
                array_push($images, $request->file('campaign_images'));
                foreach ($images as $image) {
                    $img = '/' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $request->campaign_images->move(public_path('campaign/images'), $img);
                    $campaign_image = URL::asset('public/campaign/images') . $img;
                    $c_images[] = $campaign_image;
                }
                dd($c_images);
            }
            $images = json_encode($c_images);
            $campaign = new Campaign;
            $campaign->user_id = $account_id;
            $campaign->account_plan_id = $account_plan->id;
            $campaign->unique_code = rand(100000, 999999);
            $campaign->campaign_name = $request->campaign_name;
            $campaign->description = $request->description;
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
        $campaign->banner_image = json_decode($campaign->banner_image);
        $campaign = new CampaignResource($campaign);
        $data = [
            "msg" => "Campaign retrieved successfully",
            "campaign" => $campaign
        ];
        return $data;
    }
}
