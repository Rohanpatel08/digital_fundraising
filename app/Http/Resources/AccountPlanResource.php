<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountPlanResource extends JsonResource
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
            "plan_id" => $this->plan_id,
            "account_id" =>  $this->account_id,
            "campaign_limit" => $this->campaign_limit,
            "expires_at" => $this->expires_at
        ];
    }
}
