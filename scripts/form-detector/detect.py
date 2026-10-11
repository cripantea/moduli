#!/usr/bin/env python3
"""
Rilevamento dei campi di un modulo con FFDetr (RF-DETR addestrato su CommonForms).

Modello: https://huggingface.co/jbarrow/FFDetr — licenza Apache-2.0.
Esportato in ONNX da export_model.py: qui servono solo onnxruntime, numpy e pillow.

Uso:   detect.py <modello.onnx> <pagina1.png> [<pagina2.png> ...]
Output (stdout, JSON): una lista per pagina di
       {"cls": "text"|"choice"|"signature", "conf": float, "x0", "y0", "x1", "y1"}
       con coordinate normalizzate 0..1 rispetto all'immagine.
"""
import json
import sys

import numpy as np
import onnxruntime as ort
from PIL import Image

CLASSES = ["text", "choice", "signature"]
THRESHOLD = 0.4   # come commonforms
NMS_IOU = 0.1     # NMS indipendente dalla classe, come commonforms
MEAN = np.array([0.485, 0.456, 0.406], dtype=np.float32)
STD = np.array([0.229, 0.224, 0.225], dtype=np.float32)


def nms(boxes: np.ndarray, scores: np.ndarray, iou: float) -> list[int]:
    order = scores.argsort()[::-1]
    keep: list[int] = []
    while order.size:
        i = order[0]
        keep.append(int(i))
        rest = order[1:]
        ix = np.maximum(0, np.minimum(boxes[i, 2], boxes[rest, 2]) - np.maximum(boxes[i, 0], boxes[rest, 0]))
        iy = np.maximum(0, np.minimum(boxes[i, 3], boxes[rest, 3]) - np.maximum(boxes[i, 1], boxes[rest, 1]))
        inter = ix * iy
        area_i = (boxes[i, 2] - boxes[i, 0]) * (boxes[i, 3] - boxes[i, 1])
        area_r = (boxes[rest, 2] - boxes[rest, 0]) * (boxes[rest, 3] - boxes[rest, 1])
        order = rest[inter / np.maximum(area_i + area_r - inter, 1e-9) <= iou]
    return keep


def detect(session: ort.InferenceSession, path: str) -> list[dict]:
    inp = session.get_inputs()[0]
    size = int(inp.shape[2])

    image = Image.open(path).convert("RGB").resize((size, size), Image.BILINEAR)
    x = (np.asarray(image, dtype=np.float32) / 255.0 - MEAN) / STD
    x = x.transpose(2, 0, 1)[None].astype(np.float32)

    dets, logits = session.run(None, {inp.name: x})
    probs = 1.0 / (1.0 + np.exp(-logits[0][:, : len(CLASSES)]))
    cls = probs.argmax(1)
    conf = probs.max(1)
    mask = conf >= THRESHOLD

    cx, cy, w, h = dets[0][mask].T
    boxes = np.clip(np.stack([cx - w / 2, cy - h / 2, cx + w / 2, cy + h / 2], axis=1), 0.0, 1.0)
    cls, conf = cls[mask], conf[mask]

    out = []
    for i in nms(boxes, conf, NMS_IOU):
        x0, y0, x1, y1 = (round(float(v), 5) for v in boxes[i])
        out.append({"cls": CLASSES[int(cls[i])], "conf": round(float(conf[i]), 3),
                    "x0": x0, "y0": y0, "x1": x1, "y1": y1})
    return out


def main() -> None:
    if len(sys.argv) < 3:
        print(__doc__, file=sys.stderr)
        sys.exit(2)

    options = ort.SessionOptions()
    options.intra_op_num_threads = 2  # non monopolizzare la CPU del server web
    session = ort.InferenceSession(sys.argv[1], options, providers=["CPUExecutionProvider"])

    json.dump([detect(session, p) for p in sys.argv[2:]], sys.stdout)


if __name__ == "__main__":
    main()
