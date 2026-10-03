<x-mail::message>
# Welcome to OurNear Hire

Your profile has been created from a resume uploaded by OurNear Hire.

**OurNear ID:** {{ $ournearId }}

**Username:** {{ $candidate->username }}

Set your password to open the account. A password is not included in this email.

[Set password]({{ $activationUrl }})

After you sign in, please verify your email and phone, review your profile, and update anything that is missing. Your resume library is separate from any application resume snapshot.

Regards,<br>
OurNear Hire
</x-mail::message>
