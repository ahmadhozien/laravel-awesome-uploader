document.addEventListener("DOMContentLoaded", function () {
  function getOrSetGuestToken() {
    let token = localStorage.getItem("uploader_guest_token");
    if (!token) {
      if (window.crypto && window.crypto.randomUUID) {
        token = window.crypto.randomUUID();
      } else {
        token = "guest-" + Math.random().toString(36).substr(2, 16);
      }
      localStorage.setItem("uploader_guest_token", token);
    }
    return token;
  }
  window.LaravelUploader = window.LaravelUploader || {};
  window.LaravelUploader.guestToken = getOrSetGuestToken();

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

    const config = window.LaravelUploader || {};
    const uploadUrl = config.uploadUrl;
    const csrfToken = config.csrfToken;
    const labels = config.labels || {};
    const hasNext = config.hasNext;
    const hasPrev = config.hasPrev;
    const initialTab = config.initialTab || "manager";
    const allowMultiple = config.multiple !== false;
    const guestToken = config.guestToken;

    let files = [];
    let isUploading = false;
    let lastFocusedElement = null;
    let managerFiles = [];
    let selectedManagerFiles = new Set();

    // No mock data; managerFiles starts empty and is filled after upload

    // Error alert elements
    const errorAlert = document.getElementById("uploader-error-alert");
    const errorMessage = document.getElementById("uploader-error-message");
    const errorDismiss = document.getElementById("uploader-error-dismiss");

    function showError(message) {
      if (errorAlert && errorMessage) {
        errorMessage.textContent = message;
        errorAlert.classList.remove("hidden");
        // Optionally highlight dropzone
        dropzone.classList.add("border-red-500", "bg-red-50");
        dropzone.classList.remove("border-gray-300", "bg-gray-50");
      }
    }
    function clearError() {
      if (errorAlert && errorMessage) {
        errorMessage.textContent = "";
        errorAlert.classList.add("hidden");
        dropzone.classList.remove("border-red-500", "bg-red-50");
        dropzone.classList.add("border-gray-300", "bg-gray-50");
      }
    }
    if (errorDismiss) {
      errorDismiss.addEventListener("click", clearError);
    }

    function addFiles(newFiles) {
      clearError();
      if (!allowMultiple) {
        files = [Array.from(newFiles)[0]];
      } else {
        files = [...files, ...Array.from(newFiles)];
      }
      renderFiles();
      // Dispatch files-selected event
      window.dispatchEvent(
        new CustomEvent("files-selected", { detail: { files } })
      );
    }

    function renderFiles(errors = {}) {
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
          "flex items-center gap-3 p-2 border rounded-lg bg-white shadow-sm" +
          (errors[idx] ? " border-red-400" : "");
        // Thumbnail or icon
        let thumbHtml = "";
        if (file.type && file.type.startsWith("image/")) {
          thumbHtml = `<img src="${URL.createObjectURL(
            file
          )}" alt="thumb" class="w-10 h-10 object-cover rounded border" />`;
        } else {
          thumbHtml = `<span class="w-10 h-10 flex items-center justify-center rounded border bg-gray-100 text-gray-400"><svg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 4v16m8-8H4'/></svg></span>`;
        }
        li.innerHTML = `
          ${thumbHtml}
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <span class="truncate max-w-xs font-medium">${file.name}</span>
              <button class="text-red-500 hover:text-red-700 px-2 py-1 rounded focus:outline-none focus:ring-2 focus:ring-red-400" aria-label="Remove file">&times;</button>
            </div>
            <div class="text-xs text-gray-500">${(
              file.type || ""
            ).toUpperCase()} &middot; ${formatFileSize(file.size || 0)}</div>
            ${
              errors[idx]
                ? `<div class='mt-1 text-xs text-red-600'>${errors[idx]}</div>`
                : ""
            }
          </div>
        `;
        const removeBtn = li.querySelector("button");
        removeBtn.addEventListener("click", function () {
          files.splice(idx, 1);
          renderFiles(errors);
        });
        filesList.appendChild(li);
      });
      // Footer button visibility
      // Remove footer button visibility logic for cancelSelectionBtn here
    }

    function renderFooterButtons() {
      // Next/Prev only if pagination
      if (prevBtn) prevBtn.style.display = hasPrev ? "" : "none";
      if (nextBtn) nextBtn.style.display = hasNext ? "" : "none";
      // Cancel Selection only if there are selected files in file manager
      if (cancelSelectionBtn) {
        cancelSelectionBtn.style.display =
          selectedManagerFiles.size > 0 ? "" : "none";
      }
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
        document
          .getElementById("uploader-tab-" + tab)
          .classList.remove("hidden");
      });
    });

    // File manager rendering and selection
    function renderFileGrid() {
      fileGrid.innerHTML = "";
      let visibleFiles = managerFiles;
      // Search filter
      if (searchTerm) {
        visibleFiles = visibleFiles.filter(
          (f) => f.name && f.name.toLowerCase().includes(searchTerm)
        );
      }
      // Sort
      visibleFiles = visibleFiles.slice().sort((a, b) => {
        if (sortOrder === "newest") {
          return (b.id || 0) - (a.id || 0);
        } else {
          return (a.id || 0) - (b.id || 0);
        }
      });
      if (selectedOnlyCheckbox && selectedOnlyCheckbox.checked) {
        visibleFiles = visibleFiles.filter((f) =>
          selectedManagerFiles.has(f.id)
        );
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
      renderFooterButtons();
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
        // Dispatch add-files-clicked event with full file objects
        const selectedFiles = managerFiles.filter((f) =>
          selectedManagerFiles.has(f.id)
        );
        window.dispatchEvent(
          new CustomEvent("add-files-clicked", {
            detail: { selected: selectedFiles },
          })
        );
        // Dispatch selected files (mock)
        window.dispatchEvent(
          new CustomEvent("files-uploaded", {
            detail: selectedFiles,
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

    // Add search and sort logic
    const searchInput = document.querySelector(
      'input[placeholder="' + labels.searchPlaceholder + '"]'
    );
    const sortSelect = document.querySelector("select");
    let searchTerm = "";
    let sortOrder = "newest";

    if (searchInput) {
      searchInput.addEventListener("input", function () {
        searchTerm = this.value.toLowerCase();
        renderFileGrid();
      });
    }
    if (sortSelect) {
      sortSelect.addEventListener("change", function () {
        sortOrder = this.selectedIndex === 0 ? "newest" : "oldest";
        renderFileGrid();
      });
    }

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
      // Dispatch modal-opened event
      window.dispatchEvent(new CustomEvent("modal-opened"));

      // Fetch previous uploads for this user/guest
      const guestToken = window.LaravelUploader.guestToken;
      let url = "/api/uploads";
      if (guestToken) {
        url += "?guest_token=" + encodeURIComponent(guestToken);
      }
      fetch(url)
        .then((res) => res.json())
        .then((data) => {
          managerFiles = Array.isArray(data) ? data : [];
          renderFileGrid();
          updateSelectedCount();
        });

      // Attach upload event each time modal is shown
      const uploadBtn = document.getElementById("uploader-upload-btn");
      if (uploadBtn) {
        uploadBtn.onclick = async function () {
          if (isUploading || files.length === 0) return;
          clearError();
          // Dispatch upload-start event
          window.dispatchEvent(
            new CustomEvent("upload-start", { detail: { files } })
          );
          isUploading = true;
          uploadText.classList.add("hidden");
          uploadingText.classList.remove("hidden");
          spinner.classList.remove("hidden");
          uploadBtn.disabled = true;
          const saveToDb = config.saveToDb === true;
          const formData = new FormData();
          if (allowMultiple) {
            files.forEach((file) => formData.append("files[]", file));
          } else {
            formData.append("file", files[0]);
          }
          formData.append("multiple", allowMultiple ? "1" : "0");
          formData.append("saveToDb", saveToDb ? "1" : "0");
          if (guestToken) {
            formData.append("guest_token", guestToken);
          }
          try {
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
              // Add uploaded file(s) to managerFiles
              if (Array.isArray(result)) {
                managerFiles = managerFiles.concat(result);
              } else if (result) {
                managerFiles.push(result);
              }
              renderFileGrid();
              // Switch to File Manager tab
              tabButtons.forEach((b) =>
                b.classList.remove("bg-gray-100", "text-gray-700")
              );
              tabButtons.forEach((b) => b.classList.add("text-gray-500"));
              const managerTabBtn = document.querySelector(
                '.uploader-tab[data-tab="manager"]'
              );
              if (managerTabBtn) {
                managerTabBtn.classList.add("bg-gray-100", "text-gray-700");
                managerTabBtn.classList.remove("text-gray-500");
              }
              tabContents.forEach((tc) => tc.classList.add("hidden"));
              document
                .getElementById("uploader-tab-manager")
                .classList.remove("hidden");
              window.dispatchEvent(
                new CustomEvent("upload-success", {
                  detail: { response: result },
                })
              );
              window.dispatchEvent(
                new CustomEvent("files-uploaded", { detail: result })
              );
              files = [];
              renderFiles();
              // Do NOT close the modal here
            } else {
              let errorMsg = "Upload failed";
              let fileErrors = {};
              try {
                const errorData = await response.json();
                if (errorData && errorData.errors) {
                  errorMsg = Object.values(errorData.errors).flat().join("; ");
                  // Map errors for files.* and file as before
                  if (errorData.errors["files.*"]) {
                    errorData.errors["files.*"].forEach((msg, i) => {
                      fileErrors[i] = msg;
                    });
                  } else if (errorData.errors["file"]) {
                    fileErrors[0] = errorData.errors["file"][0];
                  }
                  // Map errors for files.0, files.1, ...
                  Object.keys(errorData.errors).forEach(function (key) {
                    const match = key.match(/^files\.(\d+)$/);
                    if (match) {
                      fileErrors[parseInt(match[1], 10)] =
                        errorData.errors[key][0];
                    }
                  });
                } else if (errorData && errorData.error) {
                  errorMsg = errorData.error;
                }
              } catch (e) {}
              // Only show global error if not a file-specific error
              if (Object.keys(fileErrors).length > 0) {
                renderFiles(fileErrors);
              } else {
                showError(errorMsg);
              }
              window.dispatchEvent(
                new CustomEvent("upload-error", { detail: { error: errorMsg } })
              );
            }
          } catch (error) {
            showError("An error occurred: " + error);
            window.dispatchEvent(
              new CustomEvent("upload-error", { detail: { error } })
            );
          } finally {
            isUploading = false;
            uploadText.classList.remove("hidden");
            uploadingText.classList.add("hidden");
            spinner.classList.add("hidden");
            uploadBtn.disabled = false;
          }
        };
      }
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
        // Dispatch modal-closed event
        window.dispatchEvent(new CustomEvent("modal-closed"));
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
      clearError();
    }
    // Initial render for file manager (empty)
    renderFileGrid();
    updateSelectedCount();
  })();
});

// General file size converter
function formatFileSize(size) {
  if (size < 1024) return size + " B";
  if (size < 1024 * 1024) return (size / 1024).toFixed(2) + " KB";
  if (size < 1024 * 1024 * 1024) return (size / 1024 / 1024).toFixed(2) + " MB";
  return (size / 1024 / 1024 / 1024).toFixed(2) + " GB";
}
