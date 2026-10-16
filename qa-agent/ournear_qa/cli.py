from __future__ import annotations

import argparse
import json
import subprocess
import sys
from pathlib import Path

from ournear_qa.evidence import new_run_id
from ournear_qa.registry import load_and_validate_registry, load_registry
from ournear_qa.registry_validate import RegistryValidationError
from ournear_qa.report import RunSummary, registry_feature_count
from ournear_qa.settings import ROOT, load_settings

SUITE_DIRS = {
    "journey": "tests/journey",
    "registry": "tests/test_registry_validation.py",
    "smoke": "tests/smoke",
    "unit": "tests",
}

REGISTRY_TEST_PATHS = (
    "tests/test_registry_validation.py",
    "tests/test_registry.py",
)

SUITE_PYTEST_MARKERS: dict[str, list[str]] = {
    "journey": ["-m", "journey"],
    "smoke": ["-m", "smoke"],
}


def cmd_inventory() -> int:
    load_settings()
    data, _meta = load_and_validate_registry()
    print(json.dumps(data, indent=2))
    return 0


def cmd_validate_registry() -> int:
    load_settings()
    try:
        _data, meta = load_and_validate_registry()
    except RegistryValidationError as exc:
        print(f"Registry invalid: {exc}", file=sys.stderr)
        return 1
    print(
        json.dumps(
            {
                "ok": True,
                "feature_count": meta.feature_count,
                "journey_count": meta.journey_count,
            },
            indent=2,
        )
    )
    return 0


def cmd_preflight_staging(suite: str | None) -> int:
    settings = load_settings()
    from ournear_qa.staging_preflight import build_staging_preflight

    payload = build_staging_preflight(settings, suite=suite)
    print(json.dumps(payload, indent=2))
    if settings.ournear_allow_run != "staging":
        return 0
    return 0 if payload.get("configuration_ok") else 3


def cmd_preflight_journey() -> int:
    settings = load_settings()
    from ournear_qa.journey.preflight import inspect_journey_capacity

    report = inspect_journey_capacity(repo_root=ROOT.parent)
    if report is None:
        print(json.dumps({"ok": False, "error": "capacity script unavailable"}, indent=2))
        return 2

    payload = {
        "ok": report.blocked_reason is None,
        "allow_run": settings.ournear_allow_run,
        "base_url": str(settings.ournear_base_url),
        "fixtures_ready": report.fixtures_ready,
        "journey_apply_provision_slots": report.journey_apply_provision_slots,
        "next_eligible_slug": report.next_eligible_slug,
        "provision_would_succeed": report.provision_would_succeed,
        "candidate_applications_last_60m": report.candidate_applications_last_60m,
        "apply_hourly_limit": report.apply_hourly_limit,
        "apply_submit_budget_remaining": report.apply_submit_budget_remaining,
        "cross_role_apply_feasible": report.cross_role_apply_feasible,
        "new_apply_test_feasible": report.new_apply_test_feasible,
        "blocked_reason": report.blocked_reason,
    }
    print(json.dumps(payload, indent=2))
    return 0 if report.blocked_reason is None else 3


def cmd_run(suite: str, extra_pytest: list[str]) -> int:
    settings = load_settings()
    from ournear_qa.execution_guard import ExecutionNotAllowedError, assert_browser_run_permitted

    try:
        assert_browser_run_permitted(settings, suite)
    except ExecutionNotAllowedError as exc:
        print(str(exc), file=sys.stderr)
        return 2
    rel = SUITE_DIRS.get(suite)
    if rel is None:
        print(f"Unknown suite {suite!r}. Choose from: {', '.join(SUITE_DIRS)}", file=sys.stderr)
        return 2

    run_id = new_run_id()
    summary = RunSummary(run_id=run_id, feature_registry_count=registry_feature_count())
    summary.notes.append(f"base_url={settings.ournear_base_url}")
    summary.notes.append(f"allow_run={settings.ournear_allow_run}")

    pytest_targets = list(REGISTRY_TEST_PATHS) if suite == "registry" else [rel]
    cmd = [
        sys.executable,
        "-m",
        "pytest",
        *pytest_targets,
        "-v",
        "--rootdir",
        str(ROOT),
        *extra_pytest,
    ]
    cmd.extend(SUITE_PYTEST_MARKERS.get(suite, []))
    cmd.extend(["--ournear-suite", suite])

    completed = subprocess.run(cmd, cwd=ROOT, check=False)
    summary.notes.append(f"pytest_exit_code={completed.returncode}")

    session_summary = ROOT / "artifacts" / "session" / "last-pytest-summary.json"
    if session_summary.is_file():
        import json

        payload = json.loads(session_summary.read_text(encoding="utf-8"))
        results = payload.get("results") or {}
        summary.passed = int(results.get("passed", 0))
        summary.failed = int(results.get("failed", 0))
        summary.skipped = int(results.get("skipped", 0))
        summary.errors = int(results.get("errors", 0))
        summary.run_id = payload.get("run_id") or summary.run_id

    out_dir = ROOT / "artifacts" / "cli-runs"
    summary.write_json(out_dir / f"last-{suite}.json")
    return completed.returncode


