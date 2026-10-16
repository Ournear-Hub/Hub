import { router } from '@inertiajs/react';
import { useRef, useState, type ChangeEvent } from 'react';
import Avatar, { type AvatarSize } from '@/Components/atoms/Avatar';
import Button from '@/Components/atoms/Button';
import { focusRing } from '@/design-system/classNames';

const ACCEPT = '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp';

type CompanyLogoUploadProps = {
    companyName: string;
    logoUrl?: string | null;
    size?: AvatarSize;
    disabled?: boolean;
    onSuccess?: () => void;
};

export default function CompanyLogoUpload({
    companyName,
    logoUrl,
    size = 'xl',
    disabled = false,
    onSuccess,
}: CompanyLogoUploadProps) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const uploadLogo = (file: File) => {
        setError(null);
        setUploading(true);
        const payload = new FormData();
        payload.append('logo', file);
        router.post('/account/company/logo', payload, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                setError(typeof errors.logo === 'string' ? errors.logo : 'Could not upload logo. Try JPG, PNG, or WEBP up to 5 MB.');
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
            uploadLogo(file);
        }
    };

    const removeLogo = () => {
        if (!window.confirm('Remove your company logo?')) {
            return;
        }
        setError(null);
        setUploading(true);
        router.delete('/account/company/logo', {
            preserveScroll: true,
            onError: () => setError('Could not remove logo. Please try again.'),
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
                className={`relative shrink-0 rounded-lg ${focusRing} disabled:cursor-not-allowed disabled:opacity-60`}
                disabled={disabled || uploading}
                aria-label={logoUrl ? 'Change company logo' : 'Upload company logo'}
                onClick={openPicker}
            >
                <Avatar name={companyName || 'Company'} src={logoUrl ?? undefined} size={size} shape="square" />
                <span className="absolute inset-x-0 bottom-0 rounded-b-lg bg-ink/55 py-0.5 text-[10px] font-semibold text-white">
                    {uploading ? '…' : 'Logo'}
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
                        {uploading ? 'Uploading…' : logoUrl ? 'Change logo' : 'Upload logo'}
                    </Button>
                    {logoUrl ? (
                        <Button
                            type="button"
                            variant="outline"
                            surface="public"
                            className="min-h-10 text-danger"
                            disabled={disabled || uploading}
                            onClick={removeLogo}
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
