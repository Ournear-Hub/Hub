from __future__ import annotations

import xml.etree.ElementTree as ET
from pathlib import Path

from ournear_qa.run_record import NormalizedRunRecord, RunCaseOutcome


def write_junit_xml(record: NormalizedRunRecord, path: Path) -> Path:
    testsuite = ET.Element(
        "testsuite",
        name=f"ournear-qa-{record.suite}",
        tests=str(len(record.tests)),
        failures=str(record.counts.get("failed", 0)),
        skipped=str(record.counts.get("skipped", 0) + record.counts.get("blocked", 0)),
        errors="0",
    )
    for test in record.tests:
        case = ET.SubElement(testsuite, "testcase", classname=test.nodeid.split("::")[0], name=test.nodeid)
        if test.outcome == RunCaseOutcome.FAILED:
            ET.SubElement(case, "failure", message="failed")
        elif test.outcome == RunCaseOutcome.BLOCKED:
            skip = ET.SubElement(case, "skipped", message=test.skip_reason or "blocked")
            skip.text = "blocked"
        elif test.outcome == RunCaseOutcome.SKIPPED:
            ET.SubElement(case, "skipped", message=test.skip_reason or "skipped")
    path.parent.mkdir(parents=True, exist_ok=True)
    ET.ElementTree(testsuite).write(path, encoding="utf-8", xml_declaration=True)
    return path