def cmd_plan_regression(*, compare_ref: str | None, as_json: bool, repo: Path) -> int:
    load_settings()
    from ournear_qa.git_changes import GitChangeError
    from ournear_qa.regression_plan import format_plan_human, plan_exit_code, plan_from_repo

    try:
        plan = plan_from_repo(repo.resolve(), compare_ref=compare_ref)
    except GitChangeError as exc:
        print(json.dumps({"ok": False, "error": str(exc)}, indent=2))
        return 1
    except Exception as exc:  # noqa: BLE001 — CLI surface
        print(json.dumps({"ok": False, "error": str(exc)}, indent=2))
        return 1

    code = plan_exit_code(plan)
    if as_json:
        payload = plan.to_dict()
        payload["ok"] = True
        print(json.dumps(payload, indent=2))
    else:
        print(format_plan_human(plan))
    return code


def cmd_report(
    *,
    run_id: str | None,
    as_json: bool,
    write_html: bool,
    write_junit: bool,
    ci_exit: bool,
) -> int:
    load_settings()
    from ournear_qa.run_report import build_report_payload, generate_report_artifacts, resolve_run_record
    from ournear_qa.release_readiness import ci_exit_code_for_run, evaluate_release_readiness
    from ournear_qa.coverage_map import build_execution_coverage

    try:
        record = resolve_run_record(run_id)
    except FileNotFoundError as exc:
        print(json.dumps({"ok": False, "error": str(exc)}, indent=2))
        return 2

    outputs = generate_report_artifacts(
        record,
        write_html=write_html,
        write_json=True,
        write_junit=write_junit,
    )
    payload = build_report_payload(record)
    payload["artifacts"] = {k: str(v.relative_to(ROOT)) for k, v in outputs.items()}

    if as_json:
        print(json.dumps(payload, indent=2))
    else:
        readiness = evaluate_release_readiness(record, build_execution_coverage(record, load_registry()))
        print(f"Run {record.run_id} suite={record.suite} scope={record.regression_scope}")
        print(f"Counts: {record.counts}")
        print(f"Release readiness: {readiness.verdict}")
        for reason in readiness.reasons:
            print(f"  - {reason}")
        if "html" in outputs:
            print(f"Dashboard: {outputs['html']}")

    if ci_exit:
        return ci_exit_code_for_run(
            record,
            evaluate_release_readiness(record, build_execution_coverage(record, load_registry())),
        )
    return 0


def cmd_export_manual(
    *,
    fmt: str,
    out: Path,
    test_id: str | None,
    feature_id: str | None,
    journey_id: str | None,
    risk: str | None,
    source: str | None,
) -> int:
    load_settings()
    from ournear_qa.manual_export import write_manual_export

    meta = write_manual_export(
        out,
        fmt,
        test_id=test_id,
        feature_id=feature_id,
        journey_id=journey_id,
        risk=risk,
        source=source,
    )
    print(json.dumps({"ok": True, **meta}, indent=2))
    return 0


def cmd_validate_manual_results(path: Path) -> int:
    load_settings()
    from ournear_qa.manual_results import ManualResultsValidationError, load_manual_results_json

    try:
        doc = load_manual_results_json(path)
    except (ManualResultsValidationError, json.JSONDecodeError, OSError) as exc:
        print(json.dumps({"ok": False, "error": str(exc)}, indent=2))
        return 1
    print(
        json.dumps(
            {
                "ok": True,
                "manual_run_id": doc.manual_run_id,
                "counts": doc.summary_counts(),
                "result_count": len(doc.results),
            },
            indent=2,
        )
    )
    return 0


def cmd_import_manual_results(path: Path, attach_run_id: str | None) -> int:
    load_settings()
    from ournear_qa.manual_results import ManualResultsValidationError, load_manual_results_json, save_manual_results

    try:
        doc = load_manual_results_json(path)
    except (ManualResultsValidationError, json.JSONDecodeError, OSError) as exc:
        print(json.dumps({"ok": False, "error": str(exc)}, indent=2))
        return 1
    saved = save_manual_results(doc, attach_run_id=attach_run_id)
    print(
        json.dumps(
            {
                "ok": True,
                "saved_to": str(saved.relative_to(ROOT)),
                "automated_run_id": doc.automated_run_id,
                "counts": doc.summary_counts(),
            },
            indent=2,
        )
    )
    return 0


def cmd_phpunit_filter(feature_id: str) -> int:
    """Optional bridge: print suggested artisan test filter from registry (Phase B stub)."""
    load_settings()
    data = load_registry()
    for row in data.get("features") or []:
        if row.get("id") == feature_id:
            phpunit = row.get("phpunit") or []
            if not phpunit:
                print(f"No phpunit mapping for {feature_id}", file=sys.stderr)
                return 1
            filt = phpunit[0]
            print(f"php artisan test --filter={filt}")
            return 0
    print(f"Unknown feature id {feature_id!r}", file=sys.stderr)
    return 1


