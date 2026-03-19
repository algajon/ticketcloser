<?php

namespace App\Jobs;

use App\Models\SuggestedEvent;
use App\Models\SupportCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Analyses a newly created or updated Case's description
 * and extracts any date/time mentions to create a SuggestedEvent.
 */
class ExtractSuggestedEvents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public SupportCase $case)
    {
    }

    public function handle(): void
    {
        $text = ($this->case->description ?? '') . ' ' . ($this->case->subject ?? '');

        $extractions = $this->parseDateTime($text);

        foreach ($extractions as $extraction) {
            SuggestedEvent::create([
                'workspace_id' => $this->case->workspace_id,
                'case_id' => $this->case->id,
                'starts_at' => $extraction['starts_at'],
                'ends_at' => $extraction['ends_at'],
                'timezone' => 'UTC',
                'confidence' => $extraction['confidence'],
                'raw_text_span' => $extraction['raw'],
                'status' => 'pending',
            ]);

            Log::info('ExtractSuggestedEvents: suggestion created', [
                'case_id' => $this->case->id,
                'starts_at' => $extraction['starts_at'],
                'confidence' => $extraction['confidence'],
            ]);
        }
    }

    /**
     * Detect common date/time patterns in text.
     * Returns an array of extractions with starts_at, ends_at, confidence, raw.
     *
     * This is a lightweight regex-based extractor (Phase 1 fallback).
     * For production accuracy, swap in a proper NLP service/LLM.
     */
    protected function parseDateTime(string $text): array
    {
        $results = [];

        // Pattern 1: "meeting on March 5 at 2pm", "appointment on 2026-03-10 at 14:00"
        $patterns = [
            // "March 5 at 3pm" / "5 March 2026 at 14:00"
            '/(\b(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{1,2}(?:\s*,\s*\d{4})?\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            // "tomorrow at 2pm", "next Monday at 3:30pm"
            '/(\b(?:tomorrow|next\s+(?:monday|tuesday|wednesday|thursday|friday|saturday|sunday))\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
            // ISO date: "2026-03-15 at 14:00"
            '/(\d{4}-\d{2}-\d{2}\s+at\s+\d{1,2}(?::\d{2})?(?:\s*(?:am|pm))?)/i',
            // "on the 5th at 2pm"
            '/(\bon\s+the\s+\d{1,2}(?:st|nd|rd|th)\s+at\s+\d{1,2}(?::\d{2})?\s*(?:am|pm)?)/i',
        ];

        foreach ($patterns as $idx => $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $raw) {
                    try {
                        $parsed = \Carbon\Carbon::parse($raw);
                        $results[] = [
                            'raw' => $raw,
                            'starts_at' => $parsed->toDateTimeString(),
                            'ends_at' => $parsed->addHour()->toDateTimeString(),
                            'confidence' => $idx === 2 ? 90 : ($idx === 0 ? 75 : 60),
                        ];
                    } catch (\Exception) {
                        // Carbon couldn't parse — skip
                    }
                }
            }
        }

        return $results;
    }
}
