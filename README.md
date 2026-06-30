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

3. DB 생성 및 마이그레이션

```bash
touch database/database.sqlite
php artisan migrate
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

## 참고

- 본 저장소는 MVP 스캐폴딩 단계입니다.
- 인증(휴대폰/정부24/마이데이터), 파일 서명 URL, 관리자 상세 화면은 다음 단계에서 구현 예정입니다.
