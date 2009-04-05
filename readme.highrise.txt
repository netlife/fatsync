How to configure FatSync to use Highrise (www.highrisehq.com)
------------------------------------------------------------

1. Create a new instance of the 'highrise' class:

	$highrise = new highrise;

2. Assign a subdomain. For http://yourbusiness.highrisehq.com, use:

	$highrise->subdomain = 'yourbusiness';

3. Assign a username and password:

	$highrise->username = 'yourusername';
	$highrise->password = 'y0urpassw0rd';

4. Optionally, assign a tag which will be used to search for
   existing records, as well as tag new records. If you omit this
   option, no tags will be assigned, and all records will be synced.
   You must have at least one existing record with this tag.

   $highrise->tag = 'client';

5. Optionally, enable SSL mode. You must enable this option if it is
   enabled in Highrise.

   $highrise->ssl = true;

6. Optionally, Set your sync_additions, sync_updates and
   sync_deletions options (see readme.htm for complete explanation).

   $highrise->sync_additions = true;
   $highrise->sync_updates = true;
   $highrise->sync_deletions = true;

7. Add the instance to the $data_sources array.

	$data_sources[] = $highrise;