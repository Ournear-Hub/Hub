from __future__ import annotations

JRN_CANDIDATE_APPLY = "JRN-CANDIDATE-APPLY-01"


class JourneyStepError(Exception):
    def __init__(
        self,
        step_id: str,
        expected: str,
        actual: str,
        *,
        journey_id: str = JRN_CANDIDATE_APPLY,
    ) -> None:
        self.step_id = step_id
        self.journey_id = journey_id
        self.expected = expected
        self.actual = actual
        super().__init__(f"[{journey_id}/{step_id}] expected {expected!r}; got {actual!r}")
