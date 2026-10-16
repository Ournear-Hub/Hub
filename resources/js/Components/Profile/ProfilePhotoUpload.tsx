import { router } from '@inertiajs/react';
import { useRef, useState, type ChangeEvent } from 'react';
import Avatar, { type AvatarSize } from '@/Components/atoms/Avatar';
import Button from '@/Components/atoms/Button';
import { focusRing } from '@/design-system/classNames';

const ACCEPT = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

type ProfilePhotoUploadProps = {
    name: string;
    photoUrl?: string | null;
    size?: AvatarSize;
    disabled?: boolean;
    onSuccess?: () => void;
};

export default function ProfilePhotoUpload({
    name,
    photoUrl,
    size = 'xl',
    disabled = false,
    onSuccess,
}: ProfilePhotoUploadProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadPhoto = (file: File) => {
        setError(null);
        setUploading(true);
        const payload = new FormData();
        payload.append('photo', file);
        router.post('/account/profile/photo', payload, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                setError(typeof errors.photo === 'string' ? errors.photo : 'Could not upload photo. Try JPG, PNG, or WEBP up to 5 MB.');
            },
            onSuccess: () => {
                setError(null);
                onSuccess?.();
            },
            onFinish: () => {
                setUploading(false);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    const onFileChange = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file instanceof File && file.size > 0) {
            uploadPhoto(file);
        }
    };

    const removePhoto = () => {
        if (!window.confirm('Remove your profile photo?')) {
            return;
        }
        setError(null);
        setUploading(true);
        router.delete('/account/profile/photo', {
            preserveScroll: true,
            onError: () => setError('Could not remove photo. Please try again.'),
            onSuccess: () => {
                setError(null);
                onSuccess?.();
            },
            onFinish: () => setUploading(false),
        });
    };

    const openPicker = () => {
        if (!disabled && !uploading) {
            fileInputRef.current?.click();
        }
    };

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
            <input
                ref={fileInputRef}
                type="file"
                accept={ACCEPT}
                className="sr-only"
                disabled={disabled || uploading}
                onChange={onFileChange}
            />
            <button
                type="button"
                className={`relative shrink-0 rounded-full ${focusRing} disabled:cursor-not-allowed disabled:opacity-60`}
                disabled={disabled || uploading}
                aria-label={photoUrl ? 'Change profile photo' : 'Upload profile photo'}
                onClick={openPicker}
            >
                <Avatar name={name} src={photoUrl ?? undefined} size={size} shape="circle" />
                <span className="absolute inset-x-0 bottom-0 rounded-b-full bg-ink/55 py-0.5 text-[10px] font-semibold text-white">
                    {uploading ? '…' : 'Photo'}
                </span>
            </button>
            <div className="min-w-0 flex-1 space-y-2">
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        surface="public"
                        className="min-h-10"
                        disabled={disabled || uploading}
                        onClick={openPicker}
                    >
                        {uploading ? 'Uploading…' : photoUrl ? 'Change photo' : 'Upload photo'}
                    </Button>
                    {photoUrl ? (
                        <Button
                            type="button"
                            variant="outline"
                            surface="public"
                            className="min-h-10 text-danger"
                            disabled={disabled || uploading}
                            onClick={removePhoto}
                        >
                            Remove
                        </Button>
                    ) : null}
                </div>
                <p className="text-xs text-muted">JPG, PNG, or WEBP. Maximum 5 MB.</p>
                {error ? (
                    <p className="text-sm text-danger" role="alert">
                        {error}
                    </p>
                ) : null}
            </div>
        </div>
    );
}
