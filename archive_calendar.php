<?php
/**
 * Calendar
 *
 * It display a calendar per month or per year, with a picture in each where needed
 *
 * @author the Whole Life To Learn
 * @package plugins
 *
 *
 * version 1.0: Creation of the plug-in
 * version 1.1: Improvement of the code
 * version 1.2: Added possible custom CSS
 * version 1.52: dépassement de tableau dans la fonction "next_calendar_image()"
 */

$plugin_description = gettext("Prints a calendar with a picture in each day where pictures belong to.");
$plugin_author = "the Whole Life To Learn";
$plugin_version = '1.52'; 
$option_interface = 'Calendar';

if (in_context(ZP_INDEX)) {
	zp_register_filter('theme_head','getPluginCss'); // insert the meta tags into the <head></head> if on a theme page.
}

function getPluginCss(){
	global $_zp_themeroot;

	$css = SERVERPATH.'/'.THEMEFOLDER.'/'. getCurrentTheme() . '/archive_calendar.css';
	if (file_exists($css)) {
		$css = $_zp_themeroot . '/archive_calendar.css';
	} else {
		$css = WEBPATH. '/plugins/archive_calendar/archive_calendar.css';
	}
	echo '<link rel="stylesheet" href="'.$css.'" type="text/css" />';
}

class Calendar {

	private $month;
	private $year;
	private $minDate;
	private $maxDate;
	private $strToTime;
	
	/**
	 * class constructor
	 *
	 */
	public function __construct() {
		$this->getDates();
		$this->variablesInit();
	}
	
	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		$tmp = array(
			gettext('Monthy/Yearly') => array(
				'key' => 'calendar_month_year',
				'type' => OPTION_TYPE_RADIO,
				'buttons' => array(
					gettext('Monthly') => 'OPT_MONTH',
					gettext('Yearly') => 'OPT_YEAR'
				),
				'desc' => gettext('Choose if you wish to display a monthly calendar (1 month per page or a yearly calendar (12 month per page)')
			)
		);
		
