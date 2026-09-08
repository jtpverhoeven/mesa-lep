<?PHP

class holidays {

    /**
     * Check if the analysis date is a weekend day
    * @param $date
    */

    public function isWeekend($date) 
    {
        return (date('N', strtotime($date)) >= 6);
    }

    /**
     * Check if  a date is a holiday
    * @param $date
    */
    
    public function isHoliday($date)
    {


        $year = date('Y', strtotime($date));
        $holidayArray = array();

        $xmas1 = new \DateTime($year . "-12-25");
        $xmas2 = new \DateTime($year . "-12-26");
        $newyear = new \DateTime($year . "-01-01");
        $kingday = new \DateTime($year . "-04-27");
        $bevrijding = new \DateTime($year . "-05-05");

        if ($kingday->format('w') == 0){
            $kingday->sub(new DateInterval('P1D'));
        }

        $easter = new \DateTime();
        $easter->setTimezone(new DateTimeZone('Europe/Amsterdam'));
        $easter->setTimestamp(easter_date($year));
        $easterMonday = clone $easter;
        $easterMonday->add(new \DateInterVal('P1D')); // 1 dag na pasen

        $ascension = clone $easter;
        $ascension->add(new \DateInterVal('P39D')); // 39 dagen na pasen

        $pinksteren = clone $ascension;
        $pinksteren->add(new \DateInterVal('P10D')); // 10 dagen na OLH hemelvaart
        $pinksterMaandag = clone $pinksteren;
        $pinksterMaandag->add(new \DateInterVal('P1D')); // 1 dag na pinksteren

        if ($year % 5 == 0)
        {
            array_push($holidayArray, $bevrijding->format('d-m-Y'));
        }

        array_push($holidayArray, $xmas1->format('d-m-Y'));
        array_push($holidayArray, $xmas2->format('d-m-Y'));
        array_push($holidayArray, $newyear->format('d-m-Y'));
        array_push($holidayArray, $easter->format('d-m-Y'));
        array_push($holidayArray, $easterMonday->format('d-m-Y'));
        array_push($holidayArray, $kingday->format('d-m-Y'));
        array_push($holidayArray, $ascension->format('d-m-Y'));
        array_push($holidayArray, $pinksteren->format('d-m-Y'));
        array_push($holidayArray, $pinksterMaandag->format('d-m-Y'));

        $checkDate =  new \DateTime($date);        

        if(in_array($checkDate->format('d-m-Y'),$holidayArray ))
        {
            return True;
        } 
        else
        {
            return False;
        }
    
    }

    public function getTypeOfSpecialDate($date)
    {

        //convert dd-mm-yyyy to a new DateTime object
        $dateObj =  new \DateTime($date);
        
        //is this a holiday?
        if($this->isHoliday($date) == True)
        {
            return 'holiday';
        }

        //is this a saturday? 
        if($dateObj->format('w') == 6)
        {
            return 'saturday';
        }

        //is this a sunday?
        if($dateObj->format('w') == 0)
        {
            return 'sunday';
        }

        return 'normal';


    }

    
    public function getReadDate($date )
    {

        $isHoliday = $this->isHoliday($date);
        $isWeekend = $this->isWeekend($date);

        //is fine
        if($isHoliday == False && $isWeekend == False)
        {
            return $date;
        } 

        else
        {
            $checkDate =  new \DateTime($date);

            
            do
            {
                $checkDate->add(new \DateInterVal('P1D'));
                $isHoliday = $this->isHoliday($checkDate->format('d-m-Y'));
                $isWeekend = $this->isWeekend($checkDate->format('d-m-Y'));
            } while($isHoliday == True || $isWeekend == True);

            return $checkDate->format('d-m-Y');
        
        }

    }

    public function getReadDateFromTimestamp($timestamp, $return='timestamp')
    {
        
        
        $datetime = new DateTime('@' . $timestamp);        
        
        $read = $this->getReadDate( $datetime->format('d-m-Y'));

        if($return === 'timestamp')
        {
            //this is a bit silly that we have to re-code it into a datetime object, but it is needed
            //for backward compatability in assuranceforms controller. We should probbably refactor this at 
            //some point

            $readdatetime = new DateTime($read);        
            return $readdatetime->getTimeStamp();

        }

        else
        {
            return $read;
        }

       
    }


}
