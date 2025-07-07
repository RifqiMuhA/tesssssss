<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Photobooth - drillPTN</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #E3F2FD, #BBDEFB);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
        }

        .back-button:hover {
            background: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            max-width: 1200px;
            width: 100%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
        }

        .app-title {
            font-size: 28px;
            font-weight: bold;
            color: #2196F3;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .photo-count-selector {
            margin-bottom: 20px;
        }

        .photo-count-selector h3 {
            color: #2196F3;
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: bold;
        }

        .count-options {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .count-option {
            padding: 10px 20px;
            border: 2px solid #ddd;
            border-radius: 15px;
            background: white;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            min-width: 80px;
        }

        .count-option:hover,
        .count-option.active {
            background: #2196F3;
            color: white;
            border-color: #2196F3;
        }

        .message-area {
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .countdown {
            font-size: 72px;
            font-weight: bold;
            color: #2196F3;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .message {
            font-size: 28px;
            color: #2196F3;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .camera-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            justify-content: center;
        }

        #videoElement {
            width: 100%;
            max-width: 500px;
            height: 400px;
            border-radius: 15px;
            object-fit: cover;
            background: #000;
            border: 3px solid #ddd;
            /* Mirror effect for user preview */
            transform: scaleX(-1);
        }

        .take-photo-btn {
            background: #2196F3;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 15px auto 0 auto;
            display: block;
        }

        .take-photo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.4);
            background: #FFD700;
            color: #333;
        }

        .take-photo-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .photos-section {
            margin: 15px 0 0 0;
        }

        .photos-grid {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .photos-grid.grid-2x2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 300px;
            margin: 0 auto;
        }

        .photos-grid.grid-2x3 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            max-width: 300px;
            margin: 0 auto;
        }

        .photo-placeholder {
            width: 140px;
            height: 140px;
            border: 3px solid #ddd;
            border-radius: 15px;
            background: #f8f8f8;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .photo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-placeholder.filled {
            border-color: #2196F3;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 25px 0;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 20px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-edit {
            background: #2196F3;
            color: white;
        }

        .btn-retake {
            background: #FFD700;
            color: #333;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .edit-section {
            display: none;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
            margin-top: 30px;
        }

        .photo-strip-container {
            display: flex;
            justify-content: center;
        }

        .photo-strip {
            background: #000;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            position: relative;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .photo-strip.layout-3 {
            width: 260px;
        }

        .photo-strip.layout-4 {
            width: 360px;
        }

        .photo-strip.layout-6 {
            width: 360px;
        }

        .strip-photos {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .strip-photos.layout-4 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .strip-photos.layout-6 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .strip-photo {
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: #fff;
        }

        .strip-photo.size-3 {
            width: 220px;
            height: 165px;
        }

        .strip-photo.size-4 {
            width: 150px;
            height: 112px;
        }

        .strip-photo.size-6 {
            width: 150px;
            height: 112px;
        }

        .strip-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: relative;
            z-index: 1;
        }

        .strip-photo .sticker-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 5;
        }

        .sticker-item {
            position: absolute;
            font-size: 20px;
            cursor: move;
            user-select: none;
            z-index: 25;
            pointer-events: auto;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sticker-item img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            pointer-events: none;
        }

        .strip-date {
            color: white;
            font-size: 12px;
            text-align: right;
            margin-top: 15px;
            font-weight: normal;
            font-family: 'Georgia', 'Times New Roman', serif;
            font-style: italic;
        }

        .edit-controls {
            text-align: left;
        }

        .control-group {
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.7);
            padding: 15px;
            border-radius: 10px;
        }

        .control-group h3 {
            color: #2196F3;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }

        .stickers-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .sticker {
            width: 50px;
            height: 50px;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            overflow: hidden;
        }

        .sticker img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 4px;
        }

        .sticker:hover {
            border-color: #2196F3;
            transform: scale(1.1);
            background: rgba(255, 255, 255, 1);
        }

        .sticker.selected {
            background: rgba(33, 150, 243, 0.3);
            border-color: #2196F3;
            transform: scale(1.05);
        }

        .color-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .color-option {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }

        .color-option:hover {
            border-color: #333;
            transform: scale(1.1);
        }

        .color-option.active {
            border-color: #2196F3;
            transform: scale(1.1);
        }

        .custom-color-picker {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .custom-color-picker label {
            font-size: 14px;
            font-weight: bold;
            color: #2196F3;
        }

        .custom-color-picker input[type="color"] {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid #ddd;
            transition: all 0.3s ease;
        }

        .custom-color-picker input[type="color"]:hover {
            border-color: #2196F3;
            transform: scale(1.1);
        }

        .filter-options {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 15px;
            border: 2px solid #ccc;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #2196F3;
            color: white;
            border-color: #2196F3;
        }

        .final-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn-download {
            background: #FFD700;
            color: #333;
        }

        .btn-retake-final {
            background: #2196F3;
            color: white;
        }

        .hidden {
            display: none !important;
        }

        .instructions {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .back-button {
                top: 15px;
                left: 15px;
                padding: 10px 16px;
                font-size: 12px;
            }

            .edit-section {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .photos-grid {
                gap: 10px;
            }

            .photo-placeholder {
                width: 120px;
                height: 120px;
            }

            .container {
                padding: 20px;
            }

            .photo-strip.layout-3 {
                width: 220px;
            }

            .photo-strip.layout-4,
            .photo-strip.layout-6 {
                width: 280px;
            }

            .strip-photo.size-3 {
                width: 180px;
                height: 135px;
            }

            .strip-photo.size-4,
            .strip-photo.size-6 {
                width: 120px;
                height: 90px;
            }

            .countdown {
                font-size: 48px;
            }

            .message {
                font-size: 20px;
            }

            .count-options {
                gap: 10px;
            }

            .count-option {
                padding: 8px 15px;
                font-size: 14px;
                min-width: 60px;
            }

            .color-options {
                gap: 8px;
            }

            .custom-color-picker {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .app-title {
                font-size: 24px;
            }
        }
    </style>
</head>

<body>
    <!-- Back button only -->
    <a href="../games.php" class="back-button">
        ← Kembali
    </a>

    <div class="container">
        <!-- Photo Count Selector -->
        <div id="photoCountSelector" class="photo-count-selector">
            <h3>Pilih Jumlah Foto</h3>
            <div class="count-options">
                <div class="count-option active" data-count="3">3 Foto</div>
                <div class="count-option" data-count="4">4 Foto</div>
                <div class="count-option" data-count="6">6 Foto</div>
            </div>
        </div>

        <!-- Message Area - Above Camera -->
        <div class="message-area">
            <div id="countdownDisplay" class="countdown hidden"></div>
            <div id="messageDisplay" class="message hidden"></div>
        </div>

        <!-- Camera Section -->
        <div id="cameraSection" class="camera-section">
            <video id="videoElement" autoplay playsinline muted></video>
            <button id="takePhotoBtn" class="take-photo-btn">Cekrek!</button>
        </div>

        <!-- Photos Display -->
        <div id="photosSection" class="photos-section">
            <div class="photos-grid" id="photosGrid">
                <div class="photo-placeholder" id="photo1">
                    <span style="color: #999; font-size: 14px;">Foto 1</span>
                </div>
                <div class="photo-placeholder" id="photo2">
                    <span style="color: #999; font-size: 14px;">Foto 2</span>
                </div>
                <div class="photo-placeholder" id="photo3">
                    <span style="color: #999; font-size: 14px;">Foto 3</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div id="actionButtons" class="action-buttons hidden">
            <button class="btn btn-edit" id="editBtn">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708l-9.5 9.5L4 13l.646-1.646 9.5-9.5z" />
                    <path fill-rule="evenodd" d="M1 13.5V16h2.5l9.354-9.354-2.5-2.5L1 13.5zM2 14v-1.5L11.5 3.5l1.5 1.5L3.5 15H2z" />
                </svg>
                EDIT FOTO
            </button>
            <button class="btn btn-retake" id="retakeBtn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                    viewBox="0 0 16 16" style="margin-right: 6px;">
                    <path d="M11.534 7H15a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 10.5 7h1.07a4.998 4.998 0 0 0-4.47-3 5 5 0 1 0 4.47 8H11a4 4 0 1 1-3.995-5z" />
                </svg>
                FOTO ULANG
            </button>
        </div>

        <!-- Edit Section -->
        <div id="editSection" class="edit-section">
            <div class="photo-strip-container">
                <div class="photo-strip" id="photoStrip">
                    <div class="strip-photos" id="stripPhotos">
                        <!-- Photos will be inserted here -->
                    </div>
                    <div class="strip-date">Belajar dan terus belajar -DrillPTN</div>
                </div>
            </div>

            <div class="edit-controls">
                <div class="control-group">
                    <h3>Stiker</h3>
                    <div class="stickers-grid">
                        <div class="sticker" data-sticker-type="cat">
                            <img src="../assets/img/stiker/cat_1.png" alt="Cat Sticker">
                        </div>
                        <div class="sticker" data-sticker-type="panda">
                            <img src="../assets/img/stiker/panda_1.png" alt="Panda Sticker">
                        </div>
                        <div class="sticker" data-sticker-type="school">
                            <img src="../assets/img/stiker/school_1.png" alt="School Sticker">
                        </div>
                    </div>
                    <div class="instructions">Klik stiker lalu klik pada foto untuk menambahkan</div>
                </div>

                <div class="control-group">
                    <h3>Latar Belakang</h3>
                    <div class="color-options">
                        <div class="color-option active" style="background: #000" data-photo-bg="black"></div>
                        <div class="color-option" style="background: #fff; border: 1px solid #ccc" data-photo-bg="white"></div>
                        <div class="color-option" style="background: #ff69b4" data-photo-bg="pink"></div>
                        <div class="color-option" style="background: #87ceeb" data-photo-bg="lightblue"></div>
                        <div class="color-option" style="background: #98fb98" data-photo-bg="lightgreen"></div>
                        <div class="color-option" style="background: #dda0dd" data-photo-bg="plum"></div>
                    </div>
                    <div class="custom-color-picker">
                        <label for="customColorPicker">Warna Custom:</label>
                        <input type="color" id="customColorPicker" value="#000000">
                    </div>
                    <div class="instructions">Mengubah warna latar belakang frame photo strip</div>
                </div>

                <div class="control-group">
                    <h3>Filter</h3>
                    <div class="filter-options">
                        <div class="filter-btn active" data-filter="normal">Normal</div>
                        <div class="filter-btn" data-filter="sepia">Sepia</div>
                        <div class="filter-btn" data-filter="grayscale">Hitam Putih</div>
                        <div class="filter-btn" data-filter="vintage">Vintage</div>
                        <div class="filter-btn" data-filter="bright">Cerah</div>
                    </div>
                </div>

                <div class="final-buttons">
                    <button class="btn btn-download" id="downloadBtn">⬇️ Unduh Photostrip</button>
                    <button class="btn btn-retake-final" id="retakeFinalBtn">📸 Foto Ulang</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        class PhotoboothApp {
            constructor() {
                this.photos = [];
                this.currentPhotoIndex = 0;
                this.stream = null;
                this.canvas = document.createElement('canvas');
                this.ctx = this.canvas.getContext('2d');
                this.selectedSticker = null;
                this.stickerCounter = 0;
                this.currentFilter = 'normal';
                this.photoBackground = 'black';
                this.photoCount = 3; // Default photo count

                this.initElements();
                this.initCamera();
                this.bindEvents();
            }

            initElements() {
                this.videoElement = document.getElementById('videoElement');
                this.takePhotoBtn = document.getElementById('takePhotoBtn');
                this.countdownDisplay = document.getElementById('countdownDisplay');
                this.messageDisplay = document.getElementById('messageDisplay');
                this.actionButtons = document.getElementById('actionButtons');
                this.editSection = document.getElementById('editSection');
                this.cameraSection = document.getElementById('cameraSection');
                this.photosSection = document.getElementById('photosSection');
                this.photoStrip = document.getElementById('photoStrip');
                this.stripPhotos = document.getElementById('stripPhotos');
                this.photosGrid = document.getElementById('photosGrid');
                this.photoCountSelector = document.getElementById('photoCountSelector');
                this.customColorPicker = document.getElementById('customColorPicker');
            }

            async initCamera() {
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({
                        video: {
                            width: {
                                ideal: 640
                            },
                            height: {
                                ideal: 480
                            },
                            facingMode: 'user'
                        }
                    });
                    this.videoElement.srcObject = this.stream;
                } catch (err) {
                    console.error('Error accessing camera:', err);
                    this.messageDisplay.textContent = 'Akses kamera ditolak. Mohon aktifkan izin kamera.';
                    this.messageDisplay.classList.remove('hidden');
                }
            }

            bindEvents() {
                this.takePhotoBtn.addEventListener('click', () => this.startPhotoSequence());
                document.getElementById('editBtn').addEventListener('click', () => this.showEditSection());
                document.getElementById('retakeBtn').addEventListener('click', () => this.retakePhotos());
                document.getElementById('retakeFinalBtn').addEventListener('click', () => this.retakePhotos());
                document.getElementById('downloadBtn').addEventListener('click', () => this.downloadPhotostrip());

                // Photo count selector events
                document.querySelectorAll('.count-option').forEach(option => {
                    option.addEventListener('click', (e) => this.selectPhotoCount(e.target.dataset.count, e.target));
                });

                // Sticker events
                document.querySelectorAll('.sticker').forEach(sticker => {
                    sticker.addEventListener('click', (e) => this.selectSticker(e.target.closest('.sticker').dataset.stickerType, e.target.closest('.sticker')));
                });

                // Photo background events
                document.querySelectorAll('[data-photo-bg]').forEach(option => {
                    option.addEventListener('click', (e) => this.changePhotoBackground(e.target.dataset.photoBg, e.target));
                });

                // Custom color picker event
                this.customColorPicker.addEventListener('change', (e) => this.changeCustomBackground(e.target.value));

                // Filter events
                document.querySelectorAll('.filter-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => this.applyFilter(e.target.dataset.filter, e.target));
                });
            }

            selectPhotoCount(count, element) {
                // Remove active class from all options
                document.querySelectorAll('.count-option').forEach(option => option.classList.remove('active'));
                element.classList.add('active');

                this.photoCount = parseInt(count);
                this.setupPhotoGrid();
            }

            setupPhotoGrid() {
                this.photosGrid.innerHTML = '';
                this.photosGrid.className = 'photos-grid';

                if (this.photoCount === 4) {
                    this.photosGrid.classList.add('grid-2x2');
                } else if (this.photoCount === 6) {
                    this.photosGrid.classList.add('grid-2x3');
                }

                for (let i = 1; i <= this.photoCount; i++) {
                    const placeholder = document.createElement('div');
                    placeholder.className = 'photo-placeholder';
                    placeholder.id = `photo${i}`;
                    placeholder.innerHTML = `<span style="color: #999; font-size: 14px;">Foto ${i}</span>`;
                    this.photosGrid.appendChild(placeholder);
                }
            }

            async startPhotoSequence() {
                this.takePhotoBtn.disabled = true;
                this.photoCountSelector.style.display = 'none'; // Hide selector during photo taking
                this.currentPhotoIndex = 0;
                this.photos = [];
                this.clearPhotoPreviews();

                for (let i = 0; i < this.photoCount; i++) {
                    await this.takePhotoWithCountdown(i);
                }

                this.completePhotoSequence();
            }

            async takePhotoWithCountdown(index) {
                // Show countdown
                this.countdownDisplay.classList.remove('hidden');
                this.messageDisplay.classList.add('hidden');

                for (let count = 3; count > 0; count--) {
                    this.countdownDisplay.textContent = count;
                    await this.delay(1000);
                }

                this.countdownDisplay.textContent = 'SENYUM!';
                await this.delay(500);

                // Capture photo (flip horizontally to correct mirror effect)
                const photo = this.capturePhoto();
                this.photos.push(photo);
                this.displayPhoto(photo, index);

                this.countdownDisplay.classList.add('hidden');

                if (index < this.photoCount - 1) {
                    const remaining = this.photoCount - index - 1;
                    this.messageDisplay.textContent = remaining === 1 ? "Yang terakhir!" : `${remaining} foto lagi!`;
                    this.messageDisplay.classList.remove('hidden');
                    await this.delay(1500);
                    this.messageDisplay.classList.add('hidden');
                }
            }

            capturePhoto() {
                // Set canvas size to match video
                this.canvas.width = this.videoElement.videoWidth;
                this.canvas.height = this.videoElement.videoHeight;

                // Flip the image horizontally to correct mirror effect (for final photo, not preview)
                this.ctx.save();
                this.ctx.scale(-1, 1);
                this.ctx.drawImage(this.videoElement, -this.canvas.width, 0);
                this.ctx.restore();

                return this.canvas.toDataURL('image/jpeg', 0.9);
            }

            displayPhoto(photo, index) {
                const photoElement = document.getElementById(`photo${index + 1}`);
                photoElement.innerHTML = `<img src="${photo}" alt="Foto ${index + 1}">`;
                photoElement.classList.add('filled');
            }

            completePhotoSequence() {
                this.messageDisplay.textContent = "Kamu keren banget!";
                this.messageDisplay.classList.remove('hidden');
                this.actionButtons.classList.remove('hidden');
                // Hide take photo button after completion
                this.takePhotoBtn.style.display = 'none';
            }

            clearPhotoPreviews() {
                for (let i = 1; i <= this.photoCount; i++) {
                    const photoEl = document.getElementById(`photo${i}`);
                    if (photoEl) {
                        photoEl.innerHTML = `<span style="color: #999; font-size: 14px;">Foto ${i}</span>`;
                        photoEl.classList.remove('filled');
                    }
                }
            }

            showEditSection() {
                this.cameraSection.style.display = 'none';
                this.photosSection.style.display = 'none'; // Hide photos grid
                this.actionButtons.classList.add('hidden');
                this.messageDisplay.classList.add('hidden');
                this.countdownDisplay.classList.add('hidden');
                this.photoCountSelector.style.display = 'none';
                this.editSection.style.display = 'grid';
                this.createPhotostrip();
            }

            createPhotostrip() {
                this.stripPhotos.innerHTML = '';

                // Set layout classes
                this.photoStrip.className = `photo-strip layout-${this.photoCount}`;
                this.stripPhotos.className = `strip-photos layout-${this.photoCount}`;

                this.photos.forEach((photo, index) => {
                    const stripPhoto = document.createElement('div');
                    stripPhoto.className = `strip-photo size-${this.photoCount}`;
                    stripPhoto.style.background = '#fff'; // Individual photos always white background
                    stripPhoto.innerHTML = `
                        <img src="${photo}" alt="Foto Strip ${index + 1}" style="filter: ${this.getFilterStyle()}">
                    `;

                    this.stripPhotos.appendChild(stripPhoto);
                });

                // Create single sticker overlay for entire photostrip
                const photoStripContainer = this.stripPhotos.parentElement;
                let globalStickerOverlay = photoStripContainer.querySelector('.global-sticker-overlay');
                if (globalStickerOverlay) {
                    globalStickerOverlay.remove();
                }

                globalStickerOverlay = document.createElement('div');
                globalStickerOverlay.className = 'global-sticker-overlay';
                globalStickerOverlay.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 50;
                `;

                // Add click listener to global overlay
                globalStickerOverlay.addEventListener('click', (e) => this.addStickerToPhotostrip(e));
                globalStickerOverlay.style.pointerEvents = 'auto';

                photoStripContainer.appendChild(globalStickerOverlay);

                this.updateStripStyle();
            }

            selectSticker(stickerType, element) {
                // Remove previous selection
                document.querySelectorAll('.sticker').forEach(s => s.classList.remove('selected'));

                // Highlight selected sticker
                element.classList.add('selected');
                this.selectedSticker = stickerType;
            }

            addStickerToPhotostrip(event) {
                if (!this.selectedSticker) return;

                // Prevent event if clicking on existing sticker
                if (event.target.closest('.sticker-item')) return;

                const globalOverlay = event.currentTarget;
                const rect = globalOverlay.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;

                // Get random sticker image (1 from 5)
                const randomNum = Math.floor(Math.random() * 5) + 1;
                const stickerImageUrl = `../assets/img/stiker/${this.selectedSticker}_${randomNum}.png`;

                const stickerEl = document.createElement('div');
                stickerEl.className = 'sticker-item';
                stickerEl.style.left = `${x}px`;
                stickerEl.style.top = `${y}px`;
                stickerEl.style.transform = 'translate(-50%, -50%)';
                stickerEl.style.position = 'absolute';
                stickerEl.style.zIndex = '60';

                // Create img element with error handling
                const imgEl = document.createElement('img');
                imgEl.src = stickerImageUrl;
                imgEl.alt = `Sticker ${randomNum}`;
                imgEl.style.cssText = 'width: 40px; height: 40px; object-fit: contain; pointer-events: none;';
                imgEl.onerror = () => {
                    // Fallback to first image if random fails
                    imgEl.src = `../assets/img/stiker/${this.selectedSticker}_1.png`;
                };

                stickerEl.appendChild(imgEl);

                // Add double-click to remove
                stickerEl.addEventListener('dblclick', (e) => {
                    e.stopPropagation();
                    stickerEl.remove();
                });

                // Make draggable with boundary constraints
                this.makeDraggableWithConstraints(stickerEl, globalOverlay);

                globalOverlay.appendChild(stickerEl);
            }

            makeDraggableWithConstraints(element, container) {
                let isDragging = false;
                let startX, startY, startLeft, startTop;

                element.addEventListener('mousedown', (e) => {
                    isDragging = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startLeft = parseInt(element.style.left);
                    startTop = parseInt(element.style.top);
                    e.preventDefault();
                    e.stopPropagation();
                });

                document.addEventListener('mousemove', (e) => {
                    if (!isDragging) return;

                    const deltaX = e.clientX - startX;
                    const deltaY = e.clientY - startY;

                    let newLeft = startLeft + deltaX;
                    let newTop = startTop + deltaY;

                    // Constrain to container bounds with more precise boundaries
                    const containerRect = container.getBoundingClientRect();
                    const elementSize = 40; // sticker size

                    // Allow stickers to reach the very edges
                    newLeft = Math.max(elementSize / 2, Math.min(containerRect.width - elementSize / 2, newLeft));
                    newTop = Math.max(elementSize / 2, Math.min(containerRect.height - elementSize / 2, newTop));

                    element.style.left = `${newLeft}px`;
                    element.style.top = `${newTop}px`;
                });

                document.addEventListener('mouseup', () => {
                    isDragging = false;
                });
            }

            updateStripStyle() {
                const backgrounds = {
                    'black': '#000',
                    'white': '#fff',
                    'pink': '#ff69b4',
                    'lightblue': '#87ceeb',
                    'lightgreen': '#98fb98',
                    'plum': '#dda0dd'
                };

                // Use custom color if it's a hex value, otherwise use predefined colors
                const bgColor = this.photoBackground.startsWith('#') ? this.photoBackground : backgrounds[this.photoBackground] || '#000';
                this.photoStrip.style.background = bgColor;

                // Update date color based on background
                const dateEl = this.photoStrip.querySelector('.strip-date');
                // Calculate if color is light or dark to determine text color
                const isLightColor = this.isLightColor(bgColor);
                dateEl.style.color = isLightColor ? '#000' : '#fff';
            }

            isLightColor(color) {
                // Convert hex to RGB and calculate brightness
                const hex = color.replace('#', '');
                const r = parseInt(hex.substr(0, 2), 16);
                const g = parseInt(hex.substr(2, 2), 16);
                const b = parseInt(hex.substr(4, 2), 16);

                // Calculate brightness using luminance formula
                const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
                return brightness > 128;
            }

            getFilterStyle() {
                const filters = {
                    'normal': 'none',
                    'sepia': 'sepia(100%)',
                    'grayscale': 'grayscale(100%)',
                    'vintage': 'sepia(50%) contrast(120%) brightness(90%)',
                    'bright': 'brightness(120%) contrast(110%)'
                };
                return filters[this.currentFilter] || 'none';
            }

            changePhotoBackground(bg, element) {
                // Remove active class from all preset color options
                document.querySelectorAll('[data-photo-bg]').forEach(option => option.classList.remove('active'));
                element.classList.add('active');

                this.photoBackground = bg;
                this.updateStripStyle();
            }

            changeCustomBackground(color) {
                // Remove active class from all preset color options
                document.querySelectorAll('[data-photo-bg]').forEach(option => option.classList.remove('active'));

                this.photoBackground = color;
                this.updateStripStyle();
            }

            applyFilter(filter, button) {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                this.currentFilter = filter;

                document.querySelectorAll('.strip-photo img').forEach(img => {
                    img.style.filter = this.getFilterStyle();
                });
            }

            async downloadPhotostrip() {
                const stripCanvas = document.createElement('canvas');
                const stripCtx = stripCanvas.getContext('2d');

                // Set higher resolution for better quality
                const scaleFactor = 2.5; // Reduced from 3 to 2.5 for better proportion matching

                // Set canvas dimensions based on photo count (adjusted for narrower width)
                let canvasWidth, canvasHeight;
                if (this.photoCount === 3) {
                    canvasWidth = 320 * scaleFactor; // Reduced from 350 to 280
                    canvasHeight = 680 * scaleFactor;
                } else if (this.photoCount === 4) {
                    canvasWidth = 450 * scaleFactor; // Reduced from 500 to 400
                    canvasHeight = 500 * scaleFactor;
                } else if (this.photoCount === 6) {
                    canvasWidth = 450 * scaleFactor; // Reduced from 500 to 400
                    canvasHeight = 700 * scaleFactor;
                }

                stripCanvas.width = canvasWidth;
                stripCanvas.height = canvasHeight;

                // Enable high quality rendering
                stripCtx.imageSmoothingEnabled = true;
                stripCtx.imageSmoothingQuality = 'high';

                // Background color for strip frame (uses photoBackground)
                const backgrounds = {
                    'black': '#000',
                    'white': '#fff',
                    'pink': '#ff69b4',
                    'lightblue': '#87ceeb',
                    'lightgreen': '#98fb98',
                    'plum': '#dda0dd'
                };

                // Use custom color if it's a hex value, otherwise use predefined colors
                const bgColor = this.photoBackground.startsWith('#') ? this.photoBackground : backgrounds[this.photoBackground] || '#000';

                // Create rounded rectangle background
                stripCtx.fillStyle = bgColor;
                this.roundRect(stripCtx, 0, 0, canvasWidth, canvasHeight, 12 * scaleFactor); // Reduced from 15 to 12
                stripCtx.fill();

                // Calculate photo positions with proper spacing (scaled)
                const positions = this.getPhotoPositionsWithSpacing(canvasWidth, canvasHeight, scaleFactor);

                // Load and draw photos with full coverage (no gaps)
                const imagePromises = this.photos.map((photo, index) => {
                    return new Promise(async (resolve) => {
                        const img = new Image();
                        img.onload = async () => {
                            const pos = positions[index];

                            // Create clipping path for rounded corners
                            stripCtx.save();
                            this.roundRect(stripCtx, pos.x, pos.y, pos.width, pos.height, 6 * scaleFactor); // Reduced from 8 to 6
                            stripCtx.clip();

                            // Calculate scaling to fill entire area (like object-fit: cover)
                            const scale = Math.max(pos.width / img.width, pos.height / img.height);
                            const scaledWidth = img.width * scale;
                            const scaledHeight = img.height * scale;

                            // Center the scaled image
                            const offsetX = (pos.width - scaledWidth) / 2;
                            const offsetY = (pos.height - scaledHeight) / 2;

                            // Apply filter if needed
                            if (this.currentFilter !== 'normal') {
                                stripCtx.filter = this.getFilterStyle();
                            }

                            // Draw image to fill entire area
                            stripCtx.drawImage(img, pos.x + offsetX, pos.y + offsetY, scaledWidth, scaledHeight);
                            stripCtx.filter = 'none';
                            stripCtx.restore();

                            resolve();
                        };
                        img.src = photo;
                    });
                });

                await Promise.all(imagePromises);

                // Draw stickers from global overlay with precise 1:1 coordinate mapping
                const globalOverlay = document.querySelector('.global-sticker-overlay');
                if (globalOverlay) {
                    const stickers = globalOverlay.querySelectorAll('.sticker-item');

                    // Get exact overlay dimensions
                    const overlayRect = globalOverlay.getBoundingClientRect();

                    const stickerPromises = Array.from(stickers).map(sticker => {
                        return new Promise((stickerResolve) => {
                            const stickerImg = sticker.querySelector('img');
                            if (stickerImg && stickerImg.complete && stickerImg.naturalWidth > 0) {
                                // Get sticker position relative to overlay (0 to overlayWidth/Height)
                                const stickerX = parseInt(sticker.style.left);
                                const stickerY = parseInt(sticker.style.top);

                                // Map directly to canvas with same proportions
                                const canvasMargin = 25 * scaleFactor;
                                const canvasContentWidth = canvasWidth - (canvasMargin * 2);
                                const canvasContentHeight = canvasHeight - (canvasMargin * 2) - (40 * scaleFactor);

                                // Direct proportional mapping
                                const xRatio = stickerX / overlayRect.width;
                                const yRatio = stickerY / overlayRect.height;

                                const canvasStickerX = canvasMargin + (xRatio * canvasContentWidth);
                                const canvasStickerY = canvasMargin + (yRatio * canvasContentHeight);

                                const stickerSize = 60 * scaleFactor;

                                // Calculate proper size maintaining aspect ratio
                                const imgAspect = stickerImg.naturalWidth / stickerImg.naturalHeight;
                                let drawWidth = stickerSize;
                                let drawHeight = stickerSize;

                                if (imgAspect > 1) {
                                    drawHeight = stickerSize / imgAspect;
                                } else {
                                    drawWidth = stickerSize * imgAspect;
                                }

                                stripCtx.drawImage(stickerImg,
                                    canvasStickerX - (drawWidth / 2),
                                    canvasStickerY - (drawHeight / 2),
                                    drawWidth,
                                    drawHeight);
                                stickerResolve();
                            } else if (stickerImg) {
                                // Wait for image to load
                                const img = new Image();
                                img.onload = () => {
                                    const stickerX = parseInt(sticker.style.left);
                                    const stickerY = parseInt(sticker.style.top);

                                    const canvasMargin = 25 * scaleFactor;
                                    const canvasContentWidth = canvasWidth - (canvasMargin * 2);
                                    const canvasContentHeight = canvasHeight - (canvasMargin * 2) - (40 * scaleFactor);

                                    const xRatio = stickerX / overlayRect.width;
                                    const yRatio = stickerY / overlayRect.height;

                                    const canvasStickerX = canvasMargin + (xRatio * canvasContentWidth);
                                    const canvasStickerY = canvasMargin + (yRatio * canvasContentHeight);

                                    const stickerSize = 60 * scaleFactor;

                                    // Calculate proper size maintaining aspect ratio
                                    const imgAspect = img.width / img.height;
                                    let drawWidth = stickerSize;
                                    let drawHeight = stickerSize;

                                    if (imgAspect > 1) {
                                        drawHeight = stickerSize / imgAspect;
                                    } else {
                                        drawWidth = stickerSize * imgAspect;
                                    }

                                    stripCtx.drawImage(img,
                                        canvasStickerX - (drawWidth / 2),
                                        canvasStickerY - (drawHeight / 2),
                                        drawWidth,
                                        drawHeight);
                                    stickerResolve();
                                };
                                img.onerror = () => stickerResolve();
                                img.src = stickerImg.src;
                            } else {
                                stickerResolve();
                            }
                        });
                    });

                    await Promise.all(stickerPromises);
                }

                // Add text with elegant styling (scaled)
                const isLightColor = this.isLightColor(bgColor);
                stripCtx.fillStyle = isLightColor ? '#000' : '#fff';
                stripCtx.font = `italic ${12 * scaleFactor}px Georgia, serif`; // Reduced from 14 to 12
                stripCtx.textAlign = 'right';
                stripCtx.fillText('Belajar dan terus belajar -DrillPTN', canvasWidth - (25 * scaleFactor), canvasHeight - (15 * scaleFactor)); // Updated margins

                // Download at original resolution (canvas will be downscaled for display)
                const link = document.createElement('a');
                link.download = 'photostrip.png';
                link.href = stripCanvas.toDataURL('image/png');
                link.click();
            }

            roundRect(ctx, x, y, width, height, radius) {
                ctx.beginPath();
                ctx.moveTo(x + radius, y);
                ctx.lineTo(x + width - radius, y);
                ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
                ctx.lineTo(x + width, y + height - radius);
                ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
                ctx.lineTo(x + radius, y + height);
                ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
                ctx.lineTo(x, y + radius);
                ctx.quadraticCurveTo(x, y, x + radius, y);
                ctx.closePath();
            }

            getPhotoPositionsWithSpacing(canvasWidth, canvasHeight, scaleFactor = 1) {
                const margin = 20 * scaleFactor; // Reduced from 25 for narrower layout
                const spacing = 8 * scaleFactor; // Reduced from 10 for narrower layout
                const positions = [];

                if (this.photoCount === 3) {
                    // Vertical layout with spacing
                    const photoWidth = canvasWidth - (margin * 2);
                    const photoHeight = (canvasHeight - (margin * 2) - (spacing * 2) - (40 * scaleFactor)) / 3;

                    for (let i = 0; i < 3; i++) {
                        positions.push({
                            x: margin,
                            y: margin + (i * (photoHeight + spacing)),
                            width: photoWidth,
                            height: photoHeight
                        });
                    }
                } else if (this.photoCount === 4) {
                    // 2x2 grid with spacing
                    const photoWidth = (canvasWidth - (margin * 2) - spacing) / 2;
                    const photoHeight = (canvasHeight - (margin * 2) - spacing - (40 * scaleFactor)) / 2;

                    for (let i = 0; i < 4; i++) {
                        const row = Math.floor(i / 2);
                        const col = i % 2;
                        positions.push({
                            x: margin + (col * (photoWidth + spacing)),
                            y: margin + (row * (photoHeight + spacing)),
                            width: photoWidth,
                            height: photoHeight
                        });
                    }
                } else if (this.photoCount === 6) {
                    // 2x3 grid with spacing
                    const photoWidth = (canvasWidth - (margin * 2) - spacing) / 2;
                    const photoHeight = (canvasHeight - (margin * 2) - (spacing * 2) - (40 * scaleFactor)) / 3;

                    for (let i = 0; i < 6; i++) {
                        const row = Math.floor(i / 2);
                        const col = i % 2;
                        positions.push({
                            x: margin + (col * (photoWidth + spacing)),
                            y: margin + (row * (photoHeight + spacing)),
                            width: photoWidth,
                            height: photoHeight
                        });
                    }
                }

                return positions;
            }

            retakePhotos() {
                this.photos = [];
                this.selectedSticker = null;
                this.clearPhotoPreviews();
                this.setupPhotoGrid(); // Reset photo grid
                this.cameraSection.style.display = 'flex'; // Use flex to maintain proper alignment
                this.photosSection.style.display = 'block'; // Show photos grid again
                this.photoCountSelector.style.display = 'block'; // Show selector again
                this.editSection.style.display = 'none';
                this.actionButtons.classList.add('hidden');
                this.messageDisplay.classList.add('hidden');
                this.countdownDisplay.classList.add('hidden');
                // Show take photo button again
                this.takePhotoBtn.style.display = 'block';
                this.takePhotoBtn.disabled = false;

                // Reset sticker selection
                document.querySelectorAll('.sticker').forEach(s => s.classList.remove('selected'));

                // Clear any existing global sticker overlay
                const globalOverlay = document.querySelector('.global-sticker-overlay');
                if (globalOverlay) {
                    globalOverlay.remove();
                }
            }

            delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }
        }

        // Initialize the app when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            new PhotoboothApp();
        });
    </script>
</body>

</html>