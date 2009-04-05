How to configure FatSync to use Harvest (www.getharvest.com)
------------------------------------------------------------

1. Create a new instance of the 'harvest' class:

	$harvest = new harvest;

2. Assign a subdomain. For http://yourbusiness.harvestapp.com, use:

	$harvest->subdomain = 'yourbusiness';

3. Assign a username (your email address) and password:

	$harvest->username = 'you@yourdomain.com';
	$harvest->password = 'y0urpassw0rd';

4. Optionally, Set your sync_additions, sync_updates and
   sync_deletions options (see readme.htm for complete explanation).

   $harvest->sync_additions = true;
   $harvest->sync_updates = true;
   $harvest->sync_deletions = true;

5. Add the instance to the $data_sources array.

	$data_sources[] = $harvest;