		return $tmp;
	}

	/**
	 * get the dates that will be used
	 *
	 * @return admin_login
	 */
	private function getDates() {
		if( !( isset($_GET['month']) AND is_numeric($_GET['month']) AND $_GET['month'] >= 1 AND $_GET['month'] <= 12) ) {
			$this->month = date('m');
		} else {
			$this->month = $_GET['month'];
		}
		
		if( !( isset($_GET['year']) AND is_numeric($_GET['year']) AND $_GET['year'] <= date('Y') ) ) {
			$this->year = date('Y');
		} else {
			$this->year = $_GET['year'];
		}
	}
	
	public function setDates($month, $year) {
		$this->month = $month;
		$this->year = $year;
		$this->variablesInit();
	}
	
	/**
	 * Initializes the variables needed for the process
	 *
	 */
	private function variablesInit() {
		$this->minDate = $this->year . '-' . $this->month . '-01';
		$this->maxDate = $this->year . '-' . ($this->month+1) . '-01'; // Last day of the month
		$this->strToTime = strtotime( $this->minDate );
		setOptionDefault('calendar_month_year', 'OPT_MONTH');
	}

	/**
	 * Fetches in the database the picture that should be displayed
	 *
	 * @return array
	 */
	protected function getMonth() {
		$allDates = array();
		$allFiles = array();
		$sql = "SELECT DATE_FORMAT(". prefix('images') . ".date, '%Y-%m-%d') as day, ". prefix('albums') . ".title, `filename`, `folder` FROM ". prefix('images') . ", ". prefix('albums') . " WHERE ". prefix('images') . ".albumid = ". prefix('albums') . ".id AND ". prefix('images') . ".date >= '$this->minDate' AND ". prefix('images') . ".date < '$this->maxDate'";
		if (!zp_loggedin()) {
			$sql .= ' AND ' . prefix('albums') . '.show = 1';
		}
		$hidealbums = getNotViewableAlbums();
		if (!is_null($hidealbums)) {
			foreach ($hidealbums as $id) {
				$sql .= ' AND `albumid`!='.$id;
			}
		}
		$sql .= ' GROUP BY day';
		$result = query_full_array($sql);
		
		return $result;
	}

	/**
	 * Goes to the next picture
	 *
	 */
	protected function next_calendar_image( $images, $number ) {
		if( $number < count( $images ) ){
			global $_zp_current_image;
			set_context( ZP_IMAGE );
			$_zp_current_image = $images[$number];
		}
	}

	/**
	 * Creates the pictures objects that will be processed
	 *
	 * @return array
	 */
	protected function imgObjCreation( $array ) {
		$temp = array();
		$galleryObj = new Gallery();
		foreach( $array as $a )
		{
			$album = new Album( $galleryObj, $a['folder'] );
			$image = newImage( $album, $a['filename'] );
			$temp[] = $image;
		}
		
		return $temp;
	}

	/**
	 * Creates the the header of the calendar
	 *
	 * @return string
	 */
	protected function getCalHeader() {
		$line = '<li class="month">';
		
		if( getOption('calendar_month_year') == 'OPT_MONTH' ) {
			$line .= '<a href="' . WEBPATH . '/index.php?p=archive&year=' . date('Y', strtotime( $this->minDate . ' -1 Month') ) . '&month=' . date('m', strtotime( $this->minDate . ' -1 Month') ) . '" id="prev">&lt;&lt;</a>';
			$line .= ' ' . strftime('%B', $this->strToTime ) .' '. $this->year . ' ';
			$line .= '<a href="' . WEBPATH . '/index.php?p=archive&year=' . date('Y', strtotime( $this->minDate . ' +1 Month') ) . '&month=' . date('m', strtotime( $this->minDate . ' +1 Month') ) . '" id="next">&gt;&gt;</a>';
		} elseif( getOption('calendar_month_year') == 'OPT_YEAR' ) {
			$line .= '<a href="' . WEBPATH . '/index.php?p=archive&year=' . date('Y', strtotime( $this->minDate . ' -1 Year') ) . '&month=' . date('m', strtotime( $this->minDate . ' -1 Year') ) . '" id="prev">&lt;&lt;</a>';
			$line .= ' ' . strftime('%B', $this->strToTime ) .' '. $this->year . ' ';
			$line .= '<a href="' . WEBPATH . '/index.php?p=archive&year=' . date('Y', strtotime( $this->minDate . ' +1 Year') ) . '&month=' . date('m', strtotime( $this->minDate . ' +1 Year') ) . '" id="next">&gt;&gt;</a>';
		}
		
		$line .= '</li>';
		$line .= '<li class="first weekday">'.strftime('%A', strtotime('2009-12-07' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-08' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-09' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-10' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-11' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-12' ) ).'</li>';
		$line .= '<li class="weekday">'.strftime('%A', strtotime('2009-12-13' ) ).'</li>';
		
		return $line;
	}

	/**
	 * Creates the body of the calendar
	 *
	 * @return string
	 */
	protected function getCalBody() {
		$d = 0;
		$line = null;
		$allDays = $this->getMonth();
		$days = $this->imgObjCreation( $allDays );
		$this->next_calendar_image($days, 0);
		for ( $i = ( 2 - date('N', $this->strToTime ) ); $i <= date('t', $this->strToTime ); $i++ ) {
			$line .= '<li class="';
			if ( $i > 0 ) {
				if( date('N', strtotime( $this->year . '-' . $this->month . '-' . $i ) ) == 1 ){ // Mondays
					$line .= 'first ';
				}
				$line .= 'day">' . $i;
			}
			else { // Box that is displayed for "beauty" reason
			    if($i == ( 2 - date('N', $this->strToTime ))) {
				$line .= 'first ';
			    }
			    $line .= 'empty">';
			}
			if ( ($i > 0) AND !empty($days) AND ($i == substr(getImageDate(), 8, 2) ) ) {
				$line .= '<a href="'.getSearchURL('', substr( getImageDate(), 0, 10 ), 'date', 0).'">';
				$line .= '<img src="' . getCustomImageURL(95, NULL, NULL, 95, 90, NULL, NULL, false) . '">';
				$line .= '</a>';
				if ( $d < count( $days ) ) {
					$d++;
					$this->next_calendar_image($days, $d);
				}
			}
			$line .='</li>';
		}
		
		return $line;
	}

	/**
	 * Get the monthly calendar
	 *
	 * @return string
	 */
	public function getMonthCalendar() {
		$tmp = $this->getCalHeader();
		$tmp .= $this->getCalBody();
		
		return $tmp;
	}

	/**
	 * Prints a monthly calendar. The user can't modify the data
	 *
	 */
	public function printMonthCalendar() {
		echo '<ul id="calendar">';
		echo $this->getMonthCalendar();
		echo '</ul>';
	}
	
	/**
	 * get a yearly calendar
	 *
	 * @return string
	 */
	public function getYearCalendar() {
		$cal = null;
		
		for( $i = 1; $i <= 12; $i++ ) {
			$this->setDates($i, $this->year);
			$cal .= $this->getMonthCalendar();
			$cal .= '<div class="clear"></div>';
		}
		
		return $cal;
	}
	
	/**
	 * Prints on the screen a yearly based calendar. The data can't be modified by the user.
	 *
	 */
	public function printYearCalendar() {
		echo '<ul id="calendar">';
		echo $this->getYearCalendar();
		echo '</ul>';
	}
}

function printMonthCalendar() {
	$cal = new Calendar();
	$cal->printMonthCalendar();
}

function getMonthCalendar() {
	$cal = new Calendar();
	$cal->getMonthCalendar();
}

function printYearCalendar() {
	$cal = new Calendar();
	$cal->printYearCalendar();
}

function getYearCalendar() {
	$cal = new Calendar();
	$cal->getYearCalendar();
}

/**
 * Prints a monthly or yearly based calendar depending on the option defined in the administration panel
 *
 */
function printCalendar() {
	$cal = new Calendar();
	if( getOption('calendar_month_year') == 'OPT_MONTH' ) {
		$cal->printMonthCalendar();
	} elseif( getOption('calendar_month_year') == 'OPT_YEAR' ) {
		$cal->printYearCalendar();
	}
}

?>