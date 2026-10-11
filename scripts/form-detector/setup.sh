#!/usr/bin/env bash
#
# Installa il rilevatore di campi FFDetr (opzionale) per l'estrazione AI dei moduli.
#
#   bash scripts/form-detector/setup.sh [cartella]      (default: storage/app/form-detector)
#
# Crea:
#   <cartella>/venv         ambiente Python leggero (onnxruntime, numpy, pillow)
#   <cartella>/ffdetr.onnx  modello esportato (~130 MB)
#
# L'export richiede temporaneamente PyTorch (CPU) in un secondo venv, che viene
# cancellato alla fine. Serve Python >= 3.10 con il modulo venv
# (Ubuntu/Debian: apt install python3-venv).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"
TARGET="${1:-$APP_DIR/storage/app/form-detector}"
PYTHON="${PYTHON:-python3}"

mkdir -p "$TARGET"
TARGET="$(cd "$TARGET" && pwd)"

echo "→ Runtime in $TARGET/venv"
"$PYTHON" -m venv "$TARGET/venv"
"$TARGET/venv/bin/pip" install -q --upgrade pip
"$TARGET/venv/bin/pip" install -q onnxruntime numpy pillow

if [ -f "$TARGET/ffdetr.onnx" ] && [ "${FORCE_EXPORT:-0}" != "1" ]; then
    echo "→ Modello già presente ($TARGET/ffdetr.onnx), salto l'export (FORCE_EXPORT=1 per rifarlo)"
else
    BUILD="$(mktemp -d)"
    trap 'rm -rf "$BUILD"' EXIT

    echo "→ Export del modello (venv temporaneo con PyTorch CPU, qualche minuto)…"
    "$PYTHON" -m venv "$BUILD/venv"
    "$BUILD/venv/bin/pip" install -q --upgrade pip
    "$BUILD/venv/bin/pip" install -q torch torchvision --index-url https://download.pytorch.org/whl/cpu
    "$BUILD/venv/bin/pip" install -q rfdetr onnx onnxsim huggingface-hub
    HF_HOME="$BUILD/hf" "$BUILD/venv/bin/python" "$SCRIPT_DIR/export_model.py" "$TARGET/ffdetr.onnx"
fi

echo "→ Verifica"
"$TARGET/venv/bin/python" -c "import onnxruntime as ort; s = ort.InferenceSession('$TARGET/ffdetr.onnx'); print('  input', s.get_inputs()[0].shape)"

# Il processo PHP (www-data) deve poter leggere modello ed eseguire il venv.
chmod -R a+rX "$TARGET"

echo "✓ Rilevatore pronto. Nel .env:"
echo "  FORM_DETECTOR_PYTHON=$TARGET/venv/bin/python"
echo "  FORM_DETECTOR_MODEL=$TARGET/ffdetr.onnx"
