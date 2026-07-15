<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>비밀번호 변경 안내</title>
</head>
<body style="margin:0; padding:0; background:#f4f8fb; font-family:'Helvetica Neue',Arial,sans-serif; color:#1b2d45;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f8fb; padding:32px 16px;">
    <tr>
        <td align="center">
            <table width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#fff; border-radius:14px; border:1px solid #d6e0ea; padding:28px;">
                <tr>
                    <td>
                        <h2 style="margin:0 0 16px; font-size:1.3rem; color:#1b2d45;">비밀번호 변경 안내</h2>
                        <p style="margin:0 0 12px; color:#374151; line-height:1.6;">
                            비밀번호 변경 요청이 접수되었습니다.<br>
                            아래 버튼을 클릭하면 비밀번호 변경 페이지로 이동합니다.
                        </p>
                        <p style="margin:0 0 20px; color:#b45309; font-size:0.9rem;">
                            ⚠️ 이 링크는 발급 후 <strong>24시간 내에만</strong> 유효합니다.<br>
                            보안을 위해 변경 시 <strong>공동주택 반경 3km 이내의 위치</strong>에서만 변경이 가능합니다.
                        </p>
                        <p style="margin:0 0 20px;">
                            <a href="{{ url('/reset-password/' . $token . '?email=' . urlencode($email)) }}"
                               style="display:inline-block; background:#0b7a75; color:#fff; padding:13px 28px; border-radius:8px; text-decoration:none; font-weight:700; font-size:1rem;">
                                비밀번호 변경하기
                            </a>
                        </p>
                        <p style="margin:0 0 16px; color:#6b7280; font-size:0.88rem; line-height:1.6;">
                            이 요청을 하지 않으셨다면 이 이메일을 무시하셔도 됩니다.<br>
                            비밀번호는 변경하지 않는 한 그대로 유지됩니다.
                        </p>
                        <hr style="border:none; border-top:1px solid #e5e7eb; margin:16px 0;">
                        <p style="margin:0; color:#9ca3af; font-size:0.82rem; word-break:break-all;">
                            버튼이 작동하지 않으면 아래 링크를 복사하여 브라우저에 붙여넣기 해주세요:<br>
                            {{ url('/reset-password/' . $token . '?email=' . urlencode($email)) }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
