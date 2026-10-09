@props([
    'name' => 'due_time',
    'value' => null,
    'placeholder' => null,
])

<v-task-time-picker
    name="{{ $name }}"
    initial-value="{{ $value ? substr($value, 0, 5) : '' }}"
    placeholder="{{ $placeholder ?? trans('admin::app.tasks.fields.due_time') }}"
></v-task-time-picker>

@pushOnce('styles')
    <style>
        .task-time-picker-popover.flatpickr-calendar.noCalendar {
            width: auto !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            z-index: 1000 !important;
        }
        .dark .task-time-picker-popover.flatpickr-calendar.noCalendar {
            background: #111827 !important;
            border-color: #374151 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .task-time-picker-popover .flatpickr-time {
            height: 40px !important;
            line-height: 40px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .dark .task-time-picker-popover .flatpickr-time input {
            color: #f3f4f6 !important;
            background: transparent !important;
        }
        .dark .task-time-picker-popover .flatpickr-time .flatpickr-time-separator {
            color: #9ca3af !important;
        }
        .dark .task-time-picker-popover .numInputWrapper span.arrowUp:after {
            border-bottom-color: #9ca3af !important;
        }
        .dark .task-time-picker-popover .numInputWrapper span.arrowDown:after {
            border-top-color: #9ca3af !important;
        }

        /* Input trailing actions and padding */
        .task-time-picker-input {
            padding-right: 56px !important;
        }
        [dir="rtl"] .task-time-picker-input {
            padding-right: 10px !important;
            padding-left: 56px !important;
        }
        .task-time-picker-actions {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            right: 8px !important;
            display: flex !important;
            align-items: center !important;
            gap: 4px !important;
            z-index: 2 !important;
        }
        [dir="rtl"] .task-time-picker-actions {
            right: auto !important;
            left: 8px !important;
        }
        .task-time-picker-clear-icon {
            font-size: 10px !important;
            line-height: 1 !important;
        }
        .task-time-picker-quick-label {
            font-size: 11px !important;
            line-height: 16px !important;
        }
        .task-time-picker-preset-badge {
            font-size: 11px !important;
            line-height: 16px !important;
            padding: 2px 7px !important;
        }
    </style>
@endPushOnce

@pushOnce('scripts')
    <script type="text/x-template" id="v-task-time-picker-template">
        <div class="relative w-full">
            <div class="relative flex items-center">
                <input
                    type="text"
                    ref="timeInput"
                    :name="name"
                    v-model="timeValue"
                    @input="onInputChange"
                    class="task-time-picker-input w-full rounded border border-gray-300 px-2.5 py-2 text-sm font-normal text-gray-800 transition-all hover:border-gray-400 focus:border-brandColor focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-brandColor ltr:pr-14 rtl:pl-14"
                    :placeholder="placeholder || 'HH:MM'"
                    autocomplete="off"
                />

                <div class="task-time-picker-actions absolute top-1/2 flex -translate-y-1/2 transform items-center gap-1 ltr:right-2 rtl:left-2">
                    <!-- Clear Button -->
                    <button
                        type="button"
                        v-if="timeValue"
                        @click.stop="clearTime"
                        class="flex h-5 w-5 items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors cursor-pointer"
                        title="Clear time"
                    >
                        <i class="icon-cross-large task-time-picker-clear-icon"></i>
                    </button>

                    <!-- Clock Icon trigger -->
                    <button
                        type="button"
                        @click="openPicker"
                        class="flex h-5 w-5 items-center justify-center text-gray-400 hover:text-brandColor dark:hover:text-brandColor transition-colors cursor-pointer"
                        title="Select time"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Quick Time Presets -->
            <div class="mt-1.5 flex flex-wrap items-center gap-1">
                <span class="task-time-picker-quick-label text-gray-400 dark:text-gray-500 mr-0.5 select-none">Quick:</span>
                <button
                    type="button"
                    v-for="preset in presets"
                    :key="preset"
                    @click="setTime(preset)"
                    :class="timeValue === preset
                        ? 'bg-brandColor text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                    class="task-time-picker-preset-badge rounded font-medium transition-colors select-none cursor-pointer"
                >
                    @{{ preset }}
                </button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-task-time-picker', {
            template: '#v-task-time-picker-template',

            props: {
                name: {
                    type: String,
                    default: 'due_time',
                },

                initialValue: {
                    type: String,
                    default: '',
                },

                placeholder: {
                    type: String,
                    default: '',
                },
            },

            data() {
                return {
                    timeValue: this.initialValue ? this.initialValue.substring(0, 5) : '',
                    datepicker: null,
                    presets: ['09:00', '11:00', '13:00', '15:00', '17:00'],
                };
            },

            mounted() {
                this.$nextTick(() => {
                    this.initFlatpickr();
                });
            },

            beforeUnmount() {
                if (this.datepicker) {
                    this.datepicker.destroy();
                }
            },

            watch: {
                initialValue(val) {
                    this.timeValue = val ? val.substring(0, 5) : '';
                    if (this.datepicker) {
                        if (this.timeValue) {
                            this.datepicker.setDate(this.timeValue, false);
                        } else {
                            this.datepicker.clear();
                        }
                    }
                },
            },

            methods: {
                initFlatpickr() {
                    const input = this.$refs.timeInput;
                    if (! input || typeof window.Flatpickr === 'undefined') {
                        return;
                    }

                    this.datepicker = new window.Flatpickr(input, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: 'H:i',
                        time_24hr: true,
                        allowInput: true,
                        defaultHour: 9,
                        defaultMinute: 0,
                        minuteIncrement: 5,
                        extraClass: 'task-time-picker-popover',
                        onChange: (selectedDates, dateStr) => {
                            this.timeValue = dateStr;
                        },
                    });

                    if (this.timeValue) {
                        this.datepicker.setDate(this.timeValue, false);
                    }
                },

                openPicker() {
                    if (this.datepicker) {
                        if (this.datepicker.isOpen) {
                            this.datepicker.close();
                        } else {
                            this.datepicker.open();
                        }
                    } else if (this.$refs.timeInput) {
                        this.$refs.timeInput.focus();
                    }
                },

                setTime(val) {
                    this.timeValue = val;
                    if (this.datepicker) {
                        this.datepicker.setDate(val, true);
                    }
                },

                clearTime() {
                    this.timeValue = '';
                    if (this.datepicker) {
                        this.datepicker.clear();
                    }
                },

                onInputChange(event) {
                    this.timeValue = event.target.value;
                    if (this.datepicker && this.timeValue && this.timeValue.length >= 4) {
                        this.datepicker.setDate(this.timeValue, false);
                    }
                },
            },
        });
    </script>
@endPushOnce
