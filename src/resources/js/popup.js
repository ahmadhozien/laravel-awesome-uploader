(function () {
  // Modal logic
  const modal = document.getElementById("uploader-modal");
  const closeBtn = document.getElementById("uploader-close-btn");
  const modalContent = document.getElementById("uploader-modal-content");
  const dropzone = document.getElementById("uploader-dropzone");
  const fileInput = document.getElementById("uploader-file-input");
  const selectBtn = document.getElementById("uploader-select-btn");
  const filesSection = document.getElementById("uploader-files-section");
  const filesList = document.getElementById("uploader-files-list");
  const uploadBtn = document.getElementById("uploader-upload-btn");
  const uploadText = document.getElementById("uploader-upload-text");
  const uploadingText = document.getElementById("uploader-uploading-text");
  const spinner = document.getElementById("uploader-spinner");

  let files = [];
  let isUploading = false;
  let lastFocusedElement = null;

  // Accessibility: Focus trap
  function trapFocus(e) {
    if (!modal.contains(e.target)) {
      e.stopPropagation();
      modalContent.focus();
    }
  }

  // Open modal on custom event
  window.addEventListener("open-uploader", function () {
    lastFocusedElement = document.activeElement;
    modal.style.display = "flex";
    setTimeout(() => {
      modal.classList.add("opacity-100");
      modalContent.classList.remove("scale-95", "opacity-0");
      modalContent.classList.add("scale-100", "opacity-100");
      modalContent.focus();
    }, 10);
    document.body.style.overflow = "hidden";
    document.addEventListener("focus", trapFocus, true);
  });

  // Close modal
  function closeModal() {
    modal.classList.remove("opacity-100");
    modalContent.classList.remove("scale-100", "opacity-100");
    modalContent.classList.add("scale-95", "opacity-0");
    setTimeout(() => {
      modal.style.display = "none";
      document.body.style.overflow = "";
      resetUploader();
      document.removeEventListener("focus", trapFocus, true);
      if (lastFocusedElement) lastFocusedElement.focus();
    }, 200);
  }
  closeBtn.addEventListener("click", closeModal);
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closeModal();
  });
  // Accessibility: Esc to close
  window.addEventListener("keydown", function (e) {
    if (modal.style.display === "flex" && e.key === "Escape") {
      closeModal();
    }
  });

  // File selection
  selectBtn.addEventListener("click", function () {
    fileInput.click();
  });
  fileInput.addEventListener("change", function (e) {
    addFiles(e.target.files);
  });

  // Drag & drop
  dropzone.addEventListener("dragover", function (e) {
    e.preventDefault();
    dropzone.classList.add("border-blue-500", "bg-blue-100");
    dropzone.classList.remove("border-gray-300", "bg-gray-50");
  });
  dropzone.addEventListener("dragleave", function (e) {
    e.preventDefault();
    dropzone.classList.remove("border-blue-500", "bg-blue-100");
    dropzone.classList.add("border-gray-300", "bg-gray-50");
  });
  dropzone.addEventListener("drop", function (e) {
    e.preventDefault();
    dropzone.classList.remove("border-blue-500", "bg-blue-100");
    dropzone.classList.add("border-gray-300", "bg-gray-50");
    addFiles(e.dataTransfer.files);
  });

  function addFiles(newFiles) {
    files = [...files, ...Array.from(newFiles)];
    renderFiles();
  }

  function renderFiles() {
    filesList.innerHTML = "";
    if (files.length > 0) {
      filesSection.style.display = "";
      uploadBtn.disabled = false;
    } else {
      filesSection.style.display = "none";
      uploadBtn.disabled = true;
    }
    files.forEach((file, idx) => {
      const li = document.createElement("li");
      li.className =
        "flex items-center justify-between p-2 border rounded-lg bg-white shadow-sm";
      li.innerHTML = `<span class="truncate max-w-xs">${file.name}</span>`;
      const removeBtn = document.createElement("button");
      removeBtn.className =
        "text-red-500 hover:text-red-700 px-2 py-1 rounded focus:outline-none focus:ring-2 focus:ring-red-400";
      removeBtn.setAttribute("aria-label", "Remove file");
      removeBtn.innerHTML = "&times;";
      removeBtn.addEventListener("click", function () {
        files.splice(idx, 1);
        renderFiles();
      });
      li.appendChild(removeBtn);
      filesList.appendChild(li);
    });
  }

  // Upload
  uploadBtn.addEventListener("click", async function () {
    if (isUploading || files.length === 0) return;
    isUploading = true;
    uploadText.classList.add("hidden");
    uploadingText.classList.remove("hidden");
    spinner.classList.remove("hidden");
    uploadBtn.disabled = true;
    const formData = new FormData();
    files.forEach((file) => formData.append("files[]", file));
    try {
      const response = await fetch("{{ route('uploader.upload') }}", {
        method: "POST",
        body: formData,
        headers: {
          "X-CSRF-TOKEN": "{{ csrf_token() }}",
          Accept: "application/json",
        },
      });
      if (response.ok) {
        const result = await response.json();
        window.dispatchEvent(
          new CustomEvent("files-uploaded", { detail: result })
        );
        files = [];
        renderFiles();
        closeModal();
      } else {
        alert("Upload failed");
      }
    } catch (error) {
      alert("An error occurred: " + error);
    } finally {
      isUploading = false;
      uploadText.classList.remove("hidden");
      uploadingText.classList.add("hidden");
      spinner.classList.add("hidden");
      uploadBtn.disabled = false;
    }
  });

  function resetUploader() {
    files = [];
    renderFiles();
    uploadText.classList.remove("hidden");
    uploadingText.classList.add("hidden");
    spinner.classList.add("hidden");
    uploadBtn.disabled = true;
    isUploading = false;
  }
})();
