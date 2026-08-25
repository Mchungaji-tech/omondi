<?php
/**
 * Dedicated Events Calendar Page — Beacon Gospel Centre
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/functions.php';

ensure_event_image_column();
$events = db_fetch_all("SELECT * FROM events WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

require_once __DIR__ . '/includes/header.php';
?>

<div class="wrap" style="padding-top:100px;padding-bottom:80px;">
  <div class="shead">
    <p class="idx">Calendar &amp; Gatherings</p>
    <h1 style="font-size:clamp(2.4rem, 5.5vw, 4rem);">Upcoming <span class="ser">Church Events</span></h1>
    <p style="margin-top:10px;color:var(--ink2);max-width:640px;">Join us in Eldoret and across the Rift Valley for open-air crusades, youth camps, conferences, and praise rallies.</p>
  </div>

  <div class="tblwrap" style="background:#fff;">
    <?php foreach ($events as $evt): $eventDate = $evt['event_date'] ?: date('Y-m-d', strtotime($evt['month_label'] . ' ' . $evt['day_num'])); ?>
      <article class="evt">
        <div class="evt-poster">
          <?php if (!empty($evt['image_url'])): ?><img src="<?= esc(img_src($evt['image_url'])) ?>" alt="<?= esc($evt['title']) ?>">
          <?php else: ?><span>Event<br>Poster</span><?php endif; ?>
        </div>
        <div class="dt">
          <b><?= esc($evt['day_num']) ?></b>
          <small><?= esc($evt['month_label']) ?></small>
        </div>
        <div>
          <h3><?= esc($evt['title']) ?></h3>
          <div class="loc"><?= esc($evt['location']) ?></div>
          <p><?= esc($evt['description']) ?></p>
          <span class="evt-countdown" data-event-date="<?= esc($eventDate) ?>T12:00:00">Loading countdown...</span>
        </div>
        <div class="evt-actions">
          <button type="button" class="btn sm" data-event-view data-title="<?= esc($evt['title']) ?>" data-location="<?= esc($evt['location']) ?>" data-date="<?= esc($evt['day_num'] . ' ' . $evt['month_label']) ?>" data-description="<?= esc($evt['description']) ?>" data-image="<?= esc(img_src($evt['image_url'] ?? '')) ?>">View</button>
          <a href="<?= BASE_URL ?>invite.php" class="btn sm ghost">Book Seat / RSVP</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>

<div class="event-modal" id="eventModal" hidden>
  <div class="event-modal-card" role="dialog" aria-modal="true" aria-labelledby="eventModalTitle">
    <button type="button" class="event-modal-close" data-event-close aria-label="Close event details">&times;</button>
    <img id="eventModalImage" alt="" hidden>
    <div class="event-modal-body">
      <p class="idx">Event Details</p>
      <h2 id="eventModalTitle"></h2>
      <p class="event-modal-date" id="eventModalDate"></p>
      <p class="event-modal-location" id="eventModalLocation"></p>
      <p id="eventModalDescription"></p>
      <a href="<?= BASE_URL ?>invite.php" class="btn gold">Book Seat / RSVP</a>
    </div>
  </div>
</div>

<script>
(function () {
  const modal = document.getElementById('eventModal');
  if (!modal) return;
  const image = document.getElementById('eventModalImage');
  const countdowns = document.querySelectorAll('[data-event-date]');
  function updateCountdowns() {
    countdowns.forEach(function (el) {
      const seconds = Math.max(0, Math.floor((new Date(el.dataset.eventDate) - Date.now()) / 1000));
      if (seconds === 0) { el.textContent = 'Event day'; return; }
      const days = Math.floor(seconds / 86400), hours = Math.floor(seconds % 86400 / 3600), minutes = Math.floor(seconds % 3600 / 60);
      el.textContent = days + 'd ' + hours + 'h ' + minutes + 'm to go';
    });
  }
  updateCountdowns();
  setInterval(updateCountdowns, 60000);
  document.querySelectorAll('[data-event-view]').forEach(function (button) {
    button.addEventListener('click', function () {
      document.getElementById('eventModalTitle').textContent = button.dataset.title;
      document.getElementById('eventModalDate').textContent = button.dataset.date;
      document.getElementById('eventModalLocation').textContent = button.dataset.location;
      document.getElementById('eventModalDescription').textContent = button.dataset.description;
      image.hidden = !button.dataset.image;
      if (button.dataset.image) { image.src = button.dataset.image; image.alt = button.dataset.title; }
      modal.hidden = false;
      document.body.classList.add('event-modal-open');
    });
  });
  function closeModal() { modal.hidden = true; document.body.classList.remove('event-modal-open'); }
  modal.querySelector('[data-event-close]').addEventListener('click', closeModal);
  modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeModal(); });
}());
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
