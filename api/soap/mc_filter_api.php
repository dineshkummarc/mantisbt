<?php
# MantisConnect - A webservice interface to Mantis Bug Tracker
# Copyright (C) 2004-2011  Victor Boctor - vboctor@users.sourceforge.net
# This program is distributed under dual licensing.  These include
# GPL and a commercial licenses.  Victor Boctor reserves the right to
# change the license of future releases.
# See docs/ folder for more details

$_SOAP_API_TO_FILTER_API_NAMES = array (
	'project_id' => FILTER_PROPERTY_PROJECT_ID,
    'category_name' => FILTER_PROPERTY_CATEGORY_ID, # category_id actually searches by name
    'free_text'		=> FILTER_PROPERTY_SEARCH,
    'reporter_id' => FILTER_PROPERTY_REPORTER_ID,
    'handler_id' => FILTER_PROPERTY_HANDLER_ID,
    'note_user_id' => FILTER_PROPERTY_NOTE_USER_ID,
	'status_id' => FILTER_PROPERTY_STATUS,
	'priority_id' => FILTER_PROPERTY_PRIORITY,
	'severity_id' => FILTER_PROPERTY_SEVERITY,
	'resolution_id' => FILTER_PROPERTY_RESOLUTION,
	'view_state_id' => FILTER_PROPERTY_VIEW_STATE,
	'sticky' => FILTER_PROPERTY_STICKY,
	'product_version' => FILTER_PROPERTY_VERSION,
	'product_build' => FILTER_PROPERTY_BUILD,
	'fixed_in_version' => FILTER_PROPERTY_FIXED_IN_VERSION,
	'target_version' => FILTER_PROPERTY_TARGET_VERSION,
	'platform' => FILTER_PROPERTY_PLATFORM,
	'os' => FILTER_PROPERTY_OS,
	'os_version' => FILTER_PROPERTY_OS_BUILD
);


/**
 * Get all user defined issue filters for the given project.
 *
 * @param string $p_username  The name of the user trying to access the filters.
 * @param string $p_password  The password of the user.
 * @param integer $p_project_id  The id of the project to retrieve filters for.
 * @return Array that represents a FilterDataArray structure
 */
function mc_filter_get( $p_username, $p_password, $p_project_id ) {
	$t_user_id = mci_check_login( $p_username, $p_password );
	if( $t_user_id === false ) {
		return mci_soap_fault_login_failed();
	}
	if( !mci_has_readonly_access( $t_user_id, $p_project_id ) ) {
		return mci_soap_fault_access_denied( $t_user_id );
	}
	$t_result = array();
	foreach( mci_filter_db_get_available_queries( $p_project_id, $t_user_id ) as $t_filter_row ) {
		$t_filter = array();
		$t_filter['id'] = $t_filter_row['id'];
		$t_filter['owner'] = mci_account_get_array_by_id( $t_filter_row['user_id'] );
		$t_filter['project_id'] = $t_filter_row['project_id'];
		$t_filter['is_public'] = $t_filter_row['is_public'];
		$t_filter['name'] = $t_filter_row['name'];
		$t_filter['filter_string'] = $t_filter_row['filter_string'];
		$t_filter['url'] = $t_filter_row['url'];
		$t_result[] = $t_filter;
	}
	return $t_result;
}

/**
 * Get all issues matching the specified filter.
 *
 * @param string $p_username  The name of the user trying to access the filters.
 * @param string $p_password  The password of the user.
 * @param integer $p_filter_id  The id of the filter to apply.
 * @param integer $p_page_number  Start with the given page number (zero-based)
 * @param integer $p_per_page  Number of issues to display per page
 * @return Array that represents an IssueDataArray structure
 */
function mc_filter_get_issues( $p_username, $p_password, $p_project_id, $p_filter_id, $p_page_number, $p_per_page ) {
	$t_user_id = mci_check_login( $p_username, $p_password );
	if( $t_user_id === false ) {
		return mci_soap_fault_login_failed();
	}
	$t_lang = mci_get_user_lang( $t_user_id );

	if( !mci_has_readonly_access( $t_user_id, $p_project_id ) ) {
		return mci_soap_fault_access_denied( $t_user_id );
	}

	$t_page_count = 0;
	$t_bug_count = 0;
	$t_filter = filter_db_get_filter( $p_filter_id );
	$t_filter_detail = explode( '#', $t_filter, 2 );
	if( !isset( $t_filter_detail[1] ) ) {
		return new soap_fault( 'Server', '', 'Invalid Filter' );
	}
	$t_filter = unserialize( $t_filter_detail[1] );
	$t_filter = filter_ensure_valid_filter( $t_filter );

	$t_result = array();
	$t_rows = filter_get_bug_rows( $p_page_number, $p_per_page, $t_page_count, $t_bug_count, $t_filter, $p_project_id );

	foreach( $t_rows as $t_issue_data ) {
		$t_result[] = mci_issue_data_as_array( $t_issue_data, $t_user_id, $t_lang );
	}

	return $t_result;
}

