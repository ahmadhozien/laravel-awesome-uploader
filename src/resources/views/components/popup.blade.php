@props([
    // Tab labels
    'tabFileManager' => __('File Manager'),
    'tabUpload' => __('Upload New File'),
    // Search/filter
    'searchPlaceholder' => __('Search files'),
    'sortOptions' => [__('Sort by newest'), __('Sort by oldest')],
    'selectedOnlyLabel' => __('Selected only'),
    // Footer buttons
    'addFilesLabel' => __('Add Files'),
    'cancelSelectionLabel' => __('Cancel Selection'),
    'nextLabel' => __('Next'),
    'prevLabel' => __('Previous'),
    // File manager data (array of files)
    'files' => [],
    // Pagination
    'hasNext' => false,
    'hasPrev' => false,
    // Initial tab
    'initialTab' => 'manager',
])
<div id="uploader-modal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 transition-opacity duration-300"
    style="display:none;" tabindex="-1">
    <div id="uploader-modal-content" tabindex="-1"
        class="relative w-full max-w-4xl mx-4 bg-white rounded-2xl shadow-2xl transition-transform duration-300 scale-95 opacity-0 focus:outline-none min-h-[500px] max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 pt-6 pb-4 border-b">
            <button id="uploader-close-btn" aria-label="{{ __('Close') }}"
                class="text-2xl text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 rounded-full px-2 transition">&times;</button>
            <div class="flex-1 flex justify-center">
                <nav class="flex space-x-2" aria-label="Tabs">
                    <button data-tab="manager"
                        class="uploader-tab px-4 py-2 rounded-t-lg font-medium text-gray-700 bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">{{ $tabFileManager }}</button>
                    <button data-tab="upload"
                        class="uploader-tab px-4 py-2 rounded-t-lg font-medium text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">{{ $tabUpload }}</button>
                </nav>
            </div>
        </div>
        <!-- Body -->
        <div class="p-6 space-y-6 flex-1 overflow-y-auto">
            <!-- File Manager Tab -->
            <div id="uploader-tab-manager" class="uploader-tab-content">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <input type="text"
                        class="w-full md:w-1/3 rounded-lg border border-gray-200 px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-gray-50 text-gray-700 placeholder-gray-400"
                        placeholder="{{ $searchPlaceholder }}">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1 text-gray-600 cursor-pointer">
                            <input type="checkbox" class="form-checkbox rounded border-gray-300 text-blue-600"
                                id="uploader-selected-only">
                            {{ $selectedOnlyLabel }}
                        </label>
                        <select
                            class="rounded-lg border border-gray-200 px-3 py-2 bg-gray-50 text-gray-700 focus:ring-2 focus:ring-blue-400 focus:outline-none">
                            @foreach ($sortOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="uploader-file-grid"
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                    <!-- JS will render file cards here -->
                </div>
            </div>
            <!-- Upload Tab -->
            <div id="uploader-tab-upload" class="uploader-tab-content hidden">
                <div id="uploader-dropzone"
                    class="flex items-center justify-center w-full px-6 py-12 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 transition-colors duration-200 cursor-pointer">
                    <div class="text-center space-y-3">
                        <p class="text-lg text-gray-700">{{ __('Drag & Drop files here') }}</p>
                        <p class="text-gray-400">{{ __('or') }}</p>
                        <input type="file" multiple id="uploader-file-input" class="hidden">
                        <button id="uploader-select-btn"
                            class="px-5 py-2 text-white bg-blue-600 rounded-lg shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium transition">
                            {{ __('Select Files') }}
                        </button>
                    </div>
                </div>
                <div class="mt-8" id="uploader-files-section" style="display:none;">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">{{ __('Selected Files') }}</h3>
                    <ul class="space-y-2" id="uploader-files-list"></ul>
                    <div class="mt-6 text-left">
                        <button id="uploader-upload-btn"
                            class="inline-flex items-center px-6 py-2 text-white bg-green-600 rounded-lg shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400 font-medium transition disabled:opacity-60"
                            disabled>
                            <svg id="uploader-spinner" class="animate-spin h-5 w-5 mr-2 text-white hidden"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            <span id="uploader-upload-text">{{ __('Upload') }}</span>
                            <span id="uploader-uploading-text" class="ml-2 hidden">{{ __('Uploading...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer -->
        <div
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-6 py-4 border-t bg-gray-50 rounded-b-2xl">
            <div class="text-gray-600 text-sm">
                <span id="uploader-selected-count">0</span> {{ __('file(s) selected') }}
            </div>
            <div class="flex gap-2 flex-wrap">
                <button id="uploader-cancel-selection"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 font-medium transition">{{ $cancelSelectionLabel }}</button>
                <button id="uploader-prev"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300 font-medium transition"
                    @if (!$hasPrev) disabled @endif>{{ $prevLabel }}</button>
                <button id="uploader-next"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 font-medium transition"
                    @if (!$hasNext) disabled @endif>{{ $nextLabel }}</button>
                <button id="uploader-add-files"
                    class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 font-medium transition">{{ $addFilesLabel }}</button>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('vendor/uploader/popup.js') }}"></script>
