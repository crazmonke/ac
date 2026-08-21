{{-- 작성자 이름 클릭 → 쪽지보내기/차단하기 팝업 메뉴 (인증된 로그인 사용자만 활성화) --}}
<style>
    .msg-target { cursor: pointer; }
    .msg-target:hover { text-decoration: underline; text-underline-offset: 3px; }
    .msg-popup-menu {
        position: absolute;
        z-index: 60;
        background: #fff;
        border: 1px solid #d6e0ea;
        border-radius: 12px;
        box-shadow: 0 10px 24px rgba(20, 35, 60, 0.16);
        overflow: hidden;
        min-width: 150px;
    }
    .msg-popup-menu .msg-popup-name {
        padding: 9px 14px 7px;
        font-size: 0.78rem;
        color: #62728a;
        border-bottom: 1px solid #edf1f7;
    }
    .msg-popup-menu a {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        font-size: 0.9rem;
        font-weight: 700;
        color: #15243a;
        text-decoration: none;
    }
    .msg-popup-menu a:hover { background: #f2f7ff; }
    .msg-popup-menu .msg-block-form { margin: 0; border-top: 1px solid #edf1f7; }
    .msg-popup-menu .msg-block-button {
        display: block;
        width: 100%;
        padding: 10px 14px;
        border: 0;
        background: #fff;
        color: #b42318;
        font: inherit;
        font-size: 0.9rem;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
    }
    .msg-popup-menu .msg-block-button:hover { background: #fff1f0; }
</style>
<script>
(function () {
    var canUseMsgActions = {{ (auth()->check() && ($isVerifiedUser ?? false)) ? 'true' : 'false' }};
    var popup = null;

    function closePopup() {
        if (popup) {
            popup.remove();
            popup = null;
        }
    }

    function openPopup(target) {
        closePopup();

        var userId = target.getAttribute('data-msg-user-id');
        var userName = target.getAttribute('data-msg-user-name');
        var href = '/messages/compose?to=' + encodeURIComponent(userId);

        popup = document.createElement('div');
        popup.className = 'msg-popup-menu';

        var nameEl = document.createElement('div');
        nameEl.className = 'msg-popup-name';
        nameEl.textContent = userName;
        popup.appendChild(nameEl);

        var link = document.createElement('a');
        link.href = href;
        link.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#2e4fb8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4.5 7 7.5 6 7.5-6"/></svg><span>쪽지보내기</span>';
        popup.appendChild(link);

        var blockForm = document.createElement('form');
        blockForm.className = 'msg-block-form';
        blockForm.method = 'post';
        blockForm.action = '/users/' + encodeURIComponent(userId) + '/block';

        var csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        blockForm.appendChild(csrf);

        var blockButton = document.createElement('button');
        blockButton.type = 'submit';
        blockButton.className = 'msg-block-button';
        blockButton.textContent = '차단하기';
        blockButton.addEventListener('click', function (event) {
            if (!window.confirm(userName + '님을 차단할까요?')) {
                event.preventDefault();
            }
        });
        blockForm.appendChild(blockButton);
        popup.appendChild(blockForm);

        document.body.appendChild(popup);

        var rect = target.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 6;
        var left = rect.left + window.scrollX;
        var maxLeft = window.scrollX + document.documentElement.clientWidth - popup.offsetWidth - 8;
        popup.style.top = top + 'px';
        popup.style.left = Math.max(8, Math.min(left, maxLeft)) + 'px';
    }

    document.addEventListener('click', function (event) {
        var target = event.target.closest('.msg-target');

        if (target) {
            if (!canUseMsgActions) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            openPopup(target);
            return;
        }

        if (popup && !event.target.closest('.msg-popup-menu')) {
            closePopup();
        }
    }, true);

    window.addEventListener('scroll', closePopup, { passive: true });
})();
</script>
