<?php
// Video Gallery - Simplified Working Version
require_once __DIR__ . '/../config/config.php';

$isLocal = Environment::isLocal();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery | <?php echo $site_name; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .video-card {
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            cursor: pointer;
        }
        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            margin-top: 5%;
        }
        .close {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            cursor: pointer;
        }
        .env-badge {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: <?php echo $isLocal ? '#28a745' : '#dc3545'; ?>;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            z-index: 999;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    
    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">🎥 Video Gallery</h1>
            <p class="text-xl text-white/80">Watch highlights from our community events</p>
        </div>

        <div id="gallery-container" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Videos will load here -->
            <div class="text-center text-white py-20 col-span-full">Loading videos...</div>
        </div>
    </div>

    <div id="videoModal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <video id="modalVideo" class="modal-content" controls>
            <source id="videoSource" src="" type="video/mp4">
        </video>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?> Environment</div>

    <script>
        // Hardcoded videos that work (from your direct URL test)
        const videos = [
            {
                file: "69d509568b910_1775569238.mp4",
                caption: "Event Video 2025",
                year: 2025
            },
            {
                file: "69d50c0e0d44c_1775569934.mp4",
                caption: "Event Video 2024",
                year: 2024
            }
        ];
        
        function loadVideos() {
            const container = document.getElementById('gallery-container');
            
            if (videos.length === 0) {
                container.innerHTML = '<div class="text-center text-white col-span-full py-20">No videos available.</div>';
                return;
            }
            
            container.innerHTML = videos.map(video => {
                const videoUrl = `http://localhost/umojawetu-platform/storage/uploads/media/videos/${video.file}`;
                
                return `
                    <div class="video-card bg-white rounded-xl overflow-hidden shadow-lg">
                        <div class="relative cursor-pointer" onclick="openVideoModal('${videoUrl}')">
                            <div class="w-full h-48 bg-gradient-to-r from-purple-700 to-red-700 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white opacity-70" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center opacity-0 hover:opacity-100 transition">
                                <div class="w-16 h-16 bg-red-600 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="white" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-bold text-lg text-gray-800">${video.caption}</h3>
                            <p class="text-sm text-gray-500 mt-2">📅 ${video.year}</p>
                            <p class="text-sm text-gray-500">🎥 MP4 Video</p>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function openVideoModal(videoUrl) {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');
            const source = document.getElementById('videoSource');
            source.src = videoUrl;
            video.load();
            modal.style.display = 'block';
        }
        
        function closeModal() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');
            video.pause();
            modal.style.display = 'none';
        }
        
        loadVideos();
    </script>
</body>
</html>