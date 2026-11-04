<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Program extends Model
{
    protected $guarded = [];

    public const AUDIENCES = [
        'siswa' => 'Siswa/Mahasiswa',
        'guru' => 'Guru/Dosen',
        'umum' => 'Umum',
    ];

    public const CERTIFICATE_DISK = 'local';

    public const CERTIFICATE_DIR = 'certificate-templates';

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public static function buildMeta(array $d): array
    {
        $meta = [['icon' => 'calendar', 'text' => $d['date_label']]];
        if (! empty($d['time_label'])) {
            $meta[] = ['icon' => 'clock', 'text' => $d['time_label']];
        }
        return $meta;
    }

    public function descriptionHtml(): HtmlString
    {
        return static::renderMarkdown($this->description);
    }

    public function benefitHtml(): HtmlString
    {
        return static::renderMarkdown($this->benefit);
    }

    public static function renderMarkdown(?string $text): HtmlString
    {
        return new HtmlString(Str::markdown((string) $text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));
    }

    public function hasExternalLink(): bool
    {
        return filled($this->whatsapp_group_link);
    }

    public function isForStudents(): bool
    {
        return $this->audience === 'siswa';
    }

    public function isForTeachers(): bool
    {
        return $this->audience === 'guru';
    }

    public function isForPublic(): bool
    {
        return $this->audience === 'umum';
    }

    public function audienceLabel(): string
    {
        return self::AUDIENCES[$this->audience] ?? (string) $this->audience;
    }

    public function hasCertificateTemplate(): bool
    {
        return filled($this->certificate_front) && filled($this->certificate_back);
    }

    public function hasInstructorCertificateTemplate(): bool
    {
        return filled($this->instructor_certificate_front) && filled($this->instructor_certificate_back);
    }
    
    public function hasModeratorCertificateTemplate(): bool
    {
        return filled($this->moderator_certificate_front) && filled($this->moderator_certificate_back);
    }

    public function isFull(): bool
    {
        return $this->quota !== null && $this->registrations()->count() >= $this->quota;
    }
}