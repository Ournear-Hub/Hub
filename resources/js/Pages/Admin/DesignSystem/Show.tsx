import type { UiLabSectionId } from '@/ui-lab/metadata';
import UiLabLayout from '@/ui-lab/UiLabLayout';
import OverviewSection from '@/ui-lab/sections/OverviewSection';
import FoundationSection from '@/ui-lab/sections/FoundationSection';
import IconsSection from '@/ui-lab/sections/IconsSection';
import ButtonLabSection from '@/ui-lab/sections/ButtonLabSection';
import IconButtonLabSection from '@/ui-lab/sections/IconButtonLabSection';
import InputLabSection from '@/ui-lab/sections/InputLabSection';
import TextareaLabSection from '@/ui-lab/sections/TextareaLabSection';
import SelectLabSection from '@/ui-lab/sections/SelectLabSection';
import CheckboxLabSection from '@/ui-lab/sections/CheckboxLabSection';
import RadioLabSection from '@/ui-lab/sections/RadioLabSection';
import SwitchLabSection from '@/ui-lab/sections/SwitchLabSection';
import SkeletonLabSection from '@/ui-lab/sections/SkeletonLabSection';
import SpinnerLabSection from '@/ui-lab/sections/SpinnerLabSection';
import ProgressLabSection from '@/ui-lab/sections/ProgressLabSection';
import DividerLabSection from '@/ui-lab/sections/DividerLabSection';
import LinkLabSection from '@/ui-lab/sections/LinkLabSection';
import VisuallyHiddenLabSection from '@/ui-lab/sections/VisuallyHiddenLabSection';
import AvatarLabSection from '@/ui-lab/sections/AvatarLabSection';
import ChipLabSection from '@/ui-lab/sections/ChipLabSection';
import ImageLabSection from '@/ui-lab/sections/ImageLabSection';
import LinkButtonLabSection from '@/ui-lab/sections/LinkButtonLabSection';
import CardLabSection from '@/ui-lab/sections/CardLabSection';
import ContainerLabSection from '@/ui-lab/sections/ContainerLabSection';
import StackLabSection from '@/ui-lab/sections/StackLabSection';
import ClusterLabSection from '@/ui-lab/sections/ClusterLabSection';
import AspectRatioLabSection from '@/ui-lab/sections/AspectRatioLabSection';
import FormFieldLabSection from '@/ui-lab/sections/FormFieldLabSection';
import SearchFieldLabSection from '@/ui-lab/sections/SearchFieldLabSection';
import PasswordFieldLabSection from '@/ui-lab/sections/PasswordFieldLabSection';
import FileFieldLabSection from '@/ui-lab/sections/FileFieldLabSection';
import DateFieldLabSection from '@/ui-lab/sections/DateFieldLabSection';
import TimeFieldLabSection from '@/ui-lab/sections/TimeFieldLabSection';
import DateTimeFieldLabSection from '@/ui-lab/sections/DateTimeFieldLabSection';
import NumberFieldLabSection from '@/ui-lab/sections/NumberFieldLabSection';
import EmailFieldLabSection from '@/ui-lab/sections/EmailFieldLabSection';
import URLFieldLabSection from '@/ui-lab/sections/URLFieldLabSection';
import FieldGroupLabSection from '@/ui-lab/sections/FieldGroupLabSection';
import InputGroupLabSection from '@/ui-lab/sections/InputGroupLabSection';
import FieldSetLabSection from '@/ui-lab/sections/FieldSetLabSection';
import ComboboxLabSection from '@/ui-lab/sections/ComboboxLabSection';
import MultiSelectLabSection from '@/ui-lab/sections/MultiSelectLabSection';
import SegmentedControlLabSection from '@/ui-lab/sections/SegmentedControlLabSection';
import AccordionLabSection from '@/ui-lab/sections/AccordionLabSection';
import TooltipLabSection from '@/ui-lab/sections/TooltipLabSection';
import PopoverLabSection from '@/ui-lab/sections/PopoverLabSection';
import DropdownMenuLabSection from '@/ui-lab/sections/DropdownMenuLabSection';
import AlertLabSection from '@/ui-lab/sections/AlertLabSection';
import ToastLabSection from '@/ui-lab/sections/ToastLabSection';
import EmptyStateLabSection from '@/ui-lab/sections/EmptyStateLabSection';
import BreadcrumbLabSection from '@/ui-lab/sections/BreadcrumbLabSection';
import PaginationLabSection from '@/ui-lab/sections/PaginationLabSection';
import DataTableLabSection from '@/ui-lab/sections/DataTableLabSection';
import FilterBarLabSection from '@/ui-lab/sections/FilterBarLabSection';
import TabsLabSection from '@/ui-lab/sections/TabsLabSection';
import TimelineLabSection from '@/ui-lab/sections/TimelineLabSection';
import DialogLabSection from '@/ui-lab/sections/DialogLabSection';
import DrawerLabSection from '@/ui-lab/sections/DrawerLabSection';
import DetailHeaderLabSection from '@/ui-lab/sections/DetailHeaderLabSection';
import DetailSectionLabSection from '@/ui-lab/sections/DetailSectionLabSection';
import DocumentGroupListLabSection from '@/ui-lab/sections/DocumentGroupListLabSection';
import MatchComparisonTableLabSection from '@/ui-lab/sections/MatchComparisonTableLabSection';
import ScreeningStagesTableLabSection from '@/ui-lab/sections/ScreeningStagesTableLabSection';
import ApplicantSummaryCardLabSection from '@/ui-lab/sections/ApplicantSummaryCardLabSection';
import ApplicantResumeOverviewLabSection from '@/ui-lab/sections/ApplicantResumeOverviewLabSection';
import ApplicationTimelineLabSection from '@/ui-lab/sections/ApplicationTimelineLabSection';

