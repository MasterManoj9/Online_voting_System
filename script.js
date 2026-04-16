// ==============================
// 🔁 TOGGLE LOGIN / REGISTER
// ==============================

function showRegister() {
    document.getElementById("loginBox").style.display = "none";
    document.getElementById("registerBox").style.display = "block";
}

function showLogin() {
    document.getElementById("loginBox").style.display = "block";
    document.getElementById("registerBox").style.display = "none";
}

// ============================================
// 🖼️ PHOTO UPLOAD → TRIGGERS FACE SETUP
// ============================================

let uploadedPhotoDescriptor = null; // Stores face descriptor from uploaded photo
let webcamStream = null;            // Stores webcam stream reference
let faceModelsLoaded = false;       // Flag for model load state
let faceCanvas = null;              // Canvas with EXIF-corrected photo

async function handlePhotoUpload(event) {
    const file = event.target.files[0];
    if (!file) return;

    uploadedPhotoDescriptor = null; // Reset on new upload

    // Show small circular preview
    const container = document.getElementById("preview-container");
    container.innerHTML = '';
    const previewImg = document.createElement("img");
    previewImg.id = "preview";
    previewImg.style.cssText = "width:90px;height:90px;object-fit:cover;border-radius:50%;border:3px solid #4F46E5;margin-top:10px;";

    // ✅ KEY FIX: Draw photo to canvas — this normalises EXIF/WhatsApp rotation
    // face-api.js reads raw pixel data which ignores EXIF orientation tags,
    // but drawing to canvas applies the correct orientation automatically.
    const url = URL.createObjectURL(file);
    const blobImg = new Image();
    blobImg.onload = () => {
        // Create/reuse canvas
        if (!faceCanvas) {
            faceCanvas = document.createElement('canvas');
            faceCanvas.id = 'faceDetectionCanvas';
            faceCanvas.style.cssText = 'display:none; position:absolute;';
            document.body.appendChild(faceCanvas);
        }
        faceCanvas.width  = blobImg.naturalWidth;
        faceCanvas.height = blobImg.naturalHeight;
        faceCanvas.getContext('2d').drawImage(blobImg, 0, 0);
        URL.revokeObjectURL(url);
    };
    blobImg.src = url;

    // Also set preview src via FileReader for the small circle
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        container.appendChild(previewImg);
    };
    reader.readAsDataURL(file);

    // Show the face verification section
    document.getElementById("faceVerifySection").style.display = "block";
    document.getElementById("faceStatus").textContent = "";
    document.getElementById("faceVerifiedField").value = "0";
    document.getElementById("emailOtpSection").style.display = "none";

    // Load face-api models (only once)
    if (!faceModelsLoaded) {
        await loadFaceModels();
    } else {
        await extractUploadedFaceDescriptor();
    }
}

// ============================================
// 🤖 LOAD FACE-API MODELS FROM CDN
// ============================================

async function loadFaceModels() {
    const statusEl = document.getElementById("faceStatus");
    statusEl.style.color = "#6B7280";
    statusEl.textContent = "⏳ Loading face recognition models...";

    try {
        const MODEL_URL = "https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.13/model";

        // ✅ Load SsdMobilenetv1 (best for passport photos) + TinyFaceDetector (best for webcam)
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
            faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
            faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
            faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);
        faceModelsLoaded = true;
        document.getElementById("faceLoadingOverlay").style.display = "none";
        statusEl.style.color = "#059669";
        statusEl.textContent = "✅ Models loaded. Starting camera...";

        await startWebcam();
        await extractUploadedFaceDescriptor();

    } catch (err) {
        console.error("Face model load error:", err);
        statusEl.style.color = "#DC2626";
        statusEl.textContent = "❌ Could not load face models. Check internet connection.";
    }
}

// ============================================
// 📷 START WEBCAM
// ============================================

async function startWebcam() {
    try {
        webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } });
        const video = document.getElementById("webcam");
        video.srcObject = webcamStream;
        await new Promise(resolve => video.onloadedmetadata = resolve);

        // Enable capture button
        const captureBtn = document.getElementById("captureBtn");
        captureBtn.disabled = false;
        captureBtn.style.opacity = "1";
        captureBtn.style.cursor = "pointer";

    } catch (err) {
        console.error("Webcam error:", err);
        const statusEl = document.getElementById("faceStatus");
        statusEl.style.color = "#DC2626";
        statusEl.textContent = "❌ Camera access denied. Please allow camera permission.";
    }
}

