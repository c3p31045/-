# api_server.py
# ファミサポAIマッチングのPython APIサーバ
# - GET  /api/content-scores/{request_id}
# - POST /api/score-providers

from typing import List, Optional

import json
import pymysql
import numpy as np
import joblib
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity

# =====================================
# FastAPI 本体
# =====================================
app = FastAPI(title="Famisapo Matching API")

# =====================================
# DB 設定（matching_service.py と同じ）:contentReference[oaicite:6]{index=6}
# =====================================
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "famisapo",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}

# =====================================
# モデルのロード（アプリ起動時に1回）
# =====================================
try:
    e5_model = SentenceTransformer("intfloat/multilingual-e5-base")
except Exception as e:
    # 起動時に死ぬ方がわかりやすい
    raise RuntimeError(f"E5モデル読込エラー: {e}")

try:
    LOGIT_MODEL_PATH = "matching_model.pkl"
    logit_model = joblib.load(LOGIT_MODEL_PATH)
except Exception as e:
    raise RuntimeError(f"matching_model.pkl 読込エラー: {e}")

# =====================================
# ユーティリティ（matching_service.py より）:contentReference[oaicite:8]{index=8}
# =====================================
def embed(text: str):
    if not text:
        text = ""
    return e5_model.encode(text, normalize_embeddings=True)

def parse_activities(text: str):
    if not text:
        return set()
    text = text.replace("、", ",")
    return {x.strip() for x in text.split(",") if x.strip()}

def activity_jaccard(req_set: set, prov_set: set) -> float:
    if not req_set or not prov_set:
        return 0.0
    inter = req_set & prov_set
    if not inter:
        return 0.0
    union = req_set | prov_set
    return len(inter) / len(union)

def age_score(child_age, min_age, max_age) -> float:
    if child_age is None or min_age is None or max_age is None:
        return 0.0
    try:
        c = int(child_age)
        mn = int(min_age)
        mx = int(max_age)
    except Exception:
        return 0.0
    return 1.0 if mn <= c <= mx else 0.0

# =====================================
# DB アクセス（matching_service.py を関数化）:contentReference[oaicite:9]{index=9}
# =====================================
def get_request(request_id: int) -> dict:
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cur:
            sql = """
                SELECT
                    detail,
                    COALESCE(note,'') AS note,
                    child_age,
                    care_types_json
                FROM support_requests
                WHERE id = %s
            """
            cur.execute(sql, (request_id,))
            r = cur.fetchone()
            if not r:
                raise HTTPException(status_code=404, detail=f"support_requests.id={request_id} が見つかりません")

            req_acts = set()
            if r["care_types_json"]:
                try:
                    data = json.loads(r["care_types_json"])
                    req_acts = set(data.get("selected", []))
                except Exception:
                    req_acts = set()

            return {
                "text": f"{r['detail']} {r['note']}".strip(),
                "child_age": r["child_age"],
                "activity_set": req_acts,
            }
    finally:
        conn.close()

def get_providers() -> list[dict]:
    conn = pymysql.connect(**DB_CONFIG)
    try:
        with conn.cursor() as cur:
            sql = """
                SELECT
                    pp.id AS provider_id,
                    v.profile_text,
                    pp.child_age_min,
                    pp.child_age_max,
                    GROUP_CONCAT(DISTINCT ma.name SEPARATOR ',') AS acts
                FROM provider_profiles pp
                JOIN v_provider_profile_text v ON v.provider_id = pp.id
                LEFT JOIN provider_activities pa ON pa.provider_id = pp.id
                LEFT JOIN m_activities ma ON ma.id = pa.activity_id
                GROUP BY pp.id
            """
            cur.execute(sql)
            rows = cur.fetchall()

        providers = []
        for r in rows:
            providers.append({
                "provider_id": r["provider_id"],
                "profile_text": r["profile_text"],
                "min_age": r["child_age_min"],
                "max_age": r["child_age_max"],
                "activity_set": parse_activities(r["acts"]),
            })
        return providers
    finally:
        conn.close()

def calc_content_scores(req: dict, providers: list[dict], top_k: int = 10):
    req_vec = embed(req["text"]).reshape(1, -1)

    results = []
    for p in providers:
        prov_vec = embed(p["profile_text"]).reshape(1, -1)
        text_sim = float(cosine_similarity(req_vec, prov_vec)[0][0])

        a_score = age_score(req["child_age"], p["min_age"], p["max_age"])
        act_score = activity_jaccard(req["activity_set"], p["activity_set"])

        content_score = text_sim * a_score * act_score

        results.append({
            "provider_id": p["provider_id"],
            "text_sim": text_sim,
            "age_score": a_score,
            "activity_score": act_score,
            "content_score": content_score,
            "score": content_score,
        })

    results.sort(key=lambda x: x["content_score"], reverse=True)
    return results[:top_k]

# =====================================
# ロジスティック回帰側（ai_score_service.py より）:contentReference[oaicite:10]{index=10}
# =====================================
def predict_probability(distance_score: float, time_score: float, content_score: float) -> float:
    x = np.array([[distance_score, time_score, content_score]])
    return float(logit_model.predict_proba(x)[0][1])

# =====================================
# Pydantic モデル（POST /api/score-providers 用）
# =====================================
class ProviderScoreIn(BaseModel):
    provider_id: int
    distance_score: float
    time_score: float
    content_score: float
    distance_km: Optional[float] = None
    provider_name: Optional[str] = None

class ProviderScoreOut(ProviderScoreIn):
    expected_match_probability: float

# =====================================
# エンドポイント定義
# =====================================

@app.get("/healthz")
def health_check():
    return {"status": "ok"}

@app.get("/api/content-scores/{request_id}")
def api_content_scores(request_id: int):
    """
    request_id を指定して、内容スコア（テキスト×年齢×活動）を返す。
    matching_service.py の出力とほぼ同じ形式。
    """
    req = get_request(request_id)
    providers = get_providers()
    if not providers:
        raise HTTPException(status_code=500, detail="提供会員プロフィールがありません")

    scores = calc_content_scores(req, providers, top_k=10)
    return {
        "request_id": request_id,
        "results": scores,
    }

@app.post("/api/score-providers", response_model=List[ProviderScoreOut])
def api_score_providers(items: List[ProviderScoreIn]):
    """
    距離スコア・時間スコア・内容スコアから expected_match_probability を計算して返す。
    入出力は ai_input.json / ai_output.json と同じイメージ。
    """
    results: List[ProviderScoreOut] = []
    for item in items:
        p = predict_probability(
            distance_score=item.distance_score,
            time_score=item.time_score,
            content_score=item.content_score,
        )
        results.append(
            ProviderScoreOut(
                **item.dict(),
                expected_match_probability=p
            )
        )
    return results
