<?php

namespace App\Services;

class ResidenceNamingService
{
    public function buildDisplayName(
        ?string $officialName,
        ?string $aliasName,
        ?string $roadAddress,
        ?string $jibunAddress,
        string $housingType
    ): array {
        $officialName = trim((string) $officialName);
        $aliasName = trim((string) $aliasName);

        if ($officialName !== '') {
            return [
                'display_name' => $officialName,
                'source' => 'official',
            ];
        }

        if ($aliasName !== '') {
            return [
                'display_name' => $aliasName,
                'source' => 'alias',
            ];
        }

        $baseAddress = trim((string) ($jibunAddress ?: $roadAddress));
        $labelType = match ($housingType) {
            'officetel' => '오피스텔',
            'villa' => '빌라',
            'urban_living' => '도시형생활주택',
            'mixed' => '공동주택',
            default => '공동주택',
        };

        $generated = trim($baseAddress . ' ' . $labelType);

        return [
            'display_name' => $generated !== '' ? $generated : '이름없는 공동주택',
            'source' => 'generated',
        ];
    }

    public function normalize(string $text): string
    {
        $value = mb_strtolower(trim($text));
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }
}