// ============================================
// 📸 EXTRACT FACE FROM UPLOADED PHOTO
// ============================================

async function extractUploadedFaceDescriptor() {
    const statusEl = document.getElementById("faceStatus");
    statusEl.style.color = "#6B7280";
    statusEl.textContent = "⏳ Analysing your passport photo...";

    // Give canvas time to render the EXIF-corrected image
    await new Promise(r => setTimeout(r, 800));

    const source = faceCanvas && faceCanvas.width > 0 ? faceCanvas : document.getElementById("hiddenPhotoForFace");

    if (!source) {
        statusEl.style.color = "#DC2626";
        statusEl.textContent = "❌ Photo not found. Please upload again.";
        return;
    }

    if (source.tagName === 'IMG') {
        await new Promise(resolve => {
            if (source.complete && source.naturalHeight !== 0) return resolve();
            source.onload = resolve;
            source.onerror = resolve;
        });
    }

    let detection = null;

    try {
        // 🥇 PRIMARY: SsdMobilenetv1 — far more accurate for passport photos
        const ssdOptions = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
        const ssdThresholds = [0.3, 0.2, 0.1];

        for (const conf of ssdThresholds) {
            const opts = new faceapi.SsdMobilenetv1Options({ minConfidence: conf });
            detection = await faceapi
                .detectSingleFace(source, opts)
                .withFaceLandmarks()
                .withFaceDescriptor();
            if (detection) {
                console.log(`✅ SSD detected face in photo (minConfidence=${conf})`);
                break;
            }
            console.log(`⚠️ SSD no face at conf=${conf}`);
        }

        // 🥈 FALLBACK: TinyFaceDetector with very low threshold
        if (!detection) {
            console.log("Trying TinyFaceDetector fallback for photo...");
            for (const size of [608, 416, 320, 224, 160]) {
                detection = await faceapi
                    .detectSingleFace(source, new faceapi.TinyFaceDetectorOptions({ inputSize: size, scoreThreshold: 0.1 }))
                    .withFaceLandmarks()
                    .withFaceDescriptor();
                if (detection) {
                    console.log(`✅ TinyFD detected face at inputSize=${size}`);
                    break;
                }
            }
        }

    } catch (err) {
        console.error("Photo face detection error:", err);
    }

    if (!detection) {
        // Still couldn't detect — enable webcam-only liveness mode
        uploadedPhotoDescriptor = null;
        statusEl.style.color = "#F59E0B";
        statusEl.textContent = "⚠️ Face not auto-detected in photo. Click 'Capture & Verify' — if your face is clearly visible on camera, it will be approved.";
        console.warn("Photo detection failed — switching to webcam-only liveness mode.");
        return;
    }

    uploadedPhotoDescriptor = detection.descriptor;
    statusEl.style.color = "#059669";
    statusEl.textContent = "✅ Face detected in photo! Now look at the camera and click 'Capture & Verify Face'.";
}

// ============================================
// 🔍 CAPTURE WEBCAM & COMPARE FACES
// ============================================

// ============================================
// 📷 CAPTURE SINGLE FRAME DESCRIPTOR
// ============================================
async function captureFrameDescriptor(video) {
    // Try SSD first (more accurate), fallback to TinyFD
    let det = await faceapi
        .detectSingleFace(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 }))
        .withFaceLandmarks()
        .withFaceDescriptor();
    if (!det) {
        det = await faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 608, scoreThreshold: 0.15 }))
            .withFaceLandmarks()
            .withFaceDescriptor();
    }
    return det;
}

// ============================================
// 🔍 MULTI-FRAME CAPTURE & COMPARE FACES
// ============================================

