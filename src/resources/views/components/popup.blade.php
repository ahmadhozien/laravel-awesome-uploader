<div id="uploader-modal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 transition-opacity duration-300"
    style="display:none;">
    <div id="uploader-modal-content" tabindex="-1"
        class="relative w-full max-w-xl mx-4 bg-white rounded-2xl shadow-xl transition-transform duration-300 scale-95 opacity-0 focus:outline-none"
        style="outline: none;">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b">
            <h2 class="text-2xl font-semibold text-gray-900">Upload Files</h2>
            <button id="uploader-close-btn" aria-label="Close uploader"
                class="text-2xl text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 rounded-full px-2 transition">
                &times;
            </button>
        </div>
        <!-- Body -->
        <div class="p-6">
            <div id="uploader-dropzone"
                class="flex items-center justify-center w-full px-6 py-12 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 transition-colors duration-200 cursor-pointer">
                <div class="text-center space-y-3">
                    <p class="text-lg text-gray-700">Drag & Drop files here</p>
                    <p class="text-gray-400">or</p>
                    <input type="file" multiple id="uploader-file-input" class="hidden">
                    <button id="uploader-select-btn"
                        class="px-5 py-2 text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium transition">
                        Select Files
                    </button>
                </div>
            </div>
            <div class="mt-8" id="uploader-files-section" style="display:none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Selected Files</h3>
                <ul class="space-y-2" id="uploader-files-list"></ul>
                <div class="mt-6 text-right">
                    <button id="uploader-upload-btn"
                        class="inline-flex items-center px-6 py-2 text-white bg-green-600 rounded-lg shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400 font-medium transition disabled:opacity-60"
                        disabled>
                        <svg id="uploader-spinner" class="animate-spin h-5 w-5 mr-2 text-white hidden"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span id="uploader-upload-text">Upload</span>
                        <span id="uploader-uploading-text" class="ml-2 hidden">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/uploader/popup.js') }}"></script>
