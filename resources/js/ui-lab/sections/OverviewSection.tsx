import { architectureStack, libraryStatus, productionImportLanes } from '@/ui-lab/metadata';

export default function OverviewSection() {
    return (
        <div className="space-y-6">
            <div className="rounded-xl border border-line bg-white p-6 shadow-sm">
                <h2 className="text-2xl font-semibold text-ink">OurNear UI Lab</h2>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    Purpose: OurNear&apos;s governed design system and reusable UI library — visual validation for local development.
                    This lab renders canonical components from{' '}
                    <code className="text-xs text-ink">atoms</code>, <code className="text-xs text-ink">molecules</code>, and{' '}
                    <code className="text-xs text-ink">organisms</code>; it is not a reference clone from external design systems.
                </p>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    <strong className="font-semibold text-ink">Dual-lane reality:</strong> production still uses legacy{' '}
                    <code className="text-xs">Public</code>/<code className="text-xs">UI</code>/<code className="text-xs">Account</code>{' '}
                    and <code className="text-xs">Admin/ui</code> import paths alongside the canonical tier — see Production import
                    reality below. Companion doc:{' '}
                    <code className="text-xs text-ink">docs/OURNEAR_HIRE_FRONTEND_ARCHITECTURE_AUDIT.md</code> (Finding G-1).
                </p>
            </div>

            <section className="rounded-xl border border-line bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-ink">Production import reality</h3>
                <p className="mt-2 max-w-3xl text-sm text-muted">
                    Three coexisting lanes in the codebase. UI Lab documents the canonical lane; compatibility notes on each component
                    call out legacy paths that remain intentionally supported.
                </p>
                <div className="mt-4 overflow-x-auto">
                    <table className="w-full min-w-[640px] border-collapse text-left text-sm">
                        <thead>
                            <tr className="border-b border-line text-xs font-semibold uppercase tracking-wide text-muted">
                                <th className="py-2 pr-4">Lane</th>
                                <th className="py-2 pr-4">Import roots</th>
                                <th className="py-2 pr-4">Role</th>
                                <th className="py-2">Guidance</th>
                            </tr>
                        </thead>
                        <tbody>
                            {productionImportLanes.map((row) => (
                                <tr key={row.lane} className="border-b border-line/80 align-top">
                                    <td className="py-3 pr-4 font-medium text-ink">{row.lane}</td>
                                    <td className="py-3 pr-4 font-mono text-xs text-ink">{row.importRoots}</td>
                                    <td className="py-3 pr-4 text-muted">{row.role}</td>
                                    <td className="py-3 text-muted">{row.guidance}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="rounded-xl border border-line bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-ink">Architecture</h3>
                <ol className="mt-4 space-y-2 border-l-2 border-brand/30 pl-4">
                    {architectureStack.map((step) => (
                        <li key={step} className="text-sm font-medium text-ink">
                            {step}
                        </li>
                    ))}
                </ol>
            </section>

            <section className="rounded-xl border border-line bg-white p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-ink">Current status</h3>
                <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                    {libraryStatus.map((row) => (
                        <li
                            key={row.id}
                            className="flex items-center justify-between rounded-lg border border-line bg-surface/40 px-3 py-2 text-sm"
                        >
                            <span className="font-medium text-ink">{row.label}</span>
                            <span
                                className={`text-xs font-semibold uppercase ${
                                    row.status === 'active' ? 'text-success' : 'text-muted'
                                }`}
                            >
                                {row.status === 'active' ? 'Active' : 'Coming later'}
                            </span>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
