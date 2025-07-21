<div id="uploader-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    style="display:none;">
    <div class="w-full max-w-3xl p-6 bg-white rounded-lg shadow-lg" id="uploader-modal-content">
        <div class="flex items-center justify-between pb-4 border-b">
            <h2 class="text-2xl font-semibold">Upload Files</h2>
            <button id="uploader-close-btn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <div class="mt-6">
            <div id="uploader-dropzone"
                class="flex items-center justify-center w-full px-6 py-12 border-2 border-dashed rounded-lg">
                <div class="text-center">
                    <p class="mb-2 text-lg">Drag & Drop files here</p>
                    <p class="mb-4 text-gray-500">or</p>
                    <input type="file" multiple id="uploader-file-input" class="hidden">
                    <button id="uploader-select-btn"
                        class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        Select Files
                    </button>
                </div>
            </div>

            <div class="mt-6" id="uploader-files-section" style="display:none;">
                <h3 class="text-xl font-semibold">Selected Files</h3>
                <ul class="mt-4 space-y-2" id="uploader-files-list"></ul>
                <div class="mt-6 text-right">
                    <button id="uploader-upload-btn"
                        class="px-6 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600">
                        <span id="uploader-upload-text">Upload</span>
                        <span id="uploader-uploading-text" style="display:none;">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // Modal logic
        const modal = document.getElementById('uploader-modal');
        const closeBtn = document.getElementById('uploader-close-btn');
        const modalContent = document.getElementById('uploader-modal-content');
        const dropzone = document.getElementById('uploader-dropzone');
        const fileInput = document.getElementById('uploader-file-input');
        const selectBtn = document.getElementById('uploader-select-btn');
        const filesSection = document.getElementById('uploader-files-section');
        const filesList = document.getElementById('uploader-files-list');
        const uploadBtn = document.getElementById('uploader-upload-btn');
        const uploadText = document.getElementById('uploader-upload-text');
        const uploadingText = document.getElementById('uploader-uploading-text');

        let files = [];
        let isUploading = false;

        // Open modal on custom event
        window.addEventListener('open-uploader', function() {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Close modal
        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
            resetUploader();
        }

        // File selection
        selectBtn.addEventListener('click', function() {
            fileInput.click();
        });
        fileInput.addEventListener('change', function(e) {
            addFiles(e.target.files);
        });

        // Drag & drop
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.classList.add('border-blue-500', 'bg-blue-50');
        });
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-blue-500', 'bg-blue-50');
        });
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.classList.remove('border-blue-500', 'bg-blue-50');
            addFiles(e.dataTransfer.files);
        });

        function addFiles(newFiles) {
            files = [...files, ...Array.from(newFiles)];
            renderFiles();
        }

        function renderFiles() {
            filesList.innerHTML = '';
            if (files.length > 0) {
                filesSection.style.display = '';
            } else {
                filesSection.style.display = 'none';
            }
            files.forEach((file, idx) => {
                const li = document.createElement('li');
                li.className = 'flex items-center justify-between p-2 border rounded-lg';
                li.innerHTML = `<span>${file.name}</span>`;
                const removeBtn = document.createElement('button');
                removeBtn.className = 'text-red-500 hover:text-red-700';
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', function() {
                    files.splice(idx, 1);
                    renderFiles();
                });
                li.appendChild(removeBtn);
                filesList.appendChild(li);
            });
        }

        // Upload
        uploadBtn.addEventListener('click', async function() {
            if (isUploading || files.length === 0) return;
            isUploading = true;
            uploadText.style.display = 'none';
            uploadingText.style.display = '';
            uploadBtn.disabled = true;
            const formData = new FormData();
            files.forEach(file => formData.append('files[]', file));
            try {
                const response = await fetch("{{ route('uploader.upload') }}", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json',
                    },
                });
                if (response.ok) {
                    const result = await response.json();
                    window.dispatchEvent(new CustomEvent('files-uploaded', {
                        detail: result
                    }));
                    files = [];
                    renderFiles();
                    closeModal();
                } else {
                    alert('Upload failed');
                }
            } catch (error) {
                alert('An error occurred: ' + error);
            } finally {
                isUploading = false;
                uploadText.style.display = '';
                uploadingText.style.display = 'none';
                uploadBtn.disabled = false;
            }
        });

        function resetUploader() {
            files = [];
            renderFiles();
            uploadText.style.display = '';
            uploadingText.style.display = 'none';
            uploadBtn.disabled = false;
            isUploading = false;
        }
    })();
</script>
