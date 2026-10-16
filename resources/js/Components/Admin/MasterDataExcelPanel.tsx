import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Button from '@/Components/atoms/Button';
import AdminButton from '@/Components/Admin/ui/Button';
import type { SharedPageProps } from '@/types/page';

type PreviewRow = {
    row: number;
    action: string;
    intent?: string;
    errors?: string[];
};

type ImportPreview = {
    token: string;
    summary: Record<string, number>;
    rows: PreviewRow[];
};

type MasterDataExcelPanelProps = {
    tab: string;
    label: string;
};

export default function MasterDataExcelPanel({ tab, label }: MasterDataExcelPanelProps) {
    const page = usePage<SharedPageProps>();
    const preview = page.props.flash?.masterDataImportPreview as ImportPreview | undefined;

    const uploadForm = useForm<{ file: File | null }>({ file: null });
    const confirmForm = useForm<{ token: string }>({ token: preview?.token ?? '' });

    const submitUpload = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        if (!uploadForm.data.file) {
            return;
        }
        uploadForm.post(`/admin/master-data/${tab}/excel/preview`, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const confirmImport = () => {
        if (!preview?.token) {
            return;
        }
        confirmForm.setData('token', preview.token);
        confirmForm.post(`/admin/master-data/${tab}/excel/confirm`, { preserveScroll: true });
    };

    return (
        <section className="mt-4 rounded-xl border border-line bg-white p-4">
            <h2 className="text-base font-semibold text-ink">Excel import / export</h2>
            <p className="mt-1 text-sm text-muted">
                Download a template or export current {label.toLowerCase()}, then upload a workbook to preview changes before confirming.
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
                <Button surface="admin" variant="secondary" href={`/admin/master-data/${tab}/excel/template`}>
                    Download template
                </Button>
                <Button surface="admin" variant="secondary" href={`/admin/master-data/${tab}/excel/export`}>
                    Export {label.toLowerCase()}
                </Button>
            </div>

            <form className="mt-4 flex flex-wrap items-end gap-3" onSubmit={submitUpload}>
                <div>
                    <label className="text-sm font-medium text-ink" htmlFor="master-data-import-file">
                        Upload workbook
                    </label>
                    <input
                        id="master-data-import-file"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        className="mt-1 block text-sm"
                        onChange={(event) => uploadForm.setData('file', event.target.files?.[0] ?? null)}
                    />
                    {uploadForm.errors.file ? <p className="mt-1 text-sm text-danger">{uploadForm.errors.file}</p> : null}
                </div>
                <AdminButton type="submit" disabled={uploadForm.processing || !uploadForm.data.file}>
                    Preview import
                </AdminButton>
            </form>

            {preview ? (
                <div className="mt-4 space-y-3 rounded-lg border border-line bg-surface p-3">
                    <p className="text-sm text-ink">
                        Preview: {preview.summary.create ?? 0} create, {preview.summary.update ?? 0} update,{' '}
                        {preview.summary.unchanged ?? 0} unchanged, {preview.summary.reject ?? 0} rejected.
                    </p>
                    <div className="max-h-48 overflow-y-auto text-xs text-muted">
                        {preview.rows.slice(0, 50).map((row) => (
                            <p key={row.row}>
                                Row {row.row}: {row.action}
                                {row.intent ? ` (${row.intent})` : ''}
                                {row.errors?.length ? ` — ${row.errors.join('; ')}` : ''}
                            </p>
                        ))}
                    </div>
                    <AdminButton
                        type="button"
                        onClick={confirmImport}
                        disabled={confirmForm.processing || (preview.summary.reject ?? 0) === preview.rows.length}
                    >
                        Confirm import
                    </AdminButton>
                </div>
            ) : null}
        </section>
    );
}
