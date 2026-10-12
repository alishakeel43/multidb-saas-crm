<?php

namespace Webkul\Telephony\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Webkul\Core\Repositories\CoreConfigRepository;

class SettingsController extends Controller
{
    public function __construct(
        protected CoreConfigRepository $coreConfigRepository
    ) {}

    /**
     * Display the telephony settings form.
     */
    public function index(): mixed
    {
        abort_unless(bouncer()->hasPermission('telephony.settings.edit'), 403);

        return view('telephony::settings.index');
    }

    /**
     * Persist telephony credentials to the core_config table.
     *
     * Credentials are stored per-tenant in the tenant database and are never
     * returned to the frontend.
     */
    public function update(Request $request): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('telephony.settings.edit'), 403);

        $request->validate([
            'telephony.credentials.account_sid' => ['nullable', 'string', 'max:191'],
            'telephony.credentials.auth_token' => ['nullable', 'string', 'max:191'],
            'telephony.credentials.from_number' => ['nullable', 'string', 'max:30'],
            'telephony.credentials.status_callback_base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $this->coreConfigRepository->create($request->only(['telephony']));

        session()->flash('success', __('telephony::app.settings.saved'));

        return redirect()->back();
    }
}
