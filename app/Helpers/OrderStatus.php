


<?php

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

abstract class OrderStatus
{

    const _New = "0";
    const PartialFilled = "1";
    const Filled = "2";
    const Cancelled = "4";
    const Replaced = "5";
    const Expired = "C";
    const _Private = "Z";
    const Unplaced = "U";
    const Inactive = "E";
    const Rejected = "8";
    const Failed = "Failed";

}