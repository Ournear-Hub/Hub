from __future__ import annotations

from pathlib import Path

from ournear_qa.settings import ROOT

GENERATED_DIR = ROOT / "fixtures" / "generated"

_MINIMAL_PDF = (
    b"%PDF-1.4\n"
    b"1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
    b"2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
    b"3 0 obj<</Type/Page/MediaBox[0 0 200 200]/Parent 2 0 R>>endobj\n"
    b"xref\n0 4\n0000000000 65535 f \n"
    b"trailer<</Root 1 0 R/Size 4>>\nstartxref\n0\n%%EOF\n"
)


def synthetic_text_file(name: str = "qa-upload.txt", content: bytes | None = None) -> Path:
    GENERATED_DIR.mkdir(parents=True, exist_ok=True)
    path = GENERATED_DIR / name
    path.write_bytes(content or b"OurNear QA synthetic upload\n")
    return path


def synthetic_pdf_file(name: str = "qa-upload.pdf") -> Path:
    GENERATED_DIR.mkdir(parents=True, exist_ok=True)
    path = GENERATED_DIR / name
    path.write_bytes(_MINIMAL_PDF)
    return path
