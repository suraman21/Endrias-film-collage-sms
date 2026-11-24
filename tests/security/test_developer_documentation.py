"""Source-only navigation/trace regressions, not full manual/runtime acceptance.

No network, database, credentials, app execution or dependency changes.
Tests protect cited paths/actions and preserve explicit incomplete coverage.
They do not prove the accuracy of every sentence or the absence of UI bugs.
"""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def read(path):
    return (ROOT / path).read_text(encoding="utf-8")


def test_developer_entry_points_are_discoverable():
    index = read("docs/README.md")
    for path in (
        "DEVELOPER_GUIDE.md", "CHANGE_IMPACT.md", "QUALITY_GATES.md",
        "architecture/SECURITY_MODEL.md", "../AGENTS.md",
    ):
        assert f"]({path})" in index, path
    onboarding = read("docs/ONBOARDING.md")
    assert "](DEVELOPER_GUIDE.md)" in onboarding
    assert "](../AGENTS.md)" in onboarding


def test_root_agent_guide_has_source_configured_markdown_deny_rule():
    # Apache configuration evidence only, not a live host 403 test.
    rules = re.findall(r"^\s*RewriteRule\s+(\S+)\s+-\s+\[F,L\]", read(".htaccess"), re.M)
    assert any(re.search(rule, "AGENTS.md") for rule in rules)
    assert (ROOT / "AGENTS.md").is_file()


def test_exhaustive_manual_remains_active_and_incomplete():
    coverage = read("docs/user_manual/COVERAGE.md")
    assert "INCOMPLETE — ADDITIONAL WORK REQUIRED" in coverage
    assert "active requested work, not deferred" in coverage
    for chapter in ("FINANCE.md", "MATERIALS.md"):
        assert f"]({chapter})" in read("docs/user_manual/README.md")
        assert f"]({chapter})" in coverage
    assert "shared controls and browser acceptance pending" in coverage
    catalog = read("docs/DOCUMENTATION_CATALOG.md")
    assert "not deferred" in catalog
    assert "not complete" in catalog
