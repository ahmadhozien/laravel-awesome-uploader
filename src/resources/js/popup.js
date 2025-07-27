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
    const enableLogging = config.enableLogging !== false;

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

    // Clipboard helper functions
    function copyToClipboard(text) {
      console.log("copyToClipboard called with:", text);

      return new Promise((resolve, reject) => {
        // Try multiple methods for maximum compatibility

        // Method 1: Modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
          console.log("Using modern clipboard API");
          navigator.clipboard
            .writeText(text)
            .then(() => {
              console.log("Modern clipboard API success");
              resolve();
            })
            .catch((err) => {
              console.log("Modern clipboard API failed:", err);
              // Fallback to method 2
              tryFallbackMethod();
            });
        } else {
          console.log("Modern clipboard API not available, using fallback");
          tryFallbackMethod();
        }

        function tryFallbackMethod() {
          try {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            textArea.style.opacity = "0";
            textArea.setAttribute("readonly", "");
            document.body.appendChild(textArea);

            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, text.length);

            const success = document.execCommand("copy");
            document.body.removeChild(textArea);

            console.log("Fallback method result:", success);

            if (success) {
              resolve();
            } else {
              reject(new Error("execCommand copy failed"));
            }
          } catch (err) {
            console.log("Fallback method error:", err);
            reject(err);
          }
        }
      });
    }

    function showCopySuccess() {
      // Create a temporary success message
      const message = document.createElement("div");
      message.textContent = "URL copied to clipboard!";
      message.className =
        "fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300";
      document.body.appendChild(message);

      // Remove the message after 2 seconds
      setTimeout(() => {
        message.style.opacity = "0";
        setTimeout(() => {
          if (message.parentNode) {
            message.parentNode.removeChild(message);
          }
        }, 300);
      }, 2000);
    }

    // Logging helper functions
    function log(...args) {
      if (enableLogging) {
        console.log(...args);
      }
    }

    function logError(...args) {
      if (enableLogging) {
        console.error(...args);
      }
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
      log("Rendering file grid with files:", managerFiles);
      log("File grid element:", fileGrid);
      if (!fileGrid) {
        logError("File grid element not found!");
        return;
      }
      fileGrid.innerHTML = "";

      // Show no files message if empty
      if (managerFiles.length === 0) {
        fileGrid.innerHTML = `
          <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-500">
            <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
            <p class="text-lg font-medium mb-1">No files uploaded yet</p>
            <p class="text-sm">Upload files using the Upload tab</p>
          </div>
        `;
        return;
      }

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
      log("Visible files to render:", visibleFiles);
      visibleFiles.forEach((file, idx) => {
        log("Rendering file:", file);
        const card = document.createElement("div");
        card.className =
          "relative rounded-2xl border border-gray-200 bg-white shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer flex flex-col items-center p-3 group transform hover:scale-105" +
          (selectedManagerFiles.has(file.id)
            ? " ring-2 ring-blue-500 shadow-lg"
            : "");
        card.tabIndex = 0;
        card.setAttribute("role", "button");
        card.setAttribute(
          "aria-pressed",
          selectedManagerFiles.has(file.id) ? "true" : "false"
        );
        card.setAttribute("data-id", file.id);
        card.innerHTML = `
                <div class="w-28 h-20 flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl overflow-hidden mb-3 shadow-sm">
                    <img src="${file.url}" alt="${
          file.name
        }" class="object-contain max-h-full max-w-full rounded-lg" />
                </div>
                <div class="w-full text-center text-xs font-medium text-gray-800 truncate mb-1">${
                  file.name
                }</div>
                <div class="w-full text-center text-[11px] text-gray-500 font-medium">${formatFileSize(
                  file.size || 0
                )}</div>
                <div class="absolute top-2 left-2">
                    <span class="inline-block w-5 h-5 rounded-full border-2 border-gray-300 bg-white shadow-sm transition-all duration-200 ${
                      selectedManagerFiles.has(file.id)
                        ? "bg-blue-500 border-blue-500 scale-110"
                        : "group-hover:border-gray-400"
                    }"></span>
                </div>
                <button class="absolute top-2 right-2 z-10 uploader-ellipsis-btn p-1.5 rounded-full hover:bg-gray-100 focus:outline-none transition-all duration-200 opacity-0 group-hover:opacity-100" aria-label="Options">
                  <svg width="18" height="18" fill="none" viewBox="0 0 24 24" class="text-gray-600"><circle cx="5" cy="12" r="2" fill="currentColor"/><circle cx="12" cy="12" r="2" fill="currentColor"/><circle cx="19" cy="12" r="2" fill="currentColor"/></svg>
                </button>
                <div class="uploader-options-menu hidden absolute top-8 right-2 min-w-[180px] bg-white border border-gray-200 rounded-xl shadow-xl py-2 z-20 text-sm rtl:right-auto rtl:left-2 backdrop-blur-sm">
                  ${
                    file.permissions && file.permissions.view
                      ? '<button class="block w-full text-left px-4 py-2 hover:bg-gray-100 uploader-info-btn flex items-center gap-2">' +
                        '<svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8H6a2 2 0 01-2-2V6a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/></svg>File Info</button>'
                      : ""
                  }
                  ${
                    file.permissions && file.permissions.download
                      ? `<a class="block w-full text-left px-4 py-2 hover:bg-gray-100 uploader-download-btn flex items-center gap-2" href="${file.url}" download target="_blank">` +
                        '<svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>Download</a>'
                      : ""
                  }
                  ${
                    file.permissions && file.permissions.download
                      ? '<button class="block w-full text-left px-4 py-2 hover:bg-gray-100 uploader-copy-btn flex items-center gap-2">' +
                        '<svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect width="13" height="13" x="9" y="9" rx="2"/><path d="M5 15V5a2 2 0 012-2h10a2 2 0 012 2v10"/></svg>Copy Link</button>'
                      : ""
                  }
                  ${
                    file.is_image &&
                    file.thumbnails &&
                    Object.keys(file.thumbnails).length > 0
                      ? '<button class="block w-full text-left px-4 py-2 hover:bg-gray-100 uploader-thumbnails-btn flex items-center gap-2">' +
                        '<svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Thumbnail Links</button>'
                      : ""
                  }
                  ${
                    file.permissions && file.permissions.delete
                      ? '<button class="block w-full text-left px-4 py-2 hover:bg-gray-100 uploader-rename-btn flex items-center gap-2">' +
                        '<svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M13.5 3.5l3 3L8 15l-3 1 1-3 8.5-8.5z"/></svg>Rename</button>'
                      : ""
                  }
                  ${
                    file.permissions && file.permissions.delete
                      ? '<button class="block w-full text-left px-4 py-2 hover:bg-gray-100 text-red-600 uploader-delete-btn flex items-center gap-2">' +
                        '<svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>Delete</button>'
                      : ""
                  }
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
        // Add menu logic
        const ellipsisBtn = card.querySelector(".uploader-ellipsis-btn");
        const menu = card.querySelector(".uploader-options-menu");
        ellipsisBtn.addEventListener("click", function (e) {
          e.stopPropagation();
          // Close all other menus
          document.querySelectorAll(".uploader-options-menu").forEach((m) => {
            if (m !== menu) m.classList.add("hidden");
          });
          menu.classList.toggle("hidden");
        });
        // Close menu on click outside
        document.addEventListener("click", function (e) {
          if (!card.contains(e.target)) menu.classList.add("hidden");
        });
        // Only add event listeners for visible menu items
        if (file.permissions && file.permissions.view) {
          menu
            .querySelector(".uploader-info-btn")
            .addEventListener("click", function (e) {
              e.stopPropagation();
              menu.classList.add("hidden");
              // Render only important file info as key-value table with user-friendly labels and modern design
              const keyLabels = {
                name: "File Name",
                type: "Type",
                size: "Size",
                url: "URL",
                created_at: "Uploaded",
                updated_at: "Last Updated",
              };
              const importantKeys = [
                "name",
                "type",
                "size",
                "url",
                "created_at",
                "updated_at",
              ];
              let html = '<div class="space-y-2">';
              importantKeys.forEach((key) => {
                let value = file[key];
                if (key === "size") value = formatFileSize(value || 0);
                html += `<div class='flex items-start border-b last:border-b-0 border-gray-100 pb-2'><div class='w-32 font-semibold text-gray-700'>${
                  keyLabels[key] || key
                }:</div><div class='flex-1 break-all text-gray-900'>${
                  value ?? ""
                }</div></div>`;
              });
              html += "</div>";
              infoContent.innerHTML = html;
              infoModal.classList.remove("hidden");
            });
        }
        if (file.permissions && file.permissions.download) {
          const copyBtn = menu.querySelector(".uploader-copy-btn");
          if (copyBtn) {
            copyBtn.addEventListener("click", function (e) {
              e.stopPropagation();
              menu.classList.add("hidden");

              // Debug logging
              log("Copying URL:", file.url);
              log("Clipboard available:", !!navigator.clipboard);
              log("Secure context:", window.isSecureContext);

              if (!file.url || file.url === "") {
                logError("File URL is empty or undefined");
                alert("Error: File URL is not available");
                return;
              }

              copyToClipboard(file.url)
                .then(() => {
                  log("Copy successful");
                  if (ellipsisBtn) ellipsisBtn.blur();
                  // Show a brief success message
                  showCopySuccess();
                })
                .catch((err) => {
                  logError("Failed to copy URL:", err);
                  logError("Error details:", err.message);
                  // Fallback: show the URL in an alert
                  prompt("Copy this URL:", file.url);
                });
            });
          }
        }

        // Add thumbnail links functionality
        if (file.is_image) {
          const thumbnailsBtn = menu.querySelector(".uploader-thumbnails-btn");
          if (thumbnailsBtn) {
            thumbnailsBtn.addEventListener("click", async function (e) {
              e.stopPropagation();
              menu.classList.add("hidden");

              // Create thumbnail links modal
              const thumbnailsModal = document.createElement("div");
              thumbnailsModal.className =
                "fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50";

              // Show loading state
              thumbnailsModal.innerHTML = `
                <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
                  <h3 class="text-lg font-medium mb-4">Thumbnail Links</h3>
                  <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600">Loading thumbnails...</span>
                  </div>
                </div>
              `;

              document.body.appendChild(thumbnailsModal);

              try {
                // Fetch thumbnails from API
                let thumbnailsUrl = `/api/uploader/uploads/${file.id}/thumbnails`;
                if (guestToken) {
                  thumbnailsUrl += `?guest_token=${encodeURIComponent(
                    guestToken
                  )}`;
                }

                const response = await fetch(thumbnailsUrl, {
                  headers: {
                    Accept: "application/json",
                  },
                });

                if (!response.ok) {
                  throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();

                if (
                  !result.success ||
                  !result.thumbnails ||
                  Object.keys(result.thumbnails).length === 0
                ) {
                  // No thumbnails available
                  thumbnailsModal.innerHTML = `
                    <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
                      <h3 class="text-lg font-medium mb-4">Thumbnail Links</h3>
                      <div class="text-center py-8">
                        <p class="text-gray-600 mb-4">No thumbnails available for this image.</p>
                        <p class="text-sm text-gray-500">Thumbnails are generated automatically when images are uploaded.</p>
                      </div>
                      <div class="flex justify-end">
                        <button id="thumbnails-close-${file.id}" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded">Close</button>
                      </div>
                    </div>
                  `;
                } else {
                  // Build thumbnail links HTML
                  let thumbnailsHtml = '<div class="space-y-3">';
                  Object.entries(result.thumbnails).forEach(
                    ([size, thumbnail]) => {
                      thumbnailsHtml += `
                      <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3">
                          <span class="font-medium text-gray-700">${size}px</span>
                          <span class="text-sm text-gray-500">${formatFileSize(
                            thumbnail.size
                          )}</span>
                        </div>
                        <button class="copy-thumbnail-btn px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700" data-url="${
                          thumbnail.url
                        }">
                          Copy Link
                        </button>
                      </div>
                    `;
                    }
                  );
                  thumbnailsHtml += "</div>";

                  thumbnailsModal.innerHTML = `
                    <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4 shadow-2xl max-h-[80vh] overflow-y-auto" onclick="event.stopPropagation()">
                      <h3 class="text-lg font-medium mb-4">Thumbnail Links</h3>
                      <p class="text-sm text-gray-600 mb-4">Click "Copy Link" to copy the thumbnail URL to clipboard.</p>
                      ${thumbnailsHtml}
                      <div class="flex justify-end mt-6">
                        <button id="thumbnails-close-${file.id}" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded">Close</button>
                      </div>
                    </div>
                  `;
                }

                // Add event listeners for copy buttons
                thumbnailsModal
                  .querySelectorAll(".copy-thumbnail-btn")
                  .forEach((btn) => {
                    btn.addEventListener("click", function () {
                      const url = this.getAttribute("data-url");
                      copyToClipboard(url)
                        .then(() => {
                          // Show success feedback
                          this.textContent = "Copied!";
                          this.classList.remove(
                            "bg-blue-600",
                            "hover:bg-blue-700"
                          );
                          this.classList.add("bg-green-600");
                          setTimeout(() => {
                            this.textContent = "Copy Link";
                            this.classList.remove("bg-green-600");
                            this.classList.add(
                              "bg-blue-600",
                              "hover:bg-blue-700"
                            );
                          }, 2000);
                        })
                        .catch((err) => {
                          logError("Failed to copy thumbnail URL:", err);
                          prompt("Copy this URL:", url);
                        });
                    });
                  });

                // Close button
                thumbnailsModal
                  .querySelector(`#thumbnails-close-${file.id}`)
                  .addEventListener("click", () => {
                    document.body.removeChild(thumbnailsModal);
                  });
              } catch (err) {
                // Show error state
                thumbnailsModal.innerHTML = `
                  <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
                    <h3 class="text-lg font-medium mb-4">Thumbnail Links</h3>
                    <div class="text-center py-8">
                      <p class="text-red-600 mb-4">Failed to load thumbnails.</p>
                      <p class="text-sm text-gray-500">Please try again later.</p>
                    </div>
                    <div class="flex justify-end">
                      <button id="thumbnails-close-${file.id}" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded">Close</button>
                    </div>
                  </div>
                `;

                // Close button for error state
                thumbnailsModal
                  .querySelector(`#thumbnails-close-${file.id}`)
                  .addEventListener("click", () => {
                    document.body.removeChild(thumbnailsModal);
                  });

                logError("Failed to fetch thumbnails:", err);
              }
            });
          }
        }

        if (file.permissions && file.permissions.delete) {
          // Add rename functionality
          const renameBtn = menu.querySelector(".uploader-rename-btn");
          if (renameBtn) {
            renameBtn.addEventListener("click", function (e) {
              e.stopPropagation();
              menu.classList.add("hidden");

              // Temporarily disable focus trapping on main modal
              document.removeEventListener("focus", trapFocus, true);

              // Create rename modal
              const renameModal = document.createElement("div");
              renameModal.className =
                "fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50";

              // Get filename without extension
              const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");

              renameModal.innerHTML = `
                <div class="bg-white rounded-lg p-6 w-96 max-w-full mx-4 shadow-2xl" onclick="event.stopPropagation()">
                  <h3 class="text-lg font-medium mb-4">Rename File</h3>
                  <input type="text" id="rename-input-${file.id}" class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-blue-500" value="${nameWithoutExt}" autocomplete="off" />
                  <div class="flex gap-2 justify-end">
                    <button id="rename-cancel-${file.id}" class="px-4 py-2 text-gray-600 hover:text-gray-800 rounded">Cancel</button>
                    <button id="rename-confirm-${file.id}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Rename</button>
                  </div>
                </div>
              `;

              document.body.appendChild(renameModal);

              // Force focus on the input
              setTimeout(() => {
                const input = document.getElementById(
                  `rename-input-${file.id}`
                );
                console.log("Input element:", input);
                console.log(
                  "Input disabled:",
                  input ? input.disabled : "not found"
                );
                console.log(
                  "Input readonly:",
                  input ? input.readOnly : "not found"
                );

                if (input) {
                  input.disabled = false;
                  input.readOnly = false;
                  input.removeAttribute("disabled");
                  input.removeAttribute("readonly");
                  input.focus();
                  input.select();
                  console.log("Input focused and selected");
                }
              }, 100);

              const cancel = () => {
                document.body.removeChild(renameModal);
                // Re-enable focus trapping on main modal
                document.addEventListener("focus", trapFocus, true);
              };

              const confirm = async () => {
                const input = renameModal.querySelector(
                  `#rename-input-${file.id}`
                );
                if (!input) return;

                const newName = input.value.trim();
                if (!newName) return;

                try {
                  // Build URL with guest token (like delete request)
                  let renameUrl = `/api/uploader/uploads/${file.id}/rename`;
                  if (guestToken) {
                    renameUrl += `?guest_token=${encodeURIComponent(
                      guestToken
                    )}`;
                  }

                  // Prepare JSON data
                  const requestData = {
                    name: newName,
                  };

                  if (guestToken) {
                    requestData.guest_token = guestToken;
                  }

                  console.log("Rename URL:", renameUrl);
                  console.log("Request data:", requestData);

                  const response = await fetch(renameUrl, {
                    method: "PUT",
                    headers: {
                      "X-CSRF-TOKEN": csrfToken,
                      "Content-Type": "application/json",
                      Accept: "application/json",
                    },
                    body: JSON.stringify(requestData),
                  });

                  if (response.ok) {
                    const result = await response.json();
                    // Update the file in our array
                    const fileIndex = managerFiles.findIndex(
                      (f) => f.id === file.id
                    );
                    if (fileIndex !== -1) {
                      managerFiles[fileIndex].name = result.upload.name;
                    }
                    renderFileGrid();
                    cancel();
                  } else {
                    const error = await response.json();
                    showError(error.error || "Failed to rename file");
                    // Re-enable focus trapping even on error
                    document.addEventListener("focus", trapFocus, true);
                  }
                } catch (err) {
                  showError("Error renaming file: " + err.message);
                  // Re-enable focus trapping on error
                  document.addEventListener("focus", trapFocus, true);
                }
              };

              renameModal
                .querySelector(`#rename-cancel-${file.id}`)
                .addEventListener("click", cancel);
              renameModal
                .querySelector(`#rename-confirm-${file.id}`)
                .addEventListener("click", confirm);

              // Add keyboard event listener after input is ready
              setTimeout(() => {
                const input = renameModal.querySelector(
                  `#rename-input-${file.id}`
                );
                if (input) {
                  input.addEventListener("keydown", (e) => {
                    if (e.key === "Enter") confirm();
                    if (e.key === "Escape") cancel();
                  });
                }
              }, 60);
            });
          }
        }
        if (file.permissions && file.permissions.delete) {
          menu
            .querySelector(".uploader-delete-btn")
            .addEventListener("click", function (e) {
              e.stopPropagation();
              menu.classList.add("hidden");
              deleteModal.classList.remove("hidden");
              deleteFileCallback = async function () {
                try {
                  let deleteUrl = `/api/uploader/uploads/${file.id}`;
                  if (guestToken) {
                    deleteUrl += `?guest_token=${encodeURIComponent(
                      guestToken
                    )}`;
                  }

                  const response = await fetch(deleteUrl, {
                    method: "DELETE",
                    headers: {
                      "X-CSRF-TOKEN": csrfToken,
                      Accept: "application/json",
                    },
                  });
                  if (response.ok) {
                    managerFiles = managerFiles.filter((f) => f.id !== file.id);
                    renderFileGrid();
                    updateSelectedCount();
                  } else if (response.status === 403) {
                    showError(
                      "You do not have permission to delete this file."
                    );
                  } else {
                    const data = await response.json();
                    showError(data.error || "Failed to delete file.");
                  }
                } catch (err) {
                  showError("Error deleting file: " + err);
                }
              };
            });
        }
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
      // Show loader
      const gridLoader = document.getElementById("uploader-file-grid-loader");
      if (gridLoader) gridLoader.classList.remove("hidden");
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
      let url = "/api/uploader/uploads";
      if (guestToken) {
        url += "?guest_token=" + encodeURIComponent(guestToken);
      }

      log("Fetching uploads from URL:", url);
      log("Guest token:", guestToken);
      fetch(url)
        .then((res) => {
          if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
          }
          return res.json();
        })
        .then((response) => {
          log("API Response:", response);
          // Handle paginated response
          if (response && response.data && Array.isArray(response.data)) {
            managerFiles = response.data;
          } else if (Array.isArray(response)) {
            managerFiles = response;
          } else {
            managerFiles = [];
          }
          log("Manager files:", managerFiles);
          renderFileGrid();
          updateSelectedCount();
          // Hide loader after files are loaded
          if (gridLoader) gridLoader.classList.add("hidden");
        })
        .catch((error) => {
          logError("Error fetching uploads:", error);
          // Hide loader on error
          if (gridLoader) gridLoader.classList.add("hidden");
          // Show error message
          showError("Failed to load files: " + error.message);
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
            log("Uploading to URL:", uploadUrl);
            log("FormData contents:", Array.from(formData.entries()));

            const response = await fetch(uploadUrl, {
              method: "POST",
              body: formData,
              headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
              },
            });

            log("Response status:", response.status);
            log(
              "Response headers:",
              Object.fromEntries(response.headers.entries())
            );
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
              } catch (e) {
                logError("JSON parsing error:", e);
                // If JSON parsing fails, try to get text response
                try {
                  const textResponse = await response.text();
                  logError("Non-JSON response received:", textResponse);
                  logError("Response status:", response.status);
                  logError("Response URL:", response.url);
                  errorMsg = "Server returned invalid response format";
                } catch (textError) {
                  logError("Failed to get text response:", textError);
                  errorMsg = "Failed to parse server response";
                }
              }
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

    // Add file info modal to the DOM if not present
    if (!document.getElementById("uploader-file-info-modal")) {
      const infoModal = document.createElement("div");
      infoModal.id = "uploader-file-info-modal";
      infoModal.className =
        "fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden";
      infoModal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 relative">
          <button id="uploader-file-info-close" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold focus:outline-none">&times;</button>
          <h2 class="text-lg font-semibold mb-4">File Info</h2>
          <div id="uploader-file-info-content" class="space-y-2"></div>
        </div>
      `;
      document.body.appendChild(infoModal);
      document.getElementById("uploader-file-info-close").onclick =
        function () {
          infoModal.classList.add("hidden");
        };
      infoModal.addEventListener("click", function (e) {
        if (e.target === infoModal) infoModal.classList.add("hidden");
      });
    }
    const infoModal = document.getElementById("uploader-file-info-modal");
    const infoContent = document.getElementById("uploader-file-info-content");

    // Add delete confirmation modal to the DOM if not present
    if (!document.getElementById("uploader-delete-modal")) {
      const deleteModal = document.createElement("div");
      deleteModal.id = "uploader-delete-modal";
      deleteModal.className =
        "fixed inset-0 z-50 flex items-center justify-center bg-black/40 hidden";
      deleteModal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 relative">
          <button id="uploader-delete-close" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl font-bold focus:outline-none">&times;</button>
          <h2 class="text-lg font-semibold mb-4">Delete File</h2>
          <div class="mb-6">Are you sure you want to delete this file?</div>
          <div class="flex justify-end gap-2">
            <button id="uploader-delete-cancel" class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</button>
            <button id="uploader-delete-ok" class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 flex items-center gap-2"><span>OK</span><svg id="uploader-delete-spinner" class="hidden animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg></button>
          </div>
        </div>
      `;
      document.body.appendChild(deleteModal);
      document.getElementById("uploader-delete-close").onclick = function () {
        deleteModal.classList.add("hidden");
      };
      document.getElementById("uploader-delete-cancel").onclick = function () {
        deleteModal.classList.add("hidden");
      };
      deleteModal.addEventListener("click", function (e) {
        if (e.target === deleteModal) deleteModal.classList.add("hidden");
      });
    }
    const deleteModal = document.getElementById("uploader-delete-modal");
    const deleteOkBtn = document.getElementById("uploader-delete-ok");
    const deleteSpinner = document.getElementById("uploader-delete-spinner");
    let deleteFileCallback = null;
    deleteOkBtn.onclick = async function () {
      if (!deleteFileCallback) return;
      deleteOkBtn.disabled = true;
      deleteSpinner.classList.remove("hidden");
      await deleteFileCallback();
      deleteOkBtn.disabled = false;
      deleteSpinner.classList.add("hidden");
      deleteModal.classList.add("hidden");
      deleteFileCallback = null;
    };
  })();
});

// General file size converter
function formatFileSize(size) {
  if (size < 1024) return size + " B";
  if (size < 1024 * 1024) return (size / 1024).toFixed(2) + " KB";
  if (size < 1024 * 1024 * 1024) return (size / 1024 / 1024).toFixed(2) + " MB";
  return (size / 1024 / 1024 / 1024).toFixed(2) + " GB";
}
