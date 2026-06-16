<?php

namespace App\Services\Meetings;

use App\Models\CalendarConnection;
use App\Models\CalendarEvent;
use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MeetingBookingService
{
    public function resolveCase(Workspace $workspace, string|int|null $caseReference): ?SupportCase
    {
        if ($caseReference === null || $caseReference === '') {
            return null;
        }

        return SupportCase::where('workspace_id', $workspace->id)
            ->where(function ($query) use ($caseReference) {
                $query->where('case_number', $caseReference)
                    ->orWhere('id', $caseReference);
            })
            ->first();
    }

    public function resolveCaseForCall(Workspace $workspace, ?string $externalCallId): ?SupportCase
    {
        if ($externalCallId === null || trim($externalCallId) === '') {
            return null;
        }

        return SupportCase::where('workspace_id', $workspace->id)
            ->where('external_call_id', trim($externalCallId))
            ->latest('id')
            ->first();
    }

    public function scheduleFromVoice(SupportCase $case, array $attributes): array
    {
        return DB::transaction(function () use ($case, $attributes) {
            $startsAt = $this->parseStartsAt($attributes['dateTime'] ?? null, $attributes['timezone'] ?? null);
            $endsAt = $this->parseEndsAt($startsAt, $attributes['endsAt'] ?? null);
            $timezone = $attributes['timezone'] ?? 'UTC';

            $suggestedEvent = SuggestedEvent::updateOrCreate(
                [
                    'workspace_id' => $case->workspace_id,
                    'case_id' => $case->id,
                    'starts_at' => $startsAt,
                ],
                [
                    'contact_id' => $case->contact_id,
                    'ends_at' => $endsAt,
                    'timezone' => $timezone,
                    'confidence' => 100,
                    'raw_text_span' => $attributes['rawText'] ?? null,
                    'status' => 'pending',
                ]
            );

            $calendarEvent = null;
            $message = 'We have noted your preferred time and our team will follow up shortly to confirm the calendar invite.';

            if ($this->hasGoogleConnection($case->workspace_id)) {
                try {
                    $calendarEvent = $this->confirmSuggestedEvent($suggestedEvent, 'google', $startsAt, $endsAt);
                    $message = 'Meeting successfully booked for ' . $startsAt->format('l, F jS \a\t g:i A') . '. We have added it to the calendar and the user will see it in their account.';
                } catch (\Throwable $e) {
                    report($e);
                    $message = 'We have noted your preferred time of ' . $startsAt->format('l, F jS \a\t g:i A') . ' and our team will follow up shortly to confirm the calendar invite via email.';
                }
            } else {
                $message = 'We have noted your preferred time of ' . $startsAt->format('l, F jS \a\t g:i A') . ' and our team will follow up shortly to confirm the calendar invite via email.';
            }

            return [
                'suggestedEvent' => $suggestedEvent->fresh(),
                'calendarEvent' => $calendarEvent?->fresh(),
                'message' => $message,
                'booked' => (bool) $calendarEvent,
                'startsAt' => $startsAt,
                'endsAt' => $endsAt,
            ];
        });
    }

    public function confirmSuggestedEvent(
        SuggestedEvent $suggestedEvent,
        string $provider,
        ?Carbon $startsAt = null,
        ?Carbon $endsAt = null
    ): CalendarEvent {
        return DB::transaction(function () use ($suggestedEvent, $provider, $startsAt, $endsAt) {
            $startsAt ??= $suggestedEvent->starts_at ?? now()->addDay()->startOfHour();
            $endsAt ??= $suggestedEvent->ends_at ?? $startsAt->copy()->addMinutes(30);

            [$url, $providerEventId, $payload] = $this->dispatchProviderBooking(
                $suggestedEvent,
                $provider,
                $startsAt,
                $endsAt
            );

            if (!$this->bookingSucceeded($provider, $url, $providerEventId)) {
                throw new \RuntimeException($this->providerFailureMessage($provider));
            }

            $event = CalendarEvent::updateOrCreate(
                [
                    'workspace_id' => $suggestedEvent->workspace_id,
                    'case_id' => $suggestedEvent->case_id,
                    'suggested_event_id' => $suggestedEvent->id,
                    'provider' => $provider,
                ],
                [
                    'provider_event_id' => $providerEventId,
                    'contact_id' => $suggestedEvent->contact_id ?? $suggestedEvent->supportCase?->contact_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'timezone' => $suggestedEvent->timezone ?? 'UTC',
                    'status' => 'created',
                    'url' => $url,
                    'payload' => $payload,
                ]
            );

            $suggestedEvent->update([
                'contact_id' => $suggestedEvent->contact_id ?? $suggestedEvent->supportCase?->contact_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
            ]);

            return $event;
        });
    }

    private function dispatchProviderBooking(
        SuggestedEvent $suggestedEvent,
        string $provider,
        Carbon $startsAt,
        Carbon $endsAt
    ): array {
        return match ($provider) {
            'google' => $this->createGoogleEvent($suggestedEvent, $startsAt, $endsAt),
            'calendly' => $this->buildCalendlyLink($suggestedEvent, $startsAt),
            'ics' => [null, null, null],
            default => [null, null, null],
        };
    }

    private function createGoogleEvent(SuggestedEvent $suggestedEvent, Carbon $startsAt, Carbon $endsAt): array
    {
        $connection = CalendarConnection::where('workspace_id', $suggestedEvent->workspace_id)
            ->where('provider', 'google')
            ->first();

        if (!$connection) {
            return [null, null, null];
        }

        $context = $this->bookingContext($suggestedEvent);
        $eventBody = [
            'summary' => $context['summary'],
            'description' => $context['description'],
            'start' => ['dateTime' => $startsAt->toRfc3339String(), 'timeZone' => $suggestedEvent->timezone ?? 'UTC'],
            'end' => ['dateTime' => $endsAt->toRfc3339String(), 'timeZone' => $suggestedEvent->timezone ?? 'UTC'],
        ];

        if ($context['email'] && filter_var($context['email'], FILTER_VALIDATE_EMAIL)) {
            $attendee = [
                'email' => $context['email'],
            ];

            if ($context['name']) {
                $attendee['displayName'] = $context['name'];
            }

            $eventBody['attendees'] = [$attendee];
        }

        $response = Http::withToken($connection->tokens['access_token'] ?? '')
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $eventBody);

        if (!$response->ok()) {
            return [null, null, ['error' => $response->json()]];
        }

        return [
            $response->json('htmlLink'),
            $response->json('id'),
            ['request' => $eventBody, 'response' => $response->json()],
        ];
    }

    private function buildCalendlyLink(SuggestedEvent $suggestedEvent, Carbon $startsAt): array
    {
        $connection = CalendarConnection::where('workspace_id', $suggestedEvent->workspace_id)
            ->where('provider', 'calendly')
            ->first();

        $baseLink = $connection?->calendly_scheduling_link;
        if (!$baseLink) {
            return [null, null, null];
        }

        $context = $this->bookingContext($suggestedEvent);
        $params = array_filter([
            'name' => $context['name'],
            'email' => $context['email'],
            'month' => $startsAt->format('Y-m'),
            'date' => $startsAt->format('Y-m-d'),
            'a1' => $context['phone'],
            'a2' => $context['ticket'],
            'a3' => $context['issue'],
            'a4' => $context['property_unit'],
            'a5' => $context['visit_context'],
        ], fn ($value) => filled($value));

        return [
            $this->appendQueryString($baseLink, $params),
            null,
            ['params' => $params, 'context' => $context],
        ];
    }

    private function bookingContext(SuggestedEvent $suggestedEvent): array
    {
        $suggestedEvent->loadMissing(['contact', 'supportCase.contact']);

        $case = $suggestedEvent->supportCase;
        $contact = $suggestedEvent->contact ?: $case?->contact;
        $structuredPayload = is_array($case?->structured_payload) ? $case->structured_payload : [];
        $ticket = $case?->case_number ? "Case #{$case->case_number}" : null;
        $property = $case?->propertyDisplay();
        $unit = $case?->unitDisplay();
        $accessNotes = $case?->accessDetailsDisplay();
        $preferredWindow = $case?->preferredVisitWindowDisplay();

        $name = $this->firstFilled(
            $contact?->name,
            data_get($structuredPayload, 'requesterName'),
            data_get($structuredPayload, 'requester_name'),
            data_get($structuredPayload, 'fullName'),
            data_get($structuredPayload, 'full_name'),
            data_get($structuredPayload, 'name')
        );
        $email = $this->firstFilled(
            $case?->requester_email,
            $contact?->email,
            data_get($structuredPayload, 'requesterEmail'),
            data_get($structuredPayload, 'requester_email'),
            data_get($structuredPayload, 'email')
        );
        $phone = $this->firstFilled(
            $case?->requester_phone,
            $contact?->phone_e164,
            data_get($structuredPayload, 'requesterPhone'),
            data_get($structuredPayload, 'requester_phone'),
            data_get($structuredPayload, 'phoneNumber'),
            data_get($structuredPayload, 'phone_number'),
            data_get($structuredPayload, 'callbackNumber')
        );
        $issue = $this->firstFilled($case?->title, $case?->category, $case?->description);
        $propertyUnit = trim(implode(' ', array_filter([
            $property,
            $unit ? "Unit {$unit}" : null,
        ])));
        $visitContext = trim(implode(' | ', array_filter([
            $preferredWindow ? "Preferred window: {$preferredWindow}" : null,
            $accessNotes ? "Access: {$accessNotes}" : null,
        ])));

        $descriptionLines = array_filter([
            $ticket,
            $name ? "Caller: {$name}" : null,
            $phone ? "Phone: {$phone}" : null,
            $email ? "Email: {$email}" : null,
            $propertyUnit ? "Property: {$propertyUnit}" : null,
            $preferredWindow ? "Preferred window: {$preferredWindow}" : null,
            $accessNotes ? "Access notes: {$accessNotes}" : null,
            $case?->description ? "\nIssue details:\n{$case->description}" : null,
            $case ? "\nTicket link: " . route('app.tickets.show', $case) : null,
        ]);

        return [
            'summary' => $case
                ? "{$ticket}: {$case->title}"
                : 'tickIt follow-up',
            'description' => implode("\n", $descriptionLines),
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'ticket' => $ticket,
            'issue' => $issue,
            'property_unit' => $propertyUnit,
            'visit_context' => $visitContext,
        ];
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $normalized = trim((string) $value);

            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function appendQueryString(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        $fragment = '';
        if (str_contains($url, '#')) {
            [$url, $fragment] = explode('#', $url, 2);
            $fragment = '#' . $fragment;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986) . $fragment;
    }

    private function hasGoogleConnection(int $workspaceId): bool
    {
        $connection = CalendarConnection::where('workspace_id', $workspaceId)
            ->where('provider', 'google')
            ->first();

        return !empty($connection?->tokens['access_token']);
    }

    private function bookingSucceeded(string $provider, ?string $url, ?string $providerEventId): bool
    {
        return match ($provider) {
            'google' => filled($providerEventId),
            'calendly' => filled($url),
            'ics' => true,
            default => false,
        };
    }

    private function providerFailureMessage(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google Calendar booking failed.',
            'calendly' => 'Calendly scheduling link is not configured.',
            'ics' => 'Unable to generate calendar event.',
            default => 'Calendar booking failed.',
        };
    }

    private function parseStartsAt(?string $value, ?string $timezone): Carbon
    {
        if (!$value) {
            return now()->addDay()->setHour(14)->setMinute(0)->setSecond(0);
        }

        try {
            return Carbon::parse($value, $timezone ?: 'UTC');
        } catch (\Throwable) {
            return now()->addDay()->setHour(14)->setMinute(0)->setSecond(0);
        }
    }

    private function parseEndsAt(Carbon $startsAt, ?string $value): Carbon
    {
        if (!$value) {
            return $startsAt->copy()->addMinutes(30);
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return $startsAt->copy()->addMinutes(30);
        }
    }
}
