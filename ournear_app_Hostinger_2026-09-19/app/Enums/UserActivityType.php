<?php

namespace App\Enums;

enum UserActivityType: string
{
    case Registered = 'registered';
    case EmailVerified = 'email_verified';
    case MobileVerified = 'mobile_verified';
    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';
    case UpdatedProfile = 'updated_profile';
    case UploadedResume = 'uploaded_resume';
    case UploadedDocument = 'uploaded_document';
    case ViewedJob = 'viewed_job';
    case SavedJob = 'saved_job';
    case AppliedForJob = 'applied_for_job';
    case UpdatedApplication = 'updated_application';
    case ReceivedNotification = 'received_notification';
    case ChangedPassword = 'changed_password';
    case SecurityEvent = 'security_event';
    case Blocked = 'blocked';
    case Unblocked = 'unblocked';
    case Suspended = 'suspended';
    case Reactivated = 'reactivated';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Registered',
            self::EmailVerified => 'Email verified',
            self::MobileVerified => 'Mobile verified',
            self::LoggedIn => 'Logged in',
            self::LoggedOut => 'Logged out',
            self::UpdatedProfile => 'Updated profile',
            self::UploadedResume => 'Uploaded resume',
            self::UploadedDocument => 'Uploaded document',
            self::ViewedJob => 'Viewed job',
            self::SavedJob => 'Saved job',
            self::AppliedForJob => 'Applied for job',
            self::UpdatedApplication => 'Updated application',
            self::ReceivedNotification => 'Received notification',
            self::ChangedPassword => 'Changed password',
            self::SecurityEvent => 'Security event',
            self::Blocked => 'Blocked',
            self::Unblocked => 'Unblocked',
            self::Suspended => 'Suspended',
            self::Reactivated => 'Reactivated',
            self::Deactivated => 'Deactivated',
        };
    }
}
