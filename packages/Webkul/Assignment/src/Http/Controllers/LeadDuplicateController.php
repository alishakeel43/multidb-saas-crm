<?php

namespace Webkul\Assignment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Assignment\Services\LeadDuplicateService;

class LeadDuplicateController extends Controller
{
    public function __construct(
        protected LeadDuplicateService $duplicates,
    ) {}

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'person_id' => 'nullable|integer',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:50',
            'exclude_lead_id' => 'nullable|integer',
        ]);

        $matches = $this->duplicates->findDuplicates(
            $data['person_id'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['exclude_lead_id'] ?? null,
        );

        return response()->json([
            'count' => $matches->count(),
            'data' => $matches->map(fn ($lead) => [
                'id' => $lead->id,
                'title' => $lead->title,
                'person_id' => $lead->person_id,
                'person_name' => $lead->person?->name,
                'user_id' => $lead->user_id,
            ])->values(),
        ]);
    }
}
