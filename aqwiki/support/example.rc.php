<?PHP
// $Id 

// Example of a User authentication file. Only this user can
// view the Wiki. In this case, only "aquarion"
$_EXTRAS['reqUser'] = "aquarion";

// Example of a Group authentication file. Only these users can
// view the Wiki. In this case, only the users 
// "aquarion", "lonecat" and "ccooke" can view the wiki
$_EXTRAS['reqUsers'] = "aquarion,lonecat,ccooke";

// This means that only "aquarion" is allowed to edit the wiki
$_EXTRAS['reqEdit'] = "aquarion";

// And this means that only "aquarion", "lonecat" or "ccooke" 
// are allowed to edit the wiki
$_EXTRAS['reqEdit'] = "aquarion,lonecat,ccooke";


?>