<?php
session_start();
include('../dbcon.php');
require_once('borrow_rules.php');
if (!isset($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit();
}

// Ensure reading_progress column exists
$checkColStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='borrowed_books' AND COLUMN_NAME='reading_progress'");
$checkColStmt->execute();
$colResult = $checkColStmt->get_result();
if ($colResult->num_rows === 0) {
  // Column doesn't exist, add it
  $conn->query("ALTER TABLE borrowed_books ADD COLUMN reading_progress INT DEFAULT 0");
}
$checkColStmt->close();

$user_id = (int)$_SESSION['user_id'];
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($book_id <= 0) {
  echo 'Book not found.';
  exit;
}
$stmt = $conn->prepare('SELECT id, title, author, category, description, cover_image, views FROM books WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$book) {
  echo 'Book not found.';
  exit;
}
$viewStmt = $conn->prepare('UPDATE books SET views = views + 1 WHERE id = ?');
$viewStmt->bind_param('i', $book_id);
$viewStmt->execute();
$viewStmt->close();
$borrowRow = get_active_borrow_record($conn, $user_id, $book_id);
if (!$borrowRow) {
  $_SESSION['error'] = 'You can only read books that are currently borrowed and not returned.';
  header('Location: borrowed-books.php');
  exit();
}

// Track the current episode being read
$currentEpisodeNum = isset($_GET['episode']) ? max(1, min(5, (int)$_GET['episode'])) : 1;

// Update borrowed_books with reading progress
$updateReadingStmt = $conn->prepare("UPDATE borrowed_books SET reading_progress = ? WHERE id = ? AND user_id = ?");
if ($updateReadingStmt) {
  $progress_percent = (int)round(($currentEpisodeNum / 5) * 100);
  $updateReadingStmt->bind_param('iii', $progress_percent, $borrowRow['id'], $user_id);
  $updateReadingStmt->execute();
  $updateReadingStmt->close();
}

$lastReadStmt = $conn->prepare('SELECT read_date FROM reading_history WHERE user_id = ? AND book_id = ? ORDER BY read_date DESC LIMIT 1');
$lastReadStmt->bind_param('ii', $user_id, $book_id);
$lastReadStmt->execute();
$lastReadRow = $lastReadStmt->get_result()->fetch_assoc();
$lastReadStmt->close();
if (!$lastReadRow || $lastReadRow['read_date'] !== date('Y-m-d')) {
  $insertRead = $conn->prepare('INSERT INTO reading_history (user_id, book_id, read_date) VALUES (?, ?, CURDATE())');
  $insertRead->bind_param('ii', $user_id, $book_id);
  $insertRead->execute();
  $insertRead->close();
}
$episodes = [
  [
    'title' => 'Episode 1',
    'heading' => 'Opening Scene',
    'body' => [
      'KathNiel parang hindi lang sila simpleng loveteam noon 😭 parang buong era talaga sila ng Philippine pop culture. Tipong kapag narinig mo yung pangalan nila, automatic may flashback ka ng high school life, Twitter fan wars, Star Cinema posters, at yung feeling na nag-aabang ka ng teaser after ng primetime show HAHAHA.',
      'The central conflict starts to moveKeep reading to see how the central conflict starts to moveKeep reading to see how the central conflict starts to moveKeep reading to see how the central conflict starts to move.',
      'Something changes. The pace picks up and the character has to react instead of observe.',
      'The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.',
      'Choices start to matter more, and the consequences of earlier decisions become visible New information appears and the reader gets a clearer sense of the stakes.',
      'This is the point where the story should feel like it is moving toward a result.The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.'
      
    ]
  ],
  [
    'title' => 'Episode 2',
    'heading' => 'The Turning Point',
    'body' => [
      'Something changes. The pace picks up and the character has to react instead of observe.',
      'This section is where the story starts to feel urgent and the path forward becomes less certain.',
      'New information appears and the reader gets a clearer sense of the stakes.'
    ]
  ],
  [
    'title' => 'Episode 3',
    'heading' => 'Deep Conflict',
    'body' => [
      'The middle of the story usually carries the heaviest tension, and this episode reflects that pressure.',
      'Choices start to matter more, and the consequences of earlier decisions become visible.',
      'This is the point where the story should feel like it is moving toward a result.'
    ]
  ],
  [
    'title' => 'Episode 4',
    'heading' => 'Final Push',
    'body' => [
      'The pace tightens again as the story heads toward its final stretch.',
      'Loose threads begin to connect, and the important themes become easier to see.',
      'The reader should feel the ending getting closer with each paragraph.'
    ]
  ],
  [
    'title' => 'Episode 5',
    'heading' => 'Closing Scene',
    'body' => [
      'The last episode wraps up the reading experience and gives space for the ending to settle.',
      'What happened before now informs the final shape of the story and its emotional result.',
      'You can return to the borrowed-books page to continue with your other books.'
    ]
  ]
];
$episodeIndex = $currentEpisodeNum;
$currentEpisode = $episodes[$episodeIndex - 1];
$previousEpisode = max(1, $episodeIndex - 1);
$nextEpisode = min(count($episodes), $episodeIndex + 1);
$progress = (int)round(($episodeIndex / 5) * 100);
function cover_src($cover_image) {
  $clean = trim((string)$cover_image);
  return $clean === '' ? '../Images/logo.png' : '../Images/' . rawurlencode($clean);
}
function status_label($borrowRow) {
  return $borrowRow ? 'Borrowed' : 'Not borrowed';
}
function days_left($borrowRow) {
  if (!$borrowRow || !empty($borrowRow['return_date']) || $borrowRow['status'] === 'returned') {
    return 'Returned';
  }
  $due = new DateTime($borrowRow['due_date']);
  $today = new DateTime('today');
  $diff = (int)$today->diff($due)->format('%r%a');
  if ($diff > 0) {
    return $diff . ' day' . ($diff === 1 ? '' : 's') . ' left';
  }
  if ($diff === 0) {
    return 'Due today';
  }
  $late = abs($diff);
  return 'Overdue by ' . $late . ' day' . ($late === 1 ? '' : 's');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?> - Read</title>
  <link rel="stylesheet" href="../css/design-system.css" />
  <link rel="stylesheet" href="../css/Read-Book.css" />
  <link rel="stylesheet" href="../css/user-shell.css" />
</head>
<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()"><img src="../Images/logo.png" alt="Readly Logo"></div>
      <nav class="nav">
        <a href="homepage.php"><img class="icon" src="../Images/dashboard.png" alt="Dashboard"><span>Dashboard</span></a>
        <a href="librarypage.php"><img class="icon" src="../Images/Library.png" alt="Library"><span>Library</span></a>
        <a href="borrowed-books.php"><img class="icon" src="../Images/borrowed.png" alt="Borrowed Books"><span>Borrowed Books</span></a>
        <a href="track&record.php"><img class="icon" src="../Images/Track.png" alt="Track and Record"><span>Track and Record</span></a>
        <a href="support.php"><img class="icon" src="../Images/Support.png" alt="Support"><span>Support Page</span></a>
        <a href="setting.php"><img class="icon" src="../Images/settings.png" alt="Settings"><span>Account Settings</span></a>
      </nav>
      <div class="sign-out"><a href="../logout.php"><img class="icon" src="../Images/signout.png" alt="Sign Out"><span>Sign Out</span></a></div>
    </aside>
    <main class="main-content">
      <div class="topbar">
        <a class="btn-back" href="borrowed-books.php">← Back to Borrowed Books</a>
        <button class="btn-audio-modal" onclick="openAudioModal()" title="Open Smart Audio Reading">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
            <line x1="12" y1="19" x2="12" y2="23"></line>
            <line x1="8" y1="23" x2="16" y2="23"></line>
          </svg>
          <span>Audio Read</span>
        </button>
      </div>
      <section class="reader-shell">
        <aside class="book-panel">
          <img class="cover" src="<?php echo htmlspecialchars(cover_src($book['cover_image']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
          
          <div class="book-info">
            <div class="book-title"><?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="book-author"><?php echo htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8'); ?></div>
          </div>

          <div class="book-meta">
            <div class="meta-item">
              <span class="meta-label">Category</span>
              <span class="meta-value"><?php echo htmlspecialchars($book['category'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Status</span>
              <span class="meta-value"><?php echo htmlspecialchars(status_label($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="meta-item">
              <span class="meta-label">Due Date</span>
              <span class="meta-value"><?php echo htmlspecialchars(days_left($borrowRow), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>

          <p class="book-description"><?php echo htmlspecialchars($book['description'], ENT_QUOTES, 'UTF-8'); ?></p>

          <div class="episodes-section">
            <h3 class="episodes-title">Episodes</h3>
            <div class="episodes-list">
              <?php foreach ($episodes as $idx => $episode): $ep_num = $idx + 1; ?>
                <a href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $ep_num; ?>" class="episode-item <?php echo $episodeIndex === $ep_num ? 'active' : ''; ?>">
                  <span class="episode-number">EP <?php echo $ep_num; ?></span>
                  <span class="episode-name"><?php echo htmlspecialchars($episode['heading'], ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </aside>
        <article class="story-panel">
          <div class="story-header">
            <div>
              <h1 class="story-title"><?php echo htmlspecialchars($currentEpisode['heading'], ENT_QUOTES, 'UTF-8'); ?></h1>
              <p class="story-episode"><?php echo htmlspecialchars($currentEpisode['title'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo $episodeIndex; ?>/<?php echo count($episodes); ?>)</p>
            </div>
            <div class="progress-container">
              <div class="progress-label">Reading Progress</div>
              <div class="progress-bar"><span style="width: <?php echo $progress; ?>%"></span></div>
              <div class="progress-text"><?php echo $progress; ?>%</div>
            </div>
          </div>

          <div class="story-content">
            <?php foreach ($currentEpisode['body'] as $paragraph): ?>
              <p><?php echo htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endforeach; ?>
          </div>

          <div class="story-nav">
            <a class="btn-nav <?php echo $episodeIndex <= 1 ? 'disabled' : ''; ?>" href="<?php echo $episodeIndex > 1 ? 'read.php?id=' . $book_id . '&episode=' . $previousEpisode : '#'; ?>" <?php echo $episodeIndex <= 1 ? 'disabled aria-disabled="true"' : ''; ?>>← Previous</a>
            
            <?php if ($episodeIndex >= count($episodes)): ?>
              <button type="button" class="btn-nav btn-finish" onclick="showFinishModal(<?php echo (int)$borrowRow['id']; ?>, <?php echo (int)$book_id; ?>, '<?php echo htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>')">Finished ✓</button>
            <?php else: ?>
              <a class="btn-nav" href="read.php?id=<?php echo $book_id; ?>&episode=<?php echo $nextEpisode; ?>">Next →</a>
            <?php endif; ?>
          </div>
        </article>
      </section>
    </main>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Smart Audio Reading Modal -->
  <div id="audioModal" class="audio-modal-overlay" onclick="closeAudioModal(event)">
    <div class="audio-modal" onclick="event.stopPropagation()">        <!-- Mini Player Badge (shown when modal is closed but audio is playing) -->
        <div id="miniAudioBadge" class="audio-mini-badge hidden" onclick="openAudioModal()">
          <div class="audio-mini-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <polygon points="5 3 19 12 5 21 5 3"></polygon>
            </svg>
          </div>
          <span class="audio-mini-text">Audio Playing</span>
        </div>
      <!-- Modal Header -->
      <div class="audio-modal-header">
        <h2 class="audio-modal-title">📖 Smart Audio Reading</h2>
        <button class="audio-modal-close" onclick="closeAudioModal()" title="Close" aria-label="Close audio reading modal">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Modal Content Tabs -->
      <div class="audio-modal-tabs">
        <button class="audio-tab-btn active" onclick="switchAudioTab('reader')" data-tab="reader">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
            <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
          </svg>
          Audio Reader
        </button>
        <button class="audio-tab-btn" onclick="switchAudioTab('accessibility')" data-tab="accessibility">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"></path>
            <circle cx="12" cy="8" r="1"></circle>
            <path d="M12 11v5M9 14h6"></path>
          </svg>
          Accessibility
        </button>
      </div>

      <!-- Tab Content -->
      <div class="audio-modal-body">
        <!-- Audio Reader Tab -->
        <div id="readerTab" class="audio-tab-content active">
          <div class="audio-section">
            <h3 class="audio-section-title">Text-to-Speech Controls</h3>
            
            <!-- Playback Controls -->
            <div class="audio-controls">
              <button id="playBtn" class="audio-btn audio-btn-play" onclick="playAudio()" title="Play audio" aria-label="Play audio">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <polygon points="5 3 19 12 5 21 5 3"></polygon>
                </svg>
                <span>Play</span>
              </button>
              <button id="pauseBtn" class="audio-btn audio-btn-pause hidden" onclick="pauseAudio()" title="Pause audio" aria-label="Pause audio">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="6" y="4" width="4" height="16"></rect>
                  <rect x="14" y="4" width="4" height="16"></rect>
                </svg>
                <span>Pause</span>
              </button>
              <button id="stopBtn" class="audio-btn audio-btn-stop" onclick="stopAudio()" title="Stop audio" aria-label="Stop audio">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                </svg>
                <span>Stop</span>
              </button>
            </div>

            <!-- Speed Control -->
            <div class="audio-control-group">
              <label class="audio-label">
                <span class="audio-label-text">Reading Speed</span>
                <span class="audio-label-value" id="speedValue">1.0x</span>
              </label>
              <input type="range" id="speedControl" class="audio-slider" min="0.5" max="2" step="0.1" value="1" onchange="changeSpeed(this.value)" oninput="changeSpeed(this.value)">
              <div class="audio-speed-marks">
                <span>Slow</span>
                <span>Normal</span>
                <span>Fast</span>
              </div>
            </div>

            <!-- Progress Info -->
            <div class="audio-progress-info">
              <div class="audio-info-item">
                <span class="audio-info-label">Current Word:</span>
                <span class="audio-info-value" id="currentWord">Not started</span>
              </div>
              <div class="audio-info-item">
                <span class="audio-info-label">Status:</span>
                <span class="audio-info-value" id="audioStatus">Ready</span>
              </div>
            </div>

            <!-- Info Box -->
            <div class="audio-info-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
              </svg>
              <p>The system will highlight text as it reads aloud. You can pause anytime and continue reading manually.</p>
            </div>
          </div>
        </div>

        <!-- Accessibility Tab -->
        <div id="accessibilityTab" class="audio-tab-content">
          <div class="audio-section">
            <h3 class="audio-section-title">Accessibility Settings</h3>

            <!-- Font Size Control -->
            <div class="audio-control-group">
              <label class="audio-label">
                <span class="audio-label-text">Font Size</span>
                <span class="audio-label-value" id="fontSizeValue">100%</span>
              </label>
              <input type="range" id="fontSizeControl" class="audio-slider" min="80" max="160" step="10" value="100" onchange="changeFontSize(this.value)" oninput="changeFontSize(this.value)">
              <div class="audio-size-marks">
                <span>Small</span>
                <span>Normal</span>
                <span>Large</span>
              </div>
            </div>

            <!-- High Contrast Mode -->
            <div class="audio-control-group">
              <label class="audio-toggle-label">
                <input type="checkbox" id="highContrastToggle" onchange="toggleHighContrast(this.checked)">
                <span class="audio-toggle-switch"></span>
                <span class="audio-label-text">High Contrast Mode</span>
              </label>
              <p class="audio-toggle-description">Increases color contrast for better visibility</p>
            </div>

            <!-- Line Spacing -->
            <div class="audio-control-group">
              <label class="audio-label">
                <span class="audio-label-text">Line Spacing</span>
                <span class="audio-label-value" id="lineSpacingValue">Normal</span>
              </label>
              <div class="audio-button-group">
                <button class="audio-option-btn" onclick="setLineSpacing(1.6)">Normal</button>
                <button class="audio-option-btn" onclick="setLineSpacing(1.8)">Relaxed</button>
                <button class="audio-option-btn" onclick="setLineSpacing(2)">Spaced</button>
              </div>
            </div>

            <!-- Voice Selection -->
            <div class="audio-control-group">
              <label class="audio-label">
                <span class="audio-label-text">Voice</span>
              </label>
              <select id="voiceControl" class="audio-slider" style="appearance: auto; padding: 8px 12px; background: #ffffff; border: 1px solid #d4dce8; height: auto; width: 100%; cursor: pointer;" onchange="changeVoice(this.value)">
                <option>Loading voices...</option>
              </select>
              <p class="audio-toggle-description">Select your preferred voice for text-to-speech. Local voices typically provide higher quality audio.</p>
            </div>

            <!-- Info Box -->
            <div class="audio-info-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M12 16v-4M12 8h.01"></path>
              </svg>
              <p>These settings help make reading more comfortable for users with visual or cognitive accessibility needs.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="audio-modal-footer">
        <button class="audio-footer-btn" onclick="resetAudioSettings()">Reset All Settings</button>
      </div>
    </div>
  </div>

  <script>
    // ===== AUDIO READING FUNCTIONALITY =====
    let synth = window.speechSynthesis;
    let utterance = null;
    let isPlaying = false;
    let allText = '';
    let currentWordIndex = 0;
    let wordPositions = [];
    let availableVoices = [];
    let selectedVoice = null;

    // Get available voices and select the best one
    function initializeVoices() {
      availableVoices = synth.getVoices();
      selectBestVoice();
      populateVoiceSelector();
    }

    // Select the highest quality voice available
    function selectBestVoice() {
      if (availableVoices.length === 0) return;
      
      // Prefer: local > Google voices > others, and natural-sounding voices
      let bestVoice = availableVoices[0];
      
      // First priority: local English voices (typically highest quality)
      let localEnglishVoice = availableVoices.find(v => 
        v.localService && v.lang.startsWith('en')
      );
      if (localEnglishVoice) {
        bestVoice = localEnglishVoice;
      } else {
        // Second priority: Any English voice with local support
        let englishVoice = availableVoices.find(v => v.lang.startsWith('en'));
        if (englishVoice) bestVoice = englishVoice;
      }
      
      selectedVoice = bestVoice;
      if (document.getElementById('voiceControl')) {
        document.getElementById('voiceControl').value = availableVoices.indexOf(bestVoice);
      }
    }

    // Populate voice selector dropdown
    function populateVoiceSelector() {
      const voiceSelect = document.getElementById('voiceControl');
      if (!voiceSelect) return;
      
      voiceSelect.innerHTML = '';
      availableVoices.forEach((voice, index) => {
        const option = document.createElement('option');
        option.value = index;
        option.textContent = voice.name + (voice.localService ? ' (Local)' : ' (Online)');
        voiceSelect.appendChild(option);
      });
      
      // Set selected voice
      if (selectedVoice) {
        voiceSelect.value = availableVoices.indexOf(selectedVoice);
      }
    }

    // Change voice selection
    function changeVoice(index) {
      const voiceIndex = parseInt(index);
      if (voiceIndex >= 0 && voiceIndex < availableVoices.length) {
        selectedVoice = availableVoices[voiceIndex];
        if (isPlaying) {
          stopAudio();
          playAudio();
        }
      }
    }

    // Extract all story content text on page load
    document.addEventListener('DOMContentLoaded', function() {
      extractStoryText();
      // Initialize voices (wait for browser to load them)
      if (synth.getVoices().length > 0) {
        initializeVoices();
      } else {
        // Voices may not be loaded yet, wait for them
        synth.onvoiceschanged = initializeVoices;
      }
    });

    function extractStoryText() {
      const contentDiv = document.querySelector('.story-content');
      if (contentDiv) {
        allText = contentDiv.innerText || contentDiv.textContent;
        // Create word position map for highlighting
        const words = allText.split(/\s+/);
        let position = 0;
        wordPositions = words.map(word => {
          const start = allText.indexOf(word, position);
          const end = start + word.length;
          position = end;
          return { word, start, end };
        });
      }
    }

    function openAudioModal() {
      document.getElementById('audioModal').classList.add('active');
      document.getElementById('miniAudioBadge').classList.add('hidden');
    }

    function closeAudioModal(event) {
      // If event exists and target is not the modal itself, don't close
      if (event && event.target.id !== 'audioModal') return;
      
      // Close the modal WITHOUT stopping audio
      // Audio will continue playing in background
      document.getElementById('audioModal').classList.remove('active');
    }

    function switchAudioTab(tabName) {
      // Hide all tabs
      document.querySelectorAll('.audio-tab-content').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.audio-tab-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      // Show selected tab
      document.getElementById(tabName + 'Tab').classList.add('active');
      event.target.closest('.audio-tab-btn').classList.add('active');
    }

    function playAudio() {
      if (isPlaying) return;

      synth.cancel();
      
      utterance = new SpeechSynthesisUtterance(allText);
      utterance.rate = parseFloat(document.getElementById('speedControl').value);
      utterance.pitch = 1.05; // Slightly elevated for clarity and natural sound
      utterance.volume = 1;
      
      // Use the selected voice for higher quality
      if (selectedVoice) {
        utterance.voice = selectedVoice;
      }

      utterance.onstart = function() {
        isPlaying = true;
        updatePlayPauseButtons();
        document.getElementById('audioStatus').textContent = 'Playing...';
        // Show mini badge if modal is closed
        if (!document.getElementById('audioModal').classList.contains('active')) {
          document.getElementById('miniAudioBadge').classList.remove('hidden');
        }
      };

      utterance.onpause = function() {
        document.getElementById('audioStatus').textContent = 'Paused';
      };

      utterance.onend = function() {
        isPlaying = false;
        updatePlayPauseButtons();
        document.getElementById('audioStatus').textContent = 'Finished';
        document.getElementById('currentWord').textContent = 'Completed';
        clearHighlight();
        // Hide mini badge when audio finishes
        document.getElementById('miniAudioBadge').classList.add('hidden');
      };

      utterance.onerror = function(event) {
        document.getElementById('audioStatus').textContent = 'Error: ' + event.error;
        isPlaying = false;
        updatePlayPauseButtons();
      };

      synth.speak(utterance);
    }

    function pauseAudio() {
      synth.pause();
      isPlaying = false;
      updatePlayPauseButtons();
      document.getElementById('audioStatus').textContent = 'Paused';
    }

    function stopAudio() {
      synth.cancel();
      isPlaying = false;
      updatePlayPauseButtons();
      document.getElementById('audioStatus').textContent = 'Ready';
      document.getElementById('currentWord').textContent = 'Not started';
      clearHighlight();
      currentWordIndex = 0;
      // Hide mini badge when audio stops
      document.getElementById('miniAudioBadge').classList.add('hidden');
    }

    function updatePlayPauseButtons() {
      const playBtn = document.getElementById('playBtn');
      const pauseBtn = document.getElementById('pauseBtn');
      
      if (isPlaying) {
        playBtn.classList.add('hidden');
        pauseBtn.classList.remove('hidden');
      } else {
        playBtn.classList.remove('hidden');
        pauseBtn.classList.add('hidden');
      }
    }

    function changeSpeed(value) {
      document.getElementById('speedValue').textContent = parseFloat(value).toFixed(1) + 'x';
      if (isPlaying) {
        const currentTime = synth.paused;
        stopAudio();
        // Note: Resume from current position not directly supported by Web Speech API
        // User would need to click Play again
      }
    }

    function changeFontSize(value) {
      document.getElementById('fontSizeValue').textContent = value + '%';
      const storyContent = document.querySelector('.story-content');
      storyContent.style.fontSize = (value / 100) + 'em';
      // Apply to all paragraphs in the reading content
      storyContent.querySelectorAll('p').forEach(p => {
        p.style.fontSize = (1.05 * value / 100) + 'rem';
      });
    }

    function toggleHighContrast(isChecked) {
      const storyContent = document.querySelector('.story-content');
      if (isChecked) {
        storyContent.classList.add('high-contrast');
        document.body.classList.add('high-contrast');
      } else {
        storyContent.classList.remove('high-contrast');
        document.body.classList.remove('high-contrast');
      }
    }

    function setLineSpacing(value) {
      document.querySelector('.story-content').style.lineHeight = value;
      const labels = ['Normal', 'Relaxed', 'Spaced'];
      const values = [1.6, 1.8, 2];
      const index = values.indexOf(value);
      document.getElementById('lineSpacingValue').textContent = labels[index] || 'Normal';
    }

    function resetAudioSettings() {
      stopAudio();
      document.getElementById('speedControl').value = 1;
      document.getElementById('fontSizeControl').value = 100;
      document.getElementById('highContrastToggle').checked = false;
      document.getElementById('speedValue').textContent = '1.0x';
      document.getElementById('fontSizeValue').textContent = '100%';
      document.getElementById('lineSpacingValue').textContent = 'Normal';
      
      // Reset voice to best available
      selectBestVoice();
      
      document.querySelector('.story-content').style.fontSize = '1em';
      document.querySelector('.story-content').style.lineHeight = '1.8';
      document.querySelector('.story-content').classList.remove('high-contrast');
      document.body.classList.remove('high-contrast');
    }

    function clearHighlight() {
      document.querySelectorAll('.story-content .highlight-word').forEach(el => {
        el.classList.remove('highlight-word');
      });
    }

    // Close modal when Escape key is pressed (WITHOUT stopping audio)
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        const modal = document.getElementById('audioModal');
        if (modal && modal.classList.contains('active')) {
          // Close modal but keep audio playing
          document.getElementById('audioModal').classList.remove('active');
        }
      }
    });

    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('collapsed');
    }

    function showFinishModal(borrowId, bookId, bookTitle) {
      Swal.fire({
        title: 'Finished Reading?',
        html: `<p style="margin: 0; font-size: 1.05rem;">You've finished reading <strong>${bookTitle}</strong>.</p><p style="margin: 10px 0 0 0; color: #666; font-size: 0.95rem;">You can return the book or read it again until your return date.</p>`,
        icon: 'success',
        showCancelButton: false,
        confirmButtonText: 'Return Book',
        denyButtonText: 'Read Again',
        showDenyButton: true,
        confirmButtonColor: '#e8744f',
        denyButtonColor: '#1b678f',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Return the book
          Swal.fire({
            title: 'Returning Book...',
            html: 'Processing your return...',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          const formData = new FormData();
          formData.append('borrow_id', borrowId);
          formData.append('book_id', bookId);
          
          fetch('return_book.php', {
            method: 'POST',
            body: formData,
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: 'Book Returned',
                text: 'Thank you for reading. You can now borrow another book.',
                confirmButtonColor: '#0e3a5d',
                willClose: () => {
                  window.location.href = 'borrowed-books.php';
                }
              });
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to return the book',
                confirmButtonColor: '#0e3a5d'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An error occurred while returning the book',
              confirmButtonColor: '#0e3a5d'
            });
          });
        } else if (result.isDenied) {
          // Read again - go back to episode 1
          window.location.href = 'read.php?id=' + bookId + '&episode=1';
        }
      });
    }
  </script>
</body>
</html>
