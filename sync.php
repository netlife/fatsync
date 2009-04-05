<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

//load include files and local data
include_once('init.php');

// This array contains keys of matches which have already been synced, and
// can safely be skipped for the rest of the cycle.

$skip['clients']=array();
$skip['contacts']=array();

// check for new clients

foreach($data_sources as $ds_key => $ds) if($ds->sync_additions) {

	trace(sprintf('Looking for new clients in data source %s (%s)',$ds_key,get_class($ds)));

	if(is_array($clients=$ds->clients())) foreach($clients as $client) {

		// find this client's match list
		$match=false;
		foreach($local['matches']['clients'] as $i) if($i[$ds_key]['id']==$client->id) $match=true;
		if(!$match) {

			// match does not exist. create it.
			trace(sprintf('Found new client #%s "%s"',$client->id,$client->fields['name']));
			$match=array();
			$match[$ds_key]=array('id'=>$client->id,'hash'=>$client->hash());
			$match['lastsync']=time();

			//check other data sources
			foreach($data_sources as $k=>$ds2) if($k!=$ds_key) {

				$partner=false;

				if(is_array($clients2=$ds2->clients())) foreach($clients2 as $i)
					if(strtolower($i->fields['name'])==strtolower($client->fields['name']))
						$partner=$i;

				if(!$partner) {

					//partner not found in other data source. add it.
					$partner=$ds2->add($client);

					trace(sprintf('Added client #%s "%s" to data source %s (%s)',$partner->id,$partner->fields['name'],$k,get_class($ds2)));

				} else trace(sprintf('Paired with client #%s "%s" from data source %s (%s)',$partner->id,$partner->fields['name'],$k,get_class($ds2)));

				// add new/located partner to match list
				$match[$k]=array('id'=>$partner->id,'hash'=>$partner->hash());

			}

			// add new match to local data
			$local['matches']['clients'][]=$match;

			// add new match's key to the skip list
			$keys=array_keys($local['matches']['clients']);
			$skip['clients'][]=$keys[count($keys)-1];

			// quit if we're maxed out on calls
			check_max_calls();
		}
	}
}

// re-order match list so that the oldest ones come first
hx::csort($local['matches']['clients'], 'lastsync asc', false);

// check for updates to client records

// first, make sure all data sources flagged for update scans are cached, to minimize API calls
trace('Caching client data');
foreach($data_sources as $ds) if($ds->sync_updates) $ds->clients();

check_max_calls(); // just in case!

// loop through match list
trace('Checking for updated clients');

