<?php

namespace Webkul\Consent\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Consent\Models\PersonConsent;
use Webkul\Consent\Services\ConsentService;
use Webkul\Contact\Repositories\PersonRepository;

class ConsentController extends Controller
{
    public function __construct(
        protected ConsentService $consents,
        protected PersonRepository $personRepository,
    ) {}

    public function index(): View
    {
        return view('consent::index', [
            'channels' => ConsentService::CHANNELS,
            'consents' => PersonConsent::query()->with('person')->latest('updated_at')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'person_id' => 'required|integer',
            'channel' => 'required|in:'.implode(',', ConsentService::CHANNELS),
            'is_opted_in' => 'required|boolean',
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $person = $this->personRepository->findOrFail($data['person_id']);

        $this->consents->set(
            $person,
            $data['channel'],
            (bool) $data['is_opted_in'],
            $data['source'] ?? 'admin',
            $data['notes'] ?? null,
        );

        session()->flash('success', trans('consent::app.messages.saved'));

        return redirect()->route('admin.settings.consent.index');
    }
}
