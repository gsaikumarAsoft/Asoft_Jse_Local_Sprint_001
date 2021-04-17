<?php

namespace App\Helpers;

/*
0 = New
1 = Partial Fill
2 = Fill
4 = Cancelled
5 = Replace
6 = Pending Cancel
7 = Stopped
8 = Rejected
9 = Suspended (Order has been Gated)
E = Pending Replace
 */

class OrderStatus
{
    public static function  _New()
    {
        return "0";
    }
    public static function  PendingSent()
    {
        return "-1";
    }
    public static function  PartialFilled()
    {
        return "1";
    }
    public static function  Filled()
    {
        return "2";
    }
    public static function  Cancelled()
    {
        return "4";
    }
    public static function  PendingCancel()
    {
        return "6";
    }
    public static function  Replaced()
    {
        return "5";
    }
    public static function  Expired()
    {
        return "C";
    }
    public static function  _Private()
    {
        return "Z";
    }
    public static function  Unplaced()
    {
        return "U";
    }
    public static function  PendingReplace()
    {
        return "E";
    }
    public static function  Rejected()
    {
        return "8";
    }
    public static function  Failed()
    {
        return "Failed";
    }
    public static function  DoneForDay()
    {
        return "3";
    }
}
