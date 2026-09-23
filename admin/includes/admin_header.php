<?php
/**
 * Admin Console Navigation & Layout Header
 * Pure Procedural PHP + MySQL
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/functions.php';

// Enforce admin authentication
auth_require();

$adminUser = auth_user();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Console — Redeemed Gospel Church Eldoret</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Newsreader:ital,opsz,wght@0,6..72,300..700;1,6..72,300..700&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
</head>
<body>

<div class="shell">
  <!-- SIDEBAR NAVIGATION -->
  <aside class="side">
    <div class="brand">
      <span>✝</span>
      <b>BEACON ADMIN</b>
      <span>ELDORET · MANAGEMENT PORTAL</span>
    </div>

    <nav>
      <div class="sec">OVERVIEW &amp; SITE</div>
      <a href="index.php" class="<?= $currentPage === 'index' ? 'on' : '' ?>"><i>01</i>Dashboard</a>
      <a href="profile.php" class="<?= $currentPage === 'profile' ? 'on' : '' ?>"><i>02</i>Pastor Profile &amp; Hero</a>
      <a href="giving.php" class="<?= $currentPage === 'giving' ? 'on' : '' ?>"><i>03</i>Giving Channels</a>

      <div class="sec">COMMUNITY &amp; USERS</div>
      <a href="users.php" class="<?= $currentPage === 'users' ? 'on' : '' ?>"><i>04</i>Member Accounts</a>
      <a href="prayers.php" class="<?= $currentPage === 'prayers' ? 'on' : '' ?>"><i>05</i>Prayer Inbox</a>
      <a href="invites.php" class="<?= $currentPage === 'invites' ? 'on' : '' ?>"><i>06</i>Ministry Invitations</a>
      <a href="commitments.php" class="<?= $currentPage === 'commitments' ? 'on' : '' ?>"><i>07</i>Partner Pledges</a>

      <div class="sec">MINISTRY CONTENT</div>
      <a href="sermons.php" class="<?= $currentPage === 'sermons' ? 'on' : '' ?>"><i>08</i>Sermons Library</a>
      <a href="sermon_comments.php" class="<?= $currentPage === 'sermon_comments' ? 'on' : '' ?>"><i>08b</i>Sermon Comments</a>
      <a href="live.php" class="<?= $currentPage === 'live' ? 'on' : '' ?>"><i>09</i>Live Broadcast &amp; Chat</a>
      <a href="ministries.php" class="<?= $currentPage === 'ministries' ? 'on' : '' ?>"><i>10</i>Ministries</a>
      <a href="journey.php" class="<?= $currentPage === 'journey' ? 'on' : '' ?>"><i>10b</i>Journey Milestones</a>
      <a href="schedule.php" class="<?= $currentPage === 'schedule' ? 'on' : '' ?>"><i>11</i>Weekly Rhythm</a>
      <a href="gallery.php" class="<?= $currentPage === 'gallery' ? 'on' : '' ?>"><i>12</i>Photo Gallery</a>
      <a href="testimonies.php" class="<?= $currentPage === 'testimonies' ? 'on' : '' ?>"><i>13</i>Testimonies</a>
      <a href="events.php" class="<?= $currentPage === 'events' ? 'on' : '' ?>"><i>14</i>Events Calendar</a>
      <a href="programs.php" class="<?= $currentPage === 'programs' ? 'on' : '' ?>"><i>15</i>Programmes &amp; Funds</a>
      <a href="projects.php" class="<?= $currentPage === 'projects' ? 'on' : '' ?>"><i>16</i>Capital Projects</a>

      <div class="sec">SYSTEM &amp; SECURITY</div>
      <a href="security.php" class="<?= $currentPage === 'security' ? 'on' : '' ?>"><i>17</i>Security &amp; Audit Logs</a>
      <a href="<?= BASE_URL ?>index.php" target="_blank"><i>↗</i>View Public Portal</a>
      <a href="logout.php" style="color:var(--wine);border-top:1px solid rgba(247,242,233,.12);margin-top:14px;"><i>✕</i>Sign Out</a>
    </nav>
  </aside>

  <!-- MAIN CONTENT AREA -->
  <main class="main">
    <!-- TOPBAR -->
    <header class="topbar">
      <div class="tbrand" style="font-family:var(--mono);font-size:10.5px;letter-spacing:0.08em;color:var(--ink2);">
        <b style="color:var(--ink);">Redeemed Gospel Church Eldoret</b> · Management Portal
      </div>
        <div class="right">
        <div class="tuser" style="display:flex;align-items:center;gap:10px;">
          <span class="avatar" style="width:34px;height:34px;border-radius:50%;background:var(--wine);color:var(--paper);display:flex;align-items:center;justify-content:center;font-family:var(--disp);font-size:13px;"><?= strtoupper(substr($adminUser['username'], 0, 2)) ?></span>
          <div>
            <b style="font-family:var(--mono);font-size:11px;letter-spacing:0.06em;"><?= esc($adminUser['username']) ?></b>
            <span style="display:block;font-size:11.5px;color:var(--ink2);font-family:var(--mono);letter-spacing:0.08em;text-transform:uppercase;"><?= esc($adminUser['role']) ?></span>
          </div>
        </div>
      </div>
    </header>

    <!-- FLASH MESSAGES -->
    <?php if ($flash): ?>
      <div class="toast show <?= esc($flash['type']) ?>">
        <?= esc($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="content">