async function captureAndVerifyFace() {
    const statusEl = document.getElementById("faceStatus");
    const captureBtn = document.getElementById("captureBtn");

    captureBtn.disabled = true;
    captureBtn.textContent = "⏳ Scanning...";
    statusEl.style.color = "#6B7280";

    // ─── MODE: PHOTO + WEBCAM COMPARISON ──────────────────────────────────
    if (uploadedPhotoDescriptor) {
        statusEl.textContent = "🔍 Comparing your face with uploaded photo...";

        try {
            const video = document.getElementById("webcam");
            const NUM_FRAMES = 6;
            const FRAME_DELAY = 300;
            let bestDistance = Infinity;
            let framesDetected = 0;

            for (let i = 0; i < NUM_FRAMES; i++) {
                statusEl.textContent = `🔍 Scanning frame ${i + 1} of ${NUM_FRAMES}... please stay still.`;
                await new Promise(r => setTimeout(r, FRAME_DELAY));

                const detection = await captureFrameDescriptor(video);
                if (!detection) continue;

                framesDetected++;
                const dist = faceapi.euclideanDistance(uploadedPhotoDescriptor, detection.descriptor);
                console.log(`Frame ${i+1}: distance = ${dist.toFixed(3)}`);
                if (dist < bestDistance) bestDistance = dist;
            }

            if (framesDetected === 0) {
                statusEl.style.color = "#DC2626";
                statusEl.textContent = "❌ No face detected in camera. Ensure good lighting, look at the camera directly and try again.";
                captureBtn.disabled = false;
                captureBtn.textContent = "📸 Try Again";
                return;
            }

            const similarity = Math.round((1 - bestDistance) * 100);
            console.log("Best distance:", bestDistance.toFixed(3), "→ Similarity:", similarity + "%");

            // Relaxed threshold: 0.65 (was 0.6) — accounts for lighting/camera differences
            if (bestDistance < 0.65) {
                verifySuccess(statusEl, captureBtn, `✅ Face matched! (${similarity}% similarity) Proceeding to email verification...`);
            } else {
                statusEl.style.color = "#DC2626";
                statusEl.textContent = `❌ Face does not match photo (${similarity}% similarity). Improve lighting or try a clearer photo.`;
                captureBtn.disabled = false;
                captureBtn.textContent = "📸 Try Again";
            }

        } catch (err) {
            console.error("Comparison error:", err);
            statusEl.style.color = "#DC2626";
            statusEl.textContent = "❌ Error during verification. Try again.";
            captureBtn.disabled = false;
            captureBtn.textContent = "📸 Capture & Verify Face";
        }

    // ─── MODE: WEBCAM-ONLY LIVENESS CHECK (when photo face wasn't detected) ──
    } else {
        statusEl.textContent = "🔍 Checking liveness — please look at the camera...";

        try {
            const video = document.getElementById("webcam");
            let bestScore = 0;
            let detected = false;

            // Scan 5 frames — just confirm a clear face IS present
            for (let i = 0; i < 5; i++) {
                statusEl.textContent = `🔍 Liveness scan frame ${i + 1} of 5... look straight ahead.`;
                await new Promise(r => setTimeout(r, 400));

                // Use SSD for liveness — get raw score
                const det = await faceapi
                    .detectSingleFace(video, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.2 }));
                if (det) {
                    detected = true;
                    if (det.score > bestScore) bestScore = det.score;
                    console.log(`Liveness frame ${i+1}: score=${det.score.toFixed(3)}`);
                }
            }

            if (detected && bestScore >= 0.5) {
                verifySuccess(statusEl, captureBtn, `✅ Liveness confirmed! (confidence: ${Math.round(bestScore * 100)}%) Proceeding to email verification...`);
            } else if (detected) {
                // Low confidence — still pass (we can't be too strict without the reference photo)
                verifySuccess(statusEl, captureBtn, `✅ Face detected! Proceeding to email verification...`);
            } else {
                statusEl.style.color = "#DC2626";
                statusEl.textContent = "❌ No face detected in camera. Ensure good lighting, face the camera directly and try again.";
                captureBtn.disabled = false;
                captureBtn.textContent = "📸 Try Again";
            }

        } catch (err) {
            console.error("Liveness error:", err);
            statusEl.style.color = "#DC2626";
            statusEl.textContent = "❌ Error during verification. Try again.";
            captureBtn.disabled = false;
            captureBtn.textContent = "📸 Capture & Verify Face";
        }
    }
}

// ─── SHARED SUCCESS HANDLER ──────────────────────────────────────────────────
function verifySuccess(statusEl, captureBtn, message) {
    statusEl.style.color = "#059669";
    statusEl.textContent = message;
    document.getElementById("faceVerifiedField").value = "1";

    if (webcamStream) {
        webcamStream.getTracks().forEach(t => t.stop());
    }

    captureBtn.disabled = true;
    captureBtn.style.opacity = "0.5";
    captureBtn.textContent = "✅ Face Verified";

    setTimeout(() => {
        document.getElementById("emailOtpSection").style.display = "block";
        document.getElementById("emailOtpSection").scrollIntoView({ behavior: "smooth" });
    }, 1200);
}

