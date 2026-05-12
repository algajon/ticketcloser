<?php

namespace App\Models;

use App\Support\RegionalPilotStackCatalog;
use Illuminate\Database\Eloquent\Model;

class CallEvent extends Model
{
    protected $fillable = [
        'workspace_id','queue_id','vapi_call_id','from_number','to_number','duration_seconds','cost','transcript','recording_url','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function formatUsdCost(?float $cost, ?int $precision = null): string
    {
        if ($cost === null) {
            return '0';
        }

        $precision ??= 2;

        return '$' . number_format($cost, $precision) . ' USD';
    }

    public function formattedCost(?int $precision = null): string
    {
        return static::formatUsdCost($this->cost, $precision);
    }

    public function transcriptLanguageCode(): ?string
    {
        return RegionalPilotStackCatalog::normalizeLanguageCode(
            data_get($this->meta, 'language.transcript.code'),
            data_get($this->meta, 'language.configured.code')
        );
    }

    public function transcriptLanguageLabel(): ?string
    {
        return RegionalPilotStackCatalog::languageLabel(
            data_get($this->meta, 'language.transcript.code'),
            data_get($this->meta, 'language.configured.code')
        );
    }

    public function transcriptLanguageSourceLabel(): ?string
    {
        return data_get($this->meta, 'language.transcript.source_label')
            ?: (filled(data_get($this->meta, 'language.transcript.code')) ? 'Detected from call' : null);
    }

    public function configuredLanguageLabel(): ?string
    {
        return RegionalPilotStackCatalog::languageLabel(data_get($this->meta, 'language.configured.code'));
    }

    public function transcriberLabel(): ?string
    {
        return data_get($this->meta, 'language.transcriber.label');
    }
}
