<?php

namespace App\Models;

use App\Casts\RecoveredBackedEnumCast;
use App\Enums\ContactEnquiryStatus;
use Database\Factories\ContactEnquiryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContactEnquiry extends Model
{
    /** @use HasFactory<ContactEnquiryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'mobile_number',
        'email',
        'interested_in',
        'message',
        'status',
        'internal_notes',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'internal_notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'internal_notes' => 'array',
            'status' => RecoveredBackedEnumCast::class.':'.ContactEnquiryStatus::class,
        ];
    }

    protected static function newFactory(): ContactEnquiryFactory
    {
        return ContactEnquiryFactory::new();
    }

    /**
     * @return HasOne<CrmLead, $this>
     */
    public function crmLead(): HasOne
    {
        return $this->hasOne(CrmLead::class);
    }

    /**
     * @return list<array{body: string, author_id: int|null, author_name: string, created_at: string}>
     */
    public function notes(): array
    {
        $notes = $this->internal_notes;

        return is_array($notes) ? array_values($notes) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        $digits = preg_replace('/\D+/', '', $this->mobile_number) ?? '';

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'interested_in' => $this->interested_in,
            'message' => $this->message,
            'status' => $this->status->value,
            'internal_notes' => $this->notes(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'call_url' => 'tel:'.$this->mobile_number,
            'whatsapp_url' => $digits !== '' ? 'https://wa.me/'.$digits : null,
            'email_url' => filled($this->email) ? 'mailto:'.$this->email : null,
        ];
    }
}
