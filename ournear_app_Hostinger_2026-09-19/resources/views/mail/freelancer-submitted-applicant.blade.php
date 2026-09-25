@component('mail::message')
# Application received

Hello {{ $freelancer->name }},

Thank you for submitting a freelancer application to OurNear Talent. We have received your details and will review them.

**Freelancer ID:** {{ $freelancer->business_id }}

Please save this Freelancer ID for future reference. A member of the recruitment team will contact you if your profile matches a current freelance requirement.

This acknowledgement does not confirm placement or a freelance assignment.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