type Props = {
    section: UiLabSectionId;
};

const sectionTitles: Record<UiLabSectionId, string> = {
    overview: 'Overview',
    foundation: 'Foundation',
    icons: 'Icons',
    'atoms-button': 'Button',
    'atoms-icon-button': 'IconButton',
    'atoms-input': 'Input',
    'atoms-textarea': 'Textarea',
    'atoms-select': 'Select',
    'atoms-checkbox': 'Checkbox',
    'atoms-radio': 'Radio',
    'atoms-switch': 'Switch',
    'atoms-skeleton': 'Skeleton',
    'atoms-spinner': 'Spinner',
    'atoms-progress': 'Progress',
    'atoms-divider': 'Divider',
    'atoms-link': 'Link',
    'atoms-visually-hidden': 'VisuallyHidden',
    'atoms-avatar': 'Avatar',
    'atoms-chip': 'Chip',
    'atoms-image': 'Image',
    'atoms-link-button': 'LinkButton',
    'atoms-card': 'Card',
    'atoms-container': 'Container',
    'atoms-stack': 'Stack',
    'atoms-cluster': 'Cluster',
    'atoms-aspect-ratio': 'AspectRatio',
    'molecules-form-field': 'FormField',
    'molecules-search-field': 'SearchField',
    'molecules-password-field': 'PasswordField',
    'molecules-file-field': 'FileField',
    'molecules-date-field': 'DateField',
    'molecules-time-field': 'TimeField',
    'molecules-date-time-field': 'DateTimeField',
    'molecules-number-field': 'NumberField',
    'molecules-email-field': 'EmailField',
    'molecules-url-field': 'URLField',
    'molecules-field-group': 'FieldGroup',
    'molecules-input-group': 'InputGroup',
    'molecules-field-set': 'FieldSet',
    'molecules-combobox': 'Combobox',
    'molecules-multi-select': 'MultiSelect',
    'molecules-segmented-control': 'SegmentedControl',
    'molecules-accordion': 'Accordion',
    'molecules-tooltip': 'Tooltip',
    'molecules-popover': 'Popover',
    'molecules-dropdown-menu': 'DropdownMenu',
    'molecules-alert': 'Alert',
    'molecules-toast': 'Toast',
    'molecules-empty-state': 'EmptyState',
    'molecules-data-table': 'DataTable',
    'molecules-filter-bar': 'FilterBar',
    'molecules-breadcrumb': 'Breadcrumb',
    'molecules-pagination': 'Pagination',
    'molecules-tabs': 'Tabs',
    'molecules-timeline': 'Timeline',
    'molecules-dialog': 'Dialog',
    'molecules-drawer': 'Drawer',
    'organisms-detail-header': 'DetailHeader',
    'organisms-detail-section': 'DetailSection',
    'organisms-document-group-list': 'DocumentGroupList',
    'organisms-match-comparison-table': 'MatchComparisonTable',
    'organisms-screening-stages-table': 'ScreeningStagesTable',
    'organisms-applicant-summary-card': 'ApplicantSummaryCard',
    'organisms-applicant-resume-overview': 'ApplicantResumeOverview',
    'organisms-application-timeline': 'ApplicationTimeline',
};

