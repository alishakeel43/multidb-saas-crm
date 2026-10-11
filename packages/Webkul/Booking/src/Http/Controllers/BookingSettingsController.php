<?php

namespace Webkul\Booking\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Booking\Models\BookingAvailability;
use Webkul\Booking\Models\BookingService;
use Webkul\User\Repositories\UserRepository;

class BookingSettingsController extends Controller
{
    public function __construct(protected UserRepository $userRepository) {}

    public function index(): View
    {
        return view('booking::settings', [
            'services' => BookingService::query()->orderBy('name')->get(),
            'availabilities' => BookingAvailability::query()->with('user')->orderBy('user_id')->orderBy('weekday')->get(),
            'users' => $this->staffUsers(),
        ]);
    }

    /**
     * Active staff users excluding platform support/debug accounts.
     */
    private function staffUsers(): Collection
    {
        $repository = $this->userRepository;

        if (Schema::connection('tenant')->hasColumn('users', 'is_support_account')) {
            $repository = $repository->scopeQuery(
                fn ($query) => $query->where('is_support_account', false)
            );
        }

        return $repository->findWhere(['status' => 1]);
    }

    public function storeService(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'buffer_minutes' => 'nullable|integer|min:0|max:120',
            'description' => 'nullable|string',
        ]);

        BookingService::query()->create([
            ...$data,
            'buffer_minutes' => (int) ($data['buffer_minutes'] ?? 0),
            'is_active' => true,
        ]);

        session()->flash('success', trans('booking::app.messages.service-created'));

        return redirect()->route('admin.settings.booking.index');
    }

    public function storeAvailability(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer',
            'weekday' => 'required|integer|min:0|max:6',
            'starts_at' => 'required',
            'ends_at' => 'required',
        ]);

        BookingAvailability::query()->create([
            ...$data,
            'is_active' => true,
        ]);

        session()->flash('success', trans('booking::app.messages.availability-created'));

        return redirect()->route('admin.settings.booking.index');
    }
}