foreach($local['matches']['clients'] as $k=>&$match) if(!in_array($k, $skip['clients'])) {

	//check hashes against data source records for data sources with sync_updates enabled
	foreach($data_sources as $ds_key=>$ds) if($match[$ds_key]['id']&&$ds->sync_updates) {

		$client=$ds->client($match[$ds_key]['id']);

		if(!($client instanceof fs_client)) {

			// client has been deleted from this data source.

			trace(sprintf('Client #%s has been deleted from data source %s (%s)', $match[$ds_key]['id'], $ds_key,get_class($ds)));

			if($ds->sync_deletions) {

				// delete records from other sources
				foreach($data_sources as $ds2_k=>$ds2) if($match[$ds2_k]['id'] && $ds2_k!=$ds_key) {

					$client=new fs_client($ds2);
					$client->id=$match[$ds2_k]['id'];
					$client->delete();

					trace(sprintf('Deleted client #%s from data source %s (%s)', $match[$ds2_k]['id'], $ds2_k,get_class($ds2)));

				}

				check_max_calls();

				// delete the match
				unset($local['matches']['clients'][$k]);

				// skip to next match
				continue 2;

			} else {

				// zero out record's id in the match, so it won't be scanned again
				$match[$ds_key]['id']=0;

			}
		} else {

			if(!$client->hash($match[$ds_key]['hash'])) {

				// client has been modified. update other data sources.

				trace(sprintf('Client #%s "%s" on data source %s (%s) has been modified. Updating other data sources...',$client->id,$client->fields['name'],$ds_key,get_class($ds)));

				foreach($data_sources as $ds2_k=>$ds2) if($ds2_k!=$ds_key) {

					// get the old data based on ID stored in match list
					$partner=$ds2->client($match[$ds2_k]['id']);

					// copy changes to partner record
					$partner->fields=$client->fields;

					// save the changes
					$partner->save();
					trace(sprintf('Updated client #%s "%s" on data source %s (%s)',$partner->id,$partner->fields['name'],$ds2_k,get_class($ds2)));

					// update match list with new hash
					$match[$ds2_k]['hash']=$partner->hash();
				}

				// update match list with new hash and time
				$match[$ds_key]['hash']=$client->hash();
				$match['lastsync']=time();

				// add this match to the skip list
				$skip['clients'][]=$k;
			}

			check_max_calls();

			// check for new contacts for this client
			if($ds->sync_additions) if(is_array($contacts=$ds->contacts($client->id))) foreach($contacts as $contact) {

				$found=false;
				foreach($local['matches']['contacts'] as $k=>$i) if($i[$ds_key]['id']==$contact->id) $found=true;

				if(!$found) {

					// this is a new contact. create a match.

					trace(sprintf('Found new contact "%s" for company "%s" on data source %s (%s)',
						$contact->fields['email'],$client->fields['name'],$ds_key,get_class($ds)));

					$cmatch=array();
					$cmatch[$ds_key]=array('id'=>$contact->id,'hash'=>$contact->hash());
					$cmatch['lastsync']=time();

					// check other data sources
					foreach($data_sources as $ds2_k=>$ds2) if($ds2_k!=$ds_key) {

						$partner=false;
						if(is_array($cons2=$ds2->contacts($match[$ds2_k]['id']))) foreach($cons2 as $con2)
							if($con2->fields['email']==$contact->fields['email'])
								$partner=$con2;

						if(!$partner) {

							// partner not found. create it.
							$partner=$ds2->add($contact,$match[$ds2_k]['id']);
							trace(sprintf('Added contact #%s "%s" to client #%s on data source %s (%s)',
								$partner->id, $partner->fields['email'],
								$match[$ds2_k]['id'], $ds2_k, get_class($ds2)));

						} else trace(sprintf('Paired with contact #%s "%s" from client #%s on data source %s (%s)',
								$partner->id, $partner->fields['email'],
								$match[$ds2_k]['id'], $ds2_k, get_class($ds2)));

						// add partner to match
						$cmatch[$ds2_k]=array('id'=>$partner->id,'hash'=>$partner->hash());

					}

					// add match to match list
					$local['matches']['contacts'][]=$cmatch;

					// add match index to skip list
					$skip['contacts'][]=$k;

					check_max_calls();
				}
			}
		}
	}

	// update this match's sync time
	$match['lastsync']=time();

}

// re-order match list so that the oldest ones come first
hx::csort($local['matches']['contacts'], 'lastsync asc', false);

// check for updates to contact records

trace('Checking for updated contact records');

foreach($local['matches']['contacts'] as $k=>&$match) if(!in_array($k, $skip['contacts'])) {

	foreach($data_sources as $ds_key=>$ds) if($match[$ds_key]['id']&&$ds->sync_updates) {

		$contact=$ds->contact($match[$ds_key]['id']);

		if(!($contact instanceof fs_contact)) {

			// contact has been deleted from this data source.

			trace(sprintf('Contact #%s has been deleted from data source %s (%s)', $match[$ds_key]['id'], $ds_key,get_class($ds)));

			if($ds->sync_deletions) {

				// delete records from other sources

				foreach($data_sources as $ds2_k=>$ds2) if($match[$ds2_k]['id'] && $ds2_k!=$ds_key) {

					$contact=new fs_contact($ds2);
					$contact->id=$match[$ds2_k]['id'];
					$contact->delete();

					trace(sprintf('Deleted contact #%s from data source %s (%s)', $match[$ds2_k]['id'], $ds2_k,get_class($ds2)));
				}

				check_max_calls();

				// delete the match
				unset($local['matches']['contacts'][$k]);

				// skip to next match
				continue 2;

			} else {

				// zero out record's id in the match, so it won't be scanned again
				$match[$ds_key]['id']=0;

			}
		} else if(!$contact->hash($match[$ds_key]['hash'])) {
			
			// contact has been modified. update others.

			trace(sprintf('Contact #%s "%s" on data source %s (%s) has been modified. Updating others...',
				$contact->id,$contact->fields['email'],$ds_key,get_class($ds)));

			foreach($data_sources as $ds2_k=>$ds2) if($ds2_k!=$ds_key) {
				$partner=$ds2->contact($match[$ds2_k]['id']);
				$partner->fields=$contact->fields;
				$partner->save();

				trace(sprintf('Updated contact #%s "%s" on data source %s (%s)',$partner->id,$partner->fields['email'],$ds2_k,get_class($ds2)));

				// update match with new hash
				$match[$ds2_k]['hash']=$partner->hash();

			}

			// update match with new hash and time
			$match[$ds_key]['hash']=$contact->hash();
			$match['lastsync']=time();

			// add match index to skip list
			$skip['contacts'][]=$k;

		}

		check_max_calls();
	}
}

// save local data

trace("Sync complete.");
save_and_exit();


















?>