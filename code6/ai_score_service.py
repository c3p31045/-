# ai_score_service.py
import sys
import json
import numpy as np
import joblib

# 第一引数: 入力ファイル
# 第二引数: 出力ファイル
if len(sys.argv) < 3:
    print(json.dumps({"error": "引数が足りません"}))
    sys.exit(1)

input_path = sys.argv[1]
output_path = sys.argv[2]

MODEL_PATH = "matching_model.pkl"
model = joblib.load(MODEL_PATH)

def predict(distance_score, time_score, content_score):
    x = np.array([[distance_score, time_score, content_score]])
    return float(model.predict_proba(x)[0][1])

try:
    with open(input_path, "r", encoding="utf-8") as f:
        items = json.load(f)
except Exception as e:
    with open(output_path, "w", encoding="utf-8") as f:
        f.write(json.dumps({"error": f"入力JSONエラー: {e}"}))
    sys.exit(1)

results = []
for item in items:
    p = predict(
        float(item["distance_score"]),
        float(item["time_score"]),
        float(item["content_score"])
    )
    item["expected_match_probability"] = p
    results.append(item)

with open(output_path, "w", encoding="utf-8") as f:
    f.write(json.dumps(results, ensure_ascii=False))
