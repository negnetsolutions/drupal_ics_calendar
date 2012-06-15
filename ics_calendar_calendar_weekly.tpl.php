<?php 
/*
 * Available vars:
 * - $description: Containing the description of the FlickrGallery module you provided at the settings page
 * - $albums: Array that contains the image and titles with links
 */

$offset = date('w', mktime(0,0,0,1,1,$year));
$offset = ($offset < 5) ? 1-$offset : 8-$offset;
$monday = mktime(0,0,0,1,1+$offset, $year);
$monday = strtotime('+' . ($week - 1) . ' weeks', $monday);
$d = new DateTime();
$d->setTimestamp($monday);
$d->modify('-1 day');
$sunday = $d->getTimestamp();

$d->modify('+1 week +1 day');
$next_week = $d->getTimestamp();
$d->modify('-2 weeks');
$last_week = $d->getTimestamp();


///
$weekday_first = 0;
$days_in_month = date('t', $sunday);
$today = mktime(0,0,0,date('n',time()),date('j',time()),date('Y',time()));

?>
<div class="ics_calendar">
  <div class="event_calendar-nav-wrapper clearfix item-list">
    <ul class="pager">
      <li class="date-prev">
        <?php print l(t('« Prev'), $_GET['q'], array('query'=>array('week'=>date('Y-W',$last_week)),'attributes'=>array('title'=>'Navigate to previous week', 'rel'=>'nofollow'))); ?>
      </li>
      <li class="date-next">&nbsp;
        <?php print l(t('Next »'), $_GET['q'], array('query'=>array('week'=>date('Y-W',$next_week)),'attributes'=>array('title'=>'Navigate to next week', 'rel'=>'nofollow'))); ?>
      </li>
    </ul>
    <div class="date-heading">
      <h3><?php print date('F d', $sunday).' - '.date('F d, Y',$next_week-1);?></h3>
    </div>
  </div> 

  <table class="event_calendar">
    <tr>
      <th>Sun</th>
      <th>Mon</th>
      <th>Tue</th>
      <th>Wed</th>
      <th>Thu</th>
      <th>Fri</th>
      <th>Sat</th>
    </tr>
    <tr>
<?php

for($i = 0; $i < 7; $i++) {
  // get current day
  $day = ( $i + date('j',$sunday) );
  $day_ts = mktime(0,0,0,date('n',$sunday),$day,$year);
  $is_today = ($day_ts == $today) ? true : false;
?>
<td class="day<?php print ($is_today) ? ' today':''; ?><?php print (!isset($events[$day_ts]) || count($events[$day_ts]) == 0) ? ' no_events' : '';?>">
  <div class="daylabel"><span><?php print date('l M, ', $day_ts);?></span><?php print date('j',$day_ts);?></div>
  <?php if(isset($events[$day_ts]) && count($events[$day_ts]) > 0) : ?>
  <ul class="events">
    <?php foreach($events[$day_ts] as $event) : ?>
    <li class="<?php print ($event['allDay'] == true) ? 'all_day' : '';?>">
      <?php if( $event['allDay'] != true ) : ?>
      <span class='event_time'><? print ics_calendar_print_event_time($event['start'],$event['end']); ?></span>
      <?php endif; ?>
      <dl>
        <dt class='event_title'><? print $event['title']; ?></dt>
        <?php if($event['location'] != '' || $event['description'] != '') : ?>
        <dd>
        <?php if($event['location'] != '') : ?>
        <p><strong>Location:</strong> <?php print $event['location'];?></p>
        <?php endif; ?>
        <?php if($event['description'] != '') : ?>
        <p><strong>Description:</strong> <?php print $event['description'];?></p>
        <?php endif; ?>
        </dd>
        <?php endif; ?>
      </dl>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</td>
<?php
}
?>
  </tr>
</table>
</div>
