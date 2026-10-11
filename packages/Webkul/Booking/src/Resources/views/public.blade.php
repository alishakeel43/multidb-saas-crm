<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} · Book appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-tight">Book an appointment</h1>
        <p class="mt-2 text-sm text-slate-600">Pick a service, staff member, and time. You will receive a confirmation once accepted.</p>

        @if (session('success'))
            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('bookings.public.store') }}" class="mt-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium">Service</label>
                <select name="booking_service_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    <option value="">Select…</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(old('booking_service_id') == $service->id)>{{ $service->name }} ({{ $service->duration_minutes }} min)</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Staff</label>
                <select name="user_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    <option value="">Select…</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Starts at</label>
                <input type="datetime-local" name="starts_at" required value="{{ old('starts_at') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Your name</label>
                    <input type="text" name="guest_name" required value="{{ old('guest_name') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <input type="email" name="guest_email" required value="{{ old('guest_email') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Phone</label>
                <input type="text" name="guest_phone" value="{{ old('guest_phone') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">Request booking</button>
        </form>
    </div>
</body>
</html>
