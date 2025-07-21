import React, { useState, useCallback } from "react";

interface UploaderProps {
  onUploadSuccess: (response: any) => void;
  onUploadError?: (error: any) => void;
}

const Uploader: React.FC<UploaderProps> = ({
  onUploadSuccess,
  onUploadError,
}) => {
  const [files, setFiles] = useState<File[]>([]);
  const [isDragging, setIsDragging] = useState(false);
  const [isUploading, setIsUploading] = useState(false);

  const handleDragOver = useCallback(
    (event: React.DragEvent<HTMLDivElement>) => {
      event.preventDefault();
      setIsDragging(true);
    },
    []
  );

  const handleDragLeave = useCallback(
    (event: React.DragEvent<HTMLDivElement>) => {
      event.preventDefault();
      setIsDragging(false);
    },
    []
  );

  const handleDrop = useCallback((event: React.DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setIsDragging(false);
    const droppedFiles = Array.from(event.dataTransfer.files);
    setFiles((prevFiles) => [...prevFiles, ...droppedFiles]);
  }, []);

  const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFiles = Array.from(event.target.files || []);
    setFiles((prevFiles) => [...prevFiles, ...selectedFiles]);
  };

  const removeFile = (index: number) => {
    setFiles((prevFiles) => prevFiles.filter((_, i) => i !== index));
  };

  const handleUpload = async () => {
    setIsUploading(true);
    const formData = new FormData();
    files.forEach((file) => formData.append("files[]", file));

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
        onUploadSuccess(result);
        setFiles([]);
      } else {
        onUploadError?.(result);
      }
    } catch (error) {
      onUploadError?.(error);
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <div className="w-full max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-lg">
      <div
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        className={`flex items-center justify-center w-full px-6 py-12 border-2 border-dashed rounded-lg ${
          isDragging ? "border-blue-500 bg-blue-50" : ""
        }`}
      >
        <div className="text-center">
          <p className="mb-2 text-lg">Drag & Drop files here</p>
          <p className="mb-4 text-gray-500">or</p>
          <input
            type="file"
            multiple
            onChange={handleFileChange}
            className="hidden"
            id="file-input"
          />
          <label
            htmlFor="file-input"
            className="px-4 py-2 text-white bg-blue-500 rounded-lg cursor-pointer hover:bg-blue-600"
          >
            Select Files
          </label>
        </div>
      </div>

      {files.length > 0 && (
        <div className="mt-6">
          <h3 className="text-xl font-semibold">Selected Files</h3>
          <ul className="mt-4 space-y-2">
            {files.map((file, index) => (
              <li
                key={index}
                className="flex items-center justify-between p-2 border rounded-lg"
              >
                <span>{file.name}</span>
                <button
                  onClick={() => removeFile(index)}
                  className="text-red-500 hover:text-red-700"
                >
                  &times;
                </button>
              </li>
            ))}
          </ul>
          <div className="mt-6 text-right">
            <button
              onClick={handleUpload}
              disabled={isUploading}
              className="px-6 py-2 text-white bg-green-500 rounded-lg hover:bg-green-600"
            >
              {isUploading ? "Uploading..." : "Upload"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

export default Uploader;
