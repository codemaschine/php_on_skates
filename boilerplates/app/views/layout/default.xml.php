<?php echo '<?xml version="1.0" encoding="utf-8" ?>
'; ?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
  <title><?php if($site_title) echo $site_title; else echo "amen.de - Gib deine Sorgen ab! - Menschen beten für dich zu Gott - sicher und anonym";?></title>
  <link>https://www.amen.de/gebet.php?action=joy</link>
  <description>Bei amen.de kannst du deine Sorgen Menschen anvertrauen, die dafür zu Gott beten. Anonym und doch persönlich.</description>
  <language>de-DE</language>
  <lastBuildDate><?php echo date("r",time()); ?></lastBuildDate>
  <image>
    <url>http://www.amen.de/images/logo.png</url>
    <title><?php if($site_title) echo $site_title; else echo "amen.de - Gib deine Sorgen ab!";?></title>
    <link>https://www.amen.de/gebet.php?action=joy</link>
  </image>
  <ttl>300</ttl>
  <?php yieldit(); ?>
</channel>
</rss>