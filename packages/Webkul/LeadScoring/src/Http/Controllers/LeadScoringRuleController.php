<?php

namespace Webkul\LeadScoring\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\LeadScoring\Models\LeadScoringRule;

class LeadScoringRuleController extends Controller
{
    public function index(): View
    {
        return view('lead_scoring::settings', [
            'rules' => LeadScoringRule::query()->orderByDesc('priority')->orderBy('id')->get(),
            'fields' => [
                'title' => 'Title',
                'lead_value' => 'Lead value',
                'lead_source_id' => 'Source ID',
                'lead_type_id' => 'Type ID',
                'status' => 'Status',
            ],
            'operators' => ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'contains'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'field' => 'required|string|max:64',
            'operator' => 'required|string|max:32',
            'value' => 'nullable|string|max:255',
            'points' => 'required|integer',
            'priority' => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['priority'] = (int) ($data['priority'] ?? 0);

        LeadScoringRule::query()->create($data);
        session()->flash('success', 'Scoring rule saved.');

        return redirect()->route('admin.settings.lead-scoring.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        LeadScoringRule::query()->whereKey($id)->delete();
        session()->flash('success', 'Scoring rule deleted.');

        return redirect()->route('admin.settings.lead-scoring.index');
    }
}
