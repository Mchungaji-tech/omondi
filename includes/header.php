<?php
/**
 * Public Header Component - Beacon Gospel Centre
 * Pure Procedural PHP
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

$pastorName = get_setting('pastor_name', 'Bishop Morris Omondi');
$pastorEpithet = get_setting('pastor_epithet', 'of Eldoret');

$member = user_auth_data();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($pastorName) ?> — Redeemed Gospel Church Eldoret</title>
<meta name="description" content="<?= esc($pastorName) ?>, evangelical pastor in Eldoret, Kenya. Sermons, live streams, ministries, community support, prayer requests and preaching invitations.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/main.css">
</head>
<body>
<div class="noise" aria-hidden="true"></div>

<!-- ============ HEADER ============ -->
<header class="site-header">
  <div class="hwrap">
    <a href="<?= BASE_URL ?>index.php" class="brand" aria-label="Home">
      <span class="x">✝</span>
    </a>

    <nav class="main">
      <a href="<?= BASE_URL ?>index.php" class="<?= $currentPage === 'index' ? 'active' : '' ?>">Home</a>
      <a href="<?= BASE_URL ?>about.php" class="<?= $currentPage === 'about' ? 'active' : '' ?>">Journey</a>
      <a href="<?= BASE_URL ?>sermons.php" class="<?= $currentPage === 'sermons' ? 'active' : '' ?>">Sermons</a>
      <a href="<?= BASE_URL ?>live.php" class="<?= $currentPage === 'live' ? 'active' : '' ?>">Live Broadcast</a>
      <a href="<?= BASE_URL ?>ministries.php" class="<?= $currentPage === 'ministries' ? 'active' : '' ?>">Ministries</a>
      <a href="<?= BASE_URL ?>proposals.php" class="<?= in_array($currentPage, ['proposals', 'proposal']) ? 'active' : '' ?>">Proposals</a>
      <a href="<?= BASE_URL ?>giving.php" class="<?= $currentPage === 'giving' ? 'active' : '' ?>">Giving</a>
      <a href="<?= BASE_URL ?>prayer.php" class="<?= $currentPage === 'prayer' ? 'active' : '' ?>">Prayer</a>
      <a href="<?= BASE_URL ?>events.php" class="<?= $currentPage === 'events' ? 'active' : '' ?>">Events</a>
    </nav>

    <div class="clock" id="clock">ELD · <b>--:--</b> EAT</div>

    <!-- MEMBER PROFILE OR AUTH BUTTONS -->
    <?php if ($member): ?>
      <div class="user-menu-wrap">
        <a href="<?= BASE_URL ?>my_requests.php" class="user-chip-btn">
          <span class="avatar-circle"><?= esc($member['initials']) ?></span>
          <span class="user-name-short"><?= esc(explode(' ', $member['name'])[0]) ?></span>
        </a>
        <div class="user-dropdown">
          <div class="ud-header">
            <b><?= esc($member['name']) ?></b>
            <span><?= esc($member['email']) ?></span>
          </div>
          <a href="<?= BASE_URL ?>my_requests.php">📋 My Requests &amp; Pledges</a>
          <a href="<?= BASE_URL ?>prayer.php">🙏 Request Prayer</a>
          <a href="<?= BASE_URL ?>invite.php">✉ Invite Pastor</a>
          <a href="<?= BASE_URL ?>logout.php" style="color:var(--wine);border-top:1px solid var(--line);">Sign Out</a>
        </div>
      </div>
    <?php else: ?>
      <div class="auth-btns-header">
        <a href="<?= BASE_URL ?>login.php" class="btn ghost sm">Sign In</a>
        <a href="<?= BASE_URL ?>register.php" class="btn sm gold">Join</a>
      </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>invite.php" class="btn sm hide-mobile">Invite Pastor ✦</a>

    <button class="burger" id="burger" aria-label="Open navigation menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- ============ MOBILE FULLSCREEN MENU ============ -->
<div class="menu" id="menu">
  <button class="close" id="menuClose" aria-label="Close navigation menu">Close ✕</button>
  
  <?php if ($member): ?>
    <div style="margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid rgba(247,242,233,.15);display:flex;align-items:center;gap:12px;">
      <div class="avatar-circle" style="width:44px;height:44px;font-size:16px;"><?= esc($member['initials']) ?></div>
      <div>
        <b style="font-family:var(--disp);font-size:1.4rem;color:var(--gold2);"><?= esc($member['name']) ?></b>
        <a href="<?= BASE_URL ?>my_requests.php" style="font-family:var(--mono);font-size:11px;color:var(--paper);border:none;padding:0;margin:0;">View My Requests &rarr;</a>
      </div>
    </div>
  <?php endif; ?>

  <a href="<?= BASE_URL ?>index.php"><i>01</i> Home</a>
  <a href="<?= BASE_URL ?>about.php"><i>02</i> Journey &amp; Calling</a>
  <a href="<?= BASE_URL ?>sermons.php"><i>03</i> Sermon Library</a>
  <a href="<?= BASE_URL ?>live.php"><i>04</i> Live Broadcast</a>
  <a href="<?= BASE_URL ?>ministries.php"><i>05</i> Ministries</a>
  <a href="<?= BASE_URL ?>proposals.php"><i>06</i> Capital Proposals</a>
  <a href="<?= BASE_URL ?>giving.php"><i>07</i> Giving &amp; Support</a>
  <a href="<?= BASE_URL ?>prayer.php"><i>08</i> Prayer Requests</a>
  <a href="<?= BASE_URL ?>events.php"><i>09</i> Events Calendar</a>
  <a href="<?= BASE_URL ?>invite.php"><i>10</i> Invite the Pastor</a>

  <div style="margin-top:24px;display:flex;gap:12px;">
    <?php if ($member): ?>
      <a href="<?= BASE_URL ?>logout.php" class="btn sm danger" style="border:1px solid var(--wine);">Sign Out</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>login.php" class="btn sm ghost" style="border:1px solid var(--paper);color:var(--paper);">Sign In</a>
      <a href="<?= BASE_URL ?>register.php" class="btn sm gold">Join Community</a>
    <?php endif; ?>
  </div>
</div>
