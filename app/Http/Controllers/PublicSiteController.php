<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Banner;
use App\Models\Board;
use App\Models\BlockedUser;
use App\Models\Post;
use App\Models\PostFile;
use App\Models\PostLike;
use App\Services\PermissionService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PublicSiteController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function home(Request $request)
    {
        $requestedApartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        if ($user) {
            $user->loadMissing('preferredResidenceComplex');
        }

        $apartment = ($user?->preferred_apartment_id ? Apartment::query()->find((int) $user->preferred_apartment_id) : null)
            ?? Apartment::query()->find($requestedApartmentId)
            ?? Apartment::query()->orderBy('id')->first();

        if (! $apartment) {
            $apartment = new Apartment([
                'id' => 0,
                'name' => '공동주택 정보 준비중',
                'sido' => '',
                'sigungu' => '',
                'eupmyeondong' => '',
            ]);
        }

        $hasPostLikesTable = Schema::hasTable('post_likes');
        $canQueryFeed = Schema::hasTable('posts') && Schema::hasTable('boards');
        $hasAudienceScopeColumn = $canQueryFeed && Schema::hasColumn('posts', 'audience_scope');

        // 커뮤니티 메인(/community) 전체 탭과 동일한 조건으로 노출한다.
        if ($canQueryFeed) {
            $query = Post::query()
                ->with(['board', 'apartment', 'files', 'user', 'poll.options'])
                ->where('visibility', '!=', 'deleted')
                ->whereHas('board', function ($query) {
                    $query->where('is_active', true)
                        ->where('slug', '!=', 'policy');
                })
                ->latest()
                ->orderByDesc('id');

            if ($hasPostLikesTable) {
                $query->withCount('likes');
            }

            if ($hasAudienceScopeColumn) {
                $query->whereIn('audience_scope', ['region', 'all']);
            }

            if ($user && Schema::hasTable('blocked_users')) {
                $query->whereNotIn('user_id', function ($blockedQuery) use ($user) {
                    $blockedQuery->select('blocked_id')
                        ->from('blocked_users')
                        ->where('blocker_id', $user->id);
                });
            }

            $feedPaginator = $query->paginate(20)->withQueryString();
        } else {
            $page = max(1, (int) $request->query('page', 1));
            $feedPaginator = new LengthAwarePaginator(
                collect(),
                0,
                20,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        }

        $feedPaginator = $this->mapPostPaginator($feedPaginator, $user, $hasPostLikesTable);

        $isLoggedIn = (bool) $user;
        $isVerifiedUser = (bool) ($user && $this->permissionService->hasVerifiedRole($user));

        $feedTitle = '전국 동네 피드';
        $feedDescription = '전국 동네 게시글을 최신순으로 보여드립니다.';

        $banners = Banner::active()->ordered()->get()->map(function ($banner) {
            // 업로드된 파일이 있으면 그것을 우선 사용 (Cafe24 호환성: public/uploads 경로 사용)
            if ($banner->type === 'image' && $banner->image_path) {
                $banner->image_url = asset($banner->image_path);
            }
            if ($banner->type === 'video' && $banner->video_path) {
                $banner->video_url = asset($banner->video_path);
            }
            return $banner;
        });

        // 게시글 없을 때 표시할 랜덤 메시지
        $emptyFeedMessages = [
            '첫 게시글의 주인공이 되어보세요.',
            '우리 동네 소식을 가장 먼저 알려주세요.',
            '질문 하나, 정보 하나가 이웃에게 큰 도움이 됩니다.',
            '오늘의 첫 이야기를 남겨보세요.',
            '이웃들이 기다리고 있는 정보를 공유해 주세요.',
            '우리 동네의 첫 소식을 전해볼까요?',
            "오늘은 비어 있지만,\n내일은 이웃들의 이야기로 가득 찰 거예요.",
            '첫 번째 이야기가 새로운 인연을 만듭니다.',
            '작은 글 하나가 따뜻한 이웃을 만나는 시작입니다.',
            "모두가 기다리는 건 특별한 글이 아니라,\n당신의 첫 이야기입니다.",
            '우리 동네 이야기는 주민이 만들어갑니다.',
            '층간소음보다 따뜻한 대화가 먼저 시작되길 바랍니다.',
            "공지부터 맛집, 생활 꿀팁까지.\n우리 동네 이야기를 공유해 보세요.",
            "같은 건물, 같은 동네.\n이제는 이야기도 함께 나눠보세요.",
            '우리 단지의 정보와 일상을 함께 만들어가요.',
        ];

        return view('public.home', [
            'apartment' => $apartment,
            'feedPosts' => $feedPaginator,
            'feedTitle' => $feedTitle,
            'feedDescription' => $feedDescription,
            'isLoggedIn' => $isLoggedIn,
            'isVerifiedUser' => $isVerifiedUser,
            'banners' => $banners,
            'emptyFeedMessage' => $emptyFeedMessages[array_rand($emptyFeedMessages)],
        ]);
    }

    public function board(Request $request, string $slug)
    {
        $apartmentId = max(1, (int) $request->query('apartment_id', 1));
        $user = $request->user();

        $board = Board::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where(function ($query) use ($apartmentId) {
                $query->whereNull('apartment_id')
                    ->orWhere('apartment_id', $apartmentId);
            })
            ->orderByRaw('CASE WHEN apartment_id IS NULL THEN 0 ELSE 1 END')
            ->firstOrFail();

        $canReadBoard = $this->permissionService->hasBoardPermission($user, $board, 'read');

        $posts = Post::query()
            ->where('board_id', $board->id)
            ->where('visibility', '!=', 'deleted')
            ->when($user && Schema::hasTable('blocked_users'), function ($query) use ($user) {
                $query->whereNotIn('user_id', function ($blockedQuery) use ($user) {
                    $blockedQuery->select('blocked_id')
                        ->from('blocked_users')
                        ->where('blocker_id', $user->id);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $postReadMap = [];
        foreach ($posts as $post) {
            $postReadMap[$post->id] = $this->canReadPost($user, $post, $canReadBoard);
        }

        return view('public.board', [
            'board' => $board,
            'posts' => $posts,
            'canReadBoard' => $canReadBoard,
            'postReadMap' => $postReadMap,
            'apartmentId' => $apartmentId,
        ]);
    }

    public function post(Request $request, int $id)
    {
        $post = Post::query()
            ->with(['board', 'apartment', 'user'])
            ->findOrFail($id);

        $user = $request->user();
        if ($user && Schema::hasTable('blocked_users') && BlockedUser::query()
            ->where('blocker_id', $user->id)
            ->where('blocked_id', $post->user_id)
            ->exists()) {
            abort(404);
        }

        $canRead = $this->canReadPost($user, $post);

        return view('public.post', [
            'post' => $post,
            'canRead' => $canRead,
            'apartmentId' => (int) $post->apartment_id,
            'isLoggedIn' => (bool) $user,
        ]);
    }

    public function terms()
    {
        return view('public.policy', [
            'title' => '이용약관',
            'content' => <<<'TEXT'
서비스 이용약관

제1조 (목적)

본 약관은 아파인드(이하 "서비스")가 제공하는 공동주택 커뮤니티 서비스의 이용 조건 및 회원과 서비스 간의 권리와 의무를 규정함을 목적으로 합니다.

서비스는 부적절한 콘텐츠와 악성 사용자에 대해 무관용 원칙을 적용합니다. 위반이 확인된 게시물과 댓글은 삭제될 수 있으며, 위반 사용자는 경고 없이 서비스 이용이 제한되거나 계정이 정지 또는 삭제될 수 있습니다.

제2조 (회원가입)

회원은 본 약관과 개인정보처리방침에 동의한 후 회원가입을 완료하여 서비스를 이용할 수 있습니다.

서비스는 공동주택 거주 인증 절차를 요구할 수 있으며, 인증이 완료된 회원에 한하여 일부 서비스를 이용할 수 있습니다.

제3조 (회원의 의무)

회원은 다음 각 호의 행위를 하여서는 안 됩니다.

• 타인의 개인정보 또는 계정을 도용하는 행위
• 허위 정보를 등록하거나 사실과 다른 내용을 게시하는 행위
• 욕설, 비방, 혐오 표현 등 타인의 권리를 침해하는 행위
• 음란물 또는 청소년에게 유해한 정보를 게시하는 행위
• 광고, 홍보 또는 영리 목적의 게시물을 반복적으로 등록하는 행위
• 스팸 또는 도배 행위
• 타인의 개인정보를 무단으로 공개하는 행위
• 서비스의 정상적인 운영을 방해하는 행위
• 관계 법령을 위반하는 모든 행위

제4조 (게시물)

게시물에 대한 책임은 작성자에게 있으며, 서비스는 게시물의 내용에 대해 원칙적으로 책임을 지지 않습니다.

다만, 다음에 해당하는 게시물은 사전 통보 없이 삭제하거나 게시를 제한할 수 있습니다.

• 욕설 또는 비방이 포함된 게시물
• 명예훼손에 해당하는 게시물
• 허위 사실을 유포하는 게시물
• 정치·종교적 갈등을 조장하는 게시물
• 상업적 광고 및 홍보 목적의 게시물
• 개인정보가 포함된 게시물
• 관계 법령 또는 본 약관을 위반하는 게시물

제5조 (커뮤니티 운영)

서비스는 건전한 공동체 문화를 조성하기 위해 게시글, 댓글, 공감, 신고 등의 기능을 운영합니다.

신고가 접수된 게시물은 운영 정책에 따라 검토되며, 필요한 경우 게시물 삭제, 이용 제한 등의 조치를 취할 수 있습니다.

제6조 (포인트 제도)

서비스는 회원의 커뮤니티 활동을 장려하기 위해 포인트 제도를 운영합니다.

1. 포인트 적립
회원은 다음 활동을 통해 포인트를 적립할 수 있습니다.

• 게시글 작성
• 댓글 작성(동일 게시글당 1회에 한하며, 본인이 작성한 게시글은 제외)

1일 적립 가능한 포인트에는 상한이 있으며, 적립 기준 및 최대 적립량은 운영 정책에 따라 변경될 수 있습니다.

2. 포인트 회수
게시글 또는 댓글을 삭제하는 경우 해당 활동으로 적립된 포인트는 회수됩니다.

관리자에 의해 게시글 또는 댓글이 삭제된 경우에도 동일하게 적용됩니다.

3. 포인트 소멸
운영 정책에 따라 적립일로부터 일정 기간이 지난 포인트는 자동으로 소멸될 수 있습니다.

소멸 기준 및 일정은 사전에 공지합니다.

4. 관리자 조정
서비스 운영자는 이벤트, 시스템 오류 수정, 운영 정책 변경 등의 사유가 있는 경우 회원의 포인트를 지급하거나 차감할 수 있습니다.

5. 유의사항
• 포인트는 현금으로 환급하거나 양도할 수 없습니다.
• 회원 탈퇴 시 보유한 포인트는 모두 소멸됩니다.
• 부정한 방법으로 포인트를 취득한 사실이 확인될 경우 해당 포인트는 회수되며, 서비스 이용이 제한될 수 있습니다.

제7조 (회원 탈퇴)

회원은 언제든지 회원 탈퇴를 신청할 수 있습니다.

회원 탈퇴 시 개인정보는 개인정보처리방침에 따라 처리되며, 관계 법령에 따라 일정 기간 보관이 필요한 정보는 해당 기간 동안 보관될 수 있습니다.

제8조 (서비스 이용 제한)

서비스는 다음 각 호에 해당하는 경우 회원의 서비스 이용을 제한하거나 계정을 정지 또는 삭제할 수 있습니다.

• 본 약관을 위반한 경우
• 반복적으로 신고가 접수되어 운영 정책에 위반되는 것으로 확인된 경우
• 불법 행위를 한 경우
• 서비스 운영을 방해하거나 다른 회원의 이용을 현저히 저해한 경우

제9조 (면책사항)

서비스는 회원 간 발생한 분쟁에 직접 개입하지 않으며, 게시물에 대한 법적 책임은 작성자에게 있습니다.

서비스는 천재지변, 시스템 장애, 통신 장애 등 불가항력적인 사유로 인해 서비스를 제공할 수 없는 경우 이에 대한 책임을 지지 않습니다.

제10조 (약관 변경)

서비스는 관련 법령 또는 운영 정책의 변경이 필요한 경우 본 약관을 변경할 수 있습니다.

약관이 변경되는 경우 시행일 및 변경 내용을 서비스 내 공지사항 등을 통해 사전에 안내하며, 변경된 약관은 공지한 시행일부터 효력이 발생합니다.

최종 수정일 : 2026년 7월 14일
TEXT,
        ]);
    }

    public function privacy()
    {
        return view('public.policy', [
            'title' => '개인정보처리방침',
            'content' => <<<'TEXT'
최종 수정일 : 2026년 7월 14일

아파인드(이하 "서비스")는 「개인정보 보호법」 등 관련 법령을 준수하며 이용자의 개인정보를 안전하게 보호하기 위하여 다음과 같이 개인정보처리방침을 수립·공개합니다.

제1조 (수집하는 개인정보)

서비스는 다음과 같은 개인정보를 수집할 수 있습니다.

1. 회원가입 시
• 이름(또는 닉네임)
• 이메일 주소
• 비밀번호(암호화 저장)

2. 공동주택 인증 시
• 주소 또는 공동주택 정보
• 동·호수(필요한 경우)
• 거주 인증 정보

※ 실제 동·호수는 인증 목적으로만 사용되며, 다른 회원에게 공개되지 않습니다.

3. 서비스 이용 과정에서
• 접속 로그
• IP 주소
• 쿠키
• 기기 정보
• 이용 기록

제2조 (개인정보의 이용 목적)

수집한 개인정보는 다음의 목적으로 이용됩니다.

• 회원가입 및 본인 확인
• 공동주택 거주 여부 확인
• 커뮤니티 운영
• 게시글 및 댓글 서비스 제공
• 민원 및 문의 처리
• 서비스 개선
• 부정 이용 방지
• 법령상 의무 이행

제3조 (개인정보 보유 및 이용기간)

• 회원 탈퇴 시 지체 없이 파기합니다.
• 관계 법령에 따라 일정 기간 보관이 필요한 경우 해당 기간 동안 보관합니다.

예)
• 계약 또는 청약철회 기록 : 5년
• 소비자 불만 처리 기록 : 3년
• 접속기록 : 3개월

제4조 (개인정보의 제3자 제공)

서비스는 이용자의 개인정보를 원칙적으로 제3자에게 제공하지 않습니다.

다만 다음의 경우에는 예외로 합니다.

• 이용자가 동의한 경우
• 법령에 따른 요청이 있는 경우

제5조 (개인정보 처리 위탁)

서비스는 원활한 운영을 위해 일부 업무를 외부 업체에 위탁할 수 있으며, 위탁 시 관련 법령에 따라 안전하게 관리합니다.

제6조 (이용자의 권리)

이용자는 언제든지 다음의 권리를 행사할 수 있습니다.

• 개인정보 조회
• 수정
• 삭제
• 회원 탈퇴
• 처리 정지 요청

제7조 (쿠키 사용)

서비스는 보다 편리한 이용을 위해 쿠키를 사용할 수 있습니다.

이용자는 브라우저 설정을 통해 쿠키 저장을 거부할 수 있으나 일부 기능이 제한될 수 있습니다.

제8조 (개인정보 보호)

서비스는 개인정보 보호를 위해 다음과 같은 조치를 시행합니다.

• 개인정보 암호화
• 접근 권한 최소화
• 접근 기록 관리
• 보안 프로그램 운영
• 개인정보 취급자 교육

제9조 (개인정보 보호책임자)

개인정보 관련 문의는 아래 담당자에게 문의하실 수 있습니다.

• 담당부서 : 개인정보보호 담당
• 이메일 : kysloving@gmail.com

제10조 (방침 변경)

본 개인정보처리방침은 관련 법령 또는 서비스 정책에 따라 변경될 수 있으며, 변경 시 서비스 내 공지사항을 통해 안내합니다.
TEXT,
        ]);
    }

 public function memberinfo()
    {
        return view('public.policy', [
            'title' => '가입안내',
            'content' => <<<'TEXT'
개인정보/약관 · 2026-07-14 05:35:41

1. 안녕하세요.
우리 동네와 우리 공동주택 이웃들이 함께 소통하는 커뮤니티(아파인드)에 오신 것을 환영합니다.

2. 가입 대상
다음 공동주택 거주자라면 누구나 가입할 수 있습니다.

• 아파트
• 빌라
• 오피스텔
• 도시형생활주택
• 연립주택
• 기타 공동주택

3. 가입 절차
① 회원가입
• 이메일 또는 휴대전화번호를 이용하여 회원가입을 진행합니다.

② 공동주택 선택
• 거주 중인 공동주택을 검색하여 선택합니다.
(만일 단지명이 검색이 안된다면 도로명 주소로 검색 후 선택 바랍니다.)

③ 거주 인증
• 서비스에서 제공하는 인증 절차를 완료하면 해당 공동주택 커뮤니티를 이용할 수 있습니다.
※ 인증 방법은 서비스 정책에 따라 변경될 수 있습니다.

④ 커뮤니티 이용
(인증이 완료되면 다음 기능을 이용할 수 있습니다.)

• 자유게시판
• 생활정보 공유
• 민원/불편 신고
• 질문·답변
• 주민 투표
• 공지사항
• 사진 공유
• 댓글 및 공감

4. 이용 시 유의사항
• 서로를 존중하는 언어를 사용해 주세요.
• 개인정보 공개는 주의해 주세요.
• 허위 정보 및 광고성 게시물은 제한될 수 있습니다.
• 신고가 접수된 게시물은 운영정책에 따라 검토됩니다.

5. 이런 공간을 함께 만들어 주세요
우리 커뮤니티는 단순한 게시판이 아닌, 이웃 간의 소통과 신뢰를 바탕으로 더 나은 공동주택 문화를 만들어가는 공간입니다.
작은 인사 한마디, 생활 정보 하나가 우리 동네를 더욱 따뜻하게 만듭니다.
여러분의 첫 게시글을 기다립니다.
TEXT,
        ]);
    }

    private function mapPostCards(Collection $posts, $user, bool $hasPostLikesTable): Collection
    {
        return $posts->map(fn (Post $post) => $this->mapPostCard($post, $user, $hasPostLikesTable));
    }

    private function mapPostPaginator(LengthAwarePaginator $paginator, $user, bool $hasPostLikesTable): LengthAwarePaginator
    {
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Post $post) => $this->mapPostCard($post, $user, $hasPostLikesTable))
        );

        return $paginator;
    }

    private function mapPostCard(Post $post, $user, bool $hasPostLikesTable): array
    {
        $canRead = $this->canReadPost($user, $post);
        $authorName = $post->is_anonymous ? '익명' : trim((string) ($post->user?->name ?? '알 수 없음'));
        $authorInitial = mb_substr($authorName !== '' ? $authorName : 'U', 0, 1);
        $likeCount = (int) ($post->likes_count ?? 0);
        $pollPreview = $this->buildPollPreview($post);
        $likedByMe = $hasPostLikesTable && $user
            ? PostLike::query()->where('post_id', $post->id)->where('user_id', $user->id)->exists()
            : false;

        if ($canRead && $user) {
            $url = '/community/posts/'.$post->id;
        } elseif ($canRead) {
            $url = '/posts/'.$post->id;
        } else {
            $url = '/posts/'.$post->id;
        }

        return [
            'id' => (int) $post->id,
            'title' => $post->title,
            'created_at' => $post->created_at,
            'created_label' => $post->created_at?->diffForHumans() ?? '-',
            'display_date' => $this->formatDisplayDate($post->created_at),
            'author_name' => $authorName,
            'author_initial' => mb_strtoupper($authorInitial),
            'author_is_verified' => $post->user !== null && $this->permissionService->hasVerifiedRole($post->user),
            'board_name' => $post->board->name,
            'is_poll' => (bool) ($pollPreview['is_poll'] ?? false),
            'poll_question' => (string) ($pollPreview['question'] ?? ''),
            'poll_options_preview' => (array) ($pollPreview['options'] ?? []),
            'poll_total_votes' => (int) ($pollPreview['total_votes'] ?? 0),
            'audience_scope' => (string) ($post->audience_scope ?? 'all'),
            'apartment_name' => $post->apartment->name,
            'body_preview' => $this->buildBodyPreview($post->body),
            'media_items' => $canRead ? $this->extractMediaItems($post) : [],
            'region_label' => $this->regionLabelFromSido($post->apartment->sido),
            'brand_token' => $this->brandTokenFromApartmentName($post->apartment->name),
            'view_count' => (int) $post->view_count,
            'comment_count' => (int) $post->comment_count,
            'like_count' => $likeCount,
            'liked_by_me' => $likedByMe,
            'is_notice' => (bool) $post->is_notice,
            'is_guest_visible' => (bool) $post->is_guest_visible,
            'can_read' => $canRead,
            'access_label' => $this->permissionService->resolvePostAccessLabel($user, $post),
            'url' => $url,
        ];
    }

    private function buildBodyPreview(?string $html): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $html)) ?: '');

        return mb_strimwidth($text, 0, 220, '...');
    }

    private function extractMediaItems(Post $post): array
    {
        $items = [];
        $seenUrls = [];

        foreach ($post->files as $file) {
            $mediaType = $this->resolveMediaType($file);
            if (! $mediaType) {
                continue;
            }

            $url = '/community/files/'.$file->id;
            if (isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;

            $items[] = [
                'type' => $mediaType,
                'url' => $url,
                'name' => (string) ($file->original_name ?? 'media'),
            ];
        }

        foreach ($this->extractEmbeddedBodyMedia((string) $post->body) as $embeddedMedia) {
            $url = (string) ($embeddedMedia['url'] ?? '');
            if ($url === '' || isset($seenUrls[$url])) {
                continue;
            }

            $seenUrls[$url] = true;
            $items[] = $embeddedMedia;
        }

        return array_slice($items, 0, 8);
    }

    private function buildPollPreview(Post $post): array
    {
        if ((string) ($post->board?->board_type ?? '') !== 'poll' || ! $post->poll) {
            return [
                'is_poll' => false,
                'question' => '',
                'options' => [],
                'total_votes' => 0,
            ];
        }

        $options = $post->poll->options
            ->sortBy('sort_order')
            ->take(3)
            ->pluck('label')
            ->map(fn ($label) => trim((string) $label))
            ->filter()
            ->values()
            ->all();

        return [
            'is_poll' => true,
            'question' => trim((string) ($post->poll->question ?? '')),
            'options' => $options,
            'total_votes' => (int) $post->poll->options->sum('vote_count'),
        ];
    }

    private function extractEmbeddedBodyMedia(string $html): array
    {
        $items = [];
        $patterns = [
            ['regex' => '/<img[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'image'],
            ['regex' => '/<video[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'video'],
            ['regex' => '/<source[^>]+src=["\']([^"\']+)["\']/i', 'type' => 'video'],
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern['regex'], $html, $matches)) {
                continue;
            }

            foreach (($matches[1] ?? []) as $src) {
                $url = trim((string) $src);
                if ($url === '') {
                    continue;
                }

                $items[] = [
                    'type' => $pattern['type'],
                    'url' => $url,
                    'name' => 'embedded-media',
                ];
            }
        }

        return $items;
    }

    private function resolveMediaType(PostFile $file): ?string
    {
        $mime = Str::lower(trim((string) ($file->mime_type ?? '')));

        if (Str::startsWith($mime, 'image/')) {
            return 'image';
        }

        if (Str::startsWith($mime, 'video/')) {
            return 'video';
        }

        $sourceName = Str::lower((string) ($file->original_name ?: $file->path ?: ''));
        $extension = pathinfo($sourceName, PATHINFO_EXTENSION);

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if (in_array($extension, ['mp4', 'mov', 'webm', 'm4v'], true)) {
            return 'video';
        }

        return null;
    }
    private function applyNoticeFilter($query): void
    {
        $query->where('is_notice', true)
            ->orWhereHas('board', function ($boardQuery) {
                $boardQuery->where('board_type', 'notice');
            });
    }

    private function applyNonNoticeFilter($query): void
    {
        $query->where('is_notice', false)
            ->whereDoesntHave('board', function ($boardQuery) {
                $boardQuery->where('board_type', 'notice');
            });
    }

    private function canReadPost($user, Post $post): bool
    {
        return $this->permissionService->canReadPostDetail($user, $post);
    }

    private function regionLabelFromSido(?string $sido): string
    {
        $value = trim((string) $sido);

        if ($value === '') {
            return '-';
        }

        if (str_starts_with($value, '서울')) {
            return '서울';
        }

        return preg_replace('/\s+/', '', $value) ?: $value;
    }

    private function brandTokenFromApartmentName(?string $name): string
    {
        $value = trim((string) $name);

        if ($value === '') {
            return 'APT';
        }

        $knownBrands = [
            '자이' => 'XI',
            '래미안' => 'RAE',
            '푸르지오' => 'PRU',
            '더샵' => 'TS',
            '힐스테이트' => 'HS',
            '아이파크' => 'IP',
            '롯데캐슬' => 'LC',
            'e편한세상' => 'ECS',
            'SK뷰' => 'SKV',
            '위브' => 'WB',
            '호반베르디움' => 'HB',
        ];

        foreach ($knownBrands as $keyword => $token) {
            if (str_contains($value, $keyword)) {
                return $token;
            }
        }

        $normalized = preg_replace('/\s+/', '', $value) ?: $value;

        return mb_substr($normalized, 0, 1);
    }

    private function formatDisplayDate($createdAt): string
    {
        if (! $createdAt) {
            return '-';
        }

        $date = $createdAt->copy();

        if ($date->isSameDay(now())) {
            return $date->format('H:i');
        }

        return $date->format('m/d');
    }
}
