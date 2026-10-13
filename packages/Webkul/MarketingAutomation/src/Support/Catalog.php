<?php

namespace Webkul\MarketingAutomation\Support;

class Catalog
{
    public const EVENTS = [
        'leads' => [
            'lead.create.after' => 'Lead created',
            'lead.update.after' => 'Lead updated',
            'lead.delete.before' => 'Lead deleted',
        ],
        'persons' => [
            'contacts.person.create.after' => 'Person created',
            'contacts.person.update.after' => 'Person updated',
            'contacts.person.delete.before' => 'Person deleted',
        ],
        'activities' => [
            'activity.create.after' => 'Activity created',
            'activity.update.after' => 'Activity updated',
            'activity.delete.before' => 'Activity deleted',
        ],
        'quotes' => [
            'quote.create.after' => 'Quote created',
            'quote.update.after' => 'Quote updated',
            'quote.delete.before' => 'Quote deleted',
        ],
    ];

    public const ATTRIBUTES = [
        'leads' => [
            'title' => 'Title',
            'description' => 'Description',
            'lead_value' => 'Lead value',
            'status' => 'Status',
            'lead_source_id' => 'Source ID',
            'user_id' => 'Owner ID',
        ],
        'persons' => [
            'name' => 'Name',
            'job_title' => 'Job title',
            'user_id' => 'Owner ID',
        ],
        'activities' => [
            'title' => 'Title',
            'type' => 'Type',
            'comment' => 'Comment',
        ],
        'quotes' => [
            'subject' => 'Subject',
            'description' => 'Description',
            'grand_total' => 'Grand total',
            'user_id' => 'Owner ID',
        ],
    ];

    public const OPERATORS = ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'contains'];

    public const ACTION_TYPES = ['delay', 'task', 'notification', 'email', 'update'];

    public const UPDATE_FIELDS = [
        'leads' => ['title', 'description', 'lead_value', 'status'],
        'persons' => ['name', 'job_title'],
        'activities' => ['title', 'comment'],
        'quotes' => ['subject', 'description'],
    ];

    public const AUDIENCE_FIELDS = [
        'name' => 'Name',
        'job_title' => 'Job title',
        'email' => 'Email',
    ];

    public const ENGAGEMENT_EVENTS = [
        'email_open' => 'Email opened',
        'email_click' => 'Email clicked',
        'form_submit' => 'Form submitted',
        'unsubscribe' => 'Unsubscribed',
    ];

    /**
     * @return array<string, string>
     */
    public static function eventOptions(): array
    {
        $options = [];

        foreach (self::EVENTS as $entity => $events) {
            foreach ($events as $event => $label) {
                $options[$entity.'|'.$event] = $label;
            }
        }

        return $options;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    public static function parseEvent(string $value): ?array
    {
        $parts = explode('|', $value, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$entity, $event] = $parts;

        if (! isset(self::EVENTS[$entity][$event])) {
            return null;
        }

        return [$entity, $event];
    }

    /**
     * @return list<string>
     */
    public static function listenedEvents(): array
    {
        $events = [];

        foreach (self::EVENTS as $group) {
            foreach (array_keys($group) as $event) {
                $events[] = $event;
            }
        }

        return $events;
    }
}
