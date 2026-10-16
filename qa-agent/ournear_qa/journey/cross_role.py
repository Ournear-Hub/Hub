from __future__ import annotations

import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.admin_application import (
    AdminJourneyTarget,
    assert_role_cannot_access_admin_applications,
    discover_admin_journey_target,
    run_admin_application_steps,
)
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.candidate_apply import (
    ApplyJourneyContext,
    provision_journey_apply_target,
    run_new_application_steps,
)
from ournear_qa.journey.candidate_status_notification import run_candidate_status_and_notification_steps
from ournear_qa.journey.employer_resume import (
    EmployerJourneyTarget,
    run_employer_a_resume_steps,
    run_employer_b_isolation_steps,
)
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-CROSS-ROLE-01"


@dataclass
class CrossRoleReport:
    correlation_business_id: str
    application_id: int
    job_slug: str
    candidate_steps: list[str] = field(default_factory=list)
    employer_steps: list[str] = field(default_factory=list)
    admin_steps: list[str] = field(default_factory=list)
    authorization_steps: list[str] = field(default_factory=list)
    notification_steps: list[str] = field(default_factory=list)
    blocked: list[str] = field(default_factory=list)
    mode: str = "fresh_apply"
    admin_status_changed: bool = False


def _parse_kv_stdout(stdout: str) -> dict[str, str]:
    fields: dict[str, str] = {}
    for line in stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()
    return fields


def resolve_application_by_business_id(business_id: str, repo_root: Path = REPO_ROOT) -> AdminJourneyTarget | None:
    script = repo_root / "scripts" / "inspect-qa-application-by-business-id.php"
    php = shutil.which("php")
    if not script.is_file() or php is None or not business_id:
        return None
    completed = subprocess.run(
        [php, str(script), business_id],
        cwd=repo_root,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        return None
    fields = _parse_kv_stdout(completed.stdout)
    if fields.get("found") != "1":
        return None
    try:
        application_id = int(fields["application_id"])
    except (KeyError, ValueError):
        return None
    return AdminJourneyTarget(
        application_id=application_id,
        business_id=fields.get("business_id", business_id),
        job_slug=fields.get("job_slug", ""),
        job_title=fields.get("job_title", ""),
        resume_path_set=fields.get("resume_path_set") == "yes",
        status=fields.get("status", "New"),
    )


def run_candidate_application_history_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    target: AdminJourneyTarget,
) -> list[str]:
    steps: list[str] = []
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="STG6-candidate-login",
    )
    nav = NavigationHelper(page, base_url)
    nav.goto_path("/account/applications")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=30_000)
    steps.append("STG6-CAND-001-history-list")

    card = page.locator("li").filter(has_text=target.business_id)
    card.first.get_by_role("link", name="Open application").click()
    page.wait_for_url(f"**/account/applications/{target.application_id}**", timeout=30_000)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    steps.append("STG6-CAND-002-history-detail")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    steps.append("STG6-CAND-003-history-reload")
    return steps


def run_cross_role_reusing_fixture(
    page: Page,
    base_url: str,
    *,
    candidate_email: str,
    candidate_password: str,
    employer_a_email: str,
    employer_a_password: str,
    employer_b_email: str,
    employer_b_password: str,
    admin_email: str,
    admin_password: str,
) -> CrossRoleReport:
    """Reuse latest QA application (no new submit) when apply budget blocks fresh cross-role."""
    discovered = discover_admin_journey_target()
    if discovered is None:
        raise JourneyStepError(
            "STG6-fixture-target",
            "QA application on Employer A job",
            "discover_admin_journey_target returned None",
            journey_id=JRN_ID,
        )

    report = CrossRoleReport(
        correlation_business_id=discovered.business_id,
        application_id=discovered.application_id,
        job_slug=discovered.job_slug,
        mode="reused_fixture",
    )
    report.candidate_steps = run_candidate_application_history_steps(
        page, base_url, candidate_email, candidate_password, discovered
    )

    employer_target = _to_employer_target(discovered)
    employer_ctx = run_employer_a_resume_steps(
        page, base_url, employer_a_email, employer_a_password, employer_target
    )
    report.employer_steps = list(employer_ctx.steps_completed)

    admin_ctx = run_admin_application_steps(
        page, base_url, admin_email, admin_password, discovered
    )
    report.admin_steps = list(admin_ctx.steps_completed)
    report.admin_status_changed = admin_ctx.status_changed

    run_employer_b_isolation_steps(
        page, base_url, employer_b_email, employer_b_password, employer_target
    )
    report.authorization_steps.append("D4-auth-employer-b-isolation")

    assert_role_cannot_access_admin_applications(
        page,
        base_url,
        candidate_email,
        candidate_password,
        application_id=discovered.application_id,
        business_id=discovered.business_id,
    )
    report.authorization_steps.append("D4-auth-candidate-admin-denied")

    assert_role_cannot_access_admin_applications(
        page,
        base_url,
        employer_a_email,
        employer_a_password,
        application_id=discovered.application_id,
        business_id=discovered.business_id,
    )
    report.authorization_steps.append("D4-auth-employer-a-admin-denied")

    expect_reviewing = report.admin_status_changed or discovered.status == "Reviewing"
    report.notification_steps = run_candidate_status_and_notification_steps(
        page,
        base_url,
        candidate_email,
        candidate_password,
        application_id=discovered.application_id,
        business_id=discovered.business_id,
        expect_reviewing=expect_reviewing,
    )

    return report


