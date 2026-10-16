export { default as OurNearIcon } from '@/icons/OurNearIcon';
export type { OurNearIconProps } from '@/icons/OurNearIcon';
export { iconRegistry, getRegistryEntry, resolveRegistryIconName, listRegistryIconNames } from '@/icons/registry';
export { iconSizePx, resolveIconPixelSize } from '@/icons/sizes';
export type { IconCategory, IconRegistryEntry, IconSize } from '@/icons/types';

/**
 * Migration rule (PR-1+):
 * - New code → import OurNearIcon from `@/icons`
 * - Existing V2 → may keep `@/Components/UI/icons` until phased migration (PR-2+)
 * - Do not import `@tabler/icons-react` outside `resources/js/icons/`
 */
