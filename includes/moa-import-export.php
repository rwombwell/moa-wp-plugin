<?php

/*******************
 * MySQL CODE TO EXPORT USERS LAST LOGIN TIMES AND ROLES
 
select
    q1.ID, q1.user_login, q1.display_name, q1.user_email, q1.Role,  q1.wp_capabilities,
    substring_index(q1.user_registered,' ',1) AS Join_date,
    q3.Last_login_UM
from
(select 
    u.*,
    m1.meta_value as wp_capabilities,
    substring_index(substring_index(m1.meta_value, '"um_', -1), '"', 1) as Role
    from wp_users u
    inner join wp_usermeta m1
    on u.ID = m1.user_id
    and m1.meta_key='wp_capabilities'
) q1
left outer join
    (select u.ID,
        DATE_FORMAT( FROM_UNIXTIME(m3.meta_value), '%Y-%m-%d') as Last_login_UM
        from wp_users u
        inner join wp_usermeta m3
        on u.ID = m3.user_id
        and m3.meta_key = '_um_last_login'
) q3
    on q1.ID = q3.ID
ORDER BY q1.ID;
 ******************/
       
/***************
 * IMPORT TABLE moa_import and match on name, email etc.
 * Used to reconcile external s/sheet on users with internal list
 ***************/

add_shortcode( 'moa-import-csv', 'moa_import_csv_fn' );
function moa_import_csv_fn( $atts , $content) {
    global $wpdb;
    $i = 0; $d = 0; $un=0;
    $matched_on_name = "WEB_LOGIN,WEB_BOAT,IMP_BOAT,IMP_NAME,IMP_EMAIL,WEB_ROLES\n";
    $unmatched = "IMP_BOAT,IMP_NAME,IMP_EMAIL\n";;       // will hold list of failed import recs, where display names don't match either
    $matched = "WEB_LOGIN,WEB_BOAT,IMP_BOAT,IMP_NAME,IMP_EMAIL,WEB_ROLES\n";
    
    $fldsNotWanted = array("first_name","last_name","user_email","yacht_latlong");
    $result = $wpdb->get_results( "SELECT * FROM moa_import" );
    $num_rows = count($result);
    
    foreach ($result as $row) {
        $imp_email      = $row->user_email;
        $imp_yacht_name = $row->yacht_name;
        $imp_disp_name  = $row->first_name . " " . $row->last_name;;
        
        $user = get_user_by( 'email',$imp_email);
        
        if ( $user ) {
            ++$i;           // incr total matched count
            $user_id = $user->ID;
            $roles = implode($user->roles, ",");
            $usermeta = get_user_meta( $user_id  );
            $yacht = ( isset( $usermeta["yacht_name"]) ? $usermeta["yacht_name"][0] : "");
            $matched .= "{$user->data->user_login},{$yacht},{$imp_disp_name},{$imp_email},{$roles}\n";
            foreach ($row as $key => $value) {
                if ( !in_array( $key, $fldsNotWanted) ) {
                    if ( !empty($value) ) {
                        update_user_meta( $user_id,  $key, trim($value) ) ;
                    }
                }
            }
        } else { // failed to match on email so try display name
            // note cant use get_user_by() for display_name because the function only supports ID, slug, email or login
            $u = $wpdb->get_row( $wpdb->prepare("SELECT ID FROM wp_users WHERE display_name = %s", $imp_disp_name ) );
            if ($u) {
                ++$d;           // incr disp name matched count
                $user_id = $u->ID;
                $user = get_user_by( 'id', $user_id);
                $usermeta = get_user_meta( $user_id );
                $yacht = ( isset( $usermeta["yacht_name"]) ? $usermeta["yacht_name"][0] : "");
                $roles = implode($user->roles, ",");
                $matched_on_name.= "{$user->data->user_login},{$yacht},{$imp_yacht_name},{$imp_disp_name},{$imp_email},{$roles}\n";
                foreach ($row as $key => $value) {
                    if ( !in_array( $key, $fldsNotWanted) ) {
                        if ( !empty($value) ) {
                            update_user_meta( $user_id,  $key, trim($value) ) ;
                        }
                    }
                }
                update_user_meta( $user_id,  "user_email2", trim($row->user_email) ) ;
                
            } else { // failed to match on either display name or email, so record this
                ++$un;
                $unmatched .= "{$imp_yacht_name},{$imp_disp_name},{$imp_email}\n";
            }
            
        }
        // matched on disp name but not email, so record this as email error
    }
    $output ="Results from Import:
Total no of Import records: {$num_rows}
Matched on Email address  : {$i}
Matched on Display Name   : {$d}
Unmatched on either       : {$un}
========================================
Total Matched on Email  :
{$matched}
========================================<br>
Total Matched on Names (not Email)  :
{$matched_on_name}
========================================
Total Unmatched on either Emails and Name   :
{$unmatched}
";

// write errors to file to root of system
$file = plugin_dir_path( __FILE__ ) . 'user-import-errors.csv';
$fp = fopen( $file, 'w');
fwrite($fp, $output );
fclose($fp);

// display results on screen, replace \n with <br>
$ret = preg_replace("/[\n\r]/", "<br>", $output );
return $ret;
}