def main() -> None:
    parser = argparse.ArgumentParser(prog="ournear-qa")
    sub = parser.add_subparsers(dest="command", required=True)

    sub.add_parser("inventory", help="Print feature registry JSON")
    sub.add_parser("validate-registry", help="Validate features.yaml schema and references")
    sub.add_parser(
        "preflight-journey",
        help="Read-only local journey fixture capacity and apply throttle budget",
    )
    pre_st = sub.add_parser(
        "preflight-staging",
        help="Validate staging allowlist and suite eligibility (no HTTP, no test execution)",
    )
    pre_st.add_argument("--suite", default=None, choices=sorted(SUITE_DIRS))

    plan_p = sub.add_parser("plan-regression", help="Git-aware regression plan (does not run tests)")
    plan_p.add_argument(
        "--base",
        dest="compare_ref",
        default=None,
        help="Compare working tree against this revision (default: unstaged+staged+untracked only)",
    )
    plan_p.add_argument("--json", action="store_true", help="Emit machine-readable JSON")
    plan_p.add_argument(
        "--repo",
        type=Path,
        default=ROOT.parent,
        help="Repository root (default: parent of qa-agent)",
    )

    report_p = sub.add_parser("report", help="Generate dashboard/JSON from run-record (no test execution)")
    report_p.add_argument("--run-id", default=None, help="Run id (default: latest)")
    report_p.add_argument("--json", action="store_true", help="Print report JSON to stdout")
    report_p.add_argument("--html", action="store_true", help="Write dashboard.html under artifacts/reports/")
    report_p.add_argument("--junit", action="store_true", help="Write junit.xml under artifacts/reports/")
    report_p.add_argument(
        "--ci-exit",
        action="store_true",
        help="Exit with CI code (1 fail, 2 incomplete/blocked, 4 insufficient evidence)",
    )

    run_p = sub.add_parser("run", help="Run a pytest suite")
    run_p.add_argument("--suite", default="smoke", choices=sorted(SUITE_DIRS))
    run_p.add_argument("pytest_args", nargs="*", help="Extra args passed to pytest")

    php_p = sub.add_parser("phpunit-hint", help="Suggest php artisan test --filter for a feature id")
    php_p.add_argument("feature_id")

    exp_p = sub.add_parser(
        "export-manual",
        help="Export human manual checklist from registry journeys + manual_cases.yaml",
    )
    exp_p.add_argument(
        "--format",
        dest="fmt",
        default="markdown",
        choices=("markdown", "json", "csv"),
    )
    exp_p.add_argument(
        "--out",
        type=Path,
        default=ROOT / "artifacts" / "manual" / "checklists" / "latest.md",
    )
    exp_p.add_argument("--test-id", default=None, help="Single test id (journey or MAN-* case)")
    exp_p.add_argument("--feature-id", default=None, help="Filter cases covering a feature id")
    exp_p.add_argument("--journey-id", default=None, help="Filter by journey id")
    exp_p.add_argument("--risk", default=None, help="Filter by risk tier (P0, P1, …)")
    exp_p.add_argument("--source", default=None, choices=("journey", "manual_cases"))

    val_m = sub.add_parser("validate-manual-results", help="Validate manual results JSON without importing")
    val_m.add_argument("path", type=Path)

    imp_m = sub.add_parser("import-manual-results", help="Validate and store manual results JSON")
    imp_m.add_argument("path", type=Path)
    imp_m.add_argument(
        "--attach-run-id",
        default=None,
        help="Attach to an automated pytest run id (artifacts/<run_id>/manual-results.json)",
    )

    args = parser.parse_args()
    if args.command == "inventory":
        raise SystemExit(cmd_inventory())
    if args.command == "validate-registry":
        raise SystemExit(cmd_validate_registry())
    if args.command == "preflight-journey":
        raise SystemExit(cmd_preflight_journey())
    if args.command == "preflight-staging":
        raise SystemExit(cmd_preflight_staging(getattr(args, "suite", None)))
    if args.command == "plan-regression":
        raise SystemExit(
            cmd_plan_regression(
                compare_ref=args.compare_ref,
                as_json=args.json,
                repo=args.repo,
            )
        )
    if args.command == "report":
        raise SystemExit(
            cmd_report(
                run_id=args.run_id,
                as_json=args.json,
                write_html=args.html or not args.json,
                write_junit=args.junit,
                ci_exit=args.ci_exit,
            )
        )
    if args.command == "run":
        raise SystemExit(cmd_run(args.suite, list(args.pytest_args)))
    if args.command == "phpunit-hint":
        raise SystemExit(cmd_phpunit_filter(args.feature_id))
    if args.command == "export-manual":
        raise SystemExit(
            cmd_export_manual(
                fmt=args.fmt,
                out=args.out,
                test_id=args.test_id,
                feature_id=args.feature_id,
                journey_id=args.journey_id,
                risk=args.risk,
                source=args.source,
            )
        )
    if args.command == "validate-manual-results":
        raise SystemExit(cmd_validate_manual_results(args.path))
    if args.command == "import-manual-results":
        raise SystemExit(cmd_import_manual_results(args.path, args.attach_run_id))


if __name__ == "__main__":
    main()
