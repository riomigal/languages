<?php

namespace Riomigal\Languages\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'language_id' => $this->language_id,
            'language_code' => $this->language_code,
            'shared_identifier' => $this->shared_identifier,
            'is_vendor' => $this->is_vendor,
            'type' => $this->type,
            'namespace' => $this->namespace,
            'group' => $this->group,
            'key' => $this->key,
            'value' => $this->value,
            'old_value' => $this->old_value,
            'approved' => $this->approved,
            'needs_translation' => $this->needs_translation,
            'updated_translation' => $this->updated_translation,
            'updated_by' => $this->updated_by,
            'previous_updated_by' => $this->previous_updated_by,
            'approved_by' => $this->approved_by,
            'previous_approved_by' => $this->previous_approved_by,
            'exported' => $this->exported,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString()
        ];
    }

}
