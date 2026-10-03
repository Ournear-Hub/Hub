# Development rules (AI coding agents)

1. Code is the source of truth.
2. Documentation describes verified architecture; mark `[NOT VERIFIED]` otherwise.
3. Never invent “existing” features.
4. Search before creating.
5. Reuse models, services, components, enums, permissions.
6. Never duplicate matching, screening, shortlist, auth, or User models.
7. Never change authentication architecture without explicit approval.
8. Never modify unrelated modules.
9. Preserve database compatibility; do not bundle unrelated migrations.
10. Preserve authorization and employer/candidate isolation.
11. Never expose secrets (including phpunit APP_KEY in chat dumps).
12. Run appropriate tests; do not claim pass without running.
13. Update docs after meaningful architecture changes.
14. Keep changes scoped.
15. Report exactly what changed.
16. Report tests executed (name + counts).
17. Identify assumptions.
18. Identify unverified information.
19. Do not silently change business rules (especially hiring decisions).
20. Do not remove functionality without explicit approval.
21. Dual guards: `admin` vs `account`.
22. Automation must not auto shortlist/select/offer/reject.
23. WhatsApp OTP ≠ recruitment WhatsApp.
24. Do not commit, push, or deploy unless asked.
25. Hiring Command Center aggregates existing workspaces; do not add a second match/shortlist engine there.
26. Public `/hire` is marketing; staff Command Center is `/admin/hiring`.
