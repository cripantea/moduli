#!/usr/bin/env python3
"""
Scarica i pesi FFDetr (Apache-2.0) da Hugging Face e li esporta in ONNX.

Serve solo una volta, in un ambiente temporaneo con rfdetr + torch (vedi setup.sh).
Uso: export_model.py <file_di_output.onnx>
"""
import glob
import os
import shutil
import sys
import tempfile

from huggingface_hub import hf_hub_download
from rfdetr import RFDETRMedium


def main() -> None:
    if len(sys.argv) != 2:
        print(__doc__, file=sys.stderr)
        sys.exit(2)

    weights = hf_hub_download("jbarrow/FFDetr", "FFDetr.pth")
    model = RFDETRMedium(pretrain_weights=weights, device="cpu")

    with tempfile.TemporaryDirectory() as tmp:
        model.export(output_dir=tmp)
        exported = glob.glob(os.path.join(tmp, "*.onnx"))
        if not exported:
            sys.exit("Export ONNX fallito: nessun file prodotto.")
        shutil.move(exported[0], sys.argv[1])

    print(f"Modello esportato in {sys.argv[1]}")


if __name__ == "__main__":
    main()
