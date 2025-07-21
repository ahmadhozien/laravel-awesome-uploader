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
  const tabButtons = document.querySelectorAll(".uploader-tab");
  const tabContents = document.querySelectorAll(".uploader-tab-content");
  const fileGrid = document.getElementById("uploader-file-grid");
  const selectedCount = document.getElementById("uploader-selected-count");
  const addFilesBtn = document.getElementById("uploader-add-files");
  const cancelSelectionBtn = document.getElementById(
    "uploader-cancel-selection"
  );
  const prevBtn = document.getElementById("uploader-prev");
  const nextBtn = document.getElementById("uploader-next");
  const selectedOnlyCheckbox = document.getElementById(
    "uploader-selected-only"
  );

  let files = [];
  let isUploading = false;
  let lastFocusedElement = null;
  let managerFiles = [];
  let selectedManagerFiles = new Set();

  // Mock file manager data (replace with AJAX or backend data as needed)
  function getMockFiles() {
    return [
      {
        id: 1,
        name: "image1.jpg",
        size: 39,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=1",
      },
      {
        id: 2,
        name: "image2.jpg",
        size: 41,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=2",
      },
      {
        id: 3,
        name: "image3.jpg",
        size: 41,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=3",
      },
      {
        id: 4,
        name: "image4.jpg",
        size: 39,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=4",
      },
      {
        id: 5,
        name: "document.pdf",
        size: 240,
        type: "pdf",
        url: "https://via.placeholder.com/120x90?text=PDF",
      },
      {
        id: 6,
        name: "icon.png",
        size: 795,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=PNG",
      },
      {
        id: 7,
        name: "house.png",
        size: 525,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=Home",
      },
      {
        id: 8,
        name: "dress.png",
        size: 750,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=Dress",
      },
      {
        id: 9,
        name: "close.png",
        size: 852,
        type: "image",
        url: "https://via.placeholder.com/120x90?text=Close",
      },
    ];
  }

  // Tab switching
  tabButtons.forEach((btn) => {
    btn.addEventListener("click", function () {
      tabButtons.forEach((b) =>
        b.classList.remove("bg-gray-100", "text-gray-700")
      );
      tabButtons.forEach((b) => b.classList.add("text-gray-500"));
      this.classList.add("bg-gray-100", "text-gray-700");
      this.classList.remove("text-gray-500");
      const tab = this.getAttribute("data-tab");
      tabContents.forEach((tc) => tc.classList.add("hidden"));
      document.getElementById("uploader-tab-" + tab).classList.remove("hidden");
    });
  });

  // File manager rendering and selection
  function renderFileGrid() {
    fileGrid.innerHTML = "";
    let visibleFiles = managerFiles;
    if (selectedOnlyCheckbox && selectedOnlyCheckbox.checked) {
      visibleFiles = managerFiles.filter((f) => selectedManagerFiles.has(f.id));
    }
    visibleFiles.forEach((file) => {
      const card = document.createElement("div");
      card.className =
        "relative rounded-xl border bg-white shadow hover:shadow-lg transition cursor-pointer flex flex-col items-center p-2 group" +
        (selectedManagerFiles.has(file.id) ? " ring-2 ring-blue-500" : "");
      card.tabIndex = 0;
      card.setAttribute("role", "button");
      card.setAttribute(
        "aria-pressed",
        selectedManagerFiles.has(file.id) ? "true" : "false"
      );
      card.setAttribute("data-id", file.id);
      card.innerHTML = `
                <div class="w-28 h-20 flex items-center justify-center bg-gray-50 rounded-lg overflow-hidden mb-2">
                    <img src="${file.url}" alt="${
        file.name
      }" class="object-contain max-h-full max-w-full" />
                </div>
                <div class="w-full text-center text-xs text-gray-700 truncate">${
                  file.name
                }</div>
                <div class="w-full text-center text-[11px] text-gray-400">KB ${
                  file.size
                }</div>
                <div class="absolute top-2 left-2">
                    <span class="inline-block w-4 h-4 rounded-full border border-gray-300 bg-white ${
                      selectedManagerFiles.has(file.id)
                        ? "bg-blue-500 border-blue-500"
                        : ""
                    }"></span>
                </div>
            `;
      card.addEventListener("click", function () {
        if (selectedManagerFiles.has(file.id)) {
          selectedManagerFiles.delete(file.id);
        } else {
          selectedManagerFiles.add(file.id);
        }
        renderFileGrid();
        updateSelectedCount();
      });
      card.addEventListener("keydown", function (e) {
        if (e.key === " " || e.key === "Enter") {
          e.preventDefault();
          card.click();
        }
      });
      fileGrid.appendChild(card);
    });
  }

  function updateSelectedCount() {
    if (selectedCount) selectedCount.textContent = selectedManagerFiles.size;
  }

  if (selectedOnlyCheckbox) {
    selectedOnlyCheckbox.addEventListener("change", renderFileGrid);
  }
  if (cancelSelectionBtn) {
    cancelSelectionBtn.addEventListener("click", function () {
      selectedManagerFiles.clear();
      renderFileGrid();
      updateSelectedCount();
    });
  }
  if (addFilesBtn) {
    addFilesBtn.addEventListener("click", function () {
      // Dispatch selected files (mock)
      window.dispatchEvent(
        new CustomEvent("files-uploaded", {
          detail: Array.from(selectedManagerFiles),
        })
      );
      selectedManagerFiles.clear();
      renderFileGrid();
      updateSelectedCount();
      closeModal();
    });
  }
  if (prevBtn)
    prevBtn.addEventListener("click", function () {
      /* Pagination logic here */
    });
  if (nextBtn)
    nextBtn.addEventListener("click", function () {
      /* Pagination logic here */
    });

  // Modal open/close/focus trap logic (as before)
  function trapFocus(e) {
    if (!modal.contains(e.target)) {
      e.stopPropagation();
      modalContent.focus();
    }
  }
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
  window.addEventListener("keydown", function (e) {
    if (modal.style.display === "flex" && e.key === "Escape") {
      closeModal();
    }
  });

  // Upload tab logic (as before)
  selectBtn.addEventListener("click", function () {
    fileInput.click();
  });
  fileInput.addEventListener("change", function (e) {
    addFiles(e.target.files);
  });
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
      // Use config for all dynamic options and labels
      const config = window.LaravelUploader || {};
      const uploadUrl = config.uploadUrl;
      const csrfToken = config.csrfToken;
      const labels = config.labels || {};
      const hasNext = config.hasNext;
      const hasPrev = config.hasPrev;
      const initialTab = config.initialTab || "manager";
      const response = await fetch(uploadUrl, {
        method: "POST",
        body: formData,
        headers: {
          "X-CSRF-TOKEN": csrfToken,
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
    // Reset file manager selection
    selectedManagerFiles.clear();
    renderFileGrid();
    updateSelectedCount();
  }
  // Initial render for file manager
  managerFiles = getMockFiles();
  renderFileGrid();
  updateSelectedCount();
})();
