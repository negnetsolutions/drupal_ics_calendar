<?php 
/*
 * Available vars:
 * - $description: Containing the description of the FlickrGallery module you provided at the settings page
 * - $albums: Array that contains the image and titles with links
 */
?>
<article id="node-17" class="node node-basic-page node-promoted node-full clearfix">
  <div class="content clearfix">

    <div id='ics_calendar'>
      <?php foreach ($data as $day_of_the_week => $classes) : ?>
        <table>
          <thead>
            <tr>
              <th colspan="2"><? print date('l',substr($day_of_the_week,1));?></th>
            </tr>
            <?php foreach ($classes as $class) : ?>
            <tr>
              <td><?php print date('g:i', $class['start']); ?> - <?php print date('g:i a', $class['end']); ?></td>
              <td><?php print $class['title']; ?></td>
              <td>$<?php print money_format('%i',$class['price']); ?></td>
            </tr>
      <?php endforeach; ?>
          </thead>
        </table>
      <hr />
      <?php endforeach; ?>
    </div>
  </div>
</article>
