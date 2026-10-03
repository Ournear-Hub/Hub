@component('mail::message')
# New employer enquiry

**{{ $enquiry->company_name }}** ({{ $enquiry->contact_person }}) submitted a workforce request.

- Email: {{ $enquiry->email }}
- Mobile: {{ $enquiry->mobile_number }}
- Industry: {{ $enquiry->industry }}
- People: {{ $enquiry->number_of_people }}

@component('mail::button', ['url' => $adminUrl])
Open in admin
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
