<?php

namespace Core;

/**
 * Encode and decode geohashes
 *
 * @see https://github.com/mattsta/krmt/blob/master/geo/geohash.c
 *
 */
class Geohash
{


    /**
     *  These are constraints from EPSG:900913 / EPSG:3785 / OSGEO:41001
     */
    const LAT_MIN = -85.05112878;

    const LAT_MAX = 85.05112878;

    const LNG_MIN = -180.0;

    const LNG_MAX = 180.0;

    /**
     * hash长度52bit (26 * 2)
     */
    private $precision = 26;

    /**
     * Decode a geohash and return an array with decimal lat,long in it
     */
    public function decode($hash)
    {
        $blat = 0;
        $blng = 0;
        $islng = true;
        for ($i = $this->precision * 2; $i > 0; $i--) {
            $bit = $hash & (1 << $i - 1);
            if ($islng) {
                $blng = $blng << 1;
                if ($bit > 0) {
                    $blng++;
                }
            } else {
                $blat = $blat << 1;
                if ($bit) {
                    $blat++;
                }
            }

            $islng = !$islng;
        }

        //now concert to decimal
        $lat = $this->binDecode($blat, $this->precision, self::LAT_MIN, self::LAT_MAX);
        $lng = $this->binDecode($blng, $this->precision, self::LNG_MIN, self::LNG_MAX);

        //figure out how precise the bit count makes this calculation
        $latErr = $this->calcError($this->precision, self::LAT_MIN, self::LAT_MAX);
        $lngErr = $this->calcError($this->precision, self::LNG_MIN, self::LNG_MAX);

        //how many decimal places should we use? There's a little art to
        //this to ensure I get the same roundings as geohash.org
        $latPlaces = max(1, -round(log10($latErr))) - 1;
        $lngPlaces = max(1, -round(log10($lngErr))) - 1;

        //round it
        return [round($lat, $latPlaces), round($lng, $lngPlaces)];
    }

    /**
     * Encode a hash from given lat and long
     */
    public function encode($lat, $lng)
    {
        //encode each as binary string
        $blat = $this->binEncode($lat, self::LAT_MIN, self::LAT_MAX, $this->precision);
        $blng = $this->binEncode($lng, self::LNG_MIN, self::LNG_MAX, $this->precision);

        $num = $this->precision - 1;
        $islng = true;
        $hash = 0;
        for ($i = 2 * $this->precision; $i > 0; $i--) {
            if ($islng) {
                $mask = 1 << $num;
                $num--;
                $bit = $blng & $mask;
            } else {
                $bit = $blat & $mask;
            }

            $hash = $hash << 1;
            if ($bit > 0) {
                $hash++;
            }

            $islng = !$islng;
        }

        return $hash;
    }

    /**
     * What's the maximum error for $bits bits covering a range $min to $max
     */
    private function calcError($bits, $min, $max)
    {
        $err = ($max - $min) / 2;
        while ($bits--) {
            $err /= 2;
        }
        return $err;
    }

    /**
     * create binary enchars of number as detailed in http://en.wikipedia.org/wiki/Geohash#Example
     * removing the tail recursion is left an exercise for the reader
     */
    private function binEncode($number, $min, $max, $bitcount)
    {
        if ($bitcount == 0) {
            return 0;
        }

        //this is our mid point - we will produce a bit to say
        //whether $number is above or below this mid point
        $bitcount--;
        $mid = ($min + $max) / 2;

        if ($number <= $mid) {
            return $this->binEncode($number, $min, $mid, $bitcount);
        }

        return (1 << $bitcount) + $this->binEncode($number, $mid, $max, $bitcount);
    }

    /**
     * decodes binary enchars of number as detailed in http://en.wikipedia.org/wiki/Geohash#Example
     * removing the tail recursion is left an exercise for the reader
     */
    private function binDecode($binary, $bitlen, $min, $max)
    {
        $mid = ($min + $max) / 2;

        if ($bitlen == 0) {
            return $mid;
        }

        $bitlen--;
        $bit = $binary & (1 << $bitlen);

        if ($bit > 0) {
            $binary = $binary - $bit;
            return $this->binDecode($binary, $bitlen, $mid, $max);
        }

        return $this->binDecode($binary, $bitlen, $min, $mid);
    }
}
