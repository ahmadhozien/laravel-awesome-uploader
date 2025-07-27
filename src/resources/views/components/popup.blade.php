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
    'multiple' => true,
    'saveToDb' => true,
])
<div id="uploader-modal"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-black/70 via-black/60 to-black/70 backdrop-blur-sm transition-opacity duration-300"
    style="display:none;" tabindex="-1">
    <div id="uploader-modal-content" tabindex="-1"
        class="relative w-full max-w-7xl mx-4 bg-white/95 backdrop-blur-md rounded-3xl shadow-2xl transition-all duration-300 scale-95 opacity-0 focus:outline-none min-h-[700px] max-h-[90vh] flex flex-col border border-white/20">
        <!-- Header -->
        <div class="flex items-center justify-between px-8 pt-8 pb-6 border-b border-gray-100">
            <div class="flex items-center space-x-1 rtl:space-x-reverse">
                <nav class="flex space-x-1 rtl:space-x-reverse bg-gray-50 p-1 rounded-xl" aria-label="Tabs">
                    <button data-tab="manager"
                        class="uploader-tab px-6 py-3 rounded-lg font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-200 border border-transparent hover:bg-white hover:shadow-sm active:bg-white active:shadow-sm"
                        style="background: none;">{{ $tabFileManager }}</button>
                    <button data-tab="upload"
                        class="uploader-tab px-6 py-3 rounded-lg font-semibold text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 transition-all duration-200 border border-transparent hover:bg-white hover:shadow-sm active:bg-white active:shadow-sm"
                        style="background: none;">{{ $tabUpload }}</button>
                </nav>
            </div>
            <button id="uploader-close-btn" aria-label="{{ __('Close') }}"
                class="text-2xl text-gray-400 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-400 rounded-full p-2 transition-all duration-200 hover:bg-gray-100">&times;</button>
        </div>
        <!-- Body -->
        <div class="p-8 space-y-8 flex-1 overflow-y-auto">
            <!-- File Manager Tab -->
            <div id="uploader-tab-manager" class="uploader-tab-content">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 mb-8">
                    <div class="relative flex-1 max-w-md">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 focus:outline-none bg-gray-50/50 text-gray-700 placeholder-gray-400 transition-all duration-200"
                            placeholder="{{ $searchPlaceholder }}">
                    </div>
                    <div class="flex items-center gap-4">
                        <label
                            class="flex items-center gap-2 text-gray-600 cursor-pointer hover:text-gray-800 transition-colors duration-200">
                            <input type="checkbox"
                                class="form-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                                id="uploader-selected-only">
                            <span class="text-sm font-medium">{{ $selectedOnlyLabel }}</span>
                        </label>
                        <select
                            class="rounded-xl border border-gray-200 px-4 py-3 bg-gray-50/50 text-gray-700 focus:ring-2 focus:ring-blue-400 focus:border-blue-400 focus:outline-none transition-all duration-200">
                            @foreach ($sortOptions as $option)
                                <option>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div id="uploader-file-grid"
                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 mb-8 relative">
                    <!-- Loader overlay -->
                    <div id="uploader-file-grid-loader"
                        class="absolute inset-0 flex items-center justify-center bg-white/90 backdrop-blur-sm z-40 rounded-2xl">
                        <div class="text-center">
                            <svg class="animate-spin h-10 w-10 text-blue-600 mx-auto mb-4"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z">
                                </path>
                            </svg>
                            <p class="text-gray-600 font-medium">Loading files...</p>
                        </div>
                    </div>
                    <!-- JS will render file cards here -->
                </div>
            </div>
            <!-- Upload Tab -->
            <div id="uploader-tab-upload" class="uploader-tab-content hidden">
                <!-- Error Alert Area -->
                <div id="uploader-error-alert"
                    class="hidden mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between shadow-sm"
                    role="alert">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span id="uploader-error-message" class="font-medium"></span>
                    </div>
                    <button id="uploader-error-dismiss"
                        class="text-red-500 hover:text-red-700 focus:outline-none p-1 rounded-lg hover:bg-red-100 transition-colors duration-200"
                        aria-label="{{ __('Dismiss error') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="uploader-dropzone"
                    class="flex items-center justify-center w-full px-8 py-16 border-2 border-dashed border-gray-300 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100/50 transition-all duration-300 cursor-pointer hover:border-blue-400 hover:bg-gradient-to-br hover:from-blue-50 hover:to-blue-100/30 group">
                    <div class="text-center space-y-6">
                        <div class="flex justify-center">
                            <div
                                class="p-4 rounded-full bg-blue-100 group-hover:bg-blue-200 transition-colors duration-300">
                                <svg class="h-12 w-12 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                    </path>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <p
                                class="text-xl font-semibold text-gray-700 group-hover:text-gray-800 transition-colors duration-300">
                                {{ __('Drag & Drop files here') }}</p>
                            <p class="text-gray-500 group-hover:text-gray-600 transition-colors duration-300">
                                {{ __('or') }}</p>
                        </div>
                        <input type="file" @if ($multiple) multiple @endif id="uploader-file-input"
                            class="hidden">
                        <button id="uploader-select-btn"
                            class="px-8 py-3 text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95"
                            style="box-shadow: 0 4px 14px 0 rgba(59,130,246,0.25);">
                            {{ __('Select Files') }}
                        </button>
                    </div>
                </div>
                <div class="mt-10" id="uploader-files-section" style="display:none;">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6 flex items-center gap-3">
                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        {{ __('Selected Files') }}
                    </h3>
                    <ul class="space-y-3" id="uploader-files-list"></ul>
                    <div class="mt-8 text-left">
                        <button id="uploader-upload-btn"
                            class="inline-flex items-center px-8 py-3 text-white bg-gradient-to-r from-green-600 to-green-700 rounded-xl shadow-lg hover:shadow-xl hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-400 focus:ring-offset-2 font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 disabled:opacity-60 disabled:transform-none"
                            style="box-shadow: 0 4px 14px 0 rgba(16,185,129,0.25);" disabled>
                            <svg id="uploader-spinner" class="animate-spin h-5 w-5 mr-2 text-white hidden"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
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
            class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-8 py-6 border-t border-gray-100 bg-gradient-to-r from-gray-50 to-gray-100/50 rounded-b-3xl">
            <div class="text-gray-600 text-sm font-medium flex items-center gap-2">
                <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <span id="uploader-selected-count">0</span> {{ __('file(s) selected') }}
            </div>
            <div class="flex gap-3 flex-wrap">
                <button id="uploader-cancel-selection"
                    class="px-6 py-2.5 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition-all duration-200 border border-transparent hover:shadow-sm"
                    style="display:none;">{{ $cancelSelectionLabel }}</button>
                <button id="uploader-prev"
                    class="px-6 py-2.5 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 font-semibold transition-all duration-200 border border-transparent hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                    style="display:none;"
                    @if (!$hasPrev) disabled @endif>{{ $prevLabel }}</button>
                <button id="uploader-next"
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 font-semibold transition-all duration-200 border border-transparent hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    style="display:none;"
                    @if (!$hasNext) disabled @endif>{{ $nextLabel }}</button>
                <button id="uploader-add-files"
                    class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 font-semibold transition-all duration-200 border border-transparent hover:shadow-lg transform hover:scale-105 active:scale-95">{{ $addFilesLabel }}</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.LaravelUploader = {!! json_encode([
        'uploadUrl' => route('uploader.upload'),
        'csrfToken' => csrf_token(),
        'multiple' => $multiple,
        'saveToDb' => $saveToDb,
        'labels' => [
            'tabFileManager' => $tabFileManager,
            'tabUpload' => $tabUpload,
            'searchPlaceholder' => $searchPlaceholder,
            'sortOptions' => $sortOptions,
            'selectedOnlyLabel' => $selectedOnlyLabel,
            'addFilesLabel' => $addFilesLabel,
            'cancelSelectionLabel' => $cancelSelectionLabel,
            'nextLabel' => $nextLabel,
            'prevLabel' => $prevLabel,
            'upload' => __('Upload'),
            'uploading' => __('Uploading...'),
            'dragDrop' => __('Drag & Drop files here'),
            'or' => __('or'),
            'selectFiles' => __('Select Files'),
            'selectedFiles' => __('Selected Files'),
            'fileSelected' => __('file(s) selected'),
            'close' => __('Close'),
        ],
        'hasNext' => $hasNext,
        'hasPrev' => $hasPrev,
        'initialTab' => $initialTab,
        'enableLogging' => config('uploader.enable_logging', true),
    ]) !!};
</script>
<script src="{{ asset('vendor/uploader/popup.js') }}"></script>
