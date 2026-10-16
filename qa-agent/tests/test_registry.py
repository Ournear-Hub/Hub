from ournear_qa.registry import load_and_validate_registry


def test_registry_has_minimum_feature_ids() -> None:
    data, meta = load_and_validate_registry()
    features = data.get("features") or []
    assert meta.feature_count == len(features)
    assert len(features) == 53
    ids = {row["id"] for row in features}
    assert "PUB-03" in ids
    assert "CAN-04" in ids
    assert "X-03" in ids
