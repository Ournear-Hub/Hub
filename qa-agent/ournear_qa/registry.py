from __future__ import annotations

from pathlib import Path
from typing import Any

import yaml

from ournear_qa.registry_validate import ValidationResult, validate_registry

ROOT = Path(__file__).resolve().parents[1]
REGISTRY_PATH = ROOT / "registry" / "features.yaml"


def load_registry(*, validate: bool = False) -> dict[str, Any]:
    with REGISTRY_PATH.open(encoding="utf-8") as handle:
        data = yaml.safe_load(handle)
    if validate:
        validate_registry(data)
    return data


def load_and_validate_registry() -> tuple[dict[str, Any], ValidationResult]:
    data = load_registry()
    result = validate_registry(data)
    return data, result
