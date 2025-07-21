<div x-data="uploader()" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave" @drop.prevent="onDrop"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-show="isOpen"
    @open-uploader.window="isOpen = true" x-cloak>
    <div class="w-full max-w-3xl p-6 bg-white rounded-lg shadow-lg" @click.away="isOpen = false">
        <div class="flex items-center justify-between pb-4 border-b">
            <h2 class="text-2xl font-semibold">Upload Files</h2>
            <button @click="isOpen = false" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <div class="mt-6">
            <div :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                class="flex items-center justify-center w-full px-6 py-12 border-2 border-dashed rounded-lg">
                <div class="text-center">
                    <p class="mb-2 text-lg">Drag & Drop files here</p>
                    <p class="mb-4 text-gray-500">or</p>
                    <input type="file" multiple @change="onFileChange" class="hidden" x-ref="fileInput">
                    <button @click="$refs.fileInput.click()"
                        class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                        Select Files
                    </button>
                </div>
            </div>

            <div class="mt-6" x-show="files.length > 0">
                <h3 class="text-xl font-semibold">Selected Files</h3>
                <ul class="mt-4 space-y-2">
                    <template x-for="(file, index) in files" :key="index">
                        <li class="flex items-center justify-between p-2 border rounded-lg">
                            <span x-text="file.name"></span>
                            <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">&times;</button>
                        </li>
                    </template>
                </ul>
                <div class="mt-6 text-right">
                    <button @click="uploadFiles" class="px-6 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600"
                        :disabled="isUploading">
                        <span x-show="!isUploading">Upload</span>
                        <span x-show="isUploading">Uploading...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function uploader() {
        return {
            isOpen: false,
            isDragging: false,
            isUploading: false,
            files: [],
            onDragOver() {
                this.isDragging = true;
            },
            onDragLeave() {
                this.isDragging = false;
            },
            onDrop(event) {
                this.isDragging = false;
                this.addFiles(event.dataTransfer.files);
            },
            onFileChange(event) {
                this.addFiles(event.target.files);
            },
            addFiles(files) {
                this.files = [...this.files, ...Array.from(files)];
            },
            removeFile(index) {
                this.files.splice(index, 1);
            },
            async uploadFiles() {
                this.isUploading = true;
                const formData = new FormData();
                this.files.forEach(file => formData.append('files[]', file));

                try {
                    const response = await fetch('{{ route('uploader.upload') }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    });

                    if (response.ok) {
                        const result = await response.json();
                        // Handle successful upload, e.g., dispatch an event
                        window.dispatchEvent(new CustomEvent('files-uploaded', {
                            detail: result
                        }));
                        this.files = [];
                    } else {
                        console.error('Upload failed');
                    }
                } catch (error) {
                    console.error('An error occurred:', error);
                } finally {
                    this.isUploading = false;
                }
            },
        };
    }
</script>