/************************************************
 * App_Coordinates_From_Cartesian, a geographical class that
 * Converts Cartesian geographical values into geographic coordinates
 * ie Eastings and Northings into Longitude and Latitude
 * eg: 464343 and 464343 --> (float) 50.798714617948 and float(-1.0868854103489)
 * see https://www.rogerethomas.com/blog/converting-northings-eastings-cartesian-to-coordinates-latitude-longitude-in-php
 *
 * Rob comment: Works fine. typical usage of the Class library
 *
 $class = new App_Coordinates_From_Cartesian($Easting , $northing);
 $result = $class->Convert();
 var_dump($result);
 return $result
 // returns array named "latitude" and "longitude"
 
 * ******************************************************************
 *
 * @author Roger E Thomas
 * @package App_
 * @subpackage App_Coordinates_From_
 * @copyright 2012 Roger E Thomas | http://www.rogerethomas.com
 * ******************************************************************
 *
 *
 */
class App_Coordinates_From_Cartesian {
    
    /**
     * Holds the final Latitude
     * @var Integer
     */
    public $latitude;
    
    /**
     * Holds the final Longitude
     * @var Integer
     */
    public $longitude;
    
    /**
     * Holds the initial easting
     * @var Integer
     */
    public $easting;
    
    /**
     * Holds the initial northing
     * @var Integer
     */
    public $northing;
    
    function __construct($easting, $northing)
    {
        if (is_int($easting) && is_int($northing)) {
            $this->easting = $easting;
            $this->northing = $northing;
        }
        else {
            $this->easting = $easting;
            $this->northing = $northing;
        }
    }
    
    /**
     * Uses the $this->northing and $this->easting set in __construct to return the
     * latitude and longitude of the final point.
     * @return array
     */
    function Convert() {
        
        $East = $this->easting;
        $North = $this->northing;
        if ($East == "" || $East == 0 || $North == "" || $North == 0) {
            $this->latitude = 0;
            $this->longitude = 0;
            return array('latitude' => 0, 'longitude' => 0);
        }
        
        $a  = 6377563.396; // Semi-major axis, a
        $b  = 6356256.910; //Semi-minor axis, b
        $e0 = 400000.000; //True origin Easting, E0
        $n0 = -100000.000; //True origin Northing, N0
        $f0 = 0.999601271700; //Central Meridan Scale, F0
        
        $PHI0 = 49.0; // True origin latitude, j0
        $LAM0 = -2.0; // True origin longitude, l0
        
        //Convert angle measures to radians
        $RadPHI0 = $PHI0 * (M_PI / 180);
        $RadLAM0 = $LAM0 * (M_PI / 180);
        
        //Compute af0, bf0, e squared (e2), n and Et
        $af0 = $a * $f0;
        $bf0 = $b * $f0;
        $e2 = ($af0*$af0 - $bf0*$bf0 ) / ($af0*$af0);
        $n = ($af0 - $bf0) / ($af0 + $bf0);
        $Et = $East - $e0;
        
        //Compute initial value for latitude (PHI) in radians
        $PHId = $this->_initialLatitude($North, $n0, $af0, $RadPHI0, $n, $bf0);
        
        $sinPHId2 = pow(sin($PHId),  2);
        $cosPHId  = pow(cos($PHId), -1);
        
        $tanPHId  = tan($PHId);
        $tanPHId2 = pow($tanPHId, 2);
        $tanPHId4 = pow($tanPHId, 4);
        $tanPHId6 = pow($tanPHId, 6);
        
        //Compute nu, rho and eta2 using value for PHId
        $nu = $af0 / (sqrt(1 - ($e2 * $sinPHId2)));
        $rho = ($nu * (1 - $e2)) / (1 - $e2 * $sinPHId2);
        $eta2 = ($nu / $rho) - 1;
        
        //Compute Longitude
        $X    = $cosPHId / $nu;
        $XI   = $cosPHId / (   6 * pow($nu, 3)) * (($nu / $rho)         +  2 * $tanPHId2);
        $XII  = $cosPHId / ( 120 * pow($nu, 5)) * (5  + 28 * $tanPHId2  + 24 * $tanPHId4);
        $XIIA = $cosPHId / (5040 * pow($nu, 7)) * (61 + 662 * $tanPHId2 + 1320 * $tanPHId4 + 720 * $tanPHId6);
        
        $VII  = $tanPHId / (  2 * $rho * $nu);
        $VIII = $tanPHId / ( 24 * $rho * pow($nu, 3)) * ( 5 +  3 * $tanPHId2 + $eta2 - 9 * $eta2 * $tanPHId2 );
        $IX   = $tanPHId / (720 * $rho * pow($nu, 5)) * (61 + 90 * $tanPHId2 + 45 * $tanPHId4 );
        
        $long = (180 / M_PI) * ($RadLAM0 + ($Et * $X) - pow($Et,3) * $XI + pow($Et,5) * $XII - pow($Et,7) * $XIIA);
        $lat  = (180 / M_PI) * ($PHId - (pow($Et,2) * $VII) + (pow($Et, 4) * $VIII) - (pow($Et, 6) * $IX));
        
        $this->latitude = $lat;
        
        $this->longitude = $long;
        
        return array('latitude' => $lat, 'longitude' => $long);
    }
    
