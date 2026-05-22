// filename: js/accessibility.js
let fontSize = 18;
let synth = window.speechSynthesis;
let utterance = null;
let textNodesData = [];
let currentTextIndex = 0;
let isSpeaking = false;
let isPaused = false;

// Safe wrapper ensures script handles execute AFTER the browser finishes rendering layout HTML
window.addEventListener("load", () => {
  document.body.classList.add("accessible-text");
  loadSettings();
  prepareTextContent(); // Isolate paragraph structures for voice focus calculations
});

/* --- FONT SIZE CONTROLS (TARGETED REVISION) --- */
function increaseFont() {
  fontSize += 2;
  if (fontSize > 40) fontSize = 40; // Prevent text from expanding infinitely
  applyFont();
}

function decreaseFont() {
  fontSize -= 2;
  if (fontSize < 12) fontSize = 12; // Prevent text from becoming unreadably small
  applyFont();
}

function resetFont() {
  fontSize = 18;
  applyFont();
}

function applyFont() {
  // 1. Apply font scale directly onto the base body element
  document.body.style.fontSize = fontSize + "px";
  
  // 2. Locate and force sizing updates directly onto text nodes to override layout stylesheet restrictions
  const textParagraphs = document.querySelectorAll(".chapter-text p, .chapter-text, .reader-container p, .chapter-section p");
  if (textParagraphs.length > 0) {
    textParagraphs.forEach(el => {
      el.style.setProperty("font-size", fontSize + "px", "important");
    });
  }
  saveSettings();
}

function toggleContrast() {
  document.body.classList.toggle("high-contrast");
  saveSettings();
}

function toggleDyslexia() {
  document.body.classList.toggle("dyslexia-font");
  saveSettings();
}

function saveSettings() {
  const settings = {
    fontSize: fontSize,
    contrast: document.body.classList.contains("high-contrast"),
    dyslexia: document.body.classList.contains("dyslexia-font")
  };
  localStorage.setItem("accessibility_settings", JSON.stringify(settings));
}

function loadSettings() {
  const saved = localStorage.getItem("accessibility_settings");
  if (!saved) return;
  const settings = JSON.parse(saved);
  fontSize = settings.fontSize || 18;
  applyFont();
  if (settings.contrast) document.body.classList.add("high-contrast");
  if (settings.dyslexia) document.body.classList.add("dyslexia-font");
}

/* --- MAIN SMART AUDIO READING MODULE FEATURE ENGINE --- */
function prepareTextContent() {
  const contentArea = document.querySelector(".chapter-text") || document.querySelector(".reader-container");
  if (!contentArea) return;

  textNodesData = [];
  const paragraphs = contentArea.querySelectorAll("p");
  
  if (paragraphs.length > 0) {
    paragraphs.forEach((p) => {
      if (p.innerText.trim().length > 0) {
        textNodesData.push({
          element: p,
          text: p.innerText.trim()
        });
      }
    });
  } else {
    textNodesData.push({
      element: contentArea,
      text: contentArea.innerText.trim()
    });
  }
}

function startAudioReading() {
  if (textNodesData.length === 0) prepareTextContent();
  if (textNodesData.length === 0) return;

  if (synth.paused && isPaused) {
    synth.resume();
    isPaused = false;
    isSpeaking = true;
    return;
  }

  stopAudioReading();
  isSpeaking = true;
  speakSegment(currentTextIndex);
}

function speakSegment(index) {
  if (index >= textNodesData.length || !isSpeaking) {
    stopAudioReading();
    return;
  }

  currentTextIndex = index;
  const currentChunk = textNodesData[index];
  
  clearAllHighlights();
  currentChunk.element.classList.add("audio-highlight");
  
  // Centers window view focus tracking active narrative section
  currentChunk.element.scrollIntoView({ behavior: 'smooth', block: 'center' });

  utterance = new SpeechSynthesisUtterance(currentChunk.text);
  
  const speedDropdown = document.getElementById("reading-speed");
  utterance.rate = speedDropdown ? parseFloat(speedDropdown.value) : 1.0;

  utterance.onend = () => {
    if (isSpeaking && !isPaused) {
      currentTextIndex++;
      speakSegment(currentTextIndex);
    }
  };

  utterance.onerror = () => {
    stopAudioReading();
  };

  synth.speak(utterance);
}

function pauseAudioReading() {
  if (synth.speaking && !synth.paused) {
    synth.pause();
    isPaused = true;
    isSpeaking = false;
  }
}

function changeReadingSpeed() {
  if (isSpeaking) {
    const savedIndex = currentTextIndex;
    synth.cancel();
    isSpeaking = true;
    speakSegment(savedIndex);
  }
}

function stopAudioReading() {
  isSpeaking = false;
  isPaused = false;
  synth.cancel();
  clearAllHighlights();
  currentTextIndex = 0;
}

function clearAllHighlights() {
  textNodesData.forEach(item => {
    if (item.element) item.element.classList.remove("audio-highlight");
  });
}

// Simple Layout Shortcuts Module Map for PWD Keyboard Users
document.addEventListener("keydown", (event) => {
  if (event.altKey && event.key.toLowerCase() === "p") {
    event.preventDefault();
    startAudioReading();
  }
  if (event.altKey && event.key.toLowerCase() === "s") {
    event.preventDefault();
    stopAudioReading();
  }
  if (event.altKey && event.code === "Space") {
    event.preventDefault();
    pauseAudioReading();
  }
});