<?php

namespace Icinga\Module\Osm\Controllers;
 
use Icinga\Module\Monitoring\Controller;
 
class DataController extends Controller
{
    public function indexAction()
    {

	$meinarray = array();
        $query = $this->backend
            ->select()
            ->from('customvar', array(
                'host_name',
                'varname',
		'varvalue'))
            ->where('varname', 'geolocation');
	    
	$okhosts="";
	if (count($query->fetchAll()) > 0 ) {
        	foreach ($query as $row) {
			$query2 = $this->backend
            			->select()
	            		->from('servicestatus', array(
	                		'host_name',
	                		'service_display_name',
        					'service_host_name',
                            'service',
	                		'service_state'))
		    		->where('service_host_name', $row->host_name);
	
			$this->applyRestriction('monitoring/filter/objects', $query2);
			$okhosts.="\n".'{'."\n"
					."\t".'"geometry" : {'."\n"
					."\t\t".'"type" : "Point",'."\n"
					."\t\t".'"coordinates" : ['.$row->varvalue.']'."\n"
					."\t".'},'."\n"
					."\t".'"type" : "Feature",'."\n"
					."\t".'"properties" : {'."\n"
					."\t\t".'"popupContent" : "'
                    .'<b>Hostname: <a href=\'monitoring/host/show?host='.$row->host_name.'\' >'.$row->host_name.'</a></b><table><tr><th>Status</th><th>Service</th></tr>';
            $worststatus=0;
			foreach ($query2 as $row2) {
                    switch ($row2->service_state){
                        case 0:
                            $state="<td style='background-color: #4b7; '>OK";
                            break;;
                        case 1:
                            $state="<td style='background-color: #fa4; '>WARNING";
                            if($worststatus<1 || $worststatus==99){
                                $worststatus=1;
                            }
                            break;;
                        case 2:
                            $state="<td style='background-color: #f56; '>CRITICAL";
                            if($worststatus<2 || $worststatus==3 || $worststatus==99){
                                $worststatus=2;
                            }
                            break;;
                        case 3:
                            $state="<td style='background-color: #a4f; '>UNKNOWN";
                            if($worststatus<=1 || $worststatus==99){
                                $worststatus=3;
                            }
                            if($worststatus==2){
                                $worststatus=2;
                            }
                            break;;
                        case 99:
                            $state="<td style='background-color: blue; '>PENDING";
                            if($worststatus==0){
                                $worststatus=99;
                            }
                            break;;
                    }
                    //$okhosts.='<tr>'.$state.' '.$worststatus.'</td><td><a href=\'monitoring/service/show?host='.$row->host_name.'&service='.$row2->service.'\' >'.$row2->service_display_name.'</td></tr>';
		    $okhosts.='<tr>'.$state.'</td><td><a href=\'monitoring/service/show?host='.$row->host_name.'&service='.$row2->service.'\' >'.$row2->service_display_name.'</td></tr>';

			}
					$okhosts.='</table>Location: '.$row->varvalue.'",'."\n"
                    ."\t".'"worststatus" : "'.$worststatus.'"'."\n"
					."\t".'},'."\n"
                    ."\t".'"id" : "'.$row->host_name.'"'."\n"
					."},";
        	}
	}
	$okhosts=substr($okhosts,0,-1);
	$okfeature=      '{'."\n"
			.'"type": "FeatureCollection",'."\n"
			.'"features": ['.$okhosts.']'."\n"
			.'}';
//	echo "<pre>";
	echo $okfeature;
	
	exit;
    }
}
