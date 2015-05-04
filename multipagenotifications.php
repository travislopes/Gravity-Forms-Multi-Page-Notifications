<?php
	
	/*
	Plugin Name: Gravity Forms Multi-Page Notifications
	Plugin URI: http://travislop.es/plugins/gravity-forms-multi-page-notifications/
	Description: Send notification emails from your multi-page Gravity Forms forms before the final entry is submitted. 
	Version: 1.0
	Author: Travis Lopes
	Author URI: http://travislop.es
	*/
	
	if ( class_exists( 'GFForms' ) ) {
		
		GFForms::include_addon_framework();
		
		class GFMultiPageNotifications extends GFAddOn {
			
			protected $_version = '1.0';
			protected $_min_gravityforms_version = '1.9.5.1';
			protected $_slug = 'gravity-forms-multi-page-notifications';
			protected $_path = 'gravity-forms-multi-page-notifications/multipagenotifications.php';
			protected $_full_path = __FILE__;
			protected $_url = 'http://travislop.es';
			protected $_title = 'Gravity Forms Multi-Page Notifications';
			protected $_short_title = 'Multi-Page Notifications';
			
			public function init() {
				
				parent::init();
				
				/* Add Multi-Page Notifications settings field to form settings */
				add_filter( 'gform_form_settings', array( $this, 'add_form_settings_field' ), 10, 2 );
				
				/* Save Multi-Page Notifications form settings field */
				add_filter( 'gform_pre_form_settings_save', array( $this, 'save_form_settings_field'), 10, 1 );
				
				/* Send notifications if passed test. */
				add_filter( 'gform_pre_render', array( $this, 'maybe_send_notification' ), 10, 1 );
				
				/* Disable notifications if sent prior to last page. */
				add_filter( 'gform_disable_notification', array( $this, 'maybe_disable_notification' ), 10, 4 );
				
			}
			
			/* Add Multi-Page Notifications settings field to form settings */
			function add_form_settings_field( $settings, $form ) {
				
				/* Get number of pages form has. */
				$page_count = $this->get_form_page_count( $form );
				
				/* If the form only has one page, don't add the settings field. */
				if ( $page_count == 1 )
					return $settings;
		
				/* Get current field setting. */
				$setting = rgar( $form, 'multipage_notifications' ); 
		
				/* Build checkboxes. */
				$checkboxes = '';
				
				for ( $i = 1; $i < $page_count + 1; $i++ ) {
					
					$checkboxes .= '<label for="multipage_notifications_'. $i .'">';
					$checkboxes .= '<input type="checkbox" id="multipage_notifications_'. $i .'" name="multipage_notifications[]" value="'. $i .'"'. ( ( is_array( $setting ) && in_array( $i, $setting ) ) ? ' checked="checked"' : '') .' />';
					$checkboxes .= ' Page '. $i ;
					$checkboxes .= '</label><br />';
										
				}
				
				$settings['Multi-Page Notifications']['multipage_notifications']  = '<tr>';
				$settings['Multi-Page Notifications']['multipage_notifications'] .= '<th>'. __( 'Send notification after completion of...', 'gravityformsmultipagenotifications' ) . $this->get_form_settings_field_tooltip() . '</th>';
				$settings['Multi-Page Notifications']['multipage_notifications'] .= '<td>'. $checkboxes .'</td>';
				$settings['Multi-Page Notifications']['multipage_notifications'] .= '</tr>';
				
				return $settings;
				
			}
			
			/* Save Multi-Page Notifications form settings field */
			function save_form_settings_field( $form ) {
				
				$form['multipage_notifications'] = rgpost( 'multipage_notifications' );
				return $form;
				
			}
			
			/* Send notifications if passed test. */
			function maybe_send_notification( $form ) {
				
				// send notification if previous page needed notification to be sent
				
				/* If this form only has one page, exit. */
				if ( $this->get_form_page_count( $form ) == 1 )
					return $form;
										
				$pages_to_send_notification = rgar( $form, 'multipage_notifications' );
				//$current_page = GFFormDisplay::get_currrent_page( $form['id'] );
				//$previous_page = $current_page - 1;
				$previous_page = 0;
				
				if ( in_array( $previous_page, $pages_to_send_notification ) ) {
					
					$entry = GFFormsModel::create_lead( $form );
					GFAPI::send_notifications( $form, $entry );
					
				}
				
				return $form;
				
			}
			
			/* Disable notifications if sent prior to last page. */
			function maybe_disable_notification( $is_disabled, $notification, $form, $entry ) {
				
				/* If this form only has one page, exit. */
				$form_page_count = $this->get_form_page_count( $form );
				if ( $form_page_count == 1 )
					return $is_disabled;				
				
				/* If this page is not setup for multi-page notifications, exit. */
				$multipage_notifications = rgar( $form, 'multipage_notifications' );
				if ( empty( $multipage_notifications ) );
					return $is_disabled;
					
				/* If the last page is setup for multi-page notifications, return $is_disabled. */
				if ( in_array( $form_page_count, $multipage_notifications ) )
					return $is_disabled;
					
				/* Otherwise, disable notifications. */
				return true;
				
			}

			/* Get number of pages for form. */
			public function get_form_page_count( $form ) {
				
				return count( GFCommon::get_fields_by_type( $form, array( 'page' ) ) ) + 1;
				
			}
			
			/* Get tooltip for form settings field */
			public function get_form_settings_field_tooltip() {
				
				$tooltip  = '<h6>'. __( 'Send notification after completion of...', 'gravityformsmultipagenotifications' ) .'</h6>';
				$tooltip .= __( 'Check the pages you would like to have the form\'s notifications sent after. If any pages have notifications sent, notifications will not be sent after form completion.', 'gravityformsmultipagenotifications' );
				
				return '<a href="#" onclick="return false;" class="gf_tooltip tooltip tooltip_multipage_notifications" title="'. $tooltip .'"><i class="fa fa-question-circle"></i></a>';
				
			}
			
		}
		
		new GFMultiPageNotifications();
		
	}