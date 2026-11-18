<?php
// Video Gallery - Dynamic API Version
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 30px -10px rgba(0,0,0,0.3);
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
        .filter-btn.active {
            background-color: #dc2626 !important;
            color: white !important;
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
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .play-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .video-container:hover .play-overlay {
            opacity: 1;
        }
        .play-icon {
            width: 60px;
            height: 60px;
            background: rgba(231, 76, 60, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .play-icon svg {
            width: 30px;
            height: 30px;
            fill: white;
            margin-left: 4px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-600 via-indigo-700 to-blue-800 min-h-screen">
    
    <div class="container mx-auto px-4 py-12 max-w-6xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">🎥 Video Gallery</h1>
            <p class="text-xl text-white/80">Watch highlights from our community events</p>
        </div>

        <div class="flex flex-wrap justify-center gap-3 mb-8">
            <button data-year="all" class="filter-btn active px-4 py-2 bg-white/20 text-white rounded-lg transition">🎬 All Videos</button>
            <button data-year="2026" class="filter-btn px-4 py-2 bg-white/20 text-white rounded-lg transition">📅 2026</button>
            <button data-year="2025" class="filter-btn px-4 py-2 bg-orange-500/80 text-white rounded-lg transition">⭐ 2025</button>
        </div>

        <div id="gallery-container" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="text-center text-white py-20 col-span-full">
                <div class="spinner"></div>
                <p class="mt-4">Loading videos...</p>
            </div>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="modal" onclick="closeModal()">
        <span class="close">&times;</span>
        <video id="modalVideo" class="modal-content" controls>
            <source id="videoSource" src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <div class="env-badge"><?php echo ucfirst(Environment::detect()); ?> Environment</div>

    <script>
        let currentYear = 'all';
        
        async function loadVideos() {
            const container = document.getElementById('gallery-container');
            
            container.innerHTML = `<div class="text-center text-white py-20 col-span-full"><div class="spinner"></div><p class="mt-4">Loading videos...</p></div>`;
            
            try {
                const response = await fetch(`/umojawetu-platform/api/media/media-videos.php?year=${currentYear}`);
                const data = await response.json();
                
                if (!data.success || data.videos.length === 0) {
                    container.innerHTML = '<div class="text-center text-white col-span-full py-20">🎬 No videos found. Check back soon!</div>';
                    return;
                }
                
                container.innerHTML = data.videos.map(video => `
                    <div class="video-card bg-white rounded-xl overflow-hidden shadow-lg">
                        <div class="video-container relative cursor-pointer" onclick="openVideoModal('${video.url}')">
                            <div class="w-full h-48 bg-gradient-to-r from-purple-700 to-red-700 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white opacity-50" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M8 5v14l11-7z"/>
                                </svg>
                            </div>
                            <div class="play-overlay">
                                <div class="play-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-lg text-gray-800">${escapeHtml(video.caption)}</h3>
                                <span class="text-xs ${video.year == 2025 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'} px-2 py-1 rounded-full">
                                    📅 ${video.year}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">🎥 Video</p>
                        </div>
                    </div>
                `).join('');
                
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<div class="text-center text-white col-span-full py-20">Failed to load videos. Please refresh.</div>';
            }
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
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, m => m === '&' ? '&amp;' : m === '<' ? '&lt;' : '&gt;');
        }
        
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentYear = this.dataset.year;
                loadVideos();
            });
        });
        
        loadVideos();
    </script>
</body>
</html>