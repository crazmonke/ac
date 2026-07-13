(function () {
    var UPLOAD_URL = '/admin/banners/upload-temp';
    var CSRF = '{{ $csrfToken }}';
    var uploadingCount = 0;

    function updateSubmitState() {
        var btn = document.getElementById('submit-btn');
        var notice = document.getElementById('uploading-notice');
        if (btn) btn.disabled = uploadingCount > 0;
        if (notice) notice.style.display = uploadingCount > 0 ? 'inline' : 'none';
    }

    function uploadFile(file, hiddenId, progressWrapId, progressBarId, progressTextId, previewId, labelId, areaId) {
        var hidden   = document.getElementById(hiddenId);
        var wrap     = document.getElementById(progressWrapId);
        var bar      = document.getElementById(progressBarId);
        var text     = document.getElementById(progressTextId);
        var preview  = previewId  ? document.getElementById(previewId)  : null;
        var label    = labelId    ? document.getElementById(labelId)    : null;
        var area     = areaId     ? document.getElementById(areaId)     : null;

        uploadingCount++;
        updateSubmitState();

        if (wrap) wrap.style.display = 'block';
        if (bar)  bar.style.width = '0%';
        if (text) text.textContent = '업로드 중…';
        if (area) area.style.borderColor = '#0f7a72';

        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', CSRF);

        var xhr = new XMLHttpRequest();

        xhr.upload.onprogress = function (e) {
            if (!e.lengthComputable) return;
            var pct = Math.round(e.loaded / e.total * 100);
            if (bar)  bar.style.width = pct + '%';
            if (text) text.textContent = '업로드 중… ' + pct + '%';
        };

        xhr.onload = function () {
            uploadingCount--;
            updateSubmitState();

            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (hidden) hidden.value = data.path;
                    if (bar)  bar.style.width = '100%';
                    if (text) { text.textContent = '업로드 완료 ✓'; text.style.color = '#166b53'; }
                    if (area) area.style.borderColor = '#166b53';

                    if (preview && data.url) {
                        preview.src = data.url;
                        preview.style.display = 'block';
                        if (label) label.style.display = 'none';
                    } else if (label) {
                        label.textContent = file.name + ' 업로드 완료';
                    }
                } catch (e) {
                    showUploadError(text, bar, area, '응답 파싱 오류');
                }
            } else {
                var msg = '업로드 실패 (HTTP ' + xhr.status + ')';
                try {
                    var errData = JSON.parse(xhr.responseText);
                    if (errData.message) msg = errData.message;
                    if (errData.errors && errData.errors.file) msg = errData.errors.file[0];
                } catch(e2) {}
                showUploadError(text, bar, area, msg);
            }
        };

        xhr.onerror = function () {
            uploadingCount--;
            updateSubmitState();
            showUploadError(text, bar, area, '네트워크 오류. 다시 시도해 주세요.');
        };

        xhr.open('POST', UPLOAD_URL);
        xhr.send(fd);
    }

    function showUploadError(text, bar, area, msg) {
        if (bar)  { bar.style.width = '100%'; bar.style.background = '#b42318'; }
        if (text) { text.textContent = msg; text.style.color = '#b42318'; }
        if (area) area.style.borderColor = '#b42318';
    }

    function bindDrop(areaId, filePickId, hiddenId, progressWrapId, progressBarId, progressTextId, previewId, labelId) {
        var area     = document.getElementById(areaId);
        var filePick = document.getElementById(filePickId);
        if (!area || !filePick) return;

        area.addEventListener('dragover', function (e) {
            e.preventDefault();
            area.style.borderColor = '#0f7a72';
        });
        area.addEventListener('dragleave', function () {
            area.style.borderColor = '#d6e0ea';
        });
        area.addEventListener('drop', function (e) {
            e.preventDefault();
            area.style.borderColor = '#d6e0ea';
            var files = e.dataTransfer.files;
            if (files.length > 0) {
                uploadFile(files[0], hiddenId, progressWrapId, progressBarId, progressTextId, previewId, labelId, areaId);
            }
        });

        filePick.addEventListener('change', function () {
            if (this.files.length > 0) {
                uploadFile(this.files[0], hiddenId, progressWrapId, progressBarId, progressTextId, previewId, labelId, areaId);
                this.value = '';
            }
        });
    }

    bindDrop('image-upload-area', 'image_file_pick', 'image_path',
             'image-progress-wrap', 'image-progress-bar', 'image-progress-text',
             'image-preview', 'image-upload-label');

    bindDrop('video-upload-area', 'video_file_pick', 'video_path',
             'video-progress-wrap', 'video-progress-bar', 'video-progress-text',
             null, 'video-upload-label');

    // 폼 제출 전 업로드 중 여부 확인
    var form = document.querySelector('form[method="post"]');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (uploadingCount > 0) {
                e.preventDefault();
                alert('파일 업로드가 진행 중입니다. 잠시 후 다시 시도해 주세요.');
            }
        });
    }
})();

function updateFieldVisibility() {
    var type = document.querySelector('select[name="type"]').value;
    document.getElementById('image-field').style.display = type === 'image' ? 'block' : 'none';
    document.getElementById('video-field').style.display = type === 'video' ? 'block' : 'none';
    document.getElementById('text-field').style.display  = type === 'text'  ? 'block' : 'none';
}
