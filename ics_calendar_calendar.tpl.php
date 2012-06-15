<?php 
/*
 * Available vars:
 * - $description: Containing the description of the FlickrGallery module you provided at the settings page
 * - $albums: Array that contains the image and titles with links
 */

$month_start_ts = mktime(0,0,0,$month,1,$year);

$dt = new DateTime();
$dt->setTimestamp($month_start_ts);

$dt->modify('+1 month');
$next_month = $dt->getTimestamp();

$dt->modify('-2 months');
$last_month = $dt->getTimestamp();

$weekday_first = date('w', $month_start_ts);
$days_in_month = date('t', $month_start_ts);
$today = mktime(0,0,0,date('n',time()),date('j',time()),date('Y',time()));

?>
<div class="ics_calendar">
  <div class="event_calendar-nav-wrapper clearfix item-list">
    <ul class="pager">
      <li class="date-prev">
        <?php print l(t('« Prev'), $_GET['q'], array('query'=>array('month'=>date('Y-m',$last_month)),'attributes'=>array('title'=>'Navigate to previous month', 'rel'=>'nofollow'))); ?>
      </li>
      <li class="date-next">&nbsp;
        <?php print l(t('Next »'), $_GET['q'], array('query'=>array('month'=>date('Y-m',$next_month)),'attributes'=>array('title'=>'Navigate to next month', 'rel'=>'nofollow'))); ?>
      </li>
    </ul>
    <div class="date-heading">
      <h3><?php print date('F Y', $month_start_ts)?></h3>
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

for($i = 1; $i < 43; $i++) {

  if($i <= ($days_in_month+$weekday_first) && $i > $weekday_first)
  {
    // get current day
    $day = ($i - ($weekday_first));
    $day_ts = mktime(0,0,0,$month,$day,$year);
    $is_today = ($day_ts == $today) ? true : false;
?>
<td class="day<?php print ($is_today) ? ' today':''; ?><?php print (!isset($events[$day_ts]) || count($events[$day_ts]) == 0) ? ' no_events' : '';?>">
  <div class="daylabel"><span><?php print date('l M, ', $day_ts);?></span><?php print $day;?></div>
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
  else {
    ?><td class="no_events"></td><?php
  }

  switch ($i){
  case 7:
?>
      </tr>
      <tr>
<?php
    break;
  case 14:
?>
      </tr>
      <tr>
<?php
    break;
  case 21:
?>
      </tr>
      <tr>
<?php
    break;
  case 28:
?>
      </tr>
      <tr>
<?php
    break;
  case 35:
?>
      </tr>
      <tr>
<?php
    break;
  }
}
?>
  </tr>
</table>
</div>
