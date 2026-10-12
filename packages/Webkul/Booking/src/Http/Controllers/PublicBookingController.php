<?php

namespace Webkul\Booking\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;
use Webkul\Booking\Models\BookingService;
use Webkul\Booking\Services\BookingServiceManager;
use Webkul\User\Models\User;

class PublicBookingController
{
    public function __construct(
        protected BookingServiceManager $bookings,
    ) {}

    public function show(): View
    {
        return view('booking::public', [
            'services' => BookingService::query()->where('is_active', true)->orderBy('name')->get(),
            'users' => User::query()->excludeSupport()->where('status', 1)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_service_id' => 'required|integer',
            'user_id' => 'required|integer',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email',
            'guest_phone' => 'nullable|string|max:50',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $data['auto_confirm'] = false;

        try {
            $this->bookings->book($data);
            session()->flash('success', trans('booking::app.messages.public-created'));
        } catch (Throwable $exception) {
            session()->flash('error', $exception->getMessage());

            return back()->withInput();
        }

        return redirect()->route('bookings.public.show');
    }
}
