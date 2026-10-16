import StatusBadge from '@/Components/Admin/ui/StatusBadge';
import {
    screeningStagesTableCell,
    screeningStagesTableCellInk,
    screeningStagesTableCellMuted,
    screeningStagesTableEmpty,
    screeningStagesTableRoot,
    screeningStagesTableScroll,
    screeningStagesTableTable,
    screeningStagesTableThead,
} from '@/design-system/classNames';

export type ScreeningStagesTableRow = {
    id: string;
    stageLabel: string;
    statusLabel: string;
    occurredAt: string;
    performedBy: string;
    remarks: string;
    evidence: string;
};

export type ScreeningStagesTableProps = {
    rows: ScreeningStagesTableRow[];
    empty?: string;
    'aria-label'?: string;
    className?: string;
};

export default function ScreeningStagesTable({
    rows,
    empty,
    'aria-label': ariaLabel = 'Screening stages',
    className = '',
}: ScreeningStagesTableProps) {
    const rootClass = `${screeningStagesTableRoot} ${className}`.trim();

    if (rows.length === 0 && empty) {
        return (
            <div className={rootClass}>
                <p className={screeningStagesTableEmpty}>{empty}</p>
            </div>
        );
    }

    return (
        <div className={rootClass}>
            <div className={screeningStagesTableScroll}>
                <table className={screeningStagesTableTable} aria-label={ariaLabel}>
                    <thead className={screeningStagesTableThead}>
                        <tr>
                            <th scope="col" className={screeningStagesTableCell}>
                                Stage
                            </th>
                            <th scope="col" className={screeningStagesTableCell}>
                                Status
                            </th>
                            <th scope="col" className={screeningStagesTableCell}>
                                Date/time
                            </th>
                            <th scope="col" className={screeningStagesTableCell}>
                                Performed by
                            </th>
                            <th scope="col" className={screeningStagesTableCell}>
                                Remarks
                            </th>
                            <th scope="col" className={screeningStagesTableCell}>
                                Evidence
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t border-line">
                                <td className={screeningStagesTableCellInk}>{row.stageLabel}</td>
                                <td className={screeningStagesTableCell}>
                                    <StatusBadge status={row.statusLabel} />
                                </td>
                                <td className={screeningStagesTableCellMuted}>{row.occurredAt}</td>
                                <td className={screeningStagesTableCellMuted}>{row.performedBy}</td>
                                <td className={screeningStagesTableCellMuted}>{row.remarks}</td>
                                <td className={screeningStagesTableCellMuted}>{row.evidence}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