    /**
     * Helper function to compute meridional arc.
     // * @param $bf0 ellipsoid semi major axis multiplied by central meridian scale factor (bf0) in meters;
     // * @param $n n (computed from a, b and f0);
     // * @param $PHI0 lat of false origin
     // * @param $PHI initial or final latitude of point IN RADIANS.
     */
    private function _meridianArc($bf0, $n, $PHI0, $PHI) {
        $n2 = pow($n, 2);
        $n3 = pow($n, 3);
        $ans  = ((1 + $n + ((5 / 4) * ($n2)) + ((5 / 4) * $n3)) * ($PHI - $PHI0));
        $ans -= (((3 * $n) + (3 * $n2) + ((21 / 8) * $n3)) * (sin($PHI - $PHI0)) * (cos($PHI + $PHI0)));
        $ans += ((((15 / 8) * $n2) + ((15 / 8) * $n3)) * (sin(2 * ($PHI - $PHI0))) * (cos(2 * ($PHI + $PHI0))));
        $ans -= (((35 / 24) * $n3) * (sin(3 * ($PHI - $PHI0))) * (cos(3 * ($PHI + $PHI0))));
        return $bf0 * $ans;
    }
    
    /**
     // * Helper function to compute initial value for Latitude IN RADIANS.
     // * @param {float} $North northing of point
     // * @param $n0 northing of false origin in meters;
     // * @param $afo semi major axis multiplied by central meridian scale factor in meters;
     // * @param $PHI0 latitude of false origin IN RADIANS;
     // * @param $n computed from a, b and f0
     // * @param $bfo ellipsoid semi major axis multiplied by central meridian scale factor in meters.
     */
    private function _initialLatitude($North, $n0, $afo, $PHI0, $n, $bfo) {
        
        
        //First PHI value (PHI1)
        $PHI1 = (($North - $n0) / $afo) + $PHI0;
        
        //Calculate M
        $M = $this->_meridianArc($bfo, $n, $PHI0, $PHI1);
        
        //Calculate new PHI value (PHI2)
        $PHI2 = (($North - $n0 - $M) / $afo) + $PHI1;
        
        //Iterate to get final value for InitialLat
        while ( abs($North - $n0 - $M) > 0.00001 ) {
            $PHI2 = (($North - $n0 - $M) / $afo) + $PHI1;
            $M = $this->_meridianArc($bfo, $n, $PHI0, $PHI2);
            $PHI1 = $PHI2;
        }
        
        return $PHI2;
    }
    
}