/**
 * Get the issue headers that match the specified filter and paging details.
 *
 * @param string $p_username  The name of the user trying to access the filters.
 * @param string $p_password  The password of the user.
 * @param integer $p_filter_id  The id of the filter to apply.
 * @param integer $p_page_number  Start with the given page number (zero-based)
 * @param integer $p_per_page  Number of issues to display per page
 * @return Array that represents an IssueDataArray structure
 */
function mc_filter_get_issue_headers( $p_username, $p_password, $p_project_id, $p_filter_id, $p_page_number, $p_per_page ) {
	$t_user_id = mci_check_login( $p_username, $p_password );
	if( $t_user_id === false ) {
		return mci_soap_fault_login_failed();
	}
	if( !mci_has_readonly_access( $t_user_id, $p_project_id ) ) {
		return mci_soap_fault_access_denied( $t_user_id );
	}

	$t_page_count = 0;
	$t_bug_count = 0;
	$t_filter = filter_db_get_filter( $p_filter_id );
	$t_filter_detail = explode( '#', $t_filter, 2 );
	if( !isset( $t_filter_detail[1] ) ) {
		return new soap_fault( 'Server', '', 'Invalid Filter' );
	}
	$t_filter = unserialize( $t_filter_detail[1] );
	$t_filter = filter_ensure_valid_filter( $t_filter );

	$t_result = array();
	$t_rows = filter_get_bug_rows( $p_page_number, $p_per_page, $t_page_count, $t_bug_count, $t_filter, $p_project_id );

	foreach( $t_rows as $t_issue_data ) {
		$t_result[] = mci_issue_data_as_header_array( $t_issue_data );
	}

	return $t_result;
}

function mc_filter_search_issue_headers( $p_username, $p_password, $p_filter_search ) {
    
    global $_SOAP_API_TO_FILTER_API_NAMES;
    
	$t_user_id = mci_check_login( $p_username, $p_password );

	if( $t_user_id === false ) {
		return mci_soap_fault_login_failed();
	}

	$t_project_id = $p_filter_search['project_id'];
	
	log_event( LOG_SOAP, 'Searching issue headers for project with id ' . $t_project_id . ' .' );
	
	if( !mci_has_readonly_access( $t_user_id, $t_project_id  ) ) {
		return mci_soap_fault_access_denied( $t_user_id );
	}
	
	$t_filter = array( '_view_type' => 'advanced');
	
	// TODO: filter public/private issues based on access
	foreach ( $_SOAP_API_TO_FILTER_API_NAMES as $t_soap_name => $t_filter_name ) {
    	if ( isset ( $p_filter_search[$t_soap_name]) ) {
    	    
    	    log_event( LOG_SOAP, 'Extracting parameter for ' . $t_soap_name );
    	    
    	    if ( is_array($t_filter_name) ) {
    	        list( $t_real_filter_name, $callback ) = $t_filter_name;
    	        
    	        log_event( LOG_SOAP, 'Parameter requires callback, using ' . $callback . ' .' );
    	        
    	        $t_filter_values = array();
    	        foreach ( $p_filter_search[$t_soap_name] as $t_soap_name_i ) {
    	            log_event( LOG_SOAP, 'Applying callback to ' . $t_soap_name_i . ' .');
    	            $t_filter_values[] = call_user_func( $callback, $t_soap_name_i );
    	        }
    	        
    	        $t_filter[$t_real_filter_name] = $t_filter_values;
    	    } else { 
    	        
    	        $t_value = $p_filter_search[$t_soap_name];
    	        log_event( LOG_SOAP, 'Simple extraction, value is ' . print_r($t_value, true) . ' .' );
        	    $t_filter[$t_filter_name] = $t_value;
    	    }
    	}   
	}
	
	$t_filter = filter_ensure_valid_filter( $t_filter );
	
	$t_result = array();
	$t_page_number = 0;
	$t_per_page = $p_filter_search['issues_per_page'];
	$t_page_count = 0;
	$t_bug_count = 0;
	$t_rows = filter_get_bug_rows( $t_page_number, $t_per_page, $t_page_count, $t_bug_count, $t_filter );

	foreach( $t_rows as $t_issue_data ) {
		$t_result[] = mci_issue_data_as_header_array( $t_issue_data );
	}

	return $t_result;
}

function mc_filter_search_issues( $p_username, $p_password, $p_filter_search ) {
    
    return new soap_fault('Server', '', 'Not implemented');
}
