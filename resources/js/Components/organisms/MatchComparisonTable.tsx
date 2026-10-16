import StatusBadge from '@/Components/Admin/ui/StatusBadge';
import {
    matchComparisonTableCellDetail,
    matchComparisonTableCellFactor,
    matchComparisonTableCellValue,
    matchComparisonTableDisclaimer,
    matchComparisonTableEmpty,
    matchComparisonTableFootnote,
    matchComparisonTableRoot,
    matchComparisonTableSourceHint,
    matchComparisonTableTable,
    matchComparisonTableThead,
} from '@/design-system/classNames';

export type MatchComparisonTableRow = {
    id: string;
    factorLabel: string;
    sourceHint?: string;
    jobValue: string;
    candidateValue: string;
    stateLabel: string;
    detailText: string;
};

export type MatchComparisonTableProps = {
    rows: MatchComparisonTableRow[];
    disclaimer?: string;
    snapshotNote?: string;
    empty?: string;
    'aria-label'?: string;
    className?: string;
};

export default function MatchComparisonTable({
    rows,
    disclaimer,
    snapshotNote,
    empty,
    'aria-label': ariaLabel = 'Candidate versus job comparison',
    className = '',
}: MatchComparisonTableProps) {
    const rootClass = `${matchComparisonTableRoot} ${className}`.trim();

    if (rows.length === 0 && empty) {
        return (
            <div className={rootClass}>
                {disclaimer ? <p className={matchComparisonTableDisclaimer}>{disclaimer}</p> : null}
                <p className={matchComparisonTableEmpty}>{empty}</p>
                {snapshotNote ? <p className={matchComparisonTableFootnote}>{snapshotNote}</p> : null}
            </div>
        );
    }

    return (
        <div className={rootClass}>
            {disclaimer ? <p className={matchComparisonTableDisclaimer}>{disclaimer}</p> : null}
            <div className="overflow-x-auto rounded-lg border border-line">
                <table className={matchComparisonTableTable} aria-label={ariaLabel}>
                    <thead className={matchComparisonTableThead}>
                        <tr>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Factor
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Job recorded value
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Candidate / application
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                State
                            </th>
                            <th scope="col" className="px-3 py-2 font-medium">
                                Match information
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                                <tr key={row.id} className="border-t border-line align-top">
                                    <th scope="row" className={matchComparisonTableCellFactor}>
                                        {row.factorLabel}
                                        {row.sourceHint ? (
                                            <p className={matchComparisonTableSourceHint}>{row.sourceHint}</p>
                                        ) : null}
                                    </th>
                                    <td className={matchComparisonTableCellValue}>{row.jobValue}</td>
                                    <td className={matchComparisonTableCellValue}>{row.candidateValue}</td>
                                    <td className="px-3 py-2">
                                        <StatusBadge status={row.stateLabel} />
                                    </td>
                                    <td className={matchComparisonTableCellDetail}>{row.detailText}</td>
                                </tr>
                            ))}
                    </tbody>
                </table>
                {snapshotNote ? <p className={matchComparisonTableFootnote}>{snapshotNote}</p> : null}
            </div>
        </div>
    );
}
