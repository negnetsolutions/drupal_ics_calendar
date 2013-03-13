<?php 
/*
 * Available vars:
 * - $description: Containing the description of the FlickrGallery module you provided at the settings page
 * - $albums: Array that contains the image and titles with links
 */
?>
<div class="ics_calendar">
  <div class="ics_calendar_list">
    <?php foreach( $days as $day => $events ) : ?>
      <?php foreach( $events as $event) : ?>
        <span><?php print date('F, j',$day); ?>:&nbsp;</span>

        <span class='event_title<?php print ($event['allDay'] == true) ? ' all_day' : '';?>'><? print $event['title']; ?></span>

        <?php if( $event['allDay'] != true ) : ?>
        <span class='event_time'>, <? print ics_calendar_print_event_time($event['start'],$event['end']); ?></span>
        <?php endif; ?>
        <br />
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</div>
