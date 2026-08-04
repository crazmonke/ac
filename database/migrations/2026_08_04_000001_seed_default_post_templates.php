<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 기본 제공 설문형 게시글 템플릿 8종 등록 (데이터 마이그레이션).
 * 같은 이름의 템플릿이 이미 있으면 건너뛴다.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->templates() as $index => $template) {
            $exists = DB::table('post_templates')
                ->where('name', $template['name'])
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('post_templates')->insert([
                'name' => $template['name'],
                'description' => $template['description'],
                'title_template' => $template['title_template'],
                'questions' => json_encode($template['questions'], JSON_UNESCAPED_UNICODE),
                'board_slugs' => json_encode(['free'], JSON_UNESCAPED_UNICODE),
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('post_templates')
            ->whereIn('name', array_column($this->templates(), 'name'))
            ->delete();
    }

    private function templates(): array
    {
        return [
            [
                'name' => '분리수거·음식물쓰레기 배출 안내',
                'description' => '우리 단지 쓰레기 배출 요일과 방법을 이웃에게 알려주세요.',
                'title_template' => '우리 단지 분리수거는 {q1}!',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '분리수거(재활용품) 배출 요일은 언제인가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '우리 단지 분리수거 요일은 {answer}이에요.',
                        'options' => [
                            ['label' => '월요일', 'sentence' => ''],
                            ['label' => '화요일', 'sentence' => ''],
                            ['label' => '수요일', 'sentence' => ''],
                            ['label' => '목요일', 'sentence' => ''],
                            ['label' => '금요일', 'sentence' => ''],
                            ['label' => '토요일', 'sentence' => ''],
                            ['label' => '일요일', 'sentence' => ''],
                            ['label' => '매일 가능', 'sentence' => '요일에 상관없이 버릴 수 있어서 편해요.'],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '음식물쓰레기는 언제 버릴 수 있나요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '음식물쓰레기는 {answer} 배출할 수 있어요.',
                        'options' => [
                            ['label' => '매일', 'sentence' => ''],
                            ['label' => '지정 요일에만', 'sentence' => '요일을 꼭 확인하고 내놓아 주세요.'],
                            ['label' => '지정 시간에만', 'sentence' => '시간을 지키지 않으면 수거되지 않을 수 있어요.'],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '배출 장소는 어디인가요? (예: 각 동 지하 1층, 정문 옆 분리수거장)',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '배출 장소는 {answer}입니다.',
                        'options' => [],
                        'max_length' => 200,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '이웃이 알아두면 좋은 주의사항이 있다면 알려주세요.',
                        'type' => 'text',
                        'required' => false,
                        'output_format' => '한 가지 더! {answer}',
                        'options' => [],
                        'max_length' => 300,
                    ],
                ],
            ],
            [
                'name' => '관리비 납부 안내',
                'description' => '관리비 납부 기한과 납부 방법을 공유해 주세요.',
                'title_template' => '관리비 납부 안내 ({q1}까지)',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '관리비 납부 마감일은 언제인가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '우리 단지 관리비는 {answer}까지 납부해야 해요.',
                        'options' => [
                            ['label' => '매월 5일', 'sentence' => ''],
                            ['label' => '매월 10일', 'sentence' => ''],
                            ['label' => '매월 15일', 'sentence' => ''],
                            ['label' => '매월 20일', 'sentence' => ''],
                            ['label' => '매월 25일', 'sentence' => ''],
                            ['label' => '매월 말일', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '납부 방법은 무엇이 있나요? (모두 선택)',
                        'type' => 'multiple',
                        'required' => true,
                        'output_format' => '납부는 {answer}(으)로 할 수 있어요.',
                        'options' => [
                            ['label' => '자동이체', 'sentence' => '자동이체를 걸어두면 잊어버릴 걱정이 없어요.'],
                            ['label' => '지로 납부', 'sentence' => ''],
                            ['label' => '앱/인터넷 납부', 'sentence' => ''],
                            ['label' => '관리사무소 방문', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '기한을 넘기면 연체료가 있나요?',
                        'type' => 'yes_no',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '예', 'sentence' => '기한을 넘기면 연체료가 붙으니 날짜를 꼭 지켜주세요!'],
                            ['label' => '아니오', 'sentence' => '연체료는 따로 없지만 제때 내는 게 좋겠죠?'],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '이웃에게 덧붙이고 싶은 말이 있다면?',
                        'type' => 'text',
                        'required' => false,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 300,
                    ],
                ],
            ],
            [
                'name' => '우리 단지 주차 룰',
                'description' => '주차 규칙과 방문차량 등록 방법을 정리해 주세요.',
                'title_template' => '우리 단지 주차 룰 총정리',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '세대당 몇 대까지 주차할 수 있나요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '우리 단지는 세대당 {answer} 주차할 수 있어요.',
                        'options' => [
                            ['label' => '1대', 'sentence' => ''],
                            ['label' => '2대', 'sentence' => ''],
                            ['label' => '2대 초과분은 유료', 'sentence' => '추가 차량은 관리사무소에서 등록하고 요금을 내야 해요.'],
                            ['label' => '제한 없음', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '방문 차량은 사전 등록이 필요한가요?',
                        'type' => 'yes_no',
                        'required' => true,
                        'output_format' => '',
                        'options' => [
                            ['label' => '예', 'sentence' => '방문 차량은 미리 등록해야 하니 손님이 오시기 전에 꼭 챙겨주세요.'],
                            ['label' => '아니오', 'sentence' => '방문 차량은 따로 등록하지 않아도 돼요.'],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '우리 단지에서 지켜야 할 주차 예절을 골라주세요. (모두 선택)',
                        'type' => 'multiple',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '이중주차 금지', 'sentence' => '이중주차는 금지되어 있어요.'],
                            ['label' => '지정 구역 준수', 'sentence' => '지정된 구역에만 주차해 주세요.'],
                            ['label' => '전기차 충전구역 비워두기', 'sentence' => '전기차 충전구역은 충전 차량을 위해 꼭 비워둬야 해요.'],
                            ['label' => '이중주차 시 연락처 남기기', 'sentence' => '부득이하게 이중주차를 했다면 연락처를 꼭 남겨주세요.'],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '주차 꿀팁이 있다면 알려주세요.',
                        'type' => 'text',
                        'required' => false,
                        'output_format' => '꿀팁 하나! {answer}',
                        'options' => [],
                        'max_length' => 300,
                    ],
                ],
            ],
            [
                'name' => '우리 동네/단지 불편사항',
                'description' => '겪고 있는 불편을 차분하게 정리해서 이야기해 보세요.',
                'title_template' => '우리 단지 불편사항: {q1}',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '어떤 점이 불편한가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '요즘 {answer} 문제로 불편을 겪고 있어요.',
                        'options' => [
                            ['label' => '주차', 'sentence' => ''],
                            ['label' => '층간소음', 'sentence' => ''],
                            ['label' => '쓰레기 무단투기', 'sentence' => ''],
                            ['label' => '시설 관리', 'sentence' => ''],
                            ['label' => '흡연', 'sentence' => ''],
                            ['label' => '기타', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '얼마나 자주 겪고 있나요?',
                        'type' => 'single',
                        'required' => false,
                        'output_format' => '빈도는 {answer} 정도예요.',
                        'options' => [
                            ['label' => '거의 매일', 'sentence' => '매일 겪다 보니 스트레스가 쌓이네요.'],
                            ['label' => '일주일에 몇 번', 'sentence' => ''],
                            ['label' => '가끔', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '구체적으로 어떤 상황인지 알려주세요.',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 500,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '어떻게 개선되면 좋을까요?',
                        'type' => 'text',
                        'required' => false,
                        'output_format' => '이렇게 개선되면 좋겠어요. {answer}',
                        'options' => [],
                        'max_length' => 300,
                    ],
                ],
            ],
            [
                'name' => '우리 동네/단지 자랑거리',
                'description' => '우리 동네의 좋은 점을 이웃과 나눠보세요.',
                'title_template' => '우리 동네 자랑: {q1}',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '어떤 점을 자랑하고 싶나요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '우리 동네 자랑거리는 바로 {answer}예요.',
                        'options' => [
                            ['label' => '조경/산책로', 'sentence' => ''],
                            ['label' => '커뮤니티 시설', 'sentence' => ''],
                            ['label' => '이웃 인심', 'sentence' => ''],
                            ['label' => '교통', 'sentence' => ''],
                            ['label' => '학군/교육', 'sentence' => ''],
                            ['label' => '관리 상태', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '어떤 점이 좋은지 자세히 들려주세요.',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 500,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '이사를 고민하는 이웃에게 추천하시겠어요?',
                        'type' => 'yes_no',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '예', 'sentence' => '이사를 고민 중이라면 저는 자신 있게 추천하고 싶어요!'],
                            ['label' => '아니오', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                ],
            ],
            [
                'name' => '동네 맛집·새 점포 소개',
                'description' => '맛집이나 새로 생긴 가게를 이웃에게 소개해 주세요.',
                'title_template' => '동네 맛집 소개: {q1}',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '소개할 가게 이름은 무엇인가요?',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => "오늘 소개할 곳은 '{answer}'입니다.",
                        'options' => [],
                        'max_length' => 100,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '어떤 종류의 가게인가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '{answer} 가게예요.',
                        'options' => [
                            ['label' => '한식', 'sentence' => ''],
                            ['label' => '중식', 'sentence' => ''],
                            ['label' => '일식', 'sentence' => ''],
                            ['label' => '양식', 'sentence' => ''],
                            ['label' => '카페/디저트', 'sentence' => ''],
                            ['label' => '기타 (음식점 외 점포)', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '위치는 어디인가요? (예: 정문 앞 상가 1층)',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '위치는 {answer}에 있어요.',
                        'options' => [],
                        'max_length' => 200,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '추천 메뉴나 이용 후기를 들려주세요.',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 500,
                    ],
                    [
                        'key' => 'q5',
                        'label' => '한 줄 평을 골라주세요.',
                        'type' => 'single',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '강력 추천!', 'sentence' => '한마디로, 강력 추천합니다!'],
                            ['label' => '재방문 의사 있음', 'sentence' => '조만간 또 갈 생각이에요.'],
                            ['label' => '한 번쯤 가볼 만함', 'sentence' => '궁금하시다면 한 번쯤 가보셔도 좋을 것 같아요.'],
                        ],
                        'max_length' => null,
                    ],
                ],
            ],
            [
                'name' => '고민/넋두리',
                'description' => '혼자 담아두지 말고 이웃에게 털어놓아 보세요.',
                'title_template' => '고민 있어요: {q1}',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '어떤 고민인가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '요즘 {answer} 때문에 마음이 복잡해요.',
                        'options' => [
                            ['label' => '육아', 'sentence' => ''],
                            ['label' => '이웃 관계', 'sentence' => ''],
                            ['label' => '집/살림', 'sentence' => ''],
                            ['label' => '직장/일', 'sentence' => ''],
                            ['label' => '그냥 넋두리', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '어떤 이야기인지 편하게 적어주세요.',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 800,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '이웃에게 어떤 반응을 바라시나요?',
                        'type' => 'single',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '조언을 듣고 싶어요', 'sentence' => '따끔한 조언도 좋으니 의견 부탁드려요.'],
                            ['label' => '공감만 해주세요', 'sentence' => '해결책보다는 따뜻한 공감이 필요해요.'],
                            ['label' => '비슷한 경험이 궁금해요', 'sentence' => '비슷한 경험이 있으시다면 어떻게 하셨는지 들려주세요.'],
                        ],
                        'max_length' => null,
                    ],
                ],
            ],
            [
                'name' => '이슈/제보',
                'description' => '동네에서 일어난 일을 이웃에게 빠르게 알려주세요.',
                'title_template' => '[제보] {q1}',
                'questions' => [
                    [
                        'key' => 'q1',
                        'label' => '어떤 종류의 소식인가요?',
                        'type' => 'single',
                        'required' => true,
                        'output_format' => '우리 동네 {answer} 관련 소식을 전해요.',
                        'options' => [
                            ['label' => '공사/소음', 'sentence' => ''],
                            ['label' => '정전/단수', 'sentence' => ''],
                            ['label' => '사고/안전', 'sentence' => ''],
                            ['label' => '분실/습득', 'sentence' => ''],
                            ['label' => '교통 통제', 'sentence' => ''],
                            ['label' => '기타', 'sentence' => ''],
                        ],
                        'max_length' => null,
                    ],
                    [
                        'key' => 'q2',
                        'label' => '언제, 어디에서 있었던 일인가요?',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '장소와 시간은 {answer}입니다.',
                        'options' => [],
                        'max_length' => 200,
                    ],
                    [
                        'key' => 'q3',
                        'label' => '어떤 일이 있었는지 알려주세요.',
                        'type' => 'text',
                        'required' => true,
                        'output_format' => '{answer}',
                        'options' => [],
                        'max_length' => 500,
                    ],
                    [
                        'key' => 'q4',
                        'label' => '지금도 진행 중인 상황인가요?',
                        'type' => 'yes_no',
                        'required' => false,
                        'output_format' => '',
                        'options' => [
                            ['label' => '예', 'sentence' => '지금도 진행 중이니 근처를 지나신다면 참고하세요.'],
                            ['label' => '아니오', 'sentence' => '지금은 상황이 마무리되었습니다.'],
                        ],
                        'max_length' => null,
                    ],
                ],
            ],
        ];
    }
};
