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
      <h3><?php print date('F', $sunday).'<span> '.date('d',$sunday).' - '.date('F d,',$next_week-1);?></span> <?php print date('Y', $sunday); ?></h3>
    </div>
  </div> 

  <table class="event_calendar weekly">
    <tr>
      <?php for($i = 0; $i < 7; $i++) : ?>
      <?php
        $day_ajusted_ts = mktime( date('H',$sunday), date('i',$sunday), 0, date('n',$sunday), (date('j',$sunday) + $i), date('Y',$sunday) );
      ?>
      <th class="<?php print ($day_ajusted_ts == $today) ? ' today':''; ?>">
        <div class="daylabel"><?php print date('n/d',$day_ajusted_ts);?></div>
        <?php print substr(date('D',$day_ajusted_ts),0,($i == 4 || $i == 6) ? 2 : 1); ?><span class="day_name"><?php print substr(date('D',$day_ajusted_ts),($i == 4 || $i == 6) ? 2 : 1);?></span>
      </th>
      <?php endfor; ?>
    </tr>
    <tr>
<?php

for($i = 0; $i < 7; $i++) {
  // get current day
  $day = ( $i + date('j',$sunday) );
  $day_ts = mktime(0,0,0,date('n',$sunday),$day,$year);
  $is_today = ($day_ts == $today) ? true : false;
?>
<td class="day<?php print ($i % 2) ? ' row-odd' : ' row-even'; ?><?php print ($is_today) ? ' today':''; ?><?php print (!isset($events[$day_ts]) || count($events[$day_ts]) == 0) ? ' no_events' : '';?>">
  <div class="daylabel"><div><?php print date('l', $day_ts);?><span> <?php print date('M, ', $day_ts);?></span></div><?php print date('j', $day_ts);?></div>
  <!-- <div class="daylabel"><span><?php print date('l M, ', $day_ts);?></span><?php print date('j',$day_ts);?></div> -->
  <?php if(isset($events[$day_ts]) && count($events[$day_ts]) > 0) : ?>
  <ul class="events">
    <?php foreach($events[$day_ts] as $event) : ?>
    <li class="<?php print ($event['allDay'] == true) ? 'all_day' : '';?>">
      <?php if( $event['allDay'] != true ) : ?>
      <span class='event_time'><? print ics_calendar_print_event_time($event['start'],$event['end']); ?></span>
      <?php endif; ?>
      <dl>
        <dt class='event_title'><? print $event['title']; ?></dt>
        <dd>
          <div>
            <?php if($event['location'] != '') : ?>
            <p><strong>Location:</strong> <?php print $event['location'];?></p>
            <?php endif; ?>
            <p>
              <strong>Duration: </strong> <?php print ics_calendar_print_duration($event['start'],$event['end']); ?>
            </p>
            <?php if($event['description'] != '') : ?>
            <p><strong>Description:</strong> <?php print ics_calendar_print_description($event['description']);?></p>
            <?php endif; ?>
          </div>
        </dd>
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
<p class='printer_icon'><a  href='#'><span>p</span>Print</a></p>
</div>
