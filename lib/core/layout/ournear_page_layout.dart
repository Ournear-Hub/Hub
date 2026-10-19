import 'package:flutter/material.dart';
import 'package:ournear_mobile/core/layout/ournear_responsive_frame.dart';
import 'package:ournear_mobile/core/theme/ournear_tokens.dart';

/// Standard page scaffold: app bar, safe area, keyboard-aware scroll body.
class OurNearPageLayout extends StatelessWidget {
  const OurNearPageLayout({
    super.key,
    this.title,
    this.appBarActions,
    this.floatingActionButton,
    required this.body,
    this.scrollable = true,
    this.padding,
    this.centerBody = false,
  });

  final String? title;
  final List<Widget>? appBarActions;
  final Widget? floatingActionButton;
  final Widget body;
  final bool scrollable;
  final EdgeInsetsGeometry? padding;
  final bool centerBody;

  @override
  Widget build(BuildContext context) {
    final resolvedPadding = padding ?? OurNearTokens.pagePadding(context);

    Widget content = body;
    if (centerBody) {
      content = Center(child: body);
    }
    if (scrollable) {
      content = SingleChildScrollView(
        keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
        padding: resolvedPadding,
        child: content,
      );
    } else {
      content = Padding(padding: resolvedPadding, child: content);
    }

    return Scaffold(
      appBar: title == null ? null : AppBar(title: Text(title!), actions: appBarActions),
      floatingActionButton: floatingActionButton,
      resizeToAvoidBottomInset: true,
      body: SafeArea(
        child: OurNearResponsiveFrame(child: content),
      ),
    );
  }
}
