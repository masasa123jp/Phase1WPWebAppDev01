
# RoRo Core — Phase 1.6 Pet Platform

> **EN:** AI‑driven WordPress plugin & React front‑end for pet‑care.  
> **JA:** ペットケア向け AI 活用の WordPress プラグイン + React フロントエンド。  
> **ZH‑CN:** 面向宠物护理的 AI 驱动 WordPress 插件与 React 前端。  
> **KO:** 반려동물 케어를 위한 AI 기반 WordPress 플러그인과 React 프런트엔드.

---

## 🌟 Features / 機能 / 功能 / 기능

| Module | English | 日本語 | 中文 | 한국어 |
|--------|---------|--------|------|--------|
| **Gacha API** | Random advice & facility suggestions | ランダムにアドバイス/施設を提案 | 随机抽取建议和设施 | 랜덤 조언·시설 추천 |
| **Facility Search** | Radius search via GIS/Haversine | GIS/Haversine による距離検索 | GIS/Haversine 范围检索 | GIS/Haversine 반경 검색 |
| **Admin KPI** | Dashboard widget | ダッシュボード KPI | 仪表盘 KPI | 대시보드 KPI |
| **Blocks** | Gacha Wheel / Advice List | ガチャホイール / アドバイス一覧 | 抽奖按钮 / 建议列表 | 가차 휠 / 조언 리스트 |
| **React SPA** | LIFF auth, facility/advice pages | LIFF 認証と施設/詳細ページ | LIFF 认证及页面 | LIFF 인증 및 페이지 |

---

## 🚀 Quick Start

### Local (Docker)
```bash
git clone https://github.com/masasa123jp/Phase1WPWebAppDev01
cd Phase1WPWebAppDev01/docker
docker compose up -d
```

### Production (XServer)
1. Zip `wp-content/plugins/roro-core/` as **roro-core.zip**  
2. Upload via **Plugins → Add New → Upload Plugin**  
3. Add HTTP‑Cron: `https://<domain>/wp-cron.php?doing_wp_cron=1` every 10 min

---

## 🛠 Development Workflow
| Step | Command |
|------|---------|
| Install deps | `composer install && npm ci` |
| Lint PHP/JS   | `make lint` |
| Unit tests    | `make test` |
| E2E tests     | `make e2e` |
| Make POT      | `bash scripts/make-pot.sh` |

---

## 🗂 Structure
```
plugins/roro-core/   ← WP plugin
frontend/            ← React + Vite SPA
docker/              ← Local stack
tests/               ← PHPUnit, Vitest, Playwright
```

---

## 🌐 日本語
WordPress サイトを AI ペットケアプラットフォームに拡張。詳細は上記を参照。

## 🌏 中文
将 WordPress 升级为宠物护理平台，具体用法见上。

## 🇰🇷 한국어
WordPress를 반려동물 케어 플랫폼으로 확장합니다. 위 설명 참조.

---

## 📄 License
Plugin: GPL‑2.0+   Front‑end: MIT
