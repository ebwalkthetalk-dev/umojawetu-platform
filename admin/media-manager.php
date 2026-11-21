<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-new.php');
    exit;
}
require_once __DIR__ . '/../config/config.php';

$isLocal = Environment::isLocal();
$apiBaseUrl = $isLocal ? '/api' : 'https://event.umojawetu.com/api';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .upload-dropzone {
            border: 2px dashed #cbd5e1;
            transition: all 0.3s ease;
        }
        .upload-dropzone.dragover {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        .media-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 20px;
            border-radius: 8px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .notification-success { background: #10b981; color: white; }
        .notification-error { background: #ef4444; color: white; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="p-4 border-b border-gray-800">
                <h2 class="text-xl font-bold">Admin Panel</h2>
                <p class="text-xs text-gray-400 mt-1">Umojawetu Platform</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ </span> Dashboard
                </a>
                <a href="events.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ“…</span> Events
                </a>
                <a href="attendees.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ‘¥</span> Attendees
                </a>
                <a href="requests.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ’°</span> Financial Requests
                </a>
                <a href="media-manager.php" class="flex items-center gap-3 px-4 py-2 rounded-lg bg-red-600">
                    <span>ðŸ–¼ï¸</span> Gallery
                </a>
                <a href="payments.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ’³</span> Payments
                </a>
                <a href="email-templates.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ“§</span> Email Templates
                </a>
                <a href="admins.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ‘‘</span> Admin Management
                </a>
                <a href="analytics.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800">
                    <span>ðŸ“Š</span> Analytics
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <div class="text-sm text-gray-400 mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-gray-800 text-red-400">
                    <span>ðŸšª</span> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-2">ðŸ–¼ï¸ Media Gallery Manager</h1>
                <p class="text-gray-600 mb-6">Upload and manage photos and videos for the gallery</p>

                <!-- Upload Section -->
                <div class="bg-white rounded-xl shadow-md mb-8 p-6">
                    <h2 class="text-xl font-bold mb-4">ðŸ“¤ Upload Media</h2>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                        <select id="uploadYear" class="border rounded-lg px-3 py-2">
                            <option value="2026">2026 - This Year</option>
                            <option value="2025" selected>2025 - Last Year</option>
                        </select>
                    </div>

                    <div id="dropzone" class="upload-dropzone border-2 border-dashed rounded-xl p-12 text-center cursor-pointer hover:bg-gray-50 transition">
                        <i class="text-5xl text-gray-400 mb-3">ðŸ“</i>
                        <p class="text-gray-600">Drag & drop images or videos here</p>
                        <p class="text-gray-400 text-sm mt-1">or click to select files</p>
                        <input type="file" id="fileInput" class="hidden" multiple accept="image/*,video/mp4,video/webm">
                    </div>

                    <div id="uploadProgress" class="mt-4 hidden">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div id="progressBar" class="bg-red-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        <p id="uploadStatus" class="text-sm text-gray-500 mt-2"></p>
                    </div>
                </div>

                <!-- Media Grid -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold mb-4">ðŸ“¸ Media Library</h2>
                    <div id="mediaGrid" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 min-h-[200px]">
                        <div class="col-span-full text-center text-gray-500 py-10">Loading media...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentEditId = null;
        
        // Escape HTML special characters for safe use in attributes
        function escapeQuotes(str) {
            if(!str) return '';
            return str.replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        
        // Show notification
        function showNotification(msg, type) {
            let n = document.createElement('div');
            n.className = `notification notification-${type}`;
            n.innerHTML = (type === 'success' ? 'âœ… ' : 'âŒ ') + msg;
            document.body.appendChild(n);
            setTimeout(() => {
                n.style.opacity = '0';
                n.style.transition = 'opacity 0.5s';
                setTimeout(() => n.remove(), 500);
            }, 3000);
        }
        
        // Edit Media (caption)
        function editMedia(id, currentCaption) {
            let modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 max-w-md w-full m-4">
                    <h3 class="text-xl font-bold mb-4">âœï¸ Edit Media Caption</h3>
                    <input type="text" id="captionInput" value="${currentCaption}" class="w-full border rounded-lg p-2 mb-4" placeholder="Enter caption...">
                    <div class="flex gap-2">
                        <button onclick="saveCaptions('${id}')" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">ðŸ’¾ Save</button>
                        <button onclick="this.closest('.fixed').remove()" class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">âœ• Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            document.getElementById('captionInput').focus();
        }
        
        // Save caption
        async function saveCaptions(id) {
            const caption = document.getElementById('captionInput').value;
            const formData = new FormData();
            formData.append('id', id);
            formData.append('caption', caption);
            
            try {
                const response = await fetch('<?php echo $apiBaseUrl; ?>/media/media-update.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Caption updated!', 'success');
                    loadMedia();
                    document.querySelector('.fixed.bg-black\\/50')?.remove();
                } else {
                    showNotification('Error updating caption', 'error');
                }
            } catch (error) {
                showNotification('Error updating caption', 'error');
            }
        }
        
        // Delete Media
        function deleteMedia(id) {
            if(!confirm('ðŸ—‘ï¸ Delete this media? This cannot be undone.')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('<?php echo $apiBaseUrl; ?>/media/media-delete.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification('Media deleted!', 'success');
                    loadMedia();
                } else {
                    showNotification('Error deleting media', 'error');
                }
            })
            .catch(err => {
                showNotification('Error deleting media', 'error');
            });
        }
        
        async function loadMedia() {
            const grid = document.getElementById('mediaGrid');
            grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">Loading...</div>';
            
            try {
                const response = await fetch('<?php echo $apiBaseUrl; ?>/media/media-list.php?year=all');
                const data = await response.json();
                const mediaItems = data.media || [];
                
                if (mediaItems.length === 0) {
                    grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">No media uploaded yet.</div>';
                    return;
                }
                
                grid.innerHTML = mediaItems.map(item => `
                    <div class="media-item bg-gray-100 rounded-lg overflow-hidden shadow hover:shadow-lg transition group relative">
                        ${item.media_type === 'image' 
                            ? `<img src="${item.storage_path}" class="w-full h-32 object-cover" onerror="this.src='https://placehold.co/200x200/f0f0f0/cccccc?text=Error'">`
                            : `<div class="w-full h-32 bg-gray-800 flex items-center justify-center">
                                <span class="text-2xl">ðŸŽ¥</span>
                               </div>`
                        }
                        <!-- Action Buttons (Hidden on Hover) -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
                            <button onclick="editMedia('${item.id}', '${escapeQuotes(item.caption)}')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">âœï¸ Edit</button>
                            <button onclick="deleteMedia('${item.id}')" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">ðŸ—‘ï¸ Delete</button>
                        </div>
                        <div class="p-2 group-hover:hidden">
                            <p class="text-xs text-gray-600 truncate">${item.caption || 'No caption'}</p>
                            <p class="text-xs text-gray-400 mt-1">ðŸ“… ${item.year}</p>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                console.error('Error:', error);
                grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-10">Failed to load media</div>';
            }
        }
        
        // Upload functionality
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('fileInput');
        
        dropzone.onclick = () => fileInput.click();
        
        dropzone.ondragover = (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        };
        
        dropzone.ondragleave = () => {
            dropzone.classList.remove('dragover');
        };
        
        dropzone.ondrop = async (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            const files = Array.from(e.dataTransfer.files);
            await uploadFiles(files);
        };
        
        fileInput.onchange = async (e) => {
            const files = Array.from(e.target.files);
            await uploadFiles(files);
            fileInput.value = '';
        };
        
        async function uploadFiles(files) {
            const year = document.getElementById('uploadYear').value;
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const uploadStatus = document.getElementById('uploadStatus');
            
            progressDiv.classList.remove('hidden');
            let completed = 0;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const formData = new FormData();
                formData.append('file', file);
                formData.append('year', year);
                
                uploadStatus.innerHTML = `Uploading ${i+1} of ${files.length}: ${file.name}`;
                
                try {
                    const response = await fetch('<?php echo $apiBaseUrl; ?>/media/media-upload.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        uploadStatus.innerHTML = `âœ… Success: ${file.name}`;
                    } else {
                        uploadStatus.innerHTML = `âŒ Failed: ${file.name} - ${result.error}`;
                    }
                } catch (error) {
                    uploadStatus.innerHTML = `âŒ Error: ${file.name}`;
                }
                
                completed++;
                const percent = (completed / files.length) * 100;
                progressBar.style.width = `${percent}%`;
            }
            
            uploadStatus.innerHTML = `âœ… Upload complete! ${files.length} file(s) processed.`;
            loadMedia();
            
            setTimeout(() => {
                progressDiv.classList.add('hidden');
                progressBar.style.width = '0%';
            }, 3000);
        }
        
        loadMedia();
    </script>
</body>
</html>