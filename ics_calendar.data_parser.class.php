<?

class ical_data_parser {

  private $_start, $_end, $ics_file;

  function __construct($ics_file=null)
  {
    if($ics_file != null){
      $this->setIcsFile($ics_file) ;
    }
  }
  public function setIcsFile($file)
  {
    $this->ics_file = $file;
  }
  public function getDataByDays($start='now', $days='7')
  {
    if($start == 'now')
      $start = time();

    $d = new DateTime(date('Y-m-d', $start));

    $this->_start = $d->getTimestamp();
    $d->modify("+ $days days");

    $this->_end = $d->getTimestamp();

    return $this->filterByDay($this->getFromCache($this->ics_file));
  }
  public function getDataByMonth($month,$year)
  {

    $d = new DateTime();
    $d->setTimestamp(mktime(0,0,0,$month,'01',$year));

    $this->_start = $d->getTimestamp();

    $d->modify("+1 month");
    $d->modify("-1 second");
    $this->_end = $d->getTimestamp();

    // $data = $this->getFromCache($this->ics_file);
    $data = $this->getData($this->ics_file);

    return $this->filterByDay($data);
  }
  public function getDataByWeek($weekNumber,$year)
  {
    $d = new DateTime();

    $offset = date('w', mktime(0,0,0,1,1,$year));
    $offset = ($offset < 5) ? 1-$offset : 8-$offset;
    $monday = mktime(0,0,0,1,1+$offset, $year);
    $monday = strtotime('+' . ($weekNumber - 1) . ' weeks', $monday);
    $d->setTimestamp($monday);
    //start with Sunday
    $d->modify('-1 day');
    $this->_start = $d->getTimestamp();

    $d->modify("+1 week");
    $d->modify("-1 second");
    $this->_end = $d->getTimestamp();

    $data = $this->getFromCache($this->ics_file);

    return $this->filterByDay($data);
  }
  private function filterByDay($data)
  {
    $days = array();
    foreach($data as $event) {

      $days[mktime(0,0,0,date('n', $event['start']),date('j', $event['start']),date('Y', $event['start']))][] = $event;
    
    }

    return $days;
  }
  private function getFromCache($ics_file) {

    $filename = substr(strrchr($ics_file,'/'),1);
    $path = ics_calendar_get_cache_folder();

    $hits = array();

    //get cache file list
    $cache = scandir($path);
    foreach( $cache as $file ) {
      if( ($file == 'cached_'.$filename.'-'.$this->_start.'-'.$this->_end) !== false ) {
        $hits[] = $file;
      }
    }

    if( count($hits) == 0 ) {
      //we need to make cache
      $data = $this->getData($ics_file);
      if( file_put_contents($path.'/cached_'.$filename.'-'.$this->_start.'-'.$this->_end, base64_encode( serialize( $data ) ) ) == 0 ) {
        return array();
      }
    }

    //read the cache file
    if( ($raw = file_get_contents($path.'/cached_'.$filename.'-'.$this->_start.'-'.$this->_end)) == false) {
      return array();
    }

    return unserialize( base64_decode( $raw ) );
  }
  private function getData($ics_file)
  {
    $evts = $this->_getRawEventData($ics_file);

    if( count($evts) == 0 )
      return array();

    $data = $this->_filterEventData($evts);

    return $this->_sortData($data);
  }
  private function _sortData($data)
  {
    $starts = array();
    foreach($data as $item) {
      $starts[] = $item['start'];
    }

    array_multisort($starts, $data);
    return $data;
  }
  private function _filterEventData($evts)
  {
    foreach($evts as $id => $ev) {
      $jsEvt = array(
        'id' => ($id+1),
        'uid' => $ev->getUid(),
        'title' => $ev->getProperty('summary'),
        'location' => $ev->getLocation(),
        'allDay' => $ev->isWholeDay(),
        'start' => $ev->getStart(),
        'end'   => $ev->getEnd(),
        'description' => $ev->getDescription(),

      );

      if (isset($ev->recurrence)) {
        $count = 0;
        $start = $ev->getStart();
        $freq = $ev->getFrequency();
        if ($freq->firstOccurrence() == $start && $jsEvt["start"] >= $this->_start && ($this->_end - $jsEvt["start"]) >= 0 )
          $data[] = $jsEvt;
        while (($next = $freq->nextOccurrence($start)) > 0 ) {
          if (!$next or $jsEvt["start"] >= $this->_end ) break;
          $count++;
          $start = $next;
          $jsEvt["start"] = $start;
          $jsEvt["end"] = $start + $ev->getDuration();

          if( $jsEvt["start"] >= $this->_start && ($this->_end - $jsEvt["start"]) >= 0 ) {
            $data[] = $jsEvt;
          }
        }
      }
      else {
        if( $jsEvt["start"] >= $this->_start && ($this->_end - $jsEvt["start"]) >= 0 ) { //make sure start time is within parameters of view

          $start = mktime(0,0,0,date('n',$jsEvt['start']),date('d',$jsEvt['start']),date('Y',$jsEvt['start']));
          $end = mktime(0,0,0,date('n',$jsEvt['end']),date('d',$jsEvt['end']),date('Y',$jsEvt['end']));

          if( $ev->isWholeDay() && ($end - $start) >= 86400 ) { //handle all day events over multiple days

            for( $i = 0; $i < (($end - $start) / 86400); $i++) {

              $jsEvt['start'] = $start + ( $i * 86400 );
              $jsEvt['end'] = $start + ( ($i+1) * 86400 );
            
              if( $jsEvt["start"] >= $this->_start && ($this->_end - $jsEvt["start"]) >= 0 ) { //make sure start time is within parameters of view
                $data[] = $jsEvt;
              }
            }

          }
          else {
            $data[] = $jsEvt;
          }

        }
      }
    }
    return $data;
  }
  private function _getRawEventData($ics_file)
  {
    $ics_calendar = new SG_iCalReader($ics_file);
    return $ics_calendar->getEvents();
  }

}
