@php
    $errors = $errors ?? new \Illuminate\Support\ViewErrorBag();
@endphp

<x-admin::layouts>
    <x-slot:title>
        @lang('telephony::app.settings.title')
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    @lang('telephony::app.settings.title')
                </div>
            </div>
        </div>

        <div class="box-shadow rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-900">
            <form
                method="POST"
                action="{{ route('admin.telephony.settings.update') }}"
            >
                @csrf

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('telephony::app.configuration.telephony.credentials.account-sid')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="telephony[credentials][account_sid]"
                            :value="core()->getConfigData('telephony.credentials.account_sid') ?? ''"
                            :placeholder="__('telephony::app.configuration.telephony.credentials.account-sid')"
                        />
                    </x-admin::form.control-group>
                </div>

                <div class="mb-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('telephony::app.configuration.telephony.credentials.auth-token')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="password"
                            name="telephony[credentials][auth_token]"
                            :value="''"
                            :placeholder="__('telephony::app.settings.auth-token-placeholder')"
                        />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('telephony::app.settings.auth-token-note')
                        </p>
                    </x-admin::form.control-group>
                </div>

                <div class="mb-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('telephony::app.configuration.telephony.credentials.from-number')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="telephony[credentials][from_number]"
                            :value="core()->getConfigData('telephony.credentials.from_number') ?? ''"
                            placeholder="+15005550006"
                        />
                    </x-admin::form.control-group>
                </div>

                <div class="mb-4">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('telephony::app.configuration.telephony.credentials.status-callback-base-url')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="url"
                            name="telephony[credentials][status_callback_base_url]"
                            :value="core()->getConfigData('telephony.credentials.status_callback_base_url') ?? ''"
                            placeholder="https://your-domain.com"
                        />

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @lang('telephony::app.settings.callback-url-note')
                        </p>
                    </x-admin::form.control-group>
                </div>

                <div class="mb-6">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('telephony::app.configuration.telephony.credentials.webhook-signing-enabled')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            name="telephony[credentials][webhook_signing_enabled]"
                        >
                            <option
                                value="1"
                                {{ core()->getConfigData('telephony.credentials.webhook_signing_enabled') == '1' ? 'selected' : '' }}
                            >
                                @lang('admin::app.common.yes')
                            </option>
                            <option
                                value="0"
                                {{ core()->getConfigData('telephony.credentials.webhook_signing_enabled') == '0' ? 'selected' : '' }}
                            >
                                @lang('admin::app.common.no')
                            </option>
                        </x-admin::form.control-group.control>
                    </x-admin::form.control-group>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="primary-button"
                    >
                        @lang('telephony::app.settings.save')
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-admin::layouts>
