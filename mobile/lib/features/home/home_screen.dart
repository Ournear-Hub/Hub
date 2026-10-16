import 'package:flutter/material.dart';
import 'package:flutter_tabler_icons/flutter_tabler_icons.dart';
import 'package:ournear_mobile/core/navigation/app_shell.dart';
import 'package:ournear_mobile/core/theme/ournear_colors.dart';
import 'package:ournear_mobile/core/widgets/module_status_chip.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key, required this.onOpenModule});

  final void Function(AppTab tab) onOpenModule;

  @override
  Widget build(BuildContext context) {
    return CustomScrollView(
      slivers: [
        SliverToBoxAdapter(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('OURNEAR', style: Theme.of(context).textTheme.labelSmall),
                const SizedBox(height: 4),
                Text('One platform', style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 6),
                Text(
                  'Home, social, memory, and hiring connected under a single account — built for mobile-first discovery.',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          ),
        ),
        SliverPadding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          sliver: SliverList.separated(
            itemCount: _modules.length,
            separatorBuilder: (_, _) => const SizedBox(height: 10),
            itemBuilder: (context, index) {
              final module = _modules[index];
              return _ModuleCard(
                module: module,
                onTap: () {
                  if (module.tab != null) {
                    onOpenModule(module.tab!);
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('${module.title} opens from Profile when available.')),
                    );
                  }
                },
              );
            },
          ),
        ),
        const SliverToBoxAdapter(child: SizedBox(height: 24)),
      ],
    );
  }
}

class _ModuleDefinition {
  const _ModuleDefinition({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.availability,
    this.tab,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final ModuleAvailability availability;
  final AppTab? tab;
}

const _modules = <_ModuleDefinition>[
  _ModuleDefinition(
    title: 'OurNear Home',
    subtitle: 'Your command center across modules (this screen).',
    icon: TablerIcons.home_2,
    availability: ModuleAvailability.activeShell,
    tab: AppTab.home,
  ),
  _ModuleDefinition(
    title: 'Vibes',
    subtitle: 'Short-form social energy — shell navigation ready.',
    icon: TablerIcons.flame,
    availability: ModuleAvailability.comingSoon,
    tab: AppTab.vibes,
  ),
  _ModuleDefinition(
    title: 'Drops',
    subtitle: 'Shared moments and releases — shell navigation ready.',
    icon: TablerIcons.droplet,
    availability: ModuleAvailability.comingSoon,
    tab: AppTab.drops,
  ),
  _ModuleDefinition(
    title: 'Memory',
    subtitle: 'Timeline and keepsakes — planned; entry from Home until tab is added.',
    icon: TablerIcons.photo,
    availability: ModuleAvailability.comingSoon,
  ),
  _ModuleDefinition(
    title: 'Hire',
    subtitle: 'Jobs, applications, and employer workflows via OurNear Laravel.',
    icon: TablerIcons.briefcase,
    availability: ModuleAvailability.webOnly,
    tab: AppTab.hire,
  ),
  _ModuleDefinition(
    title: 'Notifications',
    subtitle: 'Account alerts — opens from Profile settings when wired.',
    icon: TablerIcons.bell,
    availability: ModuleAvailability.comingSoon,
  ),
];

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({required this.module, required this.onTap});

  final _ModuleDefinition module;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: '${module.title}. ${module.subtitle}',
      child: Card(
        child: InkWell(
          key: ValueKey('module-${module.title}'),
          borderRadius: BorderRadius.circular(12),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: OurNearColors.brand.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(module.icon, color: OurNearColors.brand),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(module.title, style: Theme.of(context).textTheme.titleMedium),
                          ),
                          ModuleStatusChip(availability: module.availability),
                        ],
                      ),
                      const SizedBox(height: 6),
                      Text(module.subtitle, style: Theme.of(context).textTheme.bodySmall),
                    ],
                  ),
                ),
                const Icon(TablerIcons.chevron_right, color: OurNearColors.muted, size: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
