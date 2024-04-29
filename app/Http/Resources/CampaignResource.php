<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "account_id" => $this->user_id,
            "account_plan_id" => $this->account_plan_id,
            "unique_code" => $this->unique_code,
            "campaign_name" => $this->campaign_name,
            "description" => $this->description,
            "banner_image" => $this->banner_image,
            "images" => $this->images
        ];
    }
}