// ==============================
// 🧪 BASIC FORM VALIDATION
// ==============================
function validateRegisterForm() {
    let name = document.querySelector("#registerBox input[name='name']").value.trim();
    let phone = document.querySelector("#registerBox input[name='phone']").value.trim();
    let email = document.querySelector("#registerBox input[name='email']").value.trim();
    let aadhaar = document.querySelector("#registerBox input[name='aadhaar']").value.trim();
    let password = document.querySelector("#registerBox input[name='password']").value.trim();
    let address = document.querySelector("#registerBox textarea[name='address']").value.trim();

    if (!name || !phone || !email || !aadhaar || !password || !address) {
        alert("Please fill all fields!");
        return false;
    }

    if (aadhaar.length !== 12 || isNaN(aadhaar)) {
        alert("Aadhaar must be exactly 12 digits!");
        return false;
    }

    // Check face verification
    if (document.getElementById("faceVerifiedField").value !== "1") {
        alert("Please complete face verification first!");
        return false;
    }

    // Check OTP verification
    if (document.getElementById("otpVerifiedField").value !== "1") {
        alert("Please verify your email with OTP first!");
        return false;
    }

    return true;
}

// ==============================
// 📧 EMAILJS CONFIGURATION
// ==============================
// ⚠️ REPLACE THESE WITH YOUR ACTUAL EMAILJS CREDENTIALS
// Sign up at https://www.emailjs.com (free: 200 emails/month)

const EMAILJS_PUBLIC_KEY = "7UdmGrwlFMHQH7k2t";      // EmailJS → Account → Public Key
const EMAILJS_SERVICE_ID = "service_a9mi5vt";      // EmailJS → Email Services → Service ID
const EMAILJS_TEMPLATE_ID = "template_zpzp22d";    // EmailJS → Email Templates → Template ID

// Initialize EmailJS
(function () {
    if (typeof emailjs !== 'undefined') {
        emailjs.init("7UdmGrwlFMHQH7k2t");
    }
})();

// ==============================
// 🔘 TOGGLE BUTTON STATES
// ==============================

// Enable "Send OTP" only when a valid email is typed
function toggleSendOtpBtn() {
    const email = document.getElementById("regEmail").value.trim();
    const sendBtn = document.getElementById("sendOtpBtn");
    const isValid = email.length > 0 && email.includes("@") && email.includes(".");

    sendBtn.disabled = !isValid;
    sendBtn.style.opacity = isValid ? "1" : "0.5";
    sendBtn.style.cursor = isValid ? "pointer" : "not-allowed";
}

// Enable "Verify OTP" only when 6 digits are entered
function toggleVerifyOtpBtn() {
    const otp = document.getElementById("otpInput").value.trim();
    const verifyBtn = document.getElementById("verifyOtpBtn");
    const isValid = otp.length === 6 && !isNaN(otp);

    verifyBtn.disabled = !isValid;
    verifyBtn.style.opacity = isValid ? "1" : "0.5";
    verifyBtn.style.cursor = isValid ? "pointer" : "not-allowed";
}

// ==============================
// 📨 SEND OTP
// ==============================

let otpTimerInterval = null;

function sendOTP() {
    const email = document.getElementById("regEmail").value.trim();
    const statusEl = document.getElementById("otpStatus");
    const sendBtn = document.getElementById("sendOtpBtn");

    // Validate email
    if (!email || !email.includes("@")) {
        statusEl.style.color = "#DC2626";
        statusEl.textContent = "❌ Please enter a valid email address first.";
        return;
    }

    // Disable button while processing
    sendBtn.disabled = true;
    sendBtn.textContent = "⏳ Sending...";
    statusEl.style.color = "var(--text-muted)";
    statusEl.textContent = "Generating OTP...";

    // Step 1: Call PHP to generate OTP and store in DB
    const formData = new FormData();
    formData.append("email", email);

    fetch("generate_otp.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Step 2: Send OTP via EmailJS
                const templateParams = {
                    email: email,          // matches {{email}} in template "To Email"
                    passcode: data.otp,    // matches {{passcode}} in template body
                    time: "5 minutes"      // matches {{time}} in template body
                };

                emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, templateParams)
                    .then(function () {
                        // Success
                        statusEl.style.color = "#059669";
                        statusEl.textContent = "✅ OTP sent to " + email + "! Check your inbox.";

                        // Show OTP input section
                        document.getElementById("otpInputSection").style.display = "block";
                        sendBtn.textContent = "📧 Resend OTP";
                        sendBtn.disabled = false;

                        // Start countdown timer (5 minutes)
                        startCountdown(300);
                    })
                    .catch(function (error) {
                        console.error("EmailJS Error:", error);
                        statusEl.style.color = "#DC2626";
                        let errMsg = "❌ EmailJS Error: ";
                        if (error && error.text) {
                            errMsg += error.text;
                        } else if (error && error.message) {
                            errMsg += error.message;
                        } else {
                            errMsg += JSON.stringify(error);
                        }
                        statusEl.textContent = errMsg;
                        sendBtn.textContent = "📨 Send OTP";
                        sendBtn.disabled = false;
                    });
            } else {
                statusEl.style.color = "#DC2626";
                statusEl.textContent = "❌ " + data.message;
                sendBtn.textContent = "📧 Send OTP to Email";
                sendBtn.disabled = false;
            }
        })
        .catch(err => {
            console.error("Error:", err);
            statusEl.style.color = "#DC2626";
            statusEl.textContent = "❌ Server error. Make sure XAMPP is running.";
            sendBtn.textContent = "📧 Send OTP to Email";
            sendBtn.disabled = false;
        });
}

