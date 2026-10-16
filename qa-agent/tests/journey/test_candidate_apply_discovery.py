from ournear_qa.journey.candidate_apply import discover_qa_apply_target


SAMPLE_PROBE = """
--- all published active jobs candidate has NOT applied to ---
job id=99 slug=qa-fixture-published-b employer=qa@test title=QA Fixture Published Role (Employer B)
job id=1 slug=warehouse-operative employer=null title=Warehouse Operative
job id=28 business_id=ONT-J-000025 slug=qa-fixture-published-a publishing=published already_applied=yes title=QA A
"""


def test_discover_prefers_qa_fixture_slug(monkeypatch, tmp_path) -> None:
    script = tmp_path / "scripts" / "inspect-qa-apply-eligibility.php"
    script.parent.mkdir(parents=True)
    script.write_text("<?php", encoding="utf-8")

    def fake_run(cmd, cwd, capture_output, text, check):  # noqa: ANN001
        class Result:
            returncode = 0
            stdout = SAMPLE_PROBE

        return Result()

    monkeypatch.setattr("ournear_qa.journey.candidate_apply.subprocess.run", fake_run)
    found = discover_qa_apply_target(repo_root=tmp_path)
    assert found == ("qa-fixture-published-b", "QA Fixture Published Role (Employer B)")


def test_discover_returns_none_without_qa_slug(monkeypatch, tmp_path) -> None:
    script = tmp_path / "scripts" / "inspect-qa-apply-eligibility.php"
    script.parent.mkdir()
    script.write_text("<?php", encoding="utf-8")

    def fake_run(cmd, cwd, capture_output, text, check):  # noqa: ANN001
        class Result:
            returncode = 0
            stdout = """
--- all published active jobs candidate has NOT applied to ---
job id=1 slug=warehouse-operative employer=null title=Warehouse Operative
"""

        return Result()

    monkeypatch.setattr("ournear_qa.journey.candidate_apply.subprocess.run", fake_run)
    assert discover_qa_apply_target(repo_root=tmp_path) is None
