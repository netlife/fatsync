<?php
/**
 * 	FatSync (c) 2009 Fatpublisher	http://fatpublisher.com.au
 *	Written by Neil E. Pearson		http://www.Hx.net.au
 *	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 *	This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *	You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * Use this file to set up your data sources. Add two or more instances
 * of classes which extend fs_data_source to the $data_sources array.
 *
 * Example:
 *
 * $mySource1 = new harvest;
 * $mySource1->subdomain = 'mybusiness';
 * $mySource1->username = 'me@mybusiness.com';
 * $mySource1->password = 'mypassw0rd';
 * $mySource1->sync_additions = true;       // items added to this data source will be copied to other data sources
 * $mySource1->sync_updates   = true;       // changes made to items in this data source will be copied to other data sources
 * $mySource1->sync_deletions = true;       // items deleted from this data source will also be deleted from other data sources
 *
 * $mySource2 = new highrise;
 * $mySource2->subdomain = 'mybusiness';
 * $mySource2->username = 'me';
 * $mySource2->password = 'mypassw0rd';
 * $mySource2->sync_additions = true;       // items added to this data source will be copied to other data sources
 * $mySource2->sync_updates   = true;       // changes made to items in this data source will be copied to other data sources
 * $mySource2->sync_deletions = true;       // items deleted from this data source will also be deleted from other data sources
 *
 * $data_sources = array($mySource1, $mySource2);
 *
 * For information on setting up specific data source classes, see
 * their individual .txt files.
 * 
 */

?>