<?php

namespace Webkul\Booking\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Booking\Models\Booking;
use Webkul\Booking\Models\BookingService;
use Webkul\Booking\Services\BookingServiceManager;
use Webkul\Booking\States\Cancelled;
use Webkul\Booking\States\Confirmed;
use Webkul\User\Repositories\UserRepository;

class BookingController extends Controller
{
    public function __construct(
        protected BookingServiceManager $bookings,
        protected UserRepository $userRepository,
    ) {}

    public function index(): View
    {
        return view('booking::index', [
            'bookings' => Booking::query()->with(['service', 'user'])->latest('starts_at')->limit(100)->get(),
            'services' => BookingService::query()->where('is_active', true)->orderBy('name')->get(),
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_service_id' => 'required|integer',
            'user_id' => 'required|integer',
            'person_id' => 'nullable|integer',
            'lead_id' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string|max:50',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string',
            'auto_confirm' => 'sometimes|boolean',
        ]);

        $data['auto_confirm'] = $request->boolean('auto_confirm');

        try {
            $this->bookings->book($data);
            session()->flash('success', trans('booking::app.messages.created'));
        } catch (Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }

        return redirect()->route('admin.bookings.index');
    }

    public function confirm(int $id): RedirectResponse
    {
        try {
            $booking = Booking::query()->findOrFail($id);
            $booking->status->transitionTo(Confirmed::class);
            session()->flash('success', trans('booking::app.messages.confirmed'));
        } catch (Throwable) {
            session()->flash('error', trans('booking::app.messages.transition-failed'));
        }

        return redirect()->route('admin.bookings.index');
    }

    public function cancel(int $id): RedirectResponse
    {
        try {
            $booking = Booking::query()->findOrFail($id);
            $booking->status->transitionTo(Cancelled::class);
            session()->flash('success', trans('booking::app.messages.cancelled'));
        } catch (Throwable) {
            session()->flash('error', trans('booking::app.messages.transition-failed'));
        }

        return redirect()->route('admin.bookings.index');
    }

    public function reschedule(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => 'required|date',
        ]);

        try {
            $booking = Booking::query()->findOrFail($id);
            $this->bookings->reschedule($booking, $data['starts_at']);
            session()->flash('success', trans('booking::app.messages.rescheduled'));
        } catch (Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }

        return redirect()->route('admin.bookings.index');
    }
}
