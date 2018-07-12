
<?php

/**
 * Define vendor profile usage credit
 *
 * @package plugins.reach
 * @subpackage model
 *
 */
class kReoccurringVendorCredit extends kTimeRangeVendorCredit
{
	/**
	 *  @var VendorCreditRecurrenceFrequency
	 */
	protected $frequency;

	/**
         *  @var string
         */
	protected $periodStartDate;

	/**
 	*  @var string
 	*/
	protected $periodEndDate;

	/**
 	* @param string $toDate
 	*/
	public function setFromDate($toDate)
	{
		parent::setFromDate($toDate);
        $this->periodStartDate = $this->fromDate;

	}

	/**
	 * @param string $toDate
	 */
	public function setToDate($toDate)
	{
		$endOfDay = strtotime("tomorrow", $toDate) - 1;
		$this->toDate = $endOfDay;
	}

	/**
	 * @param string $toDate
	 */
	public function getToDate()
	{
		return $this->toDate;
	}

	/**
	 * @return string $frequency
	 */
	public function getFrequency()
	{
		return $this->frequency;
	}

	/**
	 * @param ScheduleEventRecurrenceFrequency $frequency
	 */
	public function setFrequency($frequency)
	{
		$this->frequency = $frequency;
	}

	public function syncCredit($reachProfileId)
	{
		$syncedCredit = parent::syncCredit($reachProfileId);
		if ( $this->getLastSyncTime() > $this->periodEndDate )
		{
			$this->calculateNextPeriodDates( $this->periodEndDate, $this->getLastSyncTime());
			$this->setSyncedCredit(0);
		}
		return $syncedCredit;
	}

	       public function calculateNextPeriodDates($startTime,$currentDate)
	       {
		       $endTime = strtotime('+1 ' . $this->getFrequency(), $startTime);
		       while ($endTime < $currentDate)
		       {
			       $startTime = $endTime;
			       $endTime = strtotime('+1 ' . $this->getFrequency(), $endTime);
		       }
		       $this->periodStartDate = $beginOfDay = strtotime("today", $startTime);
		       $this->periodEndDate = min($endTime, $this->getToDate());
		       $this->periodEndDate = $endOfDay = strtotime("tomorrow", $this->periodEndDate) - 1;

	       }

      public function setPeriodDates()
       {
	       $this->periodStartDate = $this->getFromDate();
	       $this->periodEndDate = $this->getFromDate();
	       $this->calculateNextPeriodDates($this->periodEndDate, time());
       }

        /***
        * @param $date
        * @return int
        */
       public function getCurrentCredit($includeOverages = true)
       {
               $now = time();
               if ( $now < $this->periodStartDate || $now > $this->periodEndDate )
               {
                       KalturaLog::debug("Current date [$now] is not in credit time range  [from - $this->periodStartDate , to - $this->periodEndDate] ");
                       return 0;
               }

               $credit = $this->credit;
               if($this->allowOverage)
                       $credit += $this->overageCredit;

               return $credit;
       }

       /***
        * @return bool
        */
       public function isActive($time = null)
       {
               $now = $time != null ? $time : time();
               if (!parent::isActive($now))
                       return false;
               if ( $now < $this->periodStartDate || $now > $this->periodEndDate )
               {
                       KalturaLog::debug("Current date [$now] is not in frequency credit time Range cycle [from - $this->periodStartDate to - $this->periodEndDate] ");
                       return false;
               }
               return true;
       }

}
