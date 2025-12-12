# matching_service.py
# ----------------------------------------------------------
# 内容スコア = テキスト類似度(E5) × 年齢スコア × 活動スコア
# ・年齢スコア: 対応範囲内なら 1 / それ以外 0
# ・活動スコア: Jaccard係数
# JSON だけを標準出力に出す（エラーも JSON）
# ----------------------------------------------------------

import sys
import json
import pymysql
import numpy as np
from sentence_transformers import SentenceTransformer
from sklearn.metrics.pairwise import cosine_similarity

# ===== エラーを必ず JSON で返す =====
def json_error(msg: str):
    print(json.dumps({"error": msg}, ensure_ascii=False))
    sys.exit(1)

# ===== DB 接続情報 =====
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "famisapo",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}

# ===== E5 モデル読み込み =====
try:
    model = SentenceTransformer("intfloat/multilingual-e5-base")
except Exception as e:
    json_error(f"モデル読込エラー: {e}")

def embed(text: str):
    if not text:
        text = ""
    return model.encode(text, normalize_embeddings=True)

# ----------------------------------------------------------
# 活動まわり
# ----------------------------------------------------------
def parse_activities(text: str):
    """'乳幼児預かり, 送迎' → {'乳幼児預かり','送迎'}"""
    if not text:
        return set()
    text = text.replace("、", ",")
    return {x.strip() for x in text.split(",") if x.strip()}

def activity_jaccard(req_set: set, prov_set: set) -> float:
    """活動のJaccard係数"""
    if not req_set or not prov_set:
        return 0.0
    inter = req_set & prov_set
    if not inter:
        return 0.0
    union = req_set | prov_set
    return len(inter) / len(union)

# ----------------------------------------------------------
# 年齢スコア（範囲内なら1, それ以外0）
# ----------------------------------------------------------
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

# ----------------------------------------------------------
# 依頼情報取得（detail + note + 年齢 + 活動）
# ----------------------------------------------------------
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
                json_error(f"support_requests.id={request_id} が見つかりません")

            # 活動(JSON)
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

# ----------------------------------------------------------
# 提供会員情報取得（プロフィール + 年齢範囲 + 活動）
# ----------------------------------------------------------
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

# ----------------------------------------------------------
# 内容スコア計算（テキスト × 年齢 × 活動 の積）
# ----------------------------------------------------------
def calc_content_scores(req: dict, providers: list[dict], top_k: int = 10):
    req_vec = embed(req["text"]).reshape(1, -1)

    results = []
    for p in providers:
        # テキスト類似度
        prov_vec = embed(p["profile_text"]).reshape(1, -1)
        text_sim = float(cosine_similarity(req_vec, prov_vec)[0][0])

        # 年齢スコア
        a_score = age_score(req["child_age"], p["min_age"], p["max_age"])

        # 活動スコア
        act_score = activity_jaccard(req["activity_set"], p["activity_set"])

        # 積
        content_score = text_sim * a_score * act_score

        results.append({
            "provider_id": p["provider_id"],
            "text_sim": text_sim,
            "age_score": a_score,
            "activity_score": act_score,
            "content_score": content_score,
            # 互換性のため score = content_score も出しておく
            "score": content_score,
        })

    results.sort(key=lambda x: x["content_score"], reverse=True)
    return results[:top_k]

# ----------------------------------------------------------
# main
# ----------------------------------------------------------
def main():
    if len(sys.argv) < 2:
        json_error("request_id を指定してください")

    try:
        request_id = int(sys.argv[1])
    except Exception:
        json_error("request_id は整数で指定してください")

    try:
        req = get_request(request_id)
        providers = get_providers()
        if not providers:
            json_error("提供会員プロフィールがありません")

        scores = calc_content_scores(req, providers, top_k=10)

        # 必ず JSON だけを出力
        print(json.dumps({
            "request_id": request_id,
            "results": scores
        }, ensure_ascii=False))

    except Exception as e:
        json_error(f"内部エラー: {e}")

if __name__ == "__main__":
    main()
