# Apartment Community Platform (MVP)

PHP 8.5 / Laravel 11 / SQLite 기반 아파트 입주민 커뮤니티 플랫폼 초기 구축 저장소입니다.

## 현재 구성

- 단일 저장소에서 FE(Blade) + BE(API) 통합 관리
- DB: SQLite (초기), 추후 PostgreSQL 전환 가능
- 게시판 도메인 스키마 반영
    - apartments
    - board_categories
    - boards
    - posts
    - comments
    - post_files
    - reports
    - user_roles
- 권한 미들웨어 골격
    - RoleMiddleware
    - BoardAccessMiddleware
- API 라우트 골격
    - `/api/apartments/{apartmentId}/boards`
    - `/api/boards/{boardId}/posts`
    - `/api/posts/{id}`
    - `/api/posts/{postId}/comments`
    - `/api/reports`
    - `/api/admin/*`

## 실행 방법

1. 의존성 설치

```bash
composer install
```

2. 환경 파일 준비

```bash
cp .env.example .env
php artisan key:generate
```

3. DB 생성 및 마이그레이션 + 시드 데이터

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

4. 서버 실행

```bash
php artisan serve
```

## 웹 경로(초기 플레이스홀더)

- `/`
- `/apartments`
- `/community`
- `/community/free`
- `/community/info`
- `/community/market`
- `/community/lost`
- `/community/complaints`
- `/community/owners`
- `/community/tenants`

## 인증/관리자 경로

- `/login` (웹 세션 로그인)
- `/admin` (관리자 대시보드)
- `/admin/boards` (게시판 생성/수정/삭제)
- `/admin/reports` (신고 상태 처리)

## 초기 계정 (Seeder)

- 관리자: `admin@example.com` / `admin1234!`
- 일반 입주민: `resident@example.com` / `resident1234!`

## 로컬 DB 관련

- 현재 MVP는 SQLite 기반이라 별도 APM(MySQL) 설치 없이 로컬 테스트 가능합니다.
- 필요 시 이후 `DB_CONNECTION=mysql`로 전환해도 되지만, 현재 단계에서는 SQLite 유지가 가장 간단합니다.

## 참고

- 본 저장소는 MVP 구현 단계입니다.
- 정부24/마이데이터 연동, 정식 운영 정책, 고도화된 관리자 화면은 다음 단계에서 확장 가능합니다.
