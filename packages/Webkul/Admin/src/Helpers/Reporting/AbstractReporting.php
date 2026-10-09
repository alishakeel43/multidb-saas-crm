<?php

namespace Webkul\Admin\Helpers\Reporting;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class AbstractReporting
{
    /**
     * Number of days used when the configured range is missing or unusable.
     */
    public const FALLBACK_RANGE_IN_DAYS = 30;

    /**
     * The starting date for a given period.
     */
    protected Carbon $startDate;

    /**
     * The ending date for a given period.
     */
    protected Carbon $endDate;

    /**
     * The starting date for the previous period.
     */
    protected Carbon $lastStartDate;

    /**
     * The ending date for the previous period.
     */
    protected Carbon $lastEndDate;

    /**
     * Create a helper instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->applyRequest();
    }

    /**
     * The route keeps one controller for the life of the process, so a later
     * stats request must read its own dates instead of the first request's.
     */
    public function applyRequest(): void
    {
        $this->setStartDate($this->requestedDate('start'));

        $this->setEndDate($this->requestedDate('end'));
    }

    /**
     * Read a dashboard date without failing the page on a malformed value.
     */
    protected function requestedDate(string $key): ?Carbon
    {
        if (! request()->filled($key)) {
            return null;
        }

        try {
            return request()->date($key)?->copy();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Set the start date, or fall back to the range configured under
     * `general.settings.dashboard` when none is provided.
     *
     * @param  \Carbon\Carbon|null  $startDate
     * @return void
     */
    public function setStartDate(?Carbon $startDate = null): self
    {
        $this->startDate = $startDate ? $startDate->copy()->startOfDay() : $this->getDefaultStartDate();

        $this->setPreviousPeriod();

        return $this;
    }

    /**
     * Resolve the default start date from the configured dashboard date range.
     *
     * @return \Carbon\Carbon
     */
    public function getDefaultStartDate(): Carbon
    {
        $range = core()->getConfigData('general.settings.dashboard.date_range');

        $startDate = match ($range) {
            '3_months' => now()->subMonths(3),
            '9_months' => now()->subMonths(9),
            '1_year' => now()->subYear(),
            '2_years' => now()->subYears(2),
            'custom' => now()->subDays($this->getCustomRangeInDays()),
            default => now()->subMonth(),
        };

        return $startDate->startOfDay();
    }

    /**
     * Number of days configured for the custom range. Falls back when the
     * stored value is missing or not a positive number.
     */
    protected function getCustomRangeInDays(): int
    {
        $days = (int) core()->getConfigData('general.settings.dashboard.custom_days');

        return $days > 0 ? $days : self::FALLBACK_RANGE_IN_DAYS;
    }

    /**
     * The end of the range is the end of the selected calendar day. A missing
     * or future day stops at the end of today, so later hours today still count.
     *
     * @param  \Carbon\Carbon|null  $endDate
     */
    public function setEndDate(?Carbon $endDate = null): self
    {
        $end = $endDate ? $endDate->copy()->endOfDay() : now()->endOfDay();

        if ($end->greaterThan(now()->endOfDay())) {
            $end = now()->endOfDay();
        }

        $this->endDate = $end;

        if (isset($this->startDate) && $this->startDate->greaterThan($this->endDate)) {
            $this->startDate = $this->endDate->copy()->startOfDay();
        }

        $this->setPreviousPeriod();

        return $this;
    }

    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    /**
     * The previous window is the same number of calendar days and ends the
     * day before the selected range starts, so the two ranges do not overlap.
     */
    private function setPreviousPeriod(): void
    {
        if (! isset($this->startDate, $this->endDate)) {
            return;
        }

        $startDay = $this->startDate->copy()->startOfDay();
        $days = $this->inclusiveDayCount($startDay, $this->endDate);

        $this->lastStartDate = $startDay->copy()->subDays($days)->startOfDay();
        $this->lastEndDate = $startDay->copy()->subDay()->endOfDay();
    }

    /**
     * Inclusive calendar-day count. DateInterval::$days stays correct across
     * DST, unlike a truncated Carbon diffInDays() float.
     */
    public function inclusiveDayCount(Carbon $startDate, Carbon $endDate): int
    {
        $days = $startDate->copy()->startOfDay()->diff($endDate->copy()->startOfDay())->days;

        return max(1, (int) $days + 1);
    }

    /**
     * Get the last start date.
     *
     * @return \Carbon\Carbon
     */
    public function getLastStartDate(): Carbon
    {
        return $this->lastStartDate;
    }

    /**
     * Get the last end date.
     *
     * @return \Carbon\Carbon
     */
    public function getLastEndDate(): Carbon
    {
        return $this->lastEndDate;
    }

    /**
     * Percentage change from the previous period to the current one.
     *
     * A zero previous value is not divided. When there is no baseline, null is
     * returned so the UI can hide a meaningless percentage.
     *
     * @param  float|int|string|null  $previous
     * @param  float|int|string|null  $current
     */
    public function getPercentageChange($previous, $current): ?float
    {
        $previous = (float) $previous;
        $current = (float) $current;

        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Share of a closed total. Zero closed leads is 0%, never a division error.
     *
     * @param  float|int|string|null  $part
     * @param  float|int|string|null  $whole
     */
    public function sharePercentage($part, $whole): float
    {
        $part = (float) $part;
        $whole = (float) $whole;

        if ($whole <= 0.0) {
            return 0.0;
        }

        return ($part / $whole) * 100;
    }

    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  string  $period
     * @return array
     */
    public function getTimeInterval($startDate, $endDate, $dateColumn, $period)
    {
        if ($period == 'auto') {
            $totalMonths = $startDate->diffInMonths($endDate) + 1;

            /**
             * If the difference between the start and end date is more than 5 months
             */
            $intervals = $this->getMonthsInterval($startDate, $endDate);

            if (! empty($intervals)) {
                return [
                    'group_column' => "MONTH($dateColumn)",
                    'intervals' => $intervals,
                ];
            }

            /**
             * If the difference between the start and end date is more than 6 weeks
             */
            $intervals = $this->getWeeksInterval($startDate, $endDate);

            if (! empty($intervals)) {
                return [
                    'group_column' => "WEEK($dateColumn)",
                    'intervals' => $intervals,
                ];
            }

            /**
             * If the difference between the start and end date is less than 6 weeks
             */
            return [
                'group_column' => "DAYOFYEAR($dateColumn)",
                'intervals' => $this->getDaysInterval($startDate, $endDate),
            ];
        } else {
            $datePeriod = CarbonPeriod::create($this->startDate, "1 $period", $this->endDate);

            if ($period == 'year') {
                $formatter = '?';
            } elseif ($period == 'month') {
                $formatter = '?-?';
            } else {
                $formatter = '?-?-?';
            }

            $groupColumn = 'DATE_FORMAT('.$dateColumn.', "'.Str::replaceArray('?', ['%Y', '%m', '%d'], $formatter).'")';

            $intervals = [];

            foreach ($datePeriod as $date) {
                $formattedDate = $date->format(Str::replaceArray('?', ['Y', 'm', 'd'], $formatter));

                $intervals[] = [
                    'filter' => $formattedDate,
                    'start' => $formattedDate,
                ];
            }

            return [
                'group_column' => $groupColumn,
                'intervals' => $intervals,
            ];
        }
    }

    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getMonthsInterval($startDate, $endDate)
    {
        $intervals = [];

        $totalMonths = $startDate->diffInMonths($endDate) + 1;

        /**
         * If the difference between the start and end date is less than 5 months
         */
        if ($totalMonths <= 5) {
            return $intervals;
        }

        for ($i = 0; $i < $totalMonths; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addMonths($i);

            $start = $intervalStartDate->startOfDay();

            $end = ($totalMonths - 1 == $i)
                ? $endDate
                : $intervalStartDate->addMonth()->subDay()->endOfDay();

            $intervals[] = [
                'filter' => $start->month,
                'start' => $start->format('d M'),
                'end' => $end->format('d M'),
            ];
        }

        return $intervals;
    }

    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getWeeksInterval($startDate, $endDate)
    {
        $intervals = [];

        $startWeekDay = Carbon::createFromTimeString(core()->xWeekRange($startDate, 0).' 00:00:01');

        $endWeekDay = Carbon::createFromTimeString(core()->xWeekRange($endDate, 1).' 23:59:59');

        $totalWeeks = $startWeekDay->diffInWeeks($endWeekDay);

        /**
         * If the difference between the start and end date is less than 6 weeks
         */
        if ($totalWeeks <= 6) {
            return $intervals;
        }

        for ($i = 0; $i < $totalWeeks; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addWeeks($i);

            $start = $i == 0
                ? $startDate
                : Carbon::createFromTimeString(core()->xWeekRange($intervalStartDate, 0).' 00:00:01');

            $end = ($totalWeeks - 1 == $i)
                ? $endDate
                : Carbon::createFromTimeString(core()->xWeekRange($intervalStartDate->subDay(), 1).' 23:59:59');

            $intervals[] = [
                'filter' => $start->week,
                'start' => $start->format('d M'),
                'end' => $end->format('d M'),
            ];
        }

        return $intervals;
    }

    /**
     * Returns time intervals.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @return array
     */
    public function getDaysInterval($startDate, $endDate)
    {
        $intervals = [];

        $totalDays = $startDate->diffInDays($endDate) + 1;

        for ($i = 0; $i < $totalDays; $i++) {
            $intervalStartDate = clone $startDate;

            $intervalStartDate->addDays($i);

            $intervals[] = [
                'filter' => $intervalStartDate->dayOfYear,
                'start' => $intervalStartDate->startOfDay()->format('d M'),
                'end' => $intervalStartDate->endOfDay()->format('d M'),
            ];
        }

        return $intervals;
    }
}
