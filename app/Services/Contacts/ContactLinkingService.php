<?php

namespace App\Services\Contacts;

use App\Models\CalendarEvent;
use App\Models\Contact;
use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContactLinkingService
{
    private const INVALID_NAME_TOKENS = [
        'a', 'an', 'about', 'afternoon', 'am', 'and', 'appointment', 'bring', 'bringing', 'broken',
        'call', 'calling', 'coming', 'cosmetic', 'could', 'day', 'evening', 'for', 'from', 'gonna',
        'going', 'got', 'have', 'having', 'help', 'here', 'hi', 'i', 'im', 'is', 'issue', 'it',
        'its', 'just', 'leak', 'leaking', 'maintenance', 'meeting', 'morning', 'my', 'need', 'no',
        'not', 'now', 'of', 'okay', 'paint', 'please', 'preset', 'problem', 'prompt', 'report',
        'request', 'scheduling', 'smell', 'someone', 'something', 'support', 'system', 'team',
        'thanks', 'that', 'the', 'there', 'time', 'to', 'today', 'tomorrow', 'trying', 'unit',
        'urgent', 'visit', 'wall', 'water', 'with', 'workflow', 'yes', 'yesterday', 'current',
        'direction',
    ];

    private const INVALID_NAME_PHRASES = [
        'already on file',
        'all on file',
        'caller',
        'current caller',
        'customer',
        'existing customer',
        'existing caller',
        'information on file',
        'name on file',
        'no name',
        'not provided',
        'not given',
        'not shared',
        'on file',
        'same as file',
        'tenant',
        'unknown',
    ];

    public function resolveForWorkspace(
        Workspace $workspace,
        ?string $phone,
        ?string $name = null,
        ?string $email = null,
        ?string $propertyCode = null,
        ?string $unit = null,
        ?string $notes = null,
    ): ?Contact {
        $phone = $this->normalizePhone($phone);
        $name = $this->sanitizeHumanName($name);
        $email = $this->normalizeEmail($email);
        $propertyCode = $this->normalizeNullableString($propertyCode);
        $unit = $this->normalizeNullableString($unit);
        $notes = $this->normalizeNullableString($notes);

        if (! $phone && ! $email) {
            return null;
        }

        if ($email) {
            $byEmail = Contact::query()
                ->where('workspace_id', $workspace->id)
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();

            if ($byEmail) {
                return $this->fillAndSave($byEmail, $phone, $name, $email, $propertyCode, $unit, $notes);
            }
        }

        $phoneMatches = $phone ? $this->phoneMatches($workspace, $phone) : collect();
        if ($phoneMatches->isNotEmpty()) {
            $canonical = $this->consolidatePhoneMatches($phoneMatches, $name, $email);

            return $this->fillAndSave($canonical, $phone, $name, $email, $propertyCode, $unit, $notes);
        }

        $contact = new Contact([
            'workspace_id' => $workspace->id,
            'phone_e164' => $phone,
            'email' => $email,
        ]);

        return $this->fillAndSave($contact, $phone, $name, $email, $propertyCode, $unit, $notes);
    }

    public function lookupForWorkspace(
        Workspace $workspace,
        ?string $phone = null,
        ?string $email = null,
    ): ?Contact {
        $phone = $this->normalizePhone($phone);
        $email = $this->normalizeEmail($email);

        if ($email) {
            $byEmail = Contact::query()
                ->where('workspace_id', $workspace->id)
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();

            if ($byEmail) {
                return $byEmail;
            }
        }

        if (! $phone) {
            return null;
        }

        $matches = $this->phoneMatches($workspace, $phone);

        if ($matches->isEmpty()) {
            return null;
        }

        return $this->consolidatePhoneMatches($matches, null, $email);
    }

    public function repairWorkspaceContacts(Workspace $workspace): int
    {
        $mergedDuplicates = 0;

        Contact::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('name')
            ->get()
            ->each(function (Contact $contact) {
                $sanitized = $this->sanitizeHumanName($contact->name);

                if ($sanitized !== $contact->name) {
                    $contact->forceFill(['name' => $sanitized])->save();
                }
            });

        Contact::query()
            ->where('workspace_id', $workspace->id)
            ->whereNotNull('phone_e164')
            ->pluck('phone_e164')
            ->filter()
            ->unique()
            ->values()
            ->each(function (string $phone) use ($workspace, &$mergedDuplicates) {
                $matches = $this->phoneMatches($workspace, $phone);

                if ($matches->count() <= 1) {
                    return;
                }

                $mergedDuplicates += $matches->count() - 1;
                $this->consolidatePhoneMatches($matches, null, null);
            });

        return $mergedDuplicates;
    }

    private function phoneMatches(Workspace $workspace, string $phone): Collection
    {
        $phoneSearch = ltrim(preg_replace('/\D+/', '', $phone), '1');
        if (strlen($phoneSearch) < 6) {
            return collect();
        }

        return Contact::query()
            ->where('workspace_id', $workspace->id)
            ->where(function ($query) use ($phone, $phoneSearch) {
                $query->where('phone_e164', $phone)
                    ->orWhere('phone_e164', 'like', '%' . $phoneSearch . '%');
            })
            ->orderByRaw('CASE WHEN name IS NULL OR TRIM(name) = ? THEN 1 ELSE 0 END', [''])
            ->orderByDesc('updated_at')
            ->get();
    }

    private function fillAndSave(Contact $contact, ?string $phone, ?string $name, ?string $email, ?string $propertyCode, ?string $unit, ?string $notes): Contact
    {
        if ($phone && (! $contact->phone_e164 || strlen((string) $contact->phone_e164) < strlen($phone))) {
            $contact->phone_e164 = $phone;
        }

        if ($name && $this->shouldReplaceContactName($contact->name, $name)) {
            $contact->name = $name;
        }

        if ($email && ! filled($contact->email)) {
            $contact->email = $email;
        }

        if ($propertyCode && ! filled($contact->property_code)) {
            $contact->property_code = $propertyCode;
        }

        if ($unit && ! filled($contact->unit)) {
            $contact->unit = $unit;
        }

        if ($notes && ! filled($contact->notes)) {
            $contact->notes = $notes;
        }

        $contact->save();

        return $contact;
    }

    private function consolidatePhoneMatches(Collection $matches, ?string $incomingName, ?string $incomingEmail): Contact
    {
        if ($matches->count() === 1) {
            return $matches->first();
        }

        return DB::transaction(function () use ($matches, $incomingName, $incomingEmail) {
            $canonical = $matches->sort(function (Contact $left, Contact $right) use ($incomingName, $incomingEmail) {
                $leftScore = $this->contactStrengthScore($left, $incomingName, $incomingEmail);
                $rightScore = $this->contactStrengthScore($right, $incomingName, $incomingEmail);

                if ($leftScore === $rightScore) {
                    return $left->id <=> $right->id;
                }

                return $rightScore <=> $leftScore;
            })->first();

            foreach ($matches as $match) {
                if ($match->id === $canonical->id) {
                    continue;
                }

                $this->mergeContactIntoCanonical($canonical, $match);
            }

            $canonical->refresh();

            return $canonical;
        });
    }

    private function mergeContactIntoCanonical(Contact $canonical, Contact $duplicate): void
    {
        if ($this->shouldReplaceContactName($canonical->name, $duplicate->name ?? '')) {
            $canonical->name = $this->sanitizeHumanName($duplicate->name);
        }

        if (! filled($canonical->email) && filled($duplicate->email)) {
            $canonical->email = $duplicate->email;
        }

        if (! filled($canonical->property_code) && filled($duplicate->property_code)) {
            $canonical->property_code = $duplicate->property_code;
        }

        if (! filled($canonical->unit) && filled($duplicate->unit)) {
            $canonical->unit = $duplicate->unit;
        }

        if (! filled($canonical->notes) && filled($duplicate->notes)) {
            $canonical->notes = $duplicate->notes;
        }

        if (! filled($canonical->phone_e164) && filled($duplicate->phone_e164)) {
            $canonical->phone_e164 = $duplicate->phone_e164;
        }

        $canonical->save();

        SupportCase::query()
            ->where('contact_id', $duplicate->id)
            ->update(['contact_id' => $canonical->id]);

        SuggestedEvent::query()
            ->where('contact_id', $duplicate->id)
            ->update(['contact_id' => $canonical->id]);

        CalendarEvent::query()
            ->where('contact_id', $duplicate->id)
            ->update(['contact_id' => $canonical->id]);

        $duplicate->delete();
    }

    private function shouldReplaceContactName(?string $existing, string $incoming): bool
    {
        $incoming = $this->sanitizeHumanName($incoming);
        if (! $incoming) {
            return false;
        }

        $existing = $this->sanitizeHumanName($existing);

        if (! $existing) {
            return true;
        }

        if ($this->namesLikelyMatch($existing, $incoming)) {
            return $this->nameStrengthScore($incoming) >= $this->nameStrengthScore($existing)
                && strlen(trim($incoming)) > strlen(trim($existing));
        }

        return false;
    }

    private function namesLikelyMatch(?string $left, ?string $right): bool
    {
        $left = $this->normalizeName($left);
        $right = $this->normalizeName($right);

        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if ($this->namesSharePrefix($left, $right)) {
            return true;
        }

        similar_text($left, $right, $similarity);

        return $similarity >= 82.0;
    }

    private function namesSharePrefix(string $left, string $right): bool
    {
        $leftParts = array_values(array_filter(explode(' ', $left)));
        $rightParts = array_values(array_filter(explode(' ', $right)));

        if (count($leftParts) === 0 || count($rightParts) === 0) {
            return false;
        }

        $shorter = count($leftParts) <= count($rightParts) ? $leftParts : $rightParts;
        $longer = count($leftParts) <= count($rightParts) ? $rightParts : $leftParts;

        if (count($shorter) > 2 || count($longer) <= count($shorter)) {
            return false;
        }

        foreach ($shorter as $index => $part) {
            if (($longer[$index] ?? null) !== $part) {
                return false;
            }
        }

        return true;
    }

    private function normalizeName(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9\s]/', '', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function sanitizeHumanName(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim((string) preg_replace('/[^\pL\s\'-]+/u', ' ', $value));
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        if ($value === '') {
            return null;
        }

        $normalizedPhrase = mb_strtolower($value);
        if (in_array($normalizedPhrase, self::INVALID_NAME_PHRASES, true)) {
            return null;
        }

        $parts = array_values(array_filter(explode(' ', $value)));

        if (count($parts) === 0 || count($parts) > 4) {
            return null;
        }

        foreach ($parts as $part) {
            if ($this->isInvalidNameToken($part) || mb_strlen($part) < 2) {
                return null;
            }
        }

        return Str::title(implode(' ', array_map('mb_strtolower', $parts)));
    }

    private function contactStrengthScore(Contact $contact, ?string $incomingName, ?string $incomingEmail): int
    {
        $score = $this->nameStrengthScore($contact->name);

        if ($incomingName && $this->namesLikelyMatch($contact->name, $incomingName)) {
            $score += 4;
        }

        if ($incomingEmail && filled($contact->email) && strcasecmp((string) $contact->email, $incomingEmail) === 0) {
            $score += 3;
        }

        if (filled($contact->property_code)) {
            $score += 1;
        }

        if (filled($contact->unit)) {
            $score += 1;
        }

        if (filled($contact->email)) {
            $score += 1;
        }

        return $score;
    }

    private function nameStrengthScore(?string $name): int
    {
        $name = $this->sanitizeHumanName($name);

        if (! $name) {
            return 0;
        }

        $parts = array_values(array_filter(explode(' ', $name)));
        $score = count($parts) * 2;

        if (count($parts) >= 2) {
            $score += 2;
        }

        if (mb_strlen($name) >= 10) {
            $score += 1;
        }

        return $score;
    }

    private function isInvalidNameToken(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), self::INVALID_NAME_TOKENS, true);
    }

    private function normalizePhone(?string $value): ?string
    {
        $value = $this->normalizeNullableString($value);
        if (! $value) {
            return null;
        }

        $digits = preg_replace('/[^\d+]/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function normalizeEmail(?string $value): ?string
    {
        $value = strtolower((string) $this->normalizeNullableString($value));

        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }
}
