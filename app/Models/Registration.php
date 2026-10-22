<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Registration extends Model
{
    // Mengganti $guarded dengan $fillable agar lebih aman dan spesifik
    protected $fillable = [
        'program_id',
        'code',
        'name',
        'email',
        'phone',
        'school_name', // Kolom sekolah/instansi
        'class_name',  // Kolom kelas
        'city',
        'position',
        'motivation',
        'status',
        'certificate_number',
        'certificate_issued_at',
    ];

    protected function casts(): array
    {
        return ['certificate_issued_at' => 'datetime'];
    }

    /** Status peserta. Tautan grup WhatsApp tampil untuk semua status selama peserta sudah mendaftar. */
    public const STATUSES = [
        'registered' => 'Mendaftar',
        'certificate_issued' => 'Terbit sertifikat',
        'certificate_not_issued' => 'Tidak terbit sertifikat',
    ];

    /** Samakan format nomor HP: 0812..., +62812..., 62812... => 0812... */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }

    /**
     * Nomor HP tersimpan sebagaimana diketik saat daftar (0812..., 62812..., atau +62812...),
     * jadi cari ketiga bentuknya.
     *
     * @return list<string>
     */
    public static function phoneVariants(string $phone): array
    {
        $local = self::normalizePhone($phone); // 0812...
        $rest = substr($local, 1);

        return [$local, '62'.$rest, '+62'.$rest];
    }

    public const SESSION_KEY = 'registered_ids';

    /** Ingat pendaftaran yang baru dibuat/diverifikasi di sesi browser ini. */
    public static function rememberInSession(iterable $ids): void
    {
        $merged = collect(session(self::SESSION_KEY, []))
            ->merge($ids)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(-50)
            ->values()
            ->all();

        session([self::SESSION_KEY => $merged]);
    }

    /** Apakah pengunjung (sesi ini) sudah mendaftar pada program tsb? */
    public static function sessionHasRegistered(Program $program): bool
    {
        $ids = session(self::SESSION_KEY, []);

        if ($ids === []) {
            return false;
        }

        return static::whereIn('id', $ids)->where('program_id', $program->id)->exists();
    }

    /** Sertifikat hanya bisa diakses bila statusnya "Terbit sertifikat". */
    public function isCertificateIssued(): bool
    {
        return $this->status === 'certificate_issued';
    }

    /** Format nomor sertifikat: prefix_sertifikat-nomor_whatsapp, mis. bikin_ai_agent-083823051482. */
    public static function makeCertificateNumber(Program $program, string $phone): string
    {
        return $program->certificate_prefix.'-'.self::normalizePhone($phone);
    }

    /** Alamat PDF sertifikat di situs ini. */
    public function certificateUrl(bool $download = false): ?string
    {
        if (! filled($this->certificate_number)) {
            return null;
        }

        return route('certificate.show', $this->certificate_number).($download ? '?unduh=1' : '');
    }

    /** Alamat yang dimuat di QR Code (domain publik sertifikat). */
    public function certificateQrUrl(): string
    {
        return rtrim((string) config('certificate.base_url'), '/').'/sertifikat/'.$this->certificate_number;
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}