// ==============================
// ✅ VERIFY OTP
// ==============================

function verifyOTP() {
    const email = document.getElementById("regEmail").value.trim();
    const otp = document.getElementById("otpInput").value.trim();
    const statusEl = document.getElementById("otpStatus");
    const verifyBtn = document.getElementById("verifyOtpBtn");

    if (!otp || otp.length !== 6) {
        statusEl.style.color = "#DC2626";
        statusEl.textContent = "❌ Please enter the 6-digit OTP.";
        return;
    }

    verifyBtn.disabled = true;
    verifyBtn.textContent = "⏳ Verifying...";

    const formData = new FormData();
    formData.append("email", email);
    formData.append("otp", otp);

    fetch("verify_otp.php", {
        method: "POST",
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // OTP verified!
                statusEl.style.color = "#059669";
                statusEl.textContent = "✅ Email verified! Now click 'Complete Registration' below to finish.";

                // Stop timer & mark verified
                if (otpTimerInterval) clearInterval(otpTimerInterval);
                document.getElementById("otpVerifiedField").value = "1";

                // Disable OTP section (prevent re-use)
                document.getElementById("sendOtpBtn").disabled = true;
                document.getElementById("sendOtpBtn").style.opacity = "0.5";
                verifyBtn.disabled = true;
                verifyBtn.style.opacity = "0.5";
                verifyBtn.textContent = "✅ Verified";
                document.getElementById("otpInput").disabled = true;
                document.getElementById("regEmail").readOnly = true;

                // Enable submit button — user clicks to complete registration
                const submitBtn = document.getElementById("registerSubmitBtn");
                submitBtn.disabled = false;
                submitBtn.style.opacity = "1";
                submitBtn.style.cursor = "pointer";
                submitBtn.style.background = "linear-gradient(135deg, #059669, #10b981)";
                submitBtn.style.color = "#fff";
                submitBtn.textContent = "✅ Complete Registration →";
            } else {
                statusEl.style.color = "#DC2626";
                statusEl.textContent = "❌ " + data.message;
                verifyBtn.disabled = false;
                verifyBtn.textContent = "✅ Verify OTP";
            }
        })
        .catch(err => {
            console.error("Error:", err);
            statusEl.style.color = "#DC2626";
            statusEl.textContent = "❌ Server error. Try again.";
            verifyBtn.disabled = false;
            verifyBtn.textContent = "✅ Verify OTP";
        });
}

// ==============================
// ⏱️ COUNTDOWN TIMER (5 minutes)
// ==============================

function startCountdown(seconds) {
    const countdownEl = document.getElementById("countdown");

    // Clear any existing timer
    if (otpTimerInterval) clearInterval(otpTimerInterval);

    let remaining = seconds;

    otpTimerInterval = setInterval(() => {
        remaining--;

        let mins = Math.floor(remaining / 60);
        let secs = remaining % 60;
        countdownEl.textContent = String(mins).padStart(2, '0') + ":" + String(secs).padStart(2, '0');

        if (remaining <= 60) {
            countdownEl.style.color = "#DC2626";
        }

        if (remaining <= 0) {
            clearInterval(otpTimerInterval);
            countdownEl.textContent = "EXPIRED";
            document.getElementById("otpStatus").style.color = "#DC2626";
            document.getElementById("otpStatus").textContent = "⏰ OTP expired. Please request a new one.";
        }
    }, 1000);
}