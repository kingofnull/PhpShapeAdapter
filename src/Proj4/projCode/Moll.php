<?php
namespace ShpAdapter\Proj4\projCode;

/**
 * Author : Julien Moquet
 * 
 * Inspired by Proj4JS from Mike Adair madairATdmsolutions.ca
 *                      and Richard Greenwood rich@greenwoodma$p->com 
 * License: LGPL as per: http://www.gnu.org/copyleft/lesser.html 
 */
/*******************************************************************************
  NAME                            MOLLWEIDE

  PURPOSE:	Transforms input longitude and latitude to Easting and
  Northing for the MOllweide projection.  The
  longitude and latitude must be in radians.  The Easting
  and Northing values will be returned in meters.

  PROGRAMMER              DATE
  ----------              ----
  D. Steinwand, EROS      May, 1991;  Updated Sept, 1992; Updated Feb, 1993
  S. Nelson, EDC		Jun, 2993;	Made corrections in precision and
  number of iterations.

  ALGORITHM REFERENCES

  1.  Snyder, John P. and Voxland, Philip M., "An Album of Map Projections",
  U.S. Geological Survey Professional Paper 1453 , United State Government
  Printing Office, Washington D.C., 1989.

  2.  Snyder, John P., "Map Projections--A Working Manual", U.S. Geological
  Survey Professional Paper 1395 (Supersedes USGS Bulletin 1532), United
  State Government Printing Office, Washington D.C., 1987.
 ****************************************************************************** */

use proj4php\Proj4php;
use proj4php\Common;

class Moll
{
    /* Initialize the Mollweide projection
      ------------------------------------ */

    public function init() {
        //no-op
    }

    /* Mollweide forward equations--mapping lat,long to x,y
      ---------------------------------------------------- */
    public function forward( $p ) {

        /* Forward equations
          ----------------- */
        $lon = $p->x;
        $lat = $p->y;

        $delta_lon = \ShpAdapter\Proj4\Common::adjust_lon( $lon - $this->long0 );
        $theta = $lat;
        $con = \ShpAdapter\Proj4\Common::PI * sin( $lat );

        /* Iterate using the Newton-Raphson method to find theta
          ----------------------------------------------------- */
        for( $i = 0; true; ++$i ) {
            $delta_theta = -($theta + sin( $theta ) - $con) / (1.0 + cos( $theta ));
            $theta += $delta_theta;
            if( abs( $delta_theta ) < \ShpAdapter\Proj4\Common::EPSLN )
                break;
            if( $i >= 50 ) {
                Proj4php::reportError( "moll:Fwd:IterationError" );
                //return(241);
            }
        }
        $theta /= 2.0;

        /* If the latitude is 90 deg, force the x coordinate to be "0 . false easting"
          this is done here because of precision problems with "cos(theta)"
          -------------------------------------------------------------------------- */
        if( \ShpAdapter\Proj4\Common::PI / 2 - abs( $lat ) < \ShpAdapter\Proj4\Common::EPSLN )
            $delta_lon = 0;
        $x = 0.900316316158 * $this->a * $delta_lon * cos( $theta ) + $this->x0;
        $y = 1.4142135623731 * $this->a * sin( $theta ) + $this->y0;

        $p->x = $x;
        $p->y = $y;
        return $p;
    }

    /**
     *
     * @param type $p
     * @return type 
     */
    public function inverse( $p ) {
        #$theta;
        #$arg;

        /* Inverse equations
          ----------------- */
        $p->x-= $this->x0;
        //~ $p->y -= $this->y0;
        $arg = $p->y / (1.4142135623731 * $this->a);

        /* Because of division by zero problems, 'arg' can not be 1.0.  Therefore
          a number very close to one is used instead.
          ------------------------------------------------------------------- */
        if( abs( $arg ) > 0.999999999999 )
            $arg = 0.999999999999;
        $theta = asin( $arg );
        $lon = \ShpAdapter\Proj4\Common::adjust_lon( $this->long0 + ($p->x / (0.900316316158 * $this->a * cos( $theta ))) );
        if( $lon < (-\ShpAdapter\Proj4\Common::PI) )
            $lon = -\ShpAdapter\Proj4\Common::PI;
        if( $lon > \ShpAdapter\Proj4\Common::PI )
            $lon = \ShpAdapter\Proj4\Common::PI;
        $arg = (2.0 * $theta + sin( 2.0 * $theta )) / \ShpAdapter\Proj4\Common::PI;
        if( abs( $arg ) > 1.0 )
            $arg = 1.0;
        $lat = asin( $arg );
        //return(OK);

        $p->x = $lon;
        $p->y = $lat;
        
        return $p;
    }
}