export default function DesignSystemShow({ section }: Props) {
    return (
        <UiLabLayout section={section} title={sectionTitles[section]}>
            {section === 'overview' ? <OverviewSection /> : null}
            {section === 'foundation' ? <FoundationSection /> : null}
            {section === 'icons' ? <IconsSection /> : null}
            {section === 'atoms-button' ? <ButtonLabSection /> : null}
            {section === 'atoms-icon-button' ? <IconButtonLabSection /> : null}
            {section === 'atoms-input' ? <InputLabSection /> : null}
            {section === 'atoms-textarea' ? <TextareaLabSection /> : null}
            {section === 'atoms-select' ? <SelectLabSection /> : null}
            {section === 'atoms-checkbox' ? <CheckboxLabSection /> : null}
            {section === 'atoms-radio' ? <RadioLabSection /> : null}
            {section === 'atoms-switch' ? <SwitchLabSection /> : null}
            {section === 'atoms-skeleton' ? <SkeletonLabSection /> : null}
            {section === 'atoms-spinner' ? <SpinnerLabSection /> : null}
            {section === 'atoms-progress' ? <ProgressLabSection /> : null}
            {section === 'atoms-divider' ? <DividerLabSection /> : null}
            {section === 'atoms-link' ? <LinkLabSection /> : null}
            {section === 'atoms-visually-hidden' ? <VisuallyHiddenLabSection /> : null}
            {section === 'atoms-avatar' ? <AvatarLabSection /> : null}
            {section === 'atoms-chip' ? <ChipLabSection /> : null}
            {section === 'atoms-image' ? <ImageLabSection /> : null}
            {section === 'atoms-link-button' ? <LinkButtonLabSection /> : null}
            {section === 'atoms-card' ? <CardLabSection /> : null}
            {section === 'atoms-container' ? <ContainerLabSection /> : null}
            {section === 'atoms-stack' ? <StackLabSection /> : null}
            {section === 'atoms-cluster' ? <ClusterLabSection /> : null}
            {section === 'atoms-aspect-ratio' ? <AspectRatioLabSection /> : null}
            {section === 'molecules-form-field' ? <FormFieldLabSection /> : null}
            {section === 'molecules-search-field' ? <SearchFieldLabSection /> : null}
            {section === 'molecules-password-field' ? <PasswordFieldLabSection /> : null}
            {section === 'molecules-file-field' ? <FileFieldLabSection /> : null}
            {section === 'molecules-date-field' ? <DateFieldLabSection /> : null}
            {section === 'molecules-time-field' ? <TimeFieldLabSection /> : null}
            {section === 'molecules-date-time-field' ? <DateTimeFieldLabSection /> : null}
            {section === 'molecules-number-field' ? <NumberFieldLabSection /> : null}
            {section === 'molecules-email-field' ? <EmailFieldLabSection /> : null}
            {section === 'molecules-url-field' ? <URLFieldLabSection /> : null}
            {section === 'molecules-field-group' ? <FieldGroupLabSection /> : null}
            {section === 'molecules-input-group' ? <InputGroupLabSection /> : null}
            {section === 'molecules-field-set' ? <FieldSetLabSection /> : null}
            {section === 'molecules-combobox' ? <ComboboxLabSection /> : null}
            {section === 'molecules-multi-select' ? <MultiSelectLabSection /> : null}
            {section === 'molecules-segmented-control' ? <SegmentedControlLabSection /> : null}
            {section === 'molecules-accordion' ? <AccordionLabSection /> : null}
            {section === 'molecules-tooltip' ? <TooltipLabSection /> : null}
            {section === 'molecules-popover' ? <PopoverLabSection /> : null}
            {section === 'molecules-dropdown-menu' ? <DropdownMenuLabSection /> : null}
            {section === 'molecules-alert' ? <AlertLabSection /> : null}
            {section === 'molecules-toast' ? <ToastLabSection /> : null}
            {section === 'molecules-empty-state' ? <EmptyStateLabSection /> : null}
            {section === 'molecules-data-table' ? <DataTableLabSection /> : null}
            {section === 'molecules-filter-bar' ? <FilterBarLabSection /> : null}
            {section === 'molecules-breadcrumb' ? <BreadcrumbLabSection /> : null}
            {section === 'molecules-pagination' ? <PaginationLabSection /> : null}
            {section === 'molecules-tabs' ? <TabsLabSection /> : null}
            {section === 'molecules-timeline' ? <TimelineLabSection /> : null}
            {section === 'molecules-dialog' ? <DialogLabSection /> : null}
            {section === 'molecules-drawer' ? <DrawerLabSection /> : null}
            {section === 'organisms-detail-header' ? <DetailHeaderLabSection /> : null}
            {section === 'organisms-detail-section' ? <DetailSectionLabSection /> : null}
            {section === 'organisms-document-group-list' ? <DocumentGroupListLabSection /> : null}
            {section === 'organisms-match-comparison-table' ? <MatchComparisonTableLabSection /> : null}
            {section === 'organisms-screening-stages-table' ? <ScreeningStagesTableLabSection /> : null}
            {section === 'organisms-applicant-summary-card' ? <ApplicantSummaryCardLabSection /> : null}
            {section === 'organisms-applicant-resume-overview' ? <ApplicantResumeOverviewLabSection /> : null}
            {section === 'organisms-application-timeline' ? <ApplicationTimelineLabSection /> : null}
        </UiLabLayout>
    );
}
