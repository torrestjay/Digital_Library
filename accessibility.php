<div id="accessibility-panel">
  <h3 style="margin-top: 0; color: #0e3a5d; font-family: sans-serif; font-size: 14px; font-weight: 600; margin-bottom: 10px;">Accessibility Settings</h3>

  <div class="acc-controls" style="margin-bottom: 12px;">
    <button type="button" onclick="increaseFont()">A+</button>
    <button type="button" onclick="decreaseFont()">A-</button>
    <button type="button" onclick="resetFont()">Reset</button>
    <button type="button" onclick="toggleContrast()">High Contrast</button>
    <button type="button" onclick="toggleDyslexia()">Dyslexia Font</button>
  </div>

  <hr style="border: 0; border-top: 1px solid #ddd; margin: 12px 0;">

  <h3 style="color: #0e3a5d; font-family: sans-serif; font-size: 14px; font-weight: 600; margin-bottom: 8px;">Smart Audio Reader</h3>
  <div class="audio-controls">
    <button type="button" onclick="startAudioReading()">▶ Play / Resume</button>
    <button type="button" onclick="pauseAudioReading()">⏸ Pause</button>
    <button type="button" onclick="stopAudioReading()">⏹ Stop</button>
    
    <div style="margin-top: 12px; display: flex; align-items: center; gap: 8px;">
      <label for="reading-speed" style="font-size: 12px; font-weight: 600; color: #333;">SpeedMultiplier:</label>
      <select id="reading-speed" onchange="changeReadingSpeed()" style="padding: 4px 6px; border-radius: 4px; border: 1px solid #ccc; font-size: 12px; background: #fff; color: #333;">
        <option value="0.75">0.75x</option>
        <option value="1.0" selected>1.0x (Normal)</option>
        <option value="1.25">1.25x</option>
        <option value="1.5">1.5x</option>
        <option value="2.0">2.0x</option>
      </select>
    </div>
  </div>
</div>