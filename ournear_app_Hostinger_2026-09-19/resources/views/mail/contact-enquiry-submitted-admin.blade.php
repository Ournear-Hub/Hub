@component('mail::message')
# New contact enquiry

**{{ $enquiry->full_name }}** sent a contact message about **{{ $enquiry->interested_in }}**.

- Email: {{ $enquiry->email ?: 'Not provided' }}
- Mobile: {{ $enquiry->mobile_number }}

@component('mail::button', ['url' => $adminUrl])
Open in admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
