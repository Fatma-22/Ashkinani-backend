<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'playerName' => $this->player->name ?? '',
            'playerNameAr' => $this->player->name_ar ?? '',
            'agentName' => $this->agent->name ?? '',
            'type' => $this->type,
            'status' => $this->status,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'annualSalary' => $this->annual_salary,
            'signingBonus' => $this->signing_bonus,
            'currency' => $this->currency,
            'fileUrl' => $this->file_url ? (filter_var($this->file_url, FILTER_VALIDATE_URL) ? $this->file_url : asset('storage/' . $this->file_url)) : null,
            'notes' => $this->notes,
            'notesAr' => $this->notes_ar,
            'isVisible' => $this->is_visible,
            'feesAmount' => $this->fees_amount,
            'feesType' => $this->fees_type,
            'player' => new PlayerResource($this->whenLoaded('player')),
            'agent' => new AgentResource($this->whenLoaded('agent')),
        ];
    }
}