def _to_employer_target(target: AdminJourneyTarget) -> EmployerJourneyTarget:
    return EmployerJourneyTarget(
        application_id=target.application_id,
        business_id=target.business_id,
        job_slug=target.job_slug,
        job_title=target.job_title,
        resume_path_set=target.resume_path_set,
    )


def run_cross_role_e2e(
    page: Page,
    base_url: str,
    *,
    candidate_email: str,
    candidate_password: str,
    employer_a_email: str,
    employer_a_password: str,
    employer_b_email: str,
    employer_b_password: str,
    admin_email: str,
    admin_password: str,
) -> CrossRoleReport:
    provision = provision_journey_apply_target()
    if provision is None:
        raise JourneyStepError(
            "D4-00-provision",
            "eligible journey apply job from provision script",
            "provision returned None (60 slugs exhausted or QA fixtures missing)",
            journey_id=JRN_ID,
        )

    slug, title = provision
    apply_ctx: ApplyJourneyContext = run_new_application_steps(
        page,
        base_url,
        candidate_email,
        candidate_password,
        slug,
        title,
    )
    if not apply_ctx.business_id:
        raise JourneyStepError(
            "D4-01-candidate-apply",
            "ONT-A-* correlation key after apply",
            "missing business_id",
            journey_id=JRN_ID,
        )

    resolved = resolve_application_by_business_id(apply_ctx.business_id)
    if resolved is None:
        raise JourneyStepError(
            "D4-02-resolve",
            f"application record for {apply_ctx.business_id}",
            "inspect script returned not found",
            journey_id=JRN_ID,
        )

    report = CrossRoleReport(
        correlation_business_id=resolved.business_id,
        application_id=resolved.application_id,
        job_slug=resolved.job_slug,
        candidate_steps=list(apply_ctx.steps_completed),
    )

    employer_target = _to_employer_target(resolved)
    employer_ctx = run_employer_a_resume_steps(
        page,
        base_url,
        employer_a_email,
        employer_a_password,
        employer_target,
    )
    report.employer_steps = list(employer_ctx.steps_completed)

    admin_ctx = run_admin_application_steps(
        page,
        base_url,
        admin_email,
        admin_password,
        resolved,
    )
    report.admin_steps = list(admin_ctx.steps_completed)
    report.admin_status_changed = admin_ctx.status_changed

    run_employer_b_isolation_steps(
        page,
        base_url,
        employer_b_email,
        employer_b_password,
        employer_target,
    )
    report.authorization_steps.append("D4-auth-employer-b-isolation")

    assert_role_cannot_access_admin_applications(
        page,
        base_url,
        candidate_email,
        candidate_password,
        application_id=resolved.application_id,
        business_id=resolved.business_id,
    )
    report.authorization_steps.append("D4-auth-candidate-admin-denied")

    assert_role_cannot_access_admin_applications(
        page,
        base_url,
        employer_a_email,
        employer_a_password,
        application_id=resolved.application_id,
        business_id=resolved.business_id,
    )
    report.authorization_steps.append("D4-auth-employer-a-admin-denied")

    expect_reviewing = admin_ctx.status_changed or resolved.status == "Reviewing"
    report.notification_steps = run_candidate_status_and_notification_steps(
        page,
        base_url,
        candidate_email,
        candidate_password,
        application_id=resolved.application_id,
        business_id=resolved.business_id,
        expect_reviewing=expect_reviewing,
    )

    return report
