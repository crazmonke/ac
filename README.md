# Apartment Community Platform (MVP)

PHP 8.5 / Laravel 11 / SQLite 기반 공동주택 입주민 커뮤니티 플랫폼 초기 구축 저장소입니다.

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

5. 브라우저 접속

```text
http://127.0.0.1:8000
```

6. 서버 종료

```text
서버를 실행한 터미널에서 Ctrl + C
```

## 로컬 서버 가동 가이드

처음 1회 세팅이 끝난 뒤에는 아래 명령만으로 로컬 서버를 다시 띄울 수 있습니다.

```bash
cd /Users/user/ac
php artisan serve
```

환경값(.env)을 수정한 경우에는 기존 서버를 종료(Ctrl + C)한 뒤 다시 실행해 주세요.

```bash
php artisan serve
```

## GitHub Actions 러너 실행 방법 (로컬)

이 프로젝트 배포는 self-hosted GitHub Actions 러너가 실행 중이어야 동작합니다.

1. 러너 디렉터리로 이동

```bash
cd /Users/user/actions-runner
```

2. 러너 실행

```bash
./run.sh
```

3. 실행 상태 유지

- 위 명령은 포그라운드 실행입니다.
- 해당 터미널을 닫거나 Ctrl + C를 누르면 러너가 중지됩니다.
- 배포가 필요할 때는 러너 터미널을 켜둔 상태를 유지해 주세요.

4. 러너 중지

```text
run.sh를 실행한 터미널에서 Ctrl + C
```

## 배포 방법 (순서대로)

### A. 기본 배포 (자동)

1. 기능 브랜치에서 작업 및 테스트

```bash
git add .
git commit -m "your message"
git push origin <feature-branch>
```

2. main으로 머지

```bash
git checkout main
git pull origin main
git merge <feature-branch>
git push origin main
```

3. 자동 배포 트리거

- main 브랜치에 push되면 Deploy to Cafe24 워크플로우가 자동 실행됩니다.
- 조건: 로컬 self-hosted 러너( /Users/user/actions-runner )가 실행 중이어야 합니다.

4. 배포 완료 확인

- GitHub Actions에서 Deploy to Cafe24 실행 결과가 Success인지 확인
- 워크플로우의 Post-deploy smoke checks 통과 여부 확인
- 운영 페이지 주요 경로 점검 (예: 홈, 로그인, 회원가입 주소검색)

### B. 수동 배포 (필요 시)

코드 변경 없이 재배포가 필요하면 GitHub Actions에서 수동 실행할 수 있습니다.

1. GitHub 저장소 Actions 탭 이동
2. Deploy to Cafe24 워크플로우 선택
3. Run workflow 클릭
4. 브랜치(main) 선택 후 실행

### C. 배포가 안 돌 때 점검

1. 러너 터미널에서 ./run.sh 실행 중인지 확인
2. GitHub 저장소의 러너 상태가 Online인지 확인
3. main 브랜치에 실제 push가 있었는지 확인
4. 필요한 Secrets가 누락되지 않았는지 확인

## 자주 쓰는 개발 명령어

### DB

```bash
# 마이그레이션만 적용
php artisan migrate

# DB 초기화 + 시드 재적재(개발용)
php artisan migrate:fresh --seed

# 시드만 재실행
php artisan db:seed
```

### 캐시/설정 반영

```bash
# 캐시/설정/뷰/라우트 캐시 일괄 초기화
php artisan optimize:clear

# 설정 캐시만 초기화
php artisan config:clear

# 라우트 목록 확인
php artisan route:list
```

### 테스트

```bash
# 전체 테스트
php artisan test

# PHPUnit 직접 실행
./vendor/bin/phpunit
```

### 동기화(공동주택 마스터)

```bash
# 정부 API 동기화 사전 확인 (DB 미반영)
php artisan apartments:sync --source=gov --rows=1000 --dry-run

# 실제 반영
php artisan apartments:sync --source=gov --rows=1000 --deactivate-missing
```

## 운영/개발 주의사항

### 운영(Production)에서 주의

- `php artisan migrate:fresh --seed`는 운영 DB를 초기화하므로 절대 실행하면 안 됩니다.
- 운영에서는 `php artisan migrate --force`만 사용해 스키마 변경을 적용하세요.
- `.env` 변경 후에는 서비스 영향 시간을 고려해 재기동/캐시 반영을 진행하세요.

```bash
# 운영 배포 시 권장 예시
php artisan migrate --force
php artisan optimize:clear
```

### 개발(Local)에서만 사용

- `php artisan migrate:fresh --seed`
- 테스트용 계정/샘플 데이터 재적재 명령
- 대량 동기화 전 `--dry-run` 없이 반복 실행하는 작업

### 공통 권장

- 중요한 작업 전 DB 백업을 먼저 수행하세요.
- 동기화/마이그레이션 전후로 로그와 주요 화면(홈, 커뮤니티, 관리자)을 빠르게 점검하세요.

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
