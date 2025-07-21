<template>
  <div class="w-full max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-lg">
    <div
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      :class="{ 'border-blue-500 bg-blue-50': isDragging }"
      class="flex items-center justify-center w-full px-6 py-12 border-2 border-dashed rounded-lg"
    >
      <div class="text-center">
        <p class="mb-2 text-lg">Drag & Drop files here</p>
        <p class="mb-4 text-gray-500">or</p>
        <input
          type="file"
          multiple
          @change="onFileChange"
          class="hidden"
          ref="fileInput"
        />
        <button
          @click="openFileInput"
          class="px-4 py-2 text-white bg-blue-500 rounded-lg hover:bg-blue-600"
        >
          Select Files
        </button>
      </div>
    </div>

    <div v-if="files.length > 0" class="mt-6">
      <h3 class="text-xl font-semibold">Selected Files</h3>
      <ul class="mt-4 space-y-2">
        <li
          v-for="(file, index) in files"
          :key="index"
          class="flex items-center justify-between p-2 border rounded-lg"
        >
          <span>{{ file.name }}</span>
          <button
            @click="removeFile(index)"
            class="text-red-500 hover:text-red-700"
          >
            &times;
          </button>
        </li>
      </ul>
      <div class="mt-6 text-right">
        <button
          @click="uploadFiles"
          :disabled="isUploading"
          class="px-6 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600"
        >
          {{ isUploading ? "Uploading..." : "Upload" }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const emit = defineEmits(["upload-success", "upload-error"]);

const files = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const fileInput = ref(null);

const onDragOver = () => {
  isDragging.value = true;
};

const onDragLeave = () => {
  isDragging.value = false;
};

const onDrop = (event) => {
  isDragging.value = false;
  addFiles(event.dataTransfer.files);
};

const onFileChange = (event) => {
  addFiles(event.target.files);
};

const addFiles = (newFiles) => {
  files.value = [...files.value, ...Array.from(newFiles)];
};

const removeFile = (index) => {
  files.value.splice(index, 1);
};

const openFileInput = () => {
  fileInput.value.click();
};

const uploadFiles = async () => {
  isUploading.value = true;
  const formData = new FormData();
  files.value.forEach((file) => formData.append("files[]", file));

  try {
    const response = await fetch("/api/upload", {
      method: "POST",
      body: formData,
      headers: {
        Accept: "application/json",
      },
    });

    const result = await response.json();

    if (response.ok) {
      emit("upload-success", result);
      files.value = [];
    } else {
      emit("upload-error", result);
    }
  } catch (error) {
    emit("upload-error", error);
  } finally {
    isUploading.value = false;
  }
};
</script>
