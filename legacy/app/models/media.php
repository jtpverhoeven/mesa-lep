<?PHP

class media extends Model{

    public function mediaToday($innocDate)
    {

        date_default_timezone_set('Europe/Amsterdam');

        $samples = new Sample; 
        $samples->select('id'); 
        $samples->notInnoculated(); 
        $sampleIds = $samples->search();  

        $medSql = 'SELECT id,  short_name as name, prediction_qom, prediction_default_quant FROM media WHERE used_for_prediction = 1';
        $medData = $this->customQuery($medSql, array());

        $medData = array_columN($medData, null, 'id');

        $sql = 'SELECT results.id, results.assay_base, results.df ,
                assays.media_id
                FROM results
                LEFT JOIN assays
                ON results.assay_base = assays.id
                WHERE results.sample IN (' . implode(',',array_column($sampleIds, 'id'))  . ')';

        
        $result = $this->customQuery($sql, array());
        
        $media = array(); 

        foreach($result as $plate)
        {

            $thisMedia = json_decode($plate['media_id'], JSON_FORCE_OBJECT);
            foreach($thisMedia as $mediaId)
            {

                if(!array_key_exists($mediaId, $medData))
                {
                    continue; 
                }
            
                if(array_key_exists($mediaId, $media))
                {
                    $media[$mediaId]['count']++; 
                }

                else
                {
                    $media[$mediaId] = array(); 
                    $media[$mediaId]['count'] = 1; 
                    $media[$mediaId]['prediction_default_quant'] = $medData[$mediaId]['prediction_default_quant']; 
                    $media[$mediaId]['prediction_qom'] = $medData[$mediaId]['prediction_qom']; 
                    $media[$mediaId]['name'] = $medData[$mediaId]['name']; 
                }
                
            }
            
        }

       return $media; 


    }


}