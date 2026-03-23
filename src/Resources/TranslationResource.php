<?php

namespace Riomigal\Languages\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Riomigal\Languages\Models\Translation;

/**
 * @mixin Translation
 */
class TranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var Translation $translation */
        $translation = $this->resource;

        return [
            'id' => $translation->id,
            'language_id' => $translation->language_id,
            'language_code' => $translation->language_code,
            'shared_identifier' => $translation->shared_identifier,
            'is_vendor' => $translation->is_vendor,
            'type' => $translation->type,
            'namespace' => $translation->namespace,
            'group' => $translation->group,
            'key' => $translation->key,
            'value' => $translation->value,
            'old_value' => $translation->old_value,
            'approved' => $translation->approved,
            'needs_translation' => $translation->needs_translation,
            'updated_translation' => $translation->updated_translation,
            'updated_by' => $translation->updated_by,
            'previous_updated_by' => $translation->previous_updated_by,
            'approved_by' => $translation->approved_by,
            'previous_approved_by' => $translation->previous_approved_by,
            'exported' => $translation->exported,
            'created_at' => $translation->created_at?->toDateTimeString(),
            'updated_at' => $translation->updated_at?->toDateTimeString()
        ];
    }